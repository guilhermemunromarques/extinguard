<?php

use App\Models\Building;
use App\Models\Extinguisher;
use App\Models\Floor;
use App\Models\Placement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Posicionamentos')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $buildingFilter = '';
    public string $floorFilter = '';
    public string $equipmentFilter = '';
    public bool $showForm = false;
    public ?int $editingPlacementId = null;
    public string $code = '';
    public string $floor_id = '';
    public ?string $x = null;
    public ?string $y = null;
    public ?string $location_description = null;
    public ?string $extinguisher_id = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', Placement::class);
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedBuildingFilter(): void { $this->floorFilter = ''; $this->resetPage(); }
    public function updatedFloorFilter(): void { $this->resetPage(); }
    public function updatedEquipmentFilter(): void { $this->resetPage(); }

    public function createPlacement(): void
    {
        Gate::authorize('create', Placement::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function editPlacement(int $placementId): void
    {
        $placement = Placement::findOrFail($placementId);
        Gate::authorize('update', $placement);
        $this->editingPlacementId = $placement->id;
        $this->code = $placement->code;
        $this->floor_id = (string) $placement->floor_id;
        $this->x = $placement->x === null ? null : (string) $placement->x;
        $this->y = $placement->y === null ? null : (string) $placement->y;
        $this->location_description = $placement->location_description;
        $this->extinguisher_id = $placement->extinguisher?->id === null ? null : (string) $placement->extinguisher->id;
        $this->showForm = true;
    }

    public function savePlacement(): void
    {
        $isCreating = $this->editingPlacementId === null;
        $placement = $isCreating ? new Placement() : Placement::findOrFail($this->editingPlacementId);
        Gate::authorize($isCreating ? 'create' : 'update', $isCreating ? Placement::class : $placement);

        $canAssign = auth()->user()->can('placements.assign_extinguisher');
        $rules = [
            'code' => ['required', 'string', 'max:255', Rule::unique('placements', 'code')->ignore($placement->id)],
            'floor_id' => ['required', 'integer', 'exists:floors,id'],
            'x' => ['nullable', 'numeric'],
            'y' => ['nullable', 'numeric'],
            'location_description' => ['nullable', 'string', 'max:255'],
        ];
        if ($canAssign) {
            $rules['extinguisher_id'] = ['nullable', 'integer', 'exists:extinguishers,id'];
        }
        $validated = $this->validate($rules);

        if ($canAssign) {
            $selectedExtinguisher = $validated['extinguisher_id'] === null ? null : Extinguisher::find($validated['extinguisher_id']);
            if ($selectedExtinguisher?->placement_id !== null && $selectedExtinguisher->placement_id !== $placement->id) {
                $this->addError('extinguisher_id', 'Este extintor já está atribuído a outro posicionamento.');
                return;
            }
        }

        DB::transaction(function () use ($placement, $validated, $canAssign): void {
            $oldExtinguisher = $placement->extinguisher()->first();
            $placement->fill(collect($validated)->except('extinguisher_id')->all());
            $placement->updated_by = auth()->id();
            $placement->save();

            if (! $canAssign) {
                return;
            }

            if ($oldExtinguisher !== null && (string) $oldExtinguisher->id !== (string) ($validated['extinguisher_id'] ?? '')) {
                $oldExtinguisher->update(['placement_id' => null, 'updated_by' => auth()->id()]);
            }
            if (($validated['extinguisher_id'] ?? null) !== null) {
                Extinguisher::query()->whereKey($validated['extinguisher_id'])->update(['placement_id' => $placement->id, 'updated_by' => auth()->id()]);
            }
        });

        session()->flash('status', $isCreating ? 'Posicionamento criado.' : 'Posicionamento atualizado.');
        $this->resetForm();
    }

    public function deletePlacement(int $placementId): void
    {
        $placement = Placement::findOrFail($placementId);
        Gate::authorize('delete', $placement);
        if ($placement->extinguisher()->exists()) {
            session()->flash('error', 'Não é possível excluir um posicionamento com extintor atribuído.');
            return;
        }
        $placement->delete();
        session()->flash('status', 'Posicionamento excluído.');
    }

    public function resetForm(): void
    {
        $this->reset(['editingPlacementId', 'code', 'floor_id', 'x', 'y', 'location_description', 'extinguisher_id']);
        $this->showForm = false;
        $this->resetValidation();
    }

    public function render()
    {
        return $this->view([
            'placements' => Placement::query()->with(['floor.building', 'extinguisher'])->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query->where('code', 'like', "%{$this->search}%")->orWhere('location_description', 'like', "%{$this->search}%")->orWhereHas('extinguisher', fn ($query) => $query->where('serial_number', 'like', "%{$this->search}%"))))->when($this->buildingFilter !== '', fn ($query) => $query->whereHas('floor', fn ($query) => $query->where('building_id', $this->buildingFilter)))->when($this->floorFilter !== '', fn ($query) => $query->where('floor_id', $this->floorFilter))->when($this->equipmentFilter === 'assigned', fn ($query) => $query->whereHas('extinguisher'))->when($this->equipmentFilter === 'empty', fn ($query) => $query->whereDoesntHave('extinguisher'))->orderBy('code')->paginate(10),
            'buildings' => Building::query()->orderBy('name')->get(),
            'floors' => Floor::query()->when($this->buildingFilter !== '', fn ($query) => $query->where('building_id', $this->buildingFilter))->orderBy('building_id')->orderBy('floor_number')->get(),
            'extinguishers' => Extinguisher::query()->where(function ($query) {
                $query->whereDoesntHave('placement');
                if ($this->editingPlacementId !== null) {
                    $query->orWhereHas('placement', fn ($query) => $query->whereKey($this->editingPlacementId));
                }
            })->orderBy('serial_number')->get(),
            'canAssign' => auth()->user()->can('placements.assign_extinguisher'),
        ]);
    }
}; ?>

<div class="flex w-full flex-col gap-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div class="flex flex-col gap-1">
            <flux:heading size="xl">Posicionamentos</flux:heading>
            <flux:text>Gerencie posições físicas e seus extintores.</flux:text>
        </div>

        @can('create', App\Models\Placement::class)
            <flux:button variant="primary" icon="plus" wire:click="createPlacement">Novo posicionamento</flux:button>
        @endcan
    </div>

    @if (session('status'))
        <flux:callout variant="success">{{ session('status') }}</flux:callout>
    @endif

    @if (session('error'))
        <flux:callout variant="danger">{{ session('error') }}</flux:callout>
    @endif

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por código, descrição ou série" icon="magnifying-glass" />

        <flux:select wire:model.live="buildingFilter" placeholder="Todos os edifícios">
            <flux:select.option value="">Todos os edifícios</flux:select.option>
            @foreach ($buildings as $building)
                <flux:select.option value="{{ $building->id }}">{{ $building->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="floorFilter" placeholder="Todos os andares">
            <flux:select.option value="">Todos os andares</flux:select.option>
            @foreach ($floors as $floor)
                <flux:select.option value="{{ $floor->id }}">{{ $floor->building->name }} / {{ $floor->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="equipmentFilter" placeholder="Todos os estados">
            <flux:select.option value="">Todos os estados</flux:select.option>
            <flux:select.option value="assigned">Com extintor</flux:select.option>
            <flux:select.option value="empty">Sem extintor</flux:select.option>
        </flux:select>
    </div>

    @if ($showForm)
        <flux:card class="flex flex-col gap-5">
            <div class="flex items-center justify-between gap-4">
                <flux:heading size="lg">{{ $editingPlacementId ? 'Editar posicionamento' : 'Novo posicionamento' }}</flux:heading>
                <flux:button variant="subtle" icon="x-mark" wire:click="resetForm" aria-label="Fechar formulário" />
            </div>

            <form wire:submit="savePlacement" class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="code" label="Código" required />

                <flux:select wire:model="floor_id" label="Andar" required>
                    <flux:select.option value="">Selecione o andar</flux:select.option>
                    @foreach ($floors as $floor)
                        <flux:select.option value="{{ $floor->id }}">{{ $floor->building->name }} / {{ $floor->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="x" label="Coordenada X" type="number" step="0.01" />
                <flux:input wire:model="y" label="Coordenada Y" type="number" step="0.01" />
                <flux:input wire:model="location_description" label="Descrição da localização" class="md:col-span-2" />

                @if ($canAssign)
                    <flux:select wire:model="extinguisher_id" label="Extintor" class="md:col-span-2">
                        <flux:select.option value="">Sem extintor</flux:select.option>
                        @foreach ($extinguishers as $extinguisher)
                            <flux:select.option value="{{ $extinguisher->id }}">{{ $extinguisher->serial_number }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                <div class="flex gap-3 md:col-span-2">
                    <flux:button variant="primary" type="submit">Salvar</flux:button>
                    <flux:button type="button" wire:click="resetForm">Cancelar</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    <flux:card class="overflow-x-auto p-0">
        <flux:table>
            <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800">
                <flux:table.column class="first:ps-4">Código</flux:table.column>
                <flux:table.column>Edifício / andar</flux:table.column>
                <flux:table.column>Coordenadas</flux:table.column>
                <flux:table.column>Localização</flux:table.column>
                <flux:table.column>Extintor</flux:table.column>
                <flux:table.column class="text-end last:pe-4">Ações</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($placements as $placement)
                    <flux:table.row :key="$placement->id" class="odd:bg-zinc-50/70 even:bg-transparent hover:bg-zinc-100 dark:odd:bg-zinc-800/20 dark:hover:bg-zinc-800/50">
                        <flux:table.cell class="font-medium first:ps-4">{{ $placement->code }}</flux:table.cell>
                        <flux:table.cell>{{ $placement->floor->building->name }} / {{ $placement->floor->name }}</flux:table.cell>
                        <flux:table.cell>{{ $placement->x ?? '—' }}, {{ $placement->y ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $placement->location_description ?: 'Não informado' }}</flux:table.cell>
                        <flux:table.cell>{{ $placement->extinguisher?->serial_number ?: 'Sem extintor' }}</flux:table.cell>
                        <flux:table.cell class="text-end last:pe-4">
                            <div class="flex justify-end gap-2">
                                @can('update', $placement)
                                    <flux:button variant="subtle" size="sm" wire:click="editPlacement({{ $placement->id }})">Editar</flux:button>
                                @endcan
                                @can('delete', $placement)
                                    <flux:button variant="subtle" size="sm" wire:click="deletePlacement({{ $placement->id }})">Excluir</flux:button>
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">Nenhum posicionamento encontrado.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{ $placements->links() }}
</div>
