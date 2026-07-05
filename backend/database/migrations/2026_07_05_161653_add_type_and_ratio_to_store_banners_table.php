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
        Schema::table('store_banners', function (Blueprint $table) {
            $table->string('type')->default('vitrine')->after('image_path');
            $table->string('aspect_ratio')->default('16:9')->after('type');
            $table->unsignedBigInteger('category_id')->nullable()->after('aspect_ratio');
            $table->foreign('category_id')->references('id')->on('categorias_tipo_produto')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_banners', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['type', 'aspect_ratio', 'category_id']);
        });
    }
};
