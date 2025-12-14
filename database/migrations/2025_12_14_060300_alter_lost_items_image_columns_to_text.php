<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lost_items', function (Blueprint $table) {
            $table->text('image_path')->nullable()->change();
            $table->text('found_image_path')->nullable()->change();
            $table->text('image_url')->nullable()->change();
            $table->text('found_image_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('lost_items', function (Blueprint $table) {
            $table->string('image_path')->nullable()->change();
            $table->string('found_image_path')->nullable()->change();
            $table->string('image_url')->nullable()->change();
            $table->string('found_image_url')->nullable()->change();
        });
    }
};
