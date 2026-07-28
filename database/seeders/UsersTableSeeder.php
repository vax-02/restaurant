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

        // 2. Repartidores
        User::create([
            'name' => 'Carlos Repartidor',
            'email' => 'carlos@repartidor.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_DELIVERY,
        ]);

        User::create([
            'name' => 'Maria Repartidora',
            'email' => 'maria@repartidor.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_DELIVERY,
        ]);

        // 3. Clientes (5 clientes de prueba)
        $clientes = [
            ['Juan Pérez', 'juan@cliente.com'],
            ['Ana Gómez', 'ana@cliente.com'],
            ['Luis Martínez', 'luis@cliente.com'],
            ['Carmen López', 'carmen@cliente.com'],
            ['Pedro Sánchez', 'pedro@cliente.com'],
        ];

        foreach ($clientes as [$nombre, $email]) {
            User::create([
                'name' => $nombre,
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => User::ROLE_CLIENT,
            ]);
        }
    }
}
