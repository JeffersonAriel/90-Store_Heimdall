<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PerfilPermissaoSeeder::class,
            FullCategoryStructureSeeder::class,
            ApiConfigSeeder::class,
            FreteRegraSeeder::class,
            RegrasPontosSeeder::class,
        ]);
    }
}
