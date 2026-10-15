// database/migrations/2024_01_01_000004_create_order_tables.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable()->index();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('name')->nullable();
            $table->integer('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->enum('order_type', ['dine_in', 'takeaway']);
            $table->foreignId('business_session_id')->constrained('business_sessions');
            $table->foreignId('shift_id')->nullable()->constrained('shifts');
            $table->foreignId('waiter_staff_id')->constrained('staff');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('restaurant_table_id')->nullable()->constrained('restaurant_tables');
            $table->enum('status', ['open', 'submitted', 'preparing', 'ready', 'served', 'completed', 'cancelled'])->default('open');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'refunded'])->default('unpaid');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('service_charge_total', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_session_id', 'status']);
            $table->index(['waiter_staff_id', 'status']);
            $table->index('order_type');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->string('product_name');         // historical snapshot
            $table->decimal('unit_price', 12, 2);   // historical snapshot
            $table->integer('quantity');
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('line_total', 14, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('order_id');
        });

        Schema::create('order_item_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('modifier_option_id')->nullable()->constrained('modifier_options');
            $table->string('name_snapshot');
            $table->decimal('additional_price', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('status');
            $table->foreignId('changed_by_staff_id')->constrained('staff');
            $table->text('notes')->nullable();
            $table->timestamp('changed_at')->useCurrent();
        });

        Schema::create('order_attribution_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('previous_staff_id')->nullable()->constrained('staff');
            $table->foreignId('new_staff_id')->constrained('staff');
            $table->foreignId('authorized_by_staff_id')->constrained('staff');
            $table->text('reason');
            $table->timestamp('changed_at')->useCurrent();
        });

        Schema::create('order_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->cascadeOnDelete();
            $table->string('discount_type');       // percent, fixed
            $table->decimal('value', 12, 2);
            $table->decimal('amount', 12, 2);
            $table->text('reason');
            $table->foreignId('applied_by_staff_id')->constrained('staff');
            $table->foreignId('approved_by_staff_id')->nullable()->constrained('staff');
            $table->timestamps();
        });

        Schema::create('order_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('charge_type');         // service_charge, other
            $table->string('name');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });

        Schema::create('order_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->text('reason');
            $table->foreignId('requested_by_staff_id')->constrained('staff');
            $table->foreignId('authorized_by_staff_id')->nullable()->constrained('staff');
            $table->timestamp('cancelled_at')->useCurrent();
        });

        Schema::create('order_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->cascadeOnDelete();
            $table->text('note');
            $table->foreignId('created_by_staff_id')->constrained('staff');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_notes');
        Schema::dropIfExists('order_cancellations');
        Schema::dropIfExists('order_charges');
        Schema::dropIfExists('order_discounts');
        Schema::dropIfExists('order_attribution_history');
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('order_item_modifiers');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('restaurant_tables');
        Schema::dropIfExists('customers');
    }
};