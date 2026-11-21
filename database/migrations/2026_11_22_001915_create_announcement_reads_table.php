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
        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('announcement_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->dateTime('read_at');
            $table->timestamps();
            $table->unique(['announcement_id', 'user_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcement_reads');
    }
};
