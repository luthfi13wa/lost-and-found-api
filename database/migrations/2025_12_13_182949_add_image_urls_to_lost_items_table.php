<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lost_items', function (Blueprint $table) {
            if (!Schema::hasColumn('lost_items', 'image_url')) {
                $table->text('image_url')->nullable()->after('image_path');
            }

            if (!Schema::hasColumn('lost_items', 'found_image_url')) {
                $table->text('found_image_url')->nullable()->after('found_image_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lost_items', function (Blueprint $table) {
            if (Schema::hasColumn('lost_items', 'image_url')) {
                $table->dropColumn('image_url');
            }

            if (Schema::hasColumn('lost_items', 'found_image_url')) {
                $table->dropColumn('found_image_url');
            }
        });
    }
};
