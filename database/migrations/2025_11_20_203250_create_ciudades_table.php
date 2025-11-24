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
        Schema::create('ciudades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departamento_id')->constrained('departamentos')->onDelete('cascade');
            $table->string('code', 10);
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamp('active_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['departamento_id', 'code']);
            $table->unique(['departamento_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ciudades');
    }
};
