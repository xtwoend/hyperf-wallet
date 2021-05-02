<?php

use Hyperf\Database\Schema\Schema;
use Xtwoend\Wallet\Models\Transfer;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateTransfersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->morphs('from');
            $table->morphs('to');

            $enums = [
                Transfer::STATUS_EXCHANGE,
                Transfer::STATUS_TRANSFER,
                Transfer::STATUS_PAID,
                Transfer::STATUS_REFUND,
                Transfer::STATUS_GIFT,
            ];
            $table->enum('status', $enums)->default(Transfer::STATUS_PAID);
            $table->enum('status_last', $enums)->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('deposit_id');
            $table->unsignedBigInteger('withdraw_id');
            $table->decimal('discount', 64, 0)->default(0);
            $table->decimal('fee', 64, 0)->default(0);
            $table->uuid('uuid')->unique();
            $table->timestamps();

            $table->foreign('deposit_id')
                ->references('id')
                ->on('transactions')
                ->onDelete('cascade');

            $table->foreign('withdraw_id')
                ->references('id')
                ->on('transactions')
                ->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
}
