<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->integer('estoque_critico')->default(0)->after('peso_kg')->comment('Avisa quando o estoque total baixar deste valor');
        });

        Schema::table('variacoes_produto', function (Blueprint $table) {
            $table->dropColumn('estoque_critico');
        });
    }

    public function down()
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn('estoque_critico');
        });

        Schema::table('variacoes_produto', function (Blueprint $table) {
            $table->integer('estoque_critico')->nullable();
        });
    }
};
