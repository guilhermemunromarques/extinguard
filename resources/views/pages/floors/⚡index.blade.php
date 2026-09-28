<?php

use App\Models\Building;
use App\Models\Floor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Andares')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $buildingFilter = '';
    public bool $showForm = false;
    public ?int $editingFloorId = null;
    public string $building_id = '';
    public string $floor_number = '';
    public string $name = '';

    public function mount(): void { Gate::authorize('viewAny', Floor::class); }
    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedBuildingFilter(): void { $this->resetPage(); }
    public function createFloor(): void { Gate::authorize('create', Floor::class); $this->resetForm(); $this->showForm = true; }
    public function editFloor(int $floorId): void
    {
        $floor = Floor::findOrFail($floorId);
        Gate::authorize('update', $floor);
        $this->editingFloorId = $floor->id;
        $this->building_id = (string) $floor->building_id;
        $this->floor_number = (string) $floor->floor_number;
        $this->name = $floor->name;
        $this->showForm = true;
    }
    public function saveFloor(): void
    {
        $isCreating = $this->editingFloorId === null;
        $floor = $isCreating ? new Floor() : Floor::findOrFail($this->editingFloorId);
        Gate::authorize($isCreating ? 'create' : 'update', $isCreating ? Floor::class : $floor);
        $validated = $this->validate([
            'building_id' => ['required', 'integer', 'exists:buildings,id'],
            'floor_number' => ['required', 'integer', 'min:-100', 'max:1000', Rule::unique('floors', 'floor_number')->where(fn ($query) => $query->where('building_id', $this->building_id))->ignore($floor->id)],
            'name' => ['required', 'string', 'max:255'],
        ]);
        $floor->fill($validated)->save();
        session()->flash('status', $isCreating ? 'Andar criado.' : 'Andar atualizado.');
        $this->resetForm();
    }
    public function deleteFloor(int $floorId): void
    {
        $floor = Floor::findOrFail($floorId);
        Gate::authorize('delete', $floor);
        if ($floor->placements()->exists()) { session()->flash('error', 'Não é possível excluir um andar que possui posicionamentos.'); return; }
        $floor->delete();
        session()->flash('status', 'Andar excluído.');
    }
    public function resetForm(): void { $this->reset(['editingFloorId', 'building_id', 'floor_number', 'name']); $this->showForm = false; $this->resetValidation(); }
    public function render()
    {
        return $this->view([
            'floors' => Floor::query()->with('building')->withCount('placements')->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$this->search}%")->orWhere('floor_number', $this->search)))->when($this->buildingFilter !== '', fn ($query) => $query->where('building_id', $this->buildingFilter))->orderBy('building_id')->orderBy('floor_number')->paginate(10),
            'buildings' => Building::query()->orderBy('name')->get(),
        ]);
    }
}; ?>

<div class="flex w-full flex-col gap-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div class="flex flex-col gap-1">
            <flux:heading size="xl">Andares</flux:heading>
            <flux:text>Organize os andares de cada edifício.</flux:text>
        </div>

        @can('create', App\Models\Floor::class)
            <flux:button variant="primary" icon="plus" wire:click="createFloor">Novo andar</flux:button>
        @endcan
    </div>

    @if (session('status'))
        <flux:callout variant="success">{{ session('status') }}</flux:callout>
    @endif
    @if (session('error'))
        <flux:callout variant="danger">{{ session('error') }}</flux:callout>
    @endif

    <div class="grid gap-3 sm:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nome ou número" icon="magnifying-glass" />
        <flux:select wire:model.live="buildingFilter" placeholder="Todos os edifícios">
            <flux:select.option value="">Todos os edifícios</flux:select.option>
            @foreach ($buildings as $building)
                <flux:select.option value="{{ $building->id }}">{{ $building->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @if ($showForm)
        <flux:card class="flex flex-col gap-5">
            <div class="flex items-center justify-between gap-4">
                <flux:heading size="lg">{{ $editingFloorId ? 'Editar andar' : 'Novo andar' }}</flux:heading>
                <flux:button variant="subtle" icon="x-mark" wire:click="resetForm" aria-label="Fechar formulário" />
            </div>

            <form wire:submit="saveFloor" class="grid gap-4 md:grid-cols-2">
                <flux:select wire:model="building_id" label="Edifício" required>
                    <flux:select.option value="">Selecione o edifício</flux:select.option>
                    @foreach ($buildings as $building)
                        <flux:select.option value="{{ $building->id }}">{{ $building->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="floor_number" label="Número do andar" type="number" required />

                <flux:input wire:model="name" label="Nome" class="md:col-span-2" required />

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
                <flux:table.column class="first:ps-4">Edifício</flux:table.column>
                <flux:table.column>Número</flux:table.column>
                <flux:table.column>Nome</flux:table.column>
                <flux:table.column>Posicionamentos</flux:table.column>
                <flux:table.column class="text-end last:pe-4">Ações</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($floors as $floor)
                    <flux:table.row :key="$floor->id" class="odd:bg-zinc-50/70 even:bg-transparent hover:bg-zinc-100 dark:odd:bg-zinc-800/20 dark:hover:bg-zinc-800/50">
                        <flux:table.cell>
                            <span class="font-medium first:ps-4">{{ $floor->building->name }}</span>
                        </flux:table.cell>
                        <flux:table.cell>{{ $floor->floor_number }}</flux:table.cell>
                        <flux:table.cell class="font-medium">{{ $floor->name }}</flux:table.cell>
                        <flux:table.cell>{{ $floor->placements_count }}</flux:table.cell>
                        <flux:table.cell class="text-end last:pe-4">
                            <div class="flex justify-end gap-2">
                                @can('update', $floor)
                                    <flux:button variant="subtle" size="sm" wire:click="editFloor({{ $floor->id }})">Editar</flux:button>
                                @endcan
                                @can('delete', $floor)
                                    <flux:button variant="subtle" size="sm" wire:click="deleteFloor({{ $floor->id }})">Excluir</flux:button>
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">Nenhum andar encontrado.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{ $floors->links() }}
</div>
