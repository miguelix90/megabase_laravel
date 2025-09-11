<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    // Propiedades del formulario
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public ?int $role_id = null;
    public bool $is_active = true;
    public bool $email_verified = false;

    // Propiedades para edición
    public ?User $editingUser = null;
    public bool $showForm = false;

    public function mount()
    {
        // Verificar que el usuario tenga permisos
        if (!auth()->user()->hasRole('superadmin')) {
            abort(403);
        }
    }

    /**
     * Crear un nuevo usuario
     */
    public function createUser(): void
    {
        try {
            $validated = $this->validate([
                'name' => ['required', 'string', 'max:255', 'unique:users,name'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'role_id' => ['required', 'exists:roles,id'],
                'is_active' => ['boolean'],
                'email_verified' => ['boolean'],
            ]);

            $userData = [
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'is_active' => $this->is_active,
            ];

            // Si marcamos verificación automática, agregamos la fecha actual
            if ($this->email_verified) {
                $userData['email_verified_at'] = now();
            } else {
                $userData['email_verified_at'] = null;
            }

            $user = User::create($userData);

            if (!$user) {
                session()->flash('error', 'Error al crear el usuario.');
                return;
            }

            // Asignar el rol
            $role = Role::find($this->role_id);
            if ($role) {
                $user->assignRole($role);
            } else {
                session()->flash('error', 'Error: Rol no encontrado.');
                return;
            }

            session()->flash('message', 'Usuario creado exitosamente.');
            $this->resetForm();

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Los errores de validación se muestran automáticamente
            throw $e;
        } catch (\Exception $e) {
            session()->flash('error', 'Error al crear el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Preparar edición de usuario
     */
    public function editUser(User $user): void
    {
        $this->editingUser = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->password_confirmation = '';
        $this->role_id = $user->roles->first()?->id;
        $this->is_active = $user->is_active;
        $this->email_verified = !is_null($user->email_verified_at);
        $this->showForm = true;
    }

    /**
     * Actualizar usuario
     */
    public function updateUser(): void
    {
        try {
            $this->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($this->editingUser->id)],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($this->editingUser->id)],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
                'role_id' => ['required', 'exists:roles,id'],
                'is_active' => ['boolean'],
                'email_verified' => ['boolean'],
            ]);

            $userData = [
                'name' => $this->name,
                'email' => $this->email,
                'is_active' => $this->is_active,
            ];

            // Solo actualizar password si se proporciona uno nuevo
            if (!empty($this->password)) {
                $userData['password'] = Hash::make($this->password);
            }

            // Manejar verificación de email
            if ($this->email_verified) {
                $userData['email_verified_at'] = now();
            } else {
                $userData['email_verified_at'] = null;
            }

            $this->editingUser->update($userData);

            // Actualizar rol
            $role = Role::find($this->role_id);
            $this->editingUser->syncRoles([$role]);

            session()->flash('message', 'Usuario actualizado exitosamente.');
            $this->resetForm();

        } catch (\Exception $e) {
            session()->flash('error', 'Error al actualizar el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar usuario
     */
    public function deleteUser(User $user): void
    {
        try {
            $user->delete();
            session()->flash('message', 'Usuario eliminado exitosamente.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al eliminar el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Resetear formulario
     */
    public function resetForm(): void
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->role_id = null;
        $this->is_active = true;
        $this->email_verified = false;
        $this->editingUser = null;
        $this->showForm = false;
    }

    /**
     * Mostrar formulario de creación
     */
    public function showCreateForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    /**
     * Renderizar el componente
     */
    public function with(): array
    {
        return [
            'users' => User::with('roles')->paginate(10),
            'roles' => Role::all(),
        ];
    }
}; ?>

<div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="lg">Gestión de Usuarios</flux:heading>
            <flux:subheading>Administra los usuarios del sistema</flux:subheading>
        </div>
        <flux:button wire:click="showCreateForm" variant="primary" icon="plus">
            Crear Usuario
        </flux:button>
    </div>

    <!-- Mensajes de éxito -->
    @if (session()->has('message'))
        <div class="p-4 mb-4 text-green-800 bg-green-100 border border-green-200 rounded-lg dark:bg-green-900 dark:text-green-200 dark:border-green-800">
            {{ session('message') }}
        </div>
    @endif

    <!-- Mensajes de error -->
    @if (session()->has('error'))
        <div class="p-4 mb-4 text-red-800 bg-red-100 border border-red-200 rounded-lg dark:bg-red-900 dark:text-red-200 dark:border-red-800">
            {{ session('error') }}
        </div>
    @endif

    <!-- Mostrar errores de validación -->
    @if ($errors->any())
        <div class="p-4 mb-4 text-red-800 bg-red-100 border border-red-200 rounded-lg dark:bg-red-900 dark:text-red-200 dark:border-red-800">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Formulario de Usuario -->
    @if ($showForm)
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
            <flux:heading size="base" class="mb-4">{{ $editingUser ? 'Editar Usuario' : 'Crear Nuevo Usuario' }}</flux:heading>

            <form wire:submit="{{ $editingUser ? 'updateUser' : 'createUser' }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Nombre -->
                    <flux:input
                        wire:model="name"
                        label="Nombre"
                        placeholder="Nombre completo"
                        required
                    />

                    <!-- Email -->
                    <flux:input
                        wire:model="email"
                        label="Email"
                        type="email"
                        placeholder="usuario@ejemplo.com"
                        required
                    />

                    <!-- Password -->
                    <flux:input
                        wire:model="password"
                        label="{{ $editingUser ? 'Nueva Contraseña (opcional)' : 'Contraseña' }}"
                        type="password"
                        placeholder="••••••••"
                        {{ !$editingUser ? 'required' : '' }}
                    />

                    <!-- Confirmar Password -->
                    <flux:input
                        wire:model="password_confirmation"
                        label="Confirmar Contraseña"
                        type="password"
                        placeholder="••••••••"
                        {{ !$editingUser ? 'required' : '' }}
                    />

                    <!-- Rol -->
                    <flux:select wire:model="role_id" label="Rol" required>
                        <option value="">Seleccionar rol...</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </flux:select>

                    <div class="space-y-3">
                        <!-- Estado activo -->
                        <flux:checkbox wire:model="is_active" label="Usuario activo" />

                        <!-- Verificación de email automática -->
                        <flux:checkbox wire:model="email_verified" label="Verificar email automáticamente" />

                        @if ($email_verified)
                            <p class="text-sm text-green-600 dark:text-green-400">
                                ✓ El email se marcará como verificado automáticamente
                            </p>
                        @endif
                    </div>
                </div>

                <div class="flex gap-2 pt-4">
                    <flux:button type="submit" variant="primary">
                        {{ $editingUser ? 'Actualizar' : 'Crear' }} Usuario
                    </flux:button>
                    <flux:button wire:click="resetForm" variant="ghost">
                        Cancelar
                    </flux:button>
                </div>
            </form>
        </div>
    @endif

    <!-- Resto del código igual... -->
    <!-- Listado de Usuarios -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg">
        <div class="p-6 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="base">Usuarios Registrados</flux:heading>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Rol</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Email Verificado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($users as $user)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $user->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $user->email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($user->roles->isNotEmpty())
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        {{ $user->roles->first()->name }}
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                        Sin rol
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($user->is_active)
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Activo
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($user->email_verified_at)
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Verificado
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        No verificado
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <flux:button
                                    wire:click="editUser({{ $user->id }})"
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil"
                                >
                                    Editar
                                </flux:button>
                                <flux:button
                                    wire:click="deleteUser({{ $user->id }})"
                                    wire:confirm="¿Estás seguro de eliminar este usuario?"
                                    size="sm"
                                    variant="danger"
                                    icon="trash"
                                >
                                    Eliminar
                                </flux:button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="px-6 py-4">
            {{ $users->links() }}
        </div>
    </div>
</div>
