<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fretes_regras', function (Blueprint $table) {
            $table->string('logradouro_origem')->nullable()->default('Rua Marechal Tito');
            $table->string('numero_origem', 20)->nullable()->default('1000');
            $table->string('complemento_origem')->nullable();
            $table->string('bairro_origem')->nullable()->default('São Miguel Paulista');
            $table->string('cidade_origem')->nullable()->default('São Paulo');
            $table->string('estado_origem', 2)->nullable()->default('SP');
            $table->string('documento_origem', 20)->nullable()->default('00000000000');
            $table->string('telefone_origem', 20)->nullable()->default('11999999999');
            $table->string('email_origem')->nullable()->default('sac@90store.com.br');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fretes_regras', function (Blueprint $table) {
            $table->dropColumn([
                'logradouro_origem',
                'numero_origem',
                'complemento_origem',
                'bairro_origem',
                'cidade_origem',
                'estado_origem',
                'documento_origem',
                'telefone_origem',
                'email_origem',
            ]);
        });
    }
};
