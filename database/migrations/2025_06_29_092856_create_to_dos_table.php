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
        Schema::create('to_dos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('completed')->default(false);
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
            $table->timestamp('due_date')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium'); // Enforce valid priorities
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending'); // Enforce valid statuses
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('to_dos', function (Blueprint $table) {
            $table->dropForeign(['user_id']); // Drops the foreign key constraint
        });
        Schema::dropIfExists('to_dos');
    }
};