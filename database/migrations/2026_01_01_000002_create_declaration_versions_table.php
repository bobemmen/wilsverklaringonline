<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('declaration_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('declaration_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('declarations', function (Blueprint $table) {
            $table->foreign('current_version_id')
                ->references('id')
                ->on('declaration_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('declarations', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });
        Schema::dropIfExists('declaration_versions');
    }
};
