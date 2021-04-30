<?php

use Hyperf\DbConnection\Db;
use Hyperf\Database\Schema\Schema;
use Xtwoend\Wallet\Models\Transfer;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class UpdateTransfersTable extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table($this->table(), function (Blueprint $table) {
            $table->boolean('refund')
                ->after('withdraw_id')
                ->default(0);

            $table->index(['from_type', 'from_id', 'to_type', 'to_id', 'refund'], 'from_to_refund_ind');
            $table->index(['from_type', 'from_id', 'refund'], 'from_refund_ind');
            $table->index(['to_type', 'to_id', 'refund'], 'to_refund_ind');
        });
    }

    /**
     * @return string
     */
    protected function table(): string
    {
        return (new Transfer())->getTable();
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table($this->table(), function (Blueprint $table) {
            // if (! (Db::connection() instanceof SQLiteConnection)) {
            //     $table->dropIndex('from_to_refund_ind');
            //     $table->dropIndex('from_refund_ind');
            //     $table->dropIndex('to_refund_ind');
            // }

            $table->dropColumn('refund');
        });
    }
}
