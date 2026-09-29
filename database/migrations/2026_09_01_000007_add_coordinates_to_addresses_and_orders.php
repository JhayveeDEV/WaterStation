<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            // Nullable: a customer can save an address without pinning it, but is
            // strongly encouraged to (see the map on the "add address" form) since
            // it's what lets the rider actually find them.
            $table->decimal('lat', 10, 7)->nullable()->after('notes');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
        });

        Schema::table('orders', function (Blueprint $table) {
            // Snapshotted from the address at checkout time, same reasoning as
            // delivery_address being a text snapshot — an address edited later
            // shouldn't silently move a pin the rider already navigated to.
            $table->decimal('delivery_lat', 10, 7)->nullable()->after('delivery_address');
            $table->decimal('delivery_lng', 10, 7)->nullable()->after('delivery_lat');
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_lat', 'delivery_lng']);
        });
    }
};
