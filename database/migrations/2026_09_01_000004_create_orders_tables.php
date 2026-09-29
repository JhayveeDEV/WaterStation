<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();
            $table->string('checkout_token', 64)->nullable()->unique(); // blocks duplicate submissions
            $table->foreignId('customer_id')->constrained('users');
            $table->string('delivery_method', 20);                     // delivery | pickup
            $table->foreignId('address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->text('delivery_address')->nullable();              // snapshot at time of order
            $table->string('contact_phone', 30);
            $table->date('schedule_date')->index();
            $table->string('schedule_slot', 20)->nullable();           // e.g. 09:00-10:00
            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_method', 20)->default('cash');
            $table->string('payment_status', 20)->default('unpaid');   // unpaid | paid
            $table->timestamp('paid_at')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->text('customer_notes')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->boolean('stock_deducted')->default(false);
            $table->timestamps();

            $table->index(['customer_id', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('rider_id')->constrained('users');
            $table->foreignId('address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->string('status', 30)->default('assigned');         // assigned | out_for_delivery | delivered | cancelled
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('notes')->nullable();
            $table->decimal('last_lat', 10, 7)->nullable();
            $table->decimal('last_lng', 10, 7)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->timestamps();

            $table->index(['rider_id', 'status']);
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('type', 20);            // received | used | damaged | adjusted | returned
            $table->integer('quantity');           // signed change (+ in, - out)
            $table->integer('balance_after');
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
