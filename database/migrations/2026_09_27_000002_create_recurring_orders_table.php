<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('source_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('last_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('delivery_method', 20);
            $table->foreignId('address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->string('contact_phone', 30);
            $table->string('schedule_slot', 20)->nullable();
            $table->string('frequency', 20);
            $table->date('next_order_date')->index();
            $table->json('items');
            $table->text('customer_notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_orders');
    }
};
