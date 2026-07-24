<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_banners', function (Blueprint $table) {
            if (!Schema::hasColumn('store_banners', 'image_mobile_path')) {
                $table->string('image_mobile_path')->nullable()->after('image_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_banners', function (Blueprint $table) {
            if (Schema::hasColumn('store_banners', 'image_mobile_path')) {
                $table->dropColumn('image_mobile_path');
            }
        });
    }
};
