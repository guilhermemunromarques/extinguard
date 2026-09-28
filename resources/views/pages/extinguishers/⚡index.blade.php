<?php

use App\ExtinguisherStatus;
use App\Models\Extinguisher;
use App\Models\ExtinguisherBrand;
use App\Models\ExtinguisherSupplier;
use Carbon\CarbonImmutable;
use App\Models\Placement;
use App\Models\ExtinguisherType;
use App\Policies\ExtinguisherPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Extintores')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $typeFilter = '';
    public string $brandFilter = '';
    public string $refillFilter = '';
    public bool $showForm = false;
    public ?int $editingExtinguisherId = null;
    public ?string $placement_id = null;
    public string $serial_number = '';
    public string $extinguisher_type_id = '';
    public ?string $extinguisher_brand_id = null;
    public string $supplier_id = '';
    public string $capacity = '';
    public string $capacity_unit = 'L';
    public string $extinguishing_capacity = '';
    public ?string $maintenance_seal = null;
    public string $status = 'active';
    public string $next_refill_date = '';
    public string $next_maintenance_year = '';
    public ?string $notes = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', Extinguisher::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedBrandFilter(): void
    {
        $this->resetPage();
    }

    public function updatedRefillFilter(): void
    {
        $this->resetPage();
    }

    public function createExtinguisher(): void
    {
        Gate::authorize('create', Extinguisher::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function editExtinguisher(int $extinguisherId): void
    {
        $extinguisher = Extinguisher::findOrFail($extinguisherId);
        Gate::authorize('update', $extinguisher);

        $this->editingExtinguisherId = $extinguisher->id;
        $this->placement_id = (string) ($extinguisher->placement_id ?? '');
        $this->serial_number = $extinguisher->serial_number;
        $this->extinguisher_type_id = (string) $extinguisher->extinguisher_type_id;
        $this->extinguisher_brand_id = (string) $extinguisher->extinguisher_brand_id;
        $this->supplier_id = (string) $extinguisher->supplier_id;
        $this->capacity = (string) $extinguisher->capacity;
        $this->capacity_unit = $extinguisher->capacity_unit;
        $this->extinguishing_capacity = (string) ($extinguisher->extinguishing_capacity ?? '');
        $this->maintenance_seal = (string) ($extinguisher->maintenance_seal ?? '');
        $this->status = $extinguisher->status->value;
        $this->next_refill_date = $extinguisher->next_refill_date?->format('Y-m') ?? '';
        $this->next_maintenance_year = (string) ($extinguisher->next_maintenance_year ?? '');
        $this->notes = (string) ($extinguisher->notes ?? '');
        $this->showForm = true;
    }

    public function saveExtinguisher(): void
    {
        $isCreating = $this->editingExtinguisherId === null;
        $extinguisher = $isCreating
            ? new Extinguisher()
            : Extinguisher::findOrFail($this->editingExtinguisherId);

        Gate::authorize($isCreating ? 'create' : 'update', $isCreating ? Extinguisher::class : $extinguisher);

        $allowedFields = app(ExtinguisherPolicy::class)->updateFields(auth()->user());
        if (! auth()->user()->can('placements.assign_extinguisher')) {
            $allowedFields = array_values(array_diff($allowedFields, ['placement_id']));
        }

        foreach (['placement_id', 'extinguisher_brand_id', 'maintenance_seal', 'next_refill_date', 'notes'] as $field) {
            if ($this->{$field} === '') {
                $this->{$field} = null;
            }
        }

        $rules = $this->validationRules($extinguisher, $isCreating);
        $validated = $this->validate(array_intersect_key($rules, array_flip($allowedFields)));

        if (array_key_exists('next_refill_date', $validated)) {
            $validated['next_refill_date'] .= '-01';
        }

        if ($isCreating && ! array_key_exists('placement_id', $validated)) {
            $validated['placement_id'] = null;
        }

        $extinguisher->fill($validated);
        $extinguisher->updated_by = auth()->id();
        $extinguisher->save();

        session()->flash('status', $isCreating ? 'Extintor criado.' : 'Extintor atualizado.');
        $this->resetForm();
    }

    private function validationRules(Extinguisher $extinguisher, bool $isCreating): array
    {
        return [
            'placement_id' => [
                'nullable',
                'integer',
                'exists:placements,id',
                Rule::unique('extinguishers', 'placement_id')->ignore($extinguisher->id),
            ],
            'serial_number' => [
                $isCreating ? 'required' : 'sometimes',
                'string',
                'max:255',
                Rule::unique('extinguishers', 'serial_number')->ignore($extinguisher->id),
            ],
            'extinguisher_type_id' => [$isCreating ? 'required' : 'sometimes', 'integer', 'exists:extinguisher_types,id'],
            'extinguisher_brand_id' => ['nullable', 'integer', 'exists:extinguisher_brands,id'],
            'supplier_id' => [$isCreating ? 'required' : 'sometimes', 'integer', 'exists:extinguisher_suppliers,id'],
            'capacity' => [$isCreating ? 'required' : 'sometimes', 'numeric', 'min:0'],
            'capacity_unit' => [$isCreating ? 'required' : 'sometimes', 'in:L,kg'],
            'extinguishing_capacity' => ['nullable', 'regex:/^(?:\d+[A-Za-z]+|[A-Za-z]+)(?:-(?:\d+[A-Za-z]+|[A-Za-z]+)){0,2}$/'],
            'maintenance_seal' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('extinguishers', 'maintenance_seal')->ignore($extinguisher->id),
            ],
            'status' => ['sometimes', Rule::enum(ExtinguisherStatus::class)],
            'next_refill_date' => [$isCreating ? 'required' : 'sometimes', 'date_format:Y-m'],
            'next_maintenance_year' => [$isCreating ? 'required' : 'sometimes', 'integer', 'min:2000', 'max:2200'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingExtinguisherId',
            'placement_id',
            'serial_number',
            'extinguisher_type_id',
            'extinguisher_brand_id',
            'supplier_id',
            'capacity',
            'capacity_unit',
            'extinguishing_capacity',
            'maintenance_seal',
            'next_refill_date',
            'next_maintenance_year',
            'notes',
        ]);
        $this->status = 'active';
        $this->showForm = false;
        $this->resetValidation();
    }

    private function statusLabel(ExtinguisherStatus $status): string
    {
        return match ($status) {
            ExtinguisherStatus::Active => 'Ativo',
            ExtinguisherStatus::Maintenance => 'Em manutenção',
            ExtinguisherStatus::Decommissioned => 'Desativado',
        };
    }

    private function refillLabel(?CarbonImmutable $date): string
    {
        if ($date === null) {
            return 'Não informado';
        }

        $months = [1 => 'jan', 2 => 'fev', 3 => 'mar', 4 => 'abr', 5 => 'mai', 6 => 'jun', 7 => 'jul', 8 => 'ago', 9 => 'set', 10 => 'out', 11 => 'nov', 12 => 'dez'];

        return $months[$date->month].'-'.$date->format('y');
    }

    public function render()
    {
        $extinguishers = Extinguisher::query()
            ->with(['placement.floor.building', 'type', 'brand', 'supplier', 'latestInspection'])
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('serial_number', 'like', "%{$this->search}%")
                        ->orWhere('maintenance_seal', 'like', "%{$this->search}%")
                        ->orWhereHas('placement', fn ($query) => $query->where('code', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->typeFilter !== '', fn ($query) => $query->where('extinguisher_type_id', $this->typeFilter))
            ->when($this->brandFilter !== '', fn ($query) => $query->where('extinguisher_brand_id', $this->brandFilter))
            ->when($this->refillFilter !== '', function ($query) {
                $date = CarbonImmutable::createFromFormat('Y-m', $this->refillFilter);
                $query->whereBetween('next_refill_date', [$date->startOfMonth(), $date->endOfMonth()]);
            })
            ->orderBy('next_refill_date')
            ->orderBy('next_maintenance_year')
            ->orderBy('serial_number')
            ->paginate(10);

        $currentPlacementId = $this->editingExtinguisherId
            ? Extinguisher::query()->whereKey($this->editingExtinguisherId)->value('placement_id')
            : null;

        return $this->view([
            'extinguishers' => $extinguishers,
            'types' => ExtinguisherType::query()->orderBy('name')->get(),
            'brands' => ExtinguisherBrand::query()->orderBy('name')->get(),
            'suppliers' => ExtinguisherSupplier::query()->orderBy('name')->get(),
            'placements' => Placement::query()
                ->with('floor.building')
                ->where(function ($query) use ($currentPlacementId) {
                    $query->whereDoesntHave('extinguisher');
                    if ($currentPlacementId !== null) {
                        $query->orWhereKey($currentPlacementId);
                    }
                })
                ->orderBy('code')
                ->get(),
            'statuses' => ExtinguisherStatus::cases(),
            'canCreate' => auth()->user()->can('create', Extinguisher::class),
            'canEditEquipment' => in_array('serial_number', app(ExtinguisherPolicy::class)->updateFields(auth()->user()), true),
            'canAssignPlacement' => auth()->user()->can('placements.assign_extinguisher'),
        ]);
    }
}; ?>

<div class="flex w-full flex-col gap-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div class="flex flex-col gap-1">
            <flux:heading size="xl">Extintores</flux:heading>
            <flux:text>Gerencie equipamentos, manutenção e posições físicas.</flux:text>
        </div>

        @can('create', App\Models\Extinguisher::class)
            <flux:button variant="primary" icon="plus" wire:click="createExtinguisher">Novo extintor</flux:button>
        @endcan
    </div>

    @if (session('status'))
        <flux:callout variant="success">{{ session('status') }}</flux:callout>
    @endif

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por série, selo ou posição" icon="magnifying-glass" />
        <flux:select wire:model.live="statusFilter" placeholder="Todos os status">
            <flux:select.option value="">Todos os status</flux:select.option>
            @foreach ($statuses as $availableStatus)
                <flux:select.option value="{{ $availableStatus->value }}">{{ $this->statusLabel($availableStatus) }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="typeFilter" placeholder="Todos os tipos">
            <flux:select.option value="">Todos os tipos</flux:select.option>
            @foreach ($types as $type)
                <flux:select.option value="{{ $type->id }}">{{ $type->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="brandFilter" placeholder="Todas as marcas">
            <flux:select.option value="">Todas as marcas</flux:select.option>
            @foreach ($brands as $brand)
                <flux:select.option value="{{ $brand->id }}">{{ $brand->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model.live="refillFilter" type="month" label="Filtrar recarga" />
    </div>

    @if ($showForm)
        <flux:card class="flex flex-col gap-5">
            <div class="flex items-center justify-between gap-4">
                <flux:heading size="lg">{{ $editingExtinguisherId ? 'Editar extintor' : 'Novo extintor' }}</flux:heading>
                <flux:button variant="subtle" icon="x-mark" wire:click="resetForm" aria-label="Fechar formulário" />
            </div>

            <form wire:submit="saveExtinguisher" class="grid gap-4 md:grid-cols-2">
                @if ($canEditEquipment)
                    <flux:input wire:model="serial_number" label="Número de série" required />
                    <flux:select wire:model="extinguisher_type_id" label="Tipo" required>
                        <flux:select.option value="">Selecione o tipo</flux:select.option>
                        @foreach ($types as $type)
                            <flux:select.option value="{{ $type->id }}">{{ $type->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="extinguisher_brand_id" label="Marca">
                        <flux:select.option value="">Sem marca</flux:select.option>
                        @foreach ($brands as $brand)
                            <flux:select.option value="{{ $brand->id }}">{{ $brand->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="supplier_id" label="Fornecedor" required>
                        <flux:select.option value="">Selecione o fornecedor</flux:select.option>
                        @foreach ($suppliers as $supplier)
                            <flux:select.option value="{{ $supplier->id }}">{{ $supplier->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="capacity" label="Capacidade física" type="number" step="0.01" required />
                    <flux:select wire:model="capacity_unit" label="Unidade" required>
                        <flux:select.option value="L">Litros (L)</flux:select.option>
                        <flux:select.option value="kg">Quilogramas (kg)</flux:select.option>
                    </flux:select>
                    <flux:input wire:model="extinguishing_capacity" label="Capacidade extintora" />
                @endif

                @if ($canAssignPlacement)
                    <flux:select wire:model="placement_id" label="Posição">
                        <flux:select.option value="">Sem posição</flux:select.option>
                        @foreach ($placements as $placement)
                            <flux:select.option value="{{ $placement->id }}">
                                {{ $placement->code }} - {{ $placement->floor->building->name }} / {{ $placement->floor->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                <flux:input wire:model="maintenance_seal" label="Lacre de manutenção" />
                <flux:select wire:model="status" label="Status" required>
                    @foreach ($statuses as $availableStatus)
                        <flux:select.option value="{{ $availableStatus->value }}">{{ $this->statusLabel($availableStatus) }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="next_refill_date" label="Próxima recarga" type="month" required />
                <flux:input wire:model="next_maintenance_year" label="Ano da próxima manutenção" type="number" min="2000" max="2200" required />
                <flux:textarea wire:model="notes" label="Observações" class="md:col-span-2" />

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
                <flux:table.column class="first:ps-4">Nº Extintor</flux:table.column>
                <flux:table.column>Tipo</flux:table.column>
                <flux:table.column>Marca</flux:table.column>
                <flux:table.column>Capacidade</flux:table.column>
                <flux:table.column>Cap. Ext.</flux:table.column>
                <flux:table.column>Nº Fab. (Casco)</flux:table.column>
                <flux:table.column>Nº Selo</flux:table.column>
                <flux:table.column>Última inspeção</flux:table.column>
                <flux:table.column>Recarga</flux:table.column>
                <flux:table.column>Reteste</flux:table.column>
                <flux:table.column>Fornecedor</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Observações</flux:table.column>
                <flux:table.column class="text-end last:pe-4">Ações</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($extinguishers as $extinguisher)
                    <flux:table.row :key="$extinguisher->id" class="odd:bg-zinc-50/70 even:bg-transparent hover:bg-zinc-100 dark:odd:bg-zinc-800/20 dark:hover:bg-zinc-800/50">
                        <flux:table.cell>
                            <div class="flex flex-col first:ps-4">
                                <span class="font-medium">{{ $extinguisher->placement?->code ?: 'Sem posição' }}</span>
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $extinguisher->placement?->floor->building->name ?: '' }}
                                    {{ $extinguisher->placement?->floor->name ? '/ '.$extinguisher->placement?->floor->name : '' }}
                                </span>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $extinguisher->type->name }}</flux:table.cell>
                        <flux:table.cell>{{ $extinguisher->brand?->name ?: 'Não informado' }}</flux:table.cell>
                        <flux:table.cell>{{ $extinguisher->capacity }} {{ $extinguisher->capacity_unit }}</flux:table.cell>
                        <flux:table.cell>{{ $extinguisher->extinguishing_capacity ?: 'Não informado' }}</flux:table.cell>
                        <flux:table.cell>{{ $extinguisher->serial_number }}</flux:table.cell>
                        <flux:table.cell>{{ $extinguisher->maintenance_seal ?: 'Não informado' }}</flux:table.cell>
                        <flux:table.cell>{{ $extinguisher->latestInspection?->inspection_date?->format('d-m-Y') ?: 'Não informado' }}</flux:table.cell>
                        <flux:table.cell>{{ $this->refillLabel($extinguisher->next_refill_date) }}</flux:table.cell>
                        <flux:table.cell>{{ $extinguisher->next_maintenance_year }}</flux:table.cell>
                        <flux:table.cell>{{ $extinguisher->supplier->name }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$extinguisher->status === App\ExtinguisherStatus::Active ? 'green' : ($extinguisher->status === App\ExtinguisherStatus::Maintenance ? 'yellow' : 'zinc')">
                                {{ $this->statusLabel($extinguisher->status) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $extinguisher->notes ?: 'Não informado' }}</flux:table.cell>
                        <flux:table.cell class="text-end last:pe-4">
                            @can('update', $extinguisher)
                                <flux:button variant="subtle" size="sm" wire:click="editExtinguisher({{ $extinguisher->id }})">Editar</flux:button>
                            @endcan
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="14">Nenhum extintor encontrado.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{ $extinguishers->links() }}
</div>
