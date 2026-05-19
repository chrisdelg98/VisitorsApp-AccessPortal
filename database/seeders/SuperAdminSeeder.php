<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // id es bigint AUTO_INCREMENT — no se asigna manualmente
        User::create([
            'name'       => 'EFL Super Admin',
            'email'      => 'admin@efltrackingsystem.com',
            'password'   => Hash::make('EFL@Admin2026!'),
            'role'       => 'super_admin',
            'country_id' => null,
            'is_active'  => true,
        ]);
    }
}
