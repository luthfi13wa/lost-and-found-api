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
        Schema::create('lost_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // later for login/ownership
            $table->string('title');
            $table->text('description');
            $table->string('location');
            $table->date('date_lost');
            $table->string('contact');
            $table->enum('status', ['lost', 'found'])->default('lost');
            $table->string('image_path')->nullable(); // for when we add photo upload
            $table->timestamps();

            // optional foreign key for users table (for later)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lost_items');
    }
};
