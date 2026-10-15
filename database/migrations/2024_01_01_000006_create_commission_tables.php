// database/migrations/2024_01_01_000006_create_commission_tables.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('commission_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('commission_plan_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_plan_id')->constrained('commission_plans')->cascadeOnDelete();
            $table->integer('version')->default(1);
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->timestamps();
            $table->unique(['commission_plan_id', 'version']);
        });

        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_plan_version_id')->constrained('commission_plan_versions')->cascadeOnDelete();
            $table->string('name');
            $table->enum('calculation_type', ['percentage', 'fixed']);
            $table->decimal('rate', 12, 4);                  // 5.0000 = 5%
            $table->enum('basis', ['grand_total', 'subtotal', 'line_total'])->default('subtotal');
            $table->jsonb('eligibility')->nullable();        // e.g. {"order_types":["dine_in"]}
            $table->timestamps();
        });

        Schema::create('commission_rule_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_rule_id')->constrained('commission_rules')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->enum('mode', ['include', 'exclude'])->default('include');
            $table->unique(['commission_rule_id', 'product_id']);
        });

        Schema::create('staff_commission_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('commission_plan_version_id')->constrained('commission_plan_versions');
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->timestamps();
        });

        Schema::create('commission_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_session_id')->nullable()->constrained('business_sessions');
            $table->foreignId('shift_id')->nullable()->constrained('shifts');
            $table->date('period_start');
            $table->date('period_end');
            $table->foreignId('run_by_staff_id')->constrained('staff');
            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
            $table->timestamps();
        });

        Schema::create('commission_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_run_id')->constrained('commission_runs')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff');
            $table->decimal('total_sales', 14, 2)->default(0);
            $table->decimal('commission_amount', 14, 2)->default(0);
            $table->decimal('adjustments_total', 14, 2)->default(0);
            $table->decimal('net_payable', 14, 2)->default(0);
            $table->enum('status', ['calculated', 'approved', 'rejected', 'paid'])->default('calculated');
            $table->timestamps();
            $table->unique(['commission_run_id', 'staff_id']);
        });

        Schema::create('commission_statement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_statement_id')->constrained('commission_statements')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders');
            $table->foreignId('order_item_id')->nullable()->constrained('order_items');
            $table->foreignId('refund_id')->nullable()->constrained('refunds');
            $table->decimal('eligible_amount', 14, 2);
            $table->decimal('rate_applied', 12, 4);
            $table->decimal('commission_amount', 14, 2);
            $table->timestamps();
        });

        Schema::create('commission_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_statement_id')->constrained('commission_statements')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);       // positive or negative
            $table->text('reason');
            $table->foreignId('approved_by_staff_id')->constrained('staff');
            $table->timestamps();
        });

        Schema::create('commission_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_statement_id')->constrained('commission_statements')->cascadeOnDelete();
            $table->foreignId('approver_staff_id')->constrained('staff');
            $table->enum('decision', ['approved', 'rejected']);
            $table->text('notes')->nullable();
            $table->timestamp('decided_at')->useCurrent();
        });

        Schema::create('commission_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_statement_id')->constrained('commission_statements')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff');
            $table->decimal('amount', 14, 2);
            $table->string('reference')->nullable();
            $table->timestamp('paid_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_payments');
        Schema::dropIfExists('commission_approvals');
        Schema::dropIfExists('commission_adjustments');
        Schema::dropIfExists('commission_statement_items');
        Schema::dropIfExists('commission_statements');
        Schema::dropIfExists('commission_runs');
        Schema::dropIfExists('staff_commission_assignments');
        Schema::dropIfExists('commission_rule_products');
        Schema::dropIfExists('commission_rules');
        Schema::dropIfExists('commission_plan_versions');
        Schema::dropIfExists('commission_plans');
    }
};