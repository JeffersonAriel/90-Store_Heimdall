<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('fretes_regras')->update([
            'logradouro_origem' => 'Rua Nicolau Campanella',
            'numero_origem'     => '25',
            'bairro_origem'     => 'Vila Verde',
            'cidade_origem'     => 'São Paulo',
            'estado_origem'     => 'SP',
            'cep_origem'        => '08230-600',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
