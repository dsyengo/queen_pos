// database/migrations/2024_01_01_000007_create_audit_tables.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->nullable()->constrained('staff');
            $table->foreignId('user_account_id')->nullable()->constrained('user_accounts');
            $table->string('action');               // order.created, payment.confirmed
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->jsonb('before')->nullable();
            $table->jsonb('after')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->index(['subject_type', 'subject_id']);
            $table->index(['action', 'occurred_at']);
        });

        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_type');         // discount, refund, correction
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('requested_by_staff_id')->constrained('staff');
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });

        Schema::create('approval_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->cascadeOnDelete();
            $table->foreignId('approver_staff_id')->constrained('staff');
            $table->enum('decision', ['approved', 'rejected']);
            $table->text('notes')->nullable();
            $table->timestamp('decided_at')->useCurrent();
        });

        Schema::create('record_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->text('reason');
            $table->foreignId('requested_by_staff_id')->constrained('staff');
            $table->enum('status', ['pending', 'approved', 'rejected', 'applied'])->default('pending');
            $table->foreignId('approved_by_staff_id')->nullable()->constrained('staff');
            $table->timestamps();
        });

        Schema::create('system_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->nullable()->constrained('staff');
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('session_closing_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_session_id')->constrained('business_sessions')->cascadeOnDelete();
            $table->decimal('total_sales', 14, 2);
            $table->decimal('total_payments', 14, 2);
            $table->decimal('total_refunds', 14, 2);
            $table->decimal('discrepancy', 14, 2)->default(0);
            $table->foreignId('closed_by_staff_id')->constrained('staff');
            $table->jsonb('summary')->nullable();
            $table->timestamps();
        });

        Schema::create('session_closing_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_closing_report_id')->constrained('session_closing_reports')->cascadeOnDelete();
            $table->string('dimension');            // payment_method, category, waiter
            $table->string('dimension_value');
            $table->decimal('amount', 14, 2);
            $table->timestamps();
        });

        Schema::create('report_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('report_key');
            $table->date('period_start');
            $table->date('period_end');
            $table->jsonb('payload');
            $table->timestamp('generated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_snapshots');
        Schema::dropIfExists('session_closing_report_items');
        Schema::dropIfExists('session_closing_reports');
        Schema::dropIfExists('system_notifications');
        Schema::dropIfExists('record_correction_requests');
        Schema::dropIfExists('approval_decisions');
        Schema::dropIfExists('approval_requests');
        Schema::dropIfExists('audit_logs');
    }
};