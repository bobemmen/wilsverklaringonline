<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('declaration_id')->constrained()->cascadeOnDelete();
            $table->enum('accessor_type', ['arts', 'naaste']);
            $table->string('accessor_identifier')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->timestamp('accessed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};
