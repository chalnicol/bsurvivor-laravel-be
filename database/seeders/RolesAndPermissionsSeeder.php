<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        Permission::create(['name' => 'create challenge']);
        Permission::create(['name' => 'edit challenge']);
        Permission::create(['name' => 'delete challenge']);

        Permission::create(['name' => 'edit users']);
        Permission::create(['name' => 'delete users']);
        Permission::create(['name' => 'block users']);

        Permission::create(['name' => 'view resources']);

        // Add more permissions as needed  

        // Create Roles and assign existing Permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all()); 

        $editorRole = Role::create(['name' => 'staff']);
        $editorRole->givePermissionTo(['edit challenge', 'edit users']);

        $editorRole = Role::create(['name' => 'spectator']);
        $editorRole->givePermissionTo(['view resources']);

        // $userRole = Role::create(['name' => 'user']);
        // Users might not have any specific permissions by default,
        // or you can assign some basic ones like 'view posts' if you create it.

        // Assign roles to existing users (example)
        $admin = User::find(1); 
        if ($admin) {
            $admin->assignRole('admin');
        }

        $editor = User::find(2); 
        if ($editor) {
            $editor->assignRole('staff');
        }
    }
}
