<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Admin',
                'description' => 'Platform owner with full system access',
            ],
            [
                'name' => 'school_admin',
                'display_name' => 'School Administrator',
                'description' => 'School administrator with full school access',
            ],
            [
                'name' => 'teacher',
                'display_name' => 'Teacher',
                'description' => 'Teacher with access to assigned classes and subjects',
            ],
            [
                'name' => 'parent',
                'display_name' => 'Parent',
                'description' => 'Parent with read-only access to subscribed student data',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}

