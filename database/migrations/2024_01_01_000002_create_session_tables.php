// database/migrations/2024_01_01_000002_create_session_tables.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('business_sessions', function (Blueprint $table) {
            $table->id();
            $table->date('business_date')->unique();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('opened_by_staff_id')->constrained('staff');
            $table->foreignId('closed_by_staff_id')->nullable()->constrained('staff');
            $table->enum('status', ['open', 'closed', 'reopened'])->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('session_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_session_id')->constrained('business_sessions')->cascadeOnDelete();
            $table->string('event_type');         // opened, closed, reopened, handover
            $table->foreignId('staff_id')->constrained('staff');
            $table->jsonb('payload')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_session_id')->constrained('business_sessions')->cascadeOnDelete();
            $table->string('name');               // Morning, Evening
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();
        });

        Schema::create('shift_staff_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff');
            $table->string('responsibility')->nullable();
            $table->timestamp('assigned_at')->useCurrent();
            $table->unique(['shift_id', 'staff_id']);
        });

        Schema::create('supervisor_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_session_id')->constrained('business_sessions')->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts');
            $table->foreignId('outgoing_staff_id')->constrained('staff');
            $table->foreignId('incoming_staff_id')->constrained('staff');
            $table->text('notes')->nullable();
            $table->timestamp('handed_over_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('cash_drawers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cash_drawer_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_drawer_id')->constrained('cash_drawers');
            $table->foreignId('business_session_id')->constrained('business_sessions')->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts');
            $table->decimal('opening_float', 12, 2)->default(0);
            $table->decimal('expected_closing', 12, 2)->nullable();
            $table->decimal('actual_closing', 12, 2)->nullable();
            $table->foreignId('opened_by_staff_id')->constrained('staff');
            $table->foreignId('closed_by_staff_id')->nullable()->constrained('staff');
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_drawer_session_id')->constrained('cash_drawer_sessions')->cascadeOnDelete();
            $table->enum('direction', ['in', 'out']);
            $table->decimal('amount', 12, 2);
            $table->string('reason');
            $table->foreignId('staff_id')->constrained('staff');
            $table->foreignId('approved_by_staff_id')->nullable()->constrained('staff');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('cash_drawer_sessions');
        Schema::dropIfExists('cash_drawers');
        Schema::dropIfExists('supervisor_handovers');
        Schema::dropIfExists('shift_staff_assignments');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('session_events');
        Schema::dropIfExists('business_sessions');
    }
};