<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            // Add parent_warehouse_id column if not exists
            if (!Schema::hasColumn('warehouses', 'parent_warehouse_id')) {
                $table->unsignedBigInteger('parent_warehouse_id')->nullable()->after('company_id');

                // Add foreign key referencing warehouses(id)
                $table->foreign('parent_warehouse_id')
                      ->references('id')
                      ->on('warehouses')
                      ->onUpdate('cascade')
                      ->onDelete('set null');

                $table->index('parent_warehouse_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            if (Schema::hasColumn('warehouses', 'parent_warehouse_id')) {
                $table->dropForeign(['parent_warehouse_id']);
                $table->dropIndex(['parent_warehouse_id']);
                $table->dropColumn('parent_warehouse_id');
            }
        });
    }
};
