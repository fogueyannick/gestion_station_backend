<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Station;
use Illuminate\Support\Facades\Hash;

class InitialDataSeeder extends Seeder
{
    public function run()
    {
        User::updateOrCreate(
            ['username' => 'gerant'], // 🔑 clé UNIQUE
            [
                'name' => 'gerant',
                'email' => 'gerant@example.com',
                'password' => Hash::make('gerant'),
                'role' => 'gerant',
            ]
        );

        User::updateOrCreate(
            ['username' => 'pompiste'], // 🔑 clé UNIQUE
            [
                'name' => 'pompiste',
                'email' => 'pompiste@example.com',
                'password' => Hash::make('pompiste'),
                'role' => 'pompiste',
            ]
        );

        Station::updateOrCreate(
            ['name' => 'Auberge'],
            ['location' => 'Bafoussam']
        );

        $this->command->info('Seeder initial exécuté : gerant/gerant et pompiste/pompiste et station');
    }
}
