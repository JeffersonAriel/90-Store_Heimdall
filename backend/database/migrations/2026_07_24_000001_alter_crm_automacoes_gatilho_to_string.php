<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_automacoes', function (Blueprint $table) {
            $table->string('gatilho', 50)->default('apos_entrega')->change();
        });
    }

    public function down(): void
    {
        Schema::table('crm_automacoes', function (Blueprint $table) {
            $table->string('gatilho', 50)->default('apos_entrega')->change();
        });
    }
};
