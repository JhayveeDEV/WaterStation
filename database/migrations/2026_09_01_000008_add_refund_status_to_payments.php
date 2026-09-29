<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Null until an already-paid order is cancelled/rejected — there is no
            // real refund gateway here, so this just tracks that money is owed
            // back to the customer until an admin marks it processed (done outside
            // the app, in the actual GCash account, then recorded here).
            $table->string('refund_status', 20)->nullable()->after('status'); // pending | refunded
            $table->timestamp('refunded_at')->nullable()->after('refund_status');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['refund_status', 'refunded_at']);
        });
    }
};
