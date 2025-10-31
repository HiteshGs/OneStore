<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('product_custom_fields')) return;

        // 1) Remove duplicates (keep highest id)
        DB::statement("
            DELETE t1 FROM product_custom_fields t1
            INNER JOIN product_custom_fields t2
                ON t1.product_id = t2.product_id
               AND (t1.warehouse_id <=> t2.warehouse_id)
               AND t1.field_name = t2.field_name
               AND t1.id < t2.id
        ");

        // 2) Add composite unique
        Schema::table('product_custom_fields', function (Blueprint $table) {
            $table->unique(['product_id', 'warehouse_id', 'field_name'], 'uq_pcf_product_wh_field');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('product_custom_fields')) return;

        Schema::table('product_custom_fields', function (Blueprint $table) {
            $table->dropUnique('uq_pcf_product_wh_field');
        });
    }
};
