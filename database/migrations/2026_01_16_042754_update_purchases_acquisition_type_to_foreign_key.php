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
        // On databases migrated incrementally the acquisition_type_id column
        // already exists, so we only add the foreign key. On a fresh database
        // the column was never created, so we add it together with the key.
        if (Schema::hasColumn('purchases', 'acquisition_type_id')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->foreign('acquisition_type_id')->references('id')->on('acquisition_types');
            });

            return;
        }

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('acquisition_type_id')
                ->nullable()
                ->after('acquisition_type')
                ->constrained('acquisition_types')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['acquisition_type_id']);
        });
    }
};
