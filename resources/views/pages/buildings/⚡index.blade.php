<?php

use App\Models\Building;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Edifícios')] class extends Component {
    use WithPagination;

    public string $search = '';
    public bool $showForm = false;
    public ?int $editingBuildingId = null;
    public string $name = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Building::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function createBuilding(): void
    {
        Gate::authorize('create', Building::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function editBuilding(int $buildingId): void
    {
        $building = Building::findOrFail($buildingId);
        Gate::authorize('update', $building);
        $this->editingBuildingId = $building->id;
        $this->name = $building->name;
        $this->showForm = true;
    }

    public function saveBuilding(): void
    {
        $isCreating = $this->editingBuildingId === null;
        $building = $isCreating ? new Building() : Building::findOrFail($this->editingBuildingId);
        Gate::authorize($isCreating ? 'create' : 'update', $isCreating ? Building::class : $building);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('buildings', 'name')->ignore($building->id)],
        ]);
        $building->fill($validated)->save();
        session()->flash('status', $isCreating ? 'Edifício criado.' : 'Edifício atualizado.');
        $this->resetForm();
    }

    public function deleteBuilding(int $buildingId): void
    {
        $building = Building::findOrFail($buildingId);
        Gate::authorize('delete', $building);
        if ($building->floors()->exists()) {
            session()->flash('error', 'Não é possível excluir um edifício que possui andares.');
            return;
        }
        $building->delete();
        session()->flash('status', 'Edifício excluído.');
    }

    public function resetForm(): void
    {
        $this->reset(['editingBuildingId', 'name']);
        $this->showForm = false;
        $this->resetValidation();
    }

    public function render()
    {
        return $this->view([
            'buildings' => Building::query()->withCount('floors')->when($this->search !== '', fn ($query) => $query->where('name', 'like', "%{$this->search}%"))->orderBy('name')->paginate(10),
        ]);
    }
}; ?>

<div class="flex w-full flex-col gap-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div class="flex flex-col gap-1">
            <flux:heading size="xl">Edifícios</flux:heading>
            <flux:text>Gerencie os locais atendidos.</flux:text>
        </div>

        @can('create', App\Models\Building::class)
            <flux:button variant="primary" icon="plus" wire:click="createBuilding">Novo edifício</flux:button>
        @endcan
    </div>

    @if (session('status'))
        <flux:callout variant="success">{{ session('status') }}</flux:callout>
    @endif

    @if (session('error'))
        <flux:callout variant="danger">{{ session('error') }}</flux:callout>
    @endif

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nome" icon="magnifying-glass" />

    @if ($showForm)
        <flux:card class="flex flex-col gap-5">
            <div class="flex items-center justify-between gap-4">
                <flux:heading size="lg">{{ $editingBuildingId ? 'Editar edifício' : 'Novo edifício' }}</flux:heading>
                <flux:button variant="subtle" icon="x-mark" wire:click="resetForm" aria-label="Fechar formulário" />
            </div>

            <form wire:submit="saveBuilding" class="flex flex-col gap-4">
                <flux:input wire:model="name" label="Nome" required />

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">Salvar</flux:button>
                    <flux:button type="button" wire:click="resetForm">Cancelar</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    <flux:card class="overflow-x-auto p-0">
        <flux:table>
            <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800">
                <flux:table.column class="first:ps-4">Nome</flux:table.column>
                <flux:table.column>Andares</flux:table.column>
                <flux:table.column class="text-end last:pe-4">Ações</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($buildings as $building)
                    <flux:table.row :key="$building->id" class="odd:bg-zinc-50/70 even:bg-transparent hover:bg-zinc-100 dark:odd:bg-zinc-800/20 dark:hover:bg-zinc-800/50">
                        <flux:table.cell class="font-medium first:ps-4">{{ $building->name }}</flux:table.cell>
                        <flux:table.cell>{{ $building->floors_count }}</flux:table.cell>
                        <flux:table.cell class="text-end last:pe-4">
                            <div class="flex justify-end gap-2">
                                @can('update', $building)
                                    <flux:button variant="subtle" size="sm" wire:click="editBuilding({{ $building->id }})">Editar</flux:button>
                                @endcan
                                @can('delete', $building)
                                    <flux:button variant="subtle" size="sm" wire:click="deleteBuilding({{ $building->id }})">Excluir</flux:button>
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="3">Nenhum edifício encontrado.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{ $buildings->links() }}
</div>
