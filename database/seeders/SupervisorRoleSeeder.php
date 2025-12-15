<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SupervisorRoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'chat.view.all',
            'chat.takeover',
            'chat.close',

            'agent.view',
            'agent.manage',

            'template.review',
            'broadcast.review',

            'report.view',
            'activitylog.view',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $role = Role::firstOrCreate(['name' => 'Supervisor']);

        $role->syncPermissions($permissions);
    }
}
