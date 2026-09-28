<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExtinguisherRequest;
use App\Http\Requests\UpdateExtinguisherRequest;
use App\Http\Resources\ExtinguisherResource;
use App\Models\Extinguisher;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ExtinguisherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        return ExtinguisherResource::collection(
            Extinguisher::query()->with(['placement', 'type', 'brand', 'supplier'])->latest('id')->paginate(),
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExtinguisherRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['next_refill_date'] = CarbonImmutable::parse($data['next_refill_date'])->startOfMonth()->format('Y-m-d');
        $extinguisher = Extinguisher::query()->create($data);

        return (new ExtinguisherResource($extinguisher->load(['placement', 'type', 'brand', 'supplier'])))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Extinguisher $extinguisher): ExtinguisherResource
    {
        return new ExtinguisherResource($extinguisher->load(['placement', 'type', 'brand', 'supplier']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExtinguisherRequest $request, Extinguisher $extinguisher): ExtinguisherResource
    {
        if ($request->has('placement_id') && $extinguisher->placement_id !== $request->integer('placement_id') && ! $request->user()->can('placements.assign_extinguisher')) {
            abort(403);
        }

        $data = $request->validated();
        if (array_key_exists('next_refill_date', $data)) {
            $data['next_refill_date'] = CarbonImmutable::parse($data['next_refill_date'])->startOfMonth()->format('Y-m-d');
        }

        $extinguisher->update([
            ...$data,
            'updated_by' => $request->user()->id,
        ]);

        return new ExtinguisherResource($extinguisher->refresh()->load(['placement', 'type', 'brand', 'supplier']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Extinguisher $extinguisher): Response
    {
        $extinguisher->delete();

        return response()->noContent();
    }
}
