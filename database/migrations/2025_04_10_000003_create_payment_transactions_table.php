<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('payment_transactions')) {
            Schema::create('payment_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payment_id');
                $table->decimal('amount_paid', 10, 2);
                $table->dateTime('paid_at');
                $table->unsignedBigInteger('paid_by')->nullable()->comment('Tenant who paid');
                $table->unsignedBigInteger('received_by')->nullable()->comment('Landlord/admin who received');
                $table->enum('payment_method', [
                    'cash', 'bank_transfer', 'card', 'digital_wallet', 'other'
                ]);
                $table->string('reference_number', 100)->nullable();
                $table->enum('status', [
                    'pending', 'completed', 'failed', 'refunded', 'partially_refunded'
                ])->default('completed');
                $table->text('notes')->nullable();

                // Indexes
                $table->index('paid_at', 'payment_date');
                $table->index('status', 'payment_status');

                // Foreign keys
                $table->foreign('payment_id')->references('id')->on('property_payments')->onDelete('cascade');
                $table->foreign('paid_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('received_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down(): void {
        Schema::dropIfExists('payment_transactions');
    }
};
