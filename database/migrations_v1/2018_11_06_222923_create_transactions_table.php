<?php

use Hyperf\Utils\Fluent as ColumnDefinition;
use Hyperf\DbConnection\Db;
use Hyperf\Database\Schema\Schema;
use Hyperf\Database\MySqlConnection;
use Hyperf\Database\Schema\Blueprint;
use Xtwoend\Wallet\Models\Transaction;
use Hyperf\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::create($this->table(), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->morphs('payable');
            $table->enum('type', ['deposit', 'withdraw'])->index();
            $table->decimal('amount', 64, 0);
            $table->boolean('confirmed');
            $this->json($table, 'meta')->nullable();
            $table->uuid('uuid')->unique();
            $table->timestamps();

            $table->index(['payable_type', 'payable_id', 'type'], 'payable_type_ind');
            $table->index(['payable_type', 'payable_id', 'confirmed'], 'payable_confirmed_ind');
            $table->index(['payable_type', 'payable_id', 'type', 'confirmed'], 'payable_type_confirmed_ind');
        });
    }

    /**
     * @param Blueprint $table
     * @param string $column
     * @return ColumnDefinition
     */
    public function json(Blueprint $table, string $column): ColumnDefinition
    {
        $conn = Db::connection();
        if ($conn instanceof MySqlConnection || $conn instanceof PostgresConnection) {
            $pdo = $conn->getPdo();
            try {
                $sql = 'SELECT JSON_EXTRACT(\'[10, 20, [30, 40]]\', \'$[1]\');';
                $prepare = $pdo->prepare($sql);
                $prepare->fetch();
            } catch (\Throwable $throwable) {
                return $table->text($column);
            }
        }

        return $table->json($column);
    }

    /**
     * @return string
     */
    protected function table(): string
    {
        return (new Transaction())->getTable();
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::drop($this->table());
    }
}
