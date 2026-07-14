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
        Schema::table('produtos', function (Blueprint $table) {
            $table->boolean('esgotado')->default(false)->after('ativo');
        });

        Schema::create('solicitacoes_avise_me', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->string('nome');
            $table->string('email');
            $table->string('status')->default('pendente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_avise_me');
        
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn('esgotado');
        });
    }
};
