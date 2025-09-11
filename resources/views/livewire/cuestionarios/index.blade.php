<?php

use App\Models\Cuestionario;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    // Propiedades del formulario
    public string $nombre = '';
    public string $nombre_corto = '';
    public string $descripcion = '';
    public string $tabla = '';

    // Propiedades para edición
    public ?Cuestionario $editingCuestionario = null;
    public bool $showForm = false;

    public function mount()
    {
        // Verificar que el usuario tenga permisos
        if (!auth()->user()->hasAnyRole(['superadmin', 'admin'])) {
            abort(403);
        }
    }

    /**
     * Crear un nuevo cuestionario
     */
    public function createCuestionario(): void
    {
        try {
            $validated = $this->validate([
                'nombre' => ['required', 'string', 'max:100'],
                'nombre_corto' => ['required', 'string', 'max:50'],
                'descripcion' => ['required', 'string', 'max:250'],
            ]);

            $cuestionario = Cuestionario::create($validated);

            if (!$cuestionario) {
                session()->flash('error', 'Error al crear el cuestionario.');
                return;
            }

            session()->flash('message', 'Cuestionario creado exitosamente.');
            $this->resetForm();

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            session()->flash('error', 'Error al crear el cuestionario: ' . $e->getMessage());
        }
    }

    /**
     * Preparar edición de cuestionario
     */
    public function editCuestionario(Cuestionario $cuestionario): void
    {
        $this->editingCuestionario = $cuestionario;
        $this->nombre = $cuestionario->nombre;
        $this->nombre_corto = $cuestionario->nombre_corto;
        $this->descripcion = $cuestionario->descripcion;
        $this->tabla = $cuestionario->tabla;
        $this->showForm = true;
    }

    /**
     * Actualizar cuestionario
     */
    public function updateCuestionario(): void
    {
        try {
            $validated = $this->validate([
                'nombre' => ['required', 'string', 'max:100'],
                'nombre_corto' => ['required', 'string', 'max:50'],
                'descripcion' => ['required', 'string', 'max:250'],
            ]);

            $this->editingCuestionario->update($validated);

            session()->flash('message', 'Cuestionario actualizado exitosamente.');
            $this->resetForm();

        } catch (\Exception $e) {
            session()->flash('error', 'Error al actualizar el cuestionario: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar cuestionario
     */
    public function deleteCuestionario(Cuestionario $cuestionario): void
    {
        try {
            $cuestionario->delete();
            session()->flash('message', 'Cuestionario eliminado exitosamente.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al eliminar el cuestionario: ' . $e->getMessage());
        }
    }

    /**
     * Resetear formulario
     */
    public function resetForm(): void
    {
        $this->nombre = '';
        $this->nombre_corto = '';
        $this->descripcion = '';
        $this->editingCuestionario = null;
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
     * Ir a gestión de variables
     */
    public function manageVariables(Cuestionario $cuestionario): void
    {
        $this->redirect(route('variables.index', $cuestionario));
    }

    /**
     * Renderizar el componente
     */
    public function with(): array
    {
        return [
            'cuestionarios' => Cuestionario::withCount('variables')->paginate(10),
        ];
    }
}; ?>

<div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="lg">Gestión de Cuestionarios</flux:heading>
            <flux:subheading>Administra los cuestionarios del sistema</flux:subheading>
        </div>
        <flux:button wire:click="showCreateForm" variant="primary" icon="plus">
            Crear Cuestionario
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

    <!-- Formulario de Cuestionario -->
    @if ($showForm)
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
            <flux:heading size="base" class="mb-4">{{ $editingCuestionario ? 'Editar' : 'Crear' }} Cuestionario</flux:heading>

            <form wire:submit="{{ $editingCuestionario ? 'updateCuestionario' : 'createCuestionario' }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input
                        wire:model="nombre"
                        label="Nombre"
                        placeholder="Nombre del cuestionario"
                        required
                    />

                    <flux:input
                        wire:model="nombre_corto"
                        label="Nombre Corto"
                        placeholder="Nombre corto"
                        required
                    />
                </div>

                <flux:textarea
                    wire:model="descripcion"
                    label="Descripción"
                    placeholder="Descripción del cuestionario"
                    rows="3"
                    required
                />

                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">
                        {{ $editingCuestionario ? 'Actualizar' : 'Crear' }} Cuestionario
                    </flux:button>
                    <flux:button wire:click="resetForm" variant="ghost">
                        Cancelar
                    </flux:button>
                </div>
            </form>
        </div>
    @endif

    <!-- Listado de Cuestionarios -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg">
        <div class="p-6 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="base">Cuestionarios Registrados</flux:heading>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Nombre Corto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Descripción</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Tabla</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Variables</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($cuestionarios as $cuestionario)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $cuestionario->nombre }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $cuestionario->nombre_corto }}</td>
                            <td class="px-6 py-4 text-sm text-zinc-900 dark:text-zinc-100">{{ Str::limit($cuestionario->descripcion, 50) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $cuestionario->tabla }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $cuestionario->variables_count }} variables
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <flux:button
                                    wire:click="manageVariables({{ $cuestionario->id }})"
                                    size="sm"
                                    variant="primary"
                                    icon="cog"
                                >
                                    Variables
                                </flux:button>
                                <flux:button
                                    wire:click="editCuestionario({{ $cuestionario->id }})"
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil"
                                >
                                    Editar
                                </flux:button>
                                <flux:button
                                    wire:click="deleteCuestionario({{ $cuestionario->id }})"
                                    wire:confirm="¿Estás seguro de eliminar este cuestionario? También se eliminarán todas sus variables."
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
            {{ $cuestionarios->links() }}
        </div>
    </div>
</div>
