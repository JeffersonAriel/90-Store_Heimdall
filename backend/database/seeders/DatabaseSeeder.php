<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PerfilPermissaoSeeder::class,
            CategorySeeder::class,
            SportsCategorySeeder::class,
            ApiConfigSeeder::class,
            FreteRegraSeeder::class,
            RegrasPontosSeeder::class,
        ]);
    }
}
