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
        Schema::table('categorias_tipo_produto', function (Blueprint $table) {
            $table->string('banner_path')->nullable()->after('icone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categorias_tipo_produto', function (Blueprint $table) {
            $table->dropColumn('banner_path');
        });
    }
};
