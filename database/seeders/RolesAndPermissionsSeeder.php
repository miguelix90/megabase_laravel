<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos
        $permissions = [
            // Gestión de usuarios
            'manage_users',
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',

            // Gestión de estudios/proyectos
            'manage_studies',
            'view_studies',
            'create_studies',
            'edit_studies',
            'delete_studies',

            // Gestión de participantes
            'manage_participants',
            'view_participants',
            'create_participants',
            'edit_participants',
            'delete_participants',

            // Gestión de datos
            'upload_data',
            'view_data',
            'edit_data',
            'delete_data',
            'download_data',
            'import_data',
            'export_data',

            // Reportes y análisis
            'view_reports',
            'generate_reports',

            // Configuración del sistema
            'system_settings',
            'view_audit_logs',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Crear roles con sus permisos específicos

        // 1. SUPERADMIN - Acceso total
        $superadmin = Role::create(['name' => 'superadmin']);
        $superadmin->givePermissionTo(Permission::all());

        // 2. ADMIN - Todo menos gestión de usuarios
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'view_users', // Solo puede ver usuarios, no crearlos/editarlos
            'manage_studies',
            'view_studies',
            'create_studies',
            'edit_studies',
            'delete_studies',
            'manage_participants',
            'view_participants',
            'create_participants',
            'edit_participants',
            'delete_participants',
            'upload_data',
            'view_data',
            'edit_data',
            'delete_data',
            'download_data',
            'import_data',
            'export_data',
            'view_reports',
            'generate_reports',
            'view_audit_logs',
        ]);

        // 3. DATA_ENTRY - Solo subir datos y descargar
        $dataEntry = Role::create(['name' => 'data_entry']);
        $dataEntry->givePermissionTo([
            'view_studies', // Para ver a qué estudios puede subir datos
            'view_participants', // Para ver participantes
            'upload_data',
            'view_data', // Para ver los datos que sube
            'download_data',
            'import_data',
            'export_data',
        ]);

        // 4. CONSULTOR - Solo descargar datos
        $consultor = Role::create(['name' => 'consultor']);
        $consultor->givePermissionTo([
            'view_studies', // Para ver qué estudios están disponibles
            'view_participants', // Para contexto de los datos
            'view_data',
            'download_data',
            'export_data',
            'view_reports',
        ]);
    }
}
