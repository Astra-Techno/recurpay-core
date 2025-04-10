<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('property_payments')) {
            Schema::create('property_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('property_id');
                $table->unsignedBigInteger('lease_id')->nullable()->comment('For lease-related payments');
                $table->unsignedBigInteger('tenant_id')->nullable()->comment('For tenant-specific payments');
                $table->string('name', 100)->comment('Payment type identifier');
                $table->text('description')->nullable();
                $table->decimal('amount', 10, 2);
                $table->enum('period', [
                    'one-time', 'daily', 'weekly', 'fortnightly', 'fourweekly', 'monthly', 'yearly'
                ])->default('monthly');
                $table->date('due_from')->comment('First due date');
                $table->date('due_until')->nullable()->comment('For recurring payments');
                $table->boolean('is_refundable')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->comment('User who created this payment');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                // Indexes
                $table->index('name', 'payment_type');
                $table->index(['due_from', 'due_until'], 'payment_schedule');
            });
        }
    }

    public function down(): void {
        Schema::dropIfExists('property_payments');
    }
};
// This migration creates a table for property payments, including lease-related and tenant-specific payments.
// It includes fields for payment type, amount, schedule, and refundability.
// The table also tracks who created the payment and when it was created/updated.
// The migration ensures the table is only created if it doesn't already exist.
// The `up` method creates the table with the specified columns and indexes.
// The `down` method drops the table if it exists.
// The migration uses Laravel's Schema builder to define the table structure.
// The `useCurrent` and `useCurrentOnUpdate` methods set default values for timestamps.
// The `comment` method adds descriptions to the columns for better understanding.
// The `index` method creates indexes on specific columns to improve query performance.
