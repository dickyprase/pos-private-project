<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->foreignId('open_cashier_id')->nullable()->after('cashier_id')->constrained('users')->restrictOnDelete();
        });
        DB::table('shifts')->where('status', 'OPEN')->update(['open_cashier_id' => DB::raw('cashier_id')]);
        Schema::table('shifts', function (Blueprint $table) {
            $table->unique('open_cashier_id');
        });
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('scope');
            $table->date('sequence_date');
            $table->unsignedBigInteger('sequence')->default(0);
            $table->unique(['scope', 'sequence_date']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->char('checkout_payload_hash', 64)->nullable()->after('submission_token');
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->uuid('idempotency_key')->nullable()->unique()->after('order_id');
        });
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('source_key')->nullable()->unique()->after('type');
            $table->decimal('balance_after', 16, 4)->nullable()->after('quantity');
        });
        Schema::table('refunds', function (Blueprint $table) {
            $table->uuid('idempotency_key')->nullable()->unique()->after('payment_id');
            $table->boolean('restock')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
        Schema::table('refunds', fn (Blueprint $table) => $table->dropColumn(['idempotency_key', 'restock']));
        Schema::table('stock_movements', fn (Blueprint $table) => $table->dropColumn(['source_key', 'balance_after']));
        Schema::table('payments', fn (Blueprint $table) => $table->dropColumn('idempotency_key'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('checkout_payload_hash'));
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropUnique(['open_cashier_id']);
            $table->dropConstrainedForeignId('open_cashier_id');
        });
    }
};
