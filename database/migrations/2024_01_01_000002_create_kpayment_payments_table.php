<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpayment_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id')->nullable()->index();
            $table->string('track_id')->nullable()->index();
            $table->string('result')->nullable();
            $table->string('result_code')->nullable();
            $table->string('auth')->nullable();
            $table->string('ref')->nullable();
            $table->string('trans_id')->nullable();
            $table->string('post_date')->nullable();
            $table->string('udf1')->nullable();
            $table->string('udf2')->nullable();
            $table->string('udf3')->nullable();
            $table->string('udf4')->nullable();
            $table->string('udf5')->nullable();
            $table->decimal('amount', 15, 3)->nullable();
            $table->string('currency')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('response_data')->nullable();
            $table->text('request_data')->nullable();
            $table->timestamps();

            $table->index(['track_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpayment_payments');
    }
};
