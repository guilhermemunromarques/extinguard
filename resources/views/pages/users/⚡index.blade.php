<?php

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Livewire\Component;
use Spatie\Permission\Models\Role;

new #[Title('Usuários')] class extends Component {
    use PasswordValidationRules, WithPagination;

    public string $search = '';
    public string $roleFilter = '';
    public bool $showForm = false;
    public ?int $editingUserId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = 'Viewer';

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function createUser(): void
    {
        Gate::authorize('create', User::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function editUser(int $userId): void
    {
        $user = User::findOrFail($userId);
        Gate::authorize('update', $user);

        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->getRoleNames()->first() ?? 'Viewer';
        $this->showForm = true;
    }

    public function saveUser(): void
    {
        $isCreating = $this->editingUserId === null;
        $user = $isCreating ? new User : User::findOrFail($this->editingUserId);
        Gate::authorize($user->exists ? 'update' : 'create', $user->exists ? $user : User::class);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ];

        if ($isCreating) {
            $rules['password'] = $this->passwordRules();
        } elseif ($this->password !== '') {
            $rules['password'] = $this->passwordRules();
        }

        if (auth()->user()->can('users.manage')) {
            $rules['role'] = ['required', Rule::exists('roles', 'name')->where('guard_name', 'web')];
        }

        $validated = $this->validate($rules);
        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            ...($this->password !== '' ? ['password' => $this->password] : []),
        ]);
        $user->save();

        if (auth()->user()->can('users.manage')) {
            $user->syncRoles([$validated['role']]);
            activity('usuarios')
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties(['role' => $validated['role']])
                ->log('Role do usuário atualizada');
        } elseif ($isCreating) {
            $user->assignRole('Viewer');
        }

        session()->flash('status', $this->editingUserId ? 'Usuário atualizado.' : 'Usuário criado.');
        $this->resetForm();
    }

    public function deactivateUser(int $userId): void
    {
        $user = User::findOrFail($userId);
        Gate::authorize('delete', $user);

        $user->forceFill(['is_active' => false])->save();
        session()->flash('status', 'Usuário inativado.');
    }

    public function resetForm(): void
    {
        $this->reset(['editingUserId', 'name', 'email', 'password', 'password_confirmation']);
        $this->role = 'Viewer';
        $this->showForm = false;
        $this->resetValidation();
    }

    public function roleLabel(string $role): string
    {
        return [
            'Administrator' => 'Administrador',
            'Technician' => 'Técnico',
            'Maintenance' => 'Manutenção',
            'Viewer' => 'Visualizador',
        ][$role] ?? $role;
    }

    public function render()
    {
        $users = User::query()
            ->with('roles')
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->roleFilter !== '', fn ($query) => $query->whereHas('roles', fn ($query) => $query->where('name', $this->roleFilter)))
            ->orderBy('name')
            ->paginate(10);

        return $this->view([
            'users' => $users,
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name'),
            'canManageUsers' => auth()->user()->can('users.manage'),
        ]);
    }
}; ?>


<div class="flex w-full flex-col gap-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div class="flex flex-col gap-1">
            <flux:heading size="xl">Usuários</flux:heading>
            <flux:text>Gerencie o acesso das pessoas à aplicação.</flux:text>
        </div>

        @can('create', App\Models\User::class)
            <flux:button variant="primary" icon="plus" wire:click="createUser">Novo usuário</flux:button>
        @endcan
    </div>

    @if (session('status'))
        <flux:callout variant="success">{{ session('status') }}</flux:callout>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nome ou e-mail" icon="magnifying-glass" />
        <flux:select wire:model.live="roleFilter" placeholder="Todas as roles">
            <flux:select.option value="">Todas as roles</flux:select.option>
            @foreach ($roles as $availableRole)
                <flux:select.option value="{{ $availableRole }}">{{ $this->roleLabel($availableRole) }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @if ($showForm)
        <flux:card class="flex flex-col gap-5">
            <div class="flex items-center justify-between gap-4">
                <flux:heading size="lg">{{ $editingUserId ? 'Editar usuário' : 'Novo usuário' }}</flux:heading>
                <flux:button variant="subtle" icon="x-mark" wire:click="resetForm" aria-label="Fechar formulário" />
            </div>

            <form wire:submit="saveUser" class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="name" label="Nome" required />
                <flux:input wire:model="email" label="E-mail" type="email" required />
                <flux:input wire:model="password" label="Senha" type="password" :required="! $editingUserId" />
                <flux:input wire:model="password_confirmation" label="Confirmar senha" type="password" :required="! $editingUserId" />

                @if ($canManageUsers)
                    <flux:select wire:model="role" label="Role" required>
                        @foreach ($roles as $availableRole)
                            <flux:select.option value="{{ $availableRole }}">{{ $this->roleLabel($availableRole) }}</flux:select.option>
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
                <flux:table.column class="first:ps-4">Nome</flux:table.column>
                <flux:table.column>E-mail</flux:table.column>
                <flux:table.column>Role</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column class="text-end last:pe-4">Ações</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($users as $user)
                    <flux:table.row :key="$user->id" class="odd:bg-zinc-50/70 even:bg-transparent hover:bg-zinc-100 dark:odd:bg-zinc-800/20 dark:hover:bg-zinc-800/50">
                        <flux:table.cell class="font-medium first:ps-4">{{ $user->name }}</flux:table.cell>
                        <flux:table.cell>{{ $user->email }}</flux:table.cell>
                        <flux:table.cell>{{ $user->getRoleNames()->map(fn ($role) => $this->roleLabel($role))->join(', ') ?: 'Sem role' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$user->is_active ? 'green' : 'zinc'">
                                {{ $user->is_active ? 'Ativo' : 'Inativo' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-end last:pe-4">
                            <div class="flex justify-end gap-2">
                                @can('update', $user)
                                    <flux:button variant="subtle" size="sm" wire:click="editUser({{ $user->id }})">Editar</flux:button>
                                @endcan
                                @can('delete', $user)
                                    <flux:button variant="subtle" size="sm" wire:click="deactivateUser({{ $user->id }})">Inativar</flux:button>
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">Nenhum usuário encontrado.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{ $users->links() }}
</div>

