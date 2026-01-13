<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ==========================================
        // CREAR PERMISOS
        // ==========================================
        $permissions = [
            // Módulo Personas
            'personas.view',
            'personas.create',
            'personas.edit',
            'personas.delete',

            // Módulo Contratos
            'contratos.view',
            'contratos.create',
            'contratos.edit',
            'contratos.delete',

            // Módulo Dashboard
            'dashboard.view',
            'dashboard.export',

            // Administración de Sistema
            'users.manage',
            'roles.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // ==========================================
        // CREAR ROLES Y ASIGNAR PERMISOS
        // ==========================================

        // 1. SUPER ADMIN - Acceso total al sistema
        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // 2. ADMIN RRHH - Gestión completa de Personas y Contratos
        $adminRrhh = Role::create(['name' => 'admin_rrhh']);
        $adminRrhh->givePermissionTo([
            'personas.view',
            'personas.create',
            'personas.edit',
            'personas.delete',
            'contratos.view',
            'contratos.create',
            'contratos.edit',
            'contratos.delete',
            'dashboard.view',
            'dashboard.export',
        ]);

        // 3. SUPERVISOR - Puede ver y editar, pero no eliminar
        $supervisor = Role::create(['name' => 'supervisor']);
        $supervisor->givePermissionTo([
            'personas.view',
            'personas.edit',
            'contratos.view',
            'contratos.edit',
            'dashboard.view',
        ]);

        // 4. VIEWER - Solo visualización
        $viewer = Role::create(['name' => 'viewer']);
        $viewer->givePermissionTo([
            'personas.view',
            'contratos.view',
            'dashboard.view',
        ]);

        // ==========================================
        // ASIGNAR ROL SUPER ADMIN AL PRIMER USUARIO
        // ==========================================
        $firstUser = User::first();
        if ($firstUser) {
            $firstUser->assignRole('super_admin');
            $this->command->info("✓ Usuario '{$firstUser->email}' asignado como super_admin");
        } else {
            $this->command->warn('⚠ No hay usuarios en la BD. Crea uno y asígnale rol manualmente.');
        }

        $this->command->info('✓ Roles y permisos creados exitosamente');
        $this->command->info('');
        $this->command->info('Roles creados:');
        $this->command->info('  - super_admin: Acceso total');
        $this->command->info('  - admin_rrhh: Gestión completa de RRHH');
        $this->command->info('  - supervisor: Ver y editar (no eliminar)');
        $this->command->info('  - viewer: Solo visualización');
    }
}
