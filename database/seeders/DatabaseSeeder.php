<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiamos la tabla por si quedaron registros basura
        DB::table('users')->delete();

        // Inyectamos directo a la base de datos
        DB::table('users')->insert([
            [
                'name' => 'Henry Admin',
                'email' => 'admin@crm.com',
                'password' => Hash::make('password123'),
                'rol' => 'admin',
            ],
            [
                'name' => 'Henry Usuario',
                'email' => 'usuario@crm.com',
                'password' => Hash::make('password123'),
                'rol' => 'usuario',
            ]
        ]);
    }
}