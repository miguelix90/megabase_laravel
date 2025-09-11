<?php

use App\Models\Variable;
use App\Models\Cuestionario;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;

new class extends Component {
    use WithPagination, WithFileUploads;

    public int $cuestionarioId;
    public Cuestionario $cuestionario;

    // Propiedades del formulario
    public string $nombre = '';
    public string $etiqueta = '';
    public string $tipo = '';
    public string $valores = '';

    // Propiedades para edición
    public ?Variable $editingVariable = null;
    public bool $showForm = false;

    // Propiedades para CSV
    public $csvFile = null;
    public string $csvSeparator = ',';
    public bool $showCsvForm = false;
    public array $csvPreview = [];
    public bool $csvProcessed = false;

    public function mount(int $cuestionarioId)
    {
        $this->cuestionarioId = $cuestionarioId;
        $this->cuestionario = Cuestionario::findOrFail($cuestionarioId);

        // Verificar que el usuario tenga permisos
        if (!auth()->user()->hasAnyRole(['superadmin', 'admin'])) {
            abort(403);
        }
    }

    /**
     * Crear una nueva variable
     */
    public function createVariable(): void
    {
        try {
            $validated = $this->validate([
                'nombre' => ['required', 'string', 'max:100', 'unique:variables,nombre'],
                'etiqueta' => ['required', 'string', 'max:100'],
                'tipo' => ['required', 'string', 'max:20', 'in:' . implode(',', Variable::TIPOS)],
                'valores' => ['nullable', 'string'],
            ]);

            $validated['cuestionario_id'] = $this->cuestionario->id;

            $variable = Variable::create($validated);

            if (!$variable) {
                session()->flash('error', 'Error al crear la variable.');
                return;
            }

            session()->flash('message', 'Variable creada exitosamente.');
            $this->resetForm();

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            session()->flash('error', 'Error al crear la variable: ' . $e->getMessage());
        }
    }

    /**
     * Preparar edición de variable
     */
    public function editVariable(Variable $variable): void
    {
        $this->editingVariable = $variable;
        $this->nombre = $variable->nombre;
        $this->etiqueta = $variable->etiqueta;
        $this->tipo = $variable->tipo;
        $this->valores = $variable->valores ?? '';
        $this->showForm = true;
    }

    /**
     * Actualizar variable
     */
    public function updateVariable(): void
    {
        try {
            $validated = $this->validate([
                'nombre' => ['required', 'string', 'max:100', Rule::unique('variables')->ignore($this->editingVariable->id)],
                'etiqueta' => ['required', 'string', 'max:100'],
                'tipo' => ['required', 'string', 'max:20', 'in:' . implode(',', Variable::TIPOS)],
                'valores' => ['nullable', 'string'],
            ]);

            $this->editingVariable->update($validated);

            session()->flash('message', 'Variable actualizada exitosamente.');
            $this->resetForm();

        } catch (\Exception $e) {
            session()->flash('error', 'Error al actualizar la variable: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar variable
     */
    public function deleteVariable(Variable $variable): void
    {
        try {
            $variable->delete();
            session()->flash('message', 'Variable eliminada exitosamente.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al eliminar la variable: ' . $e->getMessage());
        }
    }

    /**
     * Procesar archivo CSV
     */
    public function processCsv(): void
    {
        try {
            $this->validate([
                'csvFile' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
                'csvSeparator' => ['required'],
            ]);

            $content = file_get_contents($this->csvFile->getRealPath());
            $lines = array_filter(explode("\n", $content));

            if (empty($lines)) {
                session()->flash('error', 'El archivo CSV está vacío.');
                return;
            }

            // Obtener encabezados
            $headers = str_getcsv($lines[0], $this->csvSeparator);
            $headers = array_map('trim', $headers);

            // Validar que existan las columnas requeridas
            $requiredColumns = ['nombre', 'etiqueta', 'tipo', 'valores'];
            $missingColumns = array_diff($requiredColumns, $headers);

            if (!empty($missingColumns)) {
                session()->flash('error', 'Faltan las siguientes columnas requeridas: ' . implode(', ', $missingColumns));
                return;
            }

            // Procesar filas
            $this->csvPreview = [];
            $errors = [];

            for ($i = 1; $i < count($lines); $i++) {
                $row = str_getcsv($lines[$i], $this->csvSeparator);

                if (count($row) !== count($headers)) {
                    $errors[] = "Fila " . ($i + 1) . ": Número incorrecto de columnas";
                    continue;
                }

                $rowData = array_combine($headers, array_map('trim', $row));

                // Validaciones básicas
                if (empty($rowData['nombre']) || empty($rowData['etiqueta']) || empty($rowData['tipo'])) {
                    $errors[] = "Fila " . ($i + 1) . ": Campos obligatorios vacíos";
                    continue;
                }

                if (!in_array($rowData['tipo'], Variable::TIPOS)) {
                    $errors[] = "Fila " . ($i + 1) . ": Tipo de variable inválido (" . $rowData['tipo'] . ")";
                    continue;
                }

                $this->csvPreview[] = $rowData;
            }

            if (!empty($errors)) {
                session()->flash('error', 'Errores en el archivo CSV: ' . implode(', ', $errors));
                return;
            }

            if (empty($this->csvPreview)) {
                session()->flash('error', 'No se encontraron filas válidas en el archivo CSV.');
                return;
            }

            $this->csvProcessed = true;
            session()->flash('message', 'Archivo CSV procesado correctamente. ' . count($this->csvPreview) . ' variables listas para importar.');

        } catch (\Exception $e) {
            session()->flash('error', 'Error al procesar el archivo CSV: ' . $e->getMessage());
        }
    }

    /**
     * Importar variables desde CSV
     */
    public function importCsv(): void
    {
        try {
            if (empty($this->csvPreview)) {
                session()->flash('error', 'No hay datos para importar. Procesa el archivo CSV primero.');
                return;
            }

            $importedCount = 0;
            $errors = [];

            foreach ($this->csvPreview as $index => $row) {
                try {
                    // Verificar si ya existe una variable con ese nombre
                    if (Variable::where('nombre', $row['nombre'])->exists()) {
                        $errors[] = "Variable '{$row['nombre']}' ya existe, se omitió";
                        continue;
                    }

                    Variable::create([
                        'cuestionario_id' => $this->cuestionario->id,
                        'nombre' => $row['nombre'],
                        'etiqueta' => $row['etiqueta'],
                        'tipo' => $row['tipo'],
                        'valores' => $row['valores'] ?? '',
                    ]);

                    $importedCount++;

                } catch (\Exception $e) {
                    $errors[] = "Error al importar variable '{$row['nombre']}': " . $e->getMessage();
                }
            }

            $message = "Se importaron {$importedCount} variables exitosamente.";
            if (!empty($errors)) {
                $message .= " Errores: " . implode(', ', $errors);
            }

            session()->flash('message', $message);
            $this->resetCsvForm();

        } catch (\Exception $e) {
            session()->flash('error', 'Error al importar variables: ' . $e->getMessage());
        }
    }

    /**
     * Resetear formulario
     */
    public function resetForm(): void
    {
        $this->nombre = '';
        $this->etiqueta = '';
        $this->tipo = '';
        $this->valores = '';
        $this->editingVariable = null;
        $this->showForm = false;
    }

    /**
     * Resetear formulario CSV
     */
    public function resetCsvForm(): void
    {
        $this->csvFile = null;
        $this->csvSeparator = ',';
        $this->showCsvForm = false;
        $this->csvPreview = [];
        $this->csvProcessed = false;
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
     * Mostrar formulario CSV
     */
    public function showCsvImportForm(): void
    {
        $this->resetCsvForm();
        $this->showCsvForm = true;
    }

    /**
     * Volver a cuestionarios
     */
    public function backToCuestionarios(): void
    {
        $this->redirect(route('cuestionarios.index'));
    }

    /**
     * Renderizar el componente
     */
    public function with(): array
    {
        return [
            'variables' => $this->cuestionario->variables()->paginate(10),
            'tipos' => Variable::TIPOS,
        ];
    }
}; ?>

<div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <flux:button wire:click="backToCuestionarios" variant="ghost" icon="arrow-left" class="mb-2">
                Volver a Cuestionarios
            </flux:button>
            <flux:heading size="lg">Variables - {{ $cuestionario->nombre }}</flux:heading>
            <flux:subheading>Gestiona las variables del cuestionario</flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:button wire:click="showCsvImportForm" variant="outline" icon="document-arrow-up">
                Importar CSV
            </flux:button>
            <flux:button wire:click="showCreateForm" variant="primary" icon="plus">
                Crear Variable
            </flux:button>
        </div>
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

    <!-- Formulario de Variable -->
    @if ($showForm)
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
            <flux:heading size="base" class="mb-4">{{ $editingVariable ? 'Editar' : 'Crear' }} Variable</flux:heading>

            <form wire:submit="{{ $editingVariable ? 'updateVariable' : 'createVariable' }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input
                        wire:model="nombre"
                        label="Nombre"
                        placeholder="Nombre de la variable"
                        required
                    />

                    <flux:input
                        wire:model="etiqueta"
                        label="Etiqueta"
                        placeholder="Etiqueta descriptiva"
                        required
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:label for="tipo">Tipo</flux:label>
                    <select
                        wire:model="tipo"
                        id="tipo"
                        class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        required
                    >
                        <option value="">Selecciona un tipo</option>
                        @foreach ($tipos as $tipoOption)
                            <option value="{{ $tipoOption }}">{{ ucfirst($tipoOption) }}</option>
                        @endforeach
                    </select>

                </div>

                <flux:textarea
                    wire:model="valores"
                    label="Valores"
                    placeholder="Para radio/select: valor1, etiqueta1 | valor2, etiqueta2"
                    rows="3"
                    description="Para tipos radio y select, especifica las opciones en formato: valor, etiqueta | valor, etiqueta"
                />

                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">
                        {{ $editingVariable ? 'Actualizar' : 'Crear' }} Variable
                    </flux:button>
                    <flux:button wire:click="resetForm" variant="ghost">
                        Cancelar
                    </flux:button>
                </div>
            </form>
        </div>
    @endif

    <!-- Formulario de importación CSV -->
    @if ($showCsvForm)
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
            <flux:heading size="base" class="mb-4">Importar Variables desde CSV</flux:heading>

            <div class="space-y-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <flux:text class="text-sm text-blue-800 dark:text-blue-200">
                        <strong>Formato del archivo CSV:</strong><br>
                        • Columnas requeridas: nombre, etiqueta, tipo, valores<br>
                        • Para tipos radio/select, usar formato en valores: valor1, etiqueta1 | valor2, etiqueta2<br>
                        • Tipos válidos: {{ implode(', ', $tipos) }}
                    </flux:text>
                </div>

                @if (!$csvProcessed)
                    <form wire:submit="processCsv" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:input
                                type="file"
                                wire:model="csvFile"
                                label="Archivo CSV"
                                accept=".csv,.txt"
                                required
                            />

                            <flux:label for="csvSeparator">Separador</flux:label>
                            <select
                                wire:model="csvSeparator"
                                id="csvSeparator"
                                class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                required
                            >
                                <option value=",">Coma (,)</option>
                                <option value=";">Punto y coma (;)</option>
                                <option value="|">Pipe (|)</option>
                            </select>
                        </div>

                        <div class="flex gap-2">
                            <flux:button type="submit" variant="primary">
                                Procesar CSV
                            </flux:button>
                            <flux:button wire:click="resetCsvForm" variant="ghost">
                                Cancelar
                            </flux:button>
                        </div>
                    </form>
                @else
                    <!-- Vista previa del CSV -->
                    <div class="space-y-4">
                        <flux:heading size="sm">Vista previa ({{ count($csvPreview) }} variables)</flux:heading>

                        <div class="overflow-x-auto max-h-96">
                            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                                <thead class="bg-zinc-50 dark:bg-zinc-800">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Nombre</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Etiqueta</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Tipo</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Valores</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                                    @foreach ($csvPreview as $row)
                                        <tr>
                                            <td class="px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100">{{ $row['nombre'] }}</td>
                                            <td class="px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100">{{ $row['etiqueta'] }}</td>
                                            <td class="px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100">{{ $row['tipo'] }}</td>
                                            <td class="px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100">{{ Str::limit($row['valores'] ?? '', 30) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="flex gap-2">
                            <flux:button wire:click="importCsv" variant="primary">
                                Importar {{ count($csvPreview) }} Variables
                            </flux:button>
                            <flux:button wire:click="resetCsvForm" variant="ghost">
                                Cancelar
                            </flux:button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Listado de Variables -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg">
        <div class="p-6 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="base">Variables del Cuestionario ({{ $variables->total() }})</flux:heading>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Etiqueta</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Valores</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($variables as $variable)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $variable->nombre }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $variable->etiqueta }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    {{ ucfirst($variable->tipo) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-900 dark:text-zinc-100">{{ Str::limit($variable->valores ?? '', 50) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <flux:button
                                    wire:click="editVariable({{ $variable->id }})"
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil"
                                >
                                    Editar
                                </flux:button>
                                <flux:button
                                    wire:click="deleteVariable({{ $variable->id }})"
                                    wire:confirm="¿Estás seguro de eliminar esta variable?"
                                    size="sm"
                                    variant="danger"
                                    icon="trash"
                                >
                                    Eliminar
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                No hay variables creadas para este cuestionario.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        @if ($variables->hasPages())
            <div class="px-6 py-4">
                {{ $variables->links() }}
            </div>
        @endif
    </div>
</div>
