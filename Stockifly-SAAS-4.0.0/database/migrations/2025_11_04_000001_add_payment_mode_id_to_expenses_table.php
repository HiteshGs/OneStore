<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentModeIdToExpensesTable extends Migration
{
    public function up()
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'payment_mode_id')) {
                $table->unsignedBigInteger('payment_mode_id')->nullable()->after('amount');
                $table->foreign('payment_mode_id', 'expenses_payment_mode_id_foreign')
                    ->references('id')->on('payment_modes')
                    ->onUpdate('cascade')->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Drop FK only if column exists
            if (Schema::hasColumn('expenses', 'payment_mode_id')) {
                $table->dropForeign('expenses_payment_mode_id_foreign');
                $table->dropColumn('payment_mode_id');
            }
        });
    }
}
