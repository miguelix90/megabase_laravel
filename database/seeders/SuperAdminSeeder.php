<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = User::create([
            'name' => 'Miguel A. Huete',
            'email' => 'miguelix90@hotmail.com',
            'password' => Hash::make('soyelAMO1981'), // Cambiar en producción
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $superadmin->assignRole('superadmin');
    }
}
