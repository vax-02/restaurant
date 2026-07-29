<?php


namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         User::create([
            'name' => 'Administrador',
            'email' => 'admin@restaurante.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        // 3. Clientes (5 clientes de prueba)
        $clientes = [
            ['Juan Pérez', 'juan@restaurante.com'],
            ['Ana Gómez', 'ana@restaurante.com'],
            ['Luis Martínez', 'luis@restaurante.com'],
        ];

        foreach ($clientes as [$nombre, $email]) {
            User::create([
                'name' => $nombre,
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => User::ROLE_AYUDANTE,
            ]);
        }
    }
}
