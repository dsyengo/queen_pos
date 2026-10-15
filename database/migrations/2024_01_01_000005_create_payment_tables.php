// database/migrations/2024_01_01_000005_create_payment_tables.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();       // Cash, M-Pesa, Card
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('payment_method_id')->constrained('payment_methods');
            $table->decimal('amount', 14, 2);
            $table->enum('status', ['pending', 'confirmed', 'failed', 'reversed'])->default('pending');
            $table->foreignId('received_by_staff_id')->constrained('staff');
            $table->timestamp('paid_at')->nullable();
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->string('provider_reference')->nullable();
            $table->string('event_type');           // initiated, confirmed, failed, reversed
            $table->jsonb('payload')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->unique(['payment_id', 'event_type', 'occurred_at']);
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments');
            $table->foreignId('order_id')->constrained('orders');
            $table->decimal('amount', 14, 2);
            $table->text('reason');
            $table->foreignId('requested_by_staff_id')->constrained('staff');
            $table->foreignId('approved_by_staff_id')->nullable()->constrained('staff');
            $table->enum('status', ['pending', 'approved', 'processed', 'rejected'])->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders');
            $table->decimal('amount', 14, 2);
            $table->timestamps();
        });

        Schema::create('payment_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_session_id')->constrained('business_sessions')->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts');
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods');
            $table->foreignId('staff_id')->nullable()->constrained('staff');
            $table->decimal('recorded_total', 14, 2);
            $table->decimal('actual_total', 14, 2)->nullable();
            $table->decimal('difference', 14, 2)->nullable();
            $table->enum('status', ['open', 'balanced', 'discrepancy'])->default('open');
            $table->timestamps();
        });

        Schema::create('payment_reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_reconciliation_id')->constrained('payment_reconciliations')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments');
            $table->foreignId('refund_id')->nullable()->constrained('refunds');
            $table->decimal('amount', 14, 2);
            $table->decimal('discrepancy', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reconciliation_items');
        Schema::dropIfExists('payment_reconciliations');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_methods');
    }
};