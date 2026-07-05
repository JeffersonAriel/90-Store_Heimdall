<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->enum('genero', ['Masculino', 'Feminino', 'Unissex', 'Infantil'])->default('Unissex')->after('nome');
        });

        Schema::table('fotos_produto', function (Blueprint $table) {
            $table->string('cor', 60)->nullable()->after('variacao_id')->comment('Cor associada à foto para agrupamento na vitrine');
        });
    }

    public function down()
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn('genero');
        });

        Schema::table('fotos_produto', function (Blueprint $table) {
            $table->dropColumn('cor');
        });
    }
};
