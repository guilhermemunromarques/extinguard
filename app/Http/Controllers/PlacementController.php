<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlacementRequest;
use App\Http\Requests\UpdatePlacementRequest;
use App\Http\Resources\PlacementResource;
use App\Models\Placement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PlacementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        return PlacementResource::collection(
            Placement::query()->with('extinguisher')->latest('id')->paginate(),
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePlacementRequest $request): JsonResponse
    {
        $placement = Placement::query()->create([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return (new PlacementResource($placement->load('extinguisher')))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Placement $placement): PlacementResource
    {
        return new PlacementResource($placement->load('extinguisher'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePlacementRequest $request, Placement $placement): PlacementResource
    {
        $placement->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return new PlacementResource($placement->refresh()->load('extinguisher'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Placement $placement): Response
    {
        $placement->delete();

        return response()->noContent();
    }
}
