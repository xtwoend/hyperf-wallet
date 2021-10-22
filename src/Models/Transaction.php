<?php

namespace Xtwoend\Wallet\Models;

use function config;
use function array_merge;
use Hyperf\DbConnection\Model\Model;
use Xtwoend\Wallet\Interfaces\Wallet;
use Xtwoend\Wallet\Interfaces\Mathable;
use Xtwoend\Wallet\Services\WalletService;
use Hyperf\Database\Model\Relations\MorphTo;
use Xtwoend\Wallet\Traits\TransactionBalance;
use Hyperf\Database\Model\Relations\BelongsTo;
use Xtwoend\Wallet\Models\Wallet as WalletModel;

/**
 * Class Transaction.
 *
 * @property string $payable_type
 * @property int $payable_id
 * @property int $wallet_id
 * @property string $uuid
 * @property string $type
 * @property int|string $amount
 * @property float $amountFloat
 * @property bool $confirmed
 * @property array $meta
 * @property Wallet $payable
 * @property WalletModel $wallet
 */
class Transaction extends Model
{
    use TransactionBalance;

    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_WITHDRAW = 'withdraw';

    /**
     * @var array
     */
    protected $fillable = [
        'payable_type',
        'payable_id',
        'wallet_id',
        'uuid',
        'type',
        'amount',
        'balance',
        'confirmed',
        'meta',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'wallet_id' => 'int',
        'confirmed' => 'bool',
        'meta' => 'json',
    ];

    /**
     * {@inheritdoc}
     */
    public function getCasts(): array
    {
        return array_merge(
            parent::getCasts(),
            config('wallet.transaction.casts', [])
        );
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        if (! $this->table) {
            $this->table = config('wallet.transaction.table', 'transactions');
        }

        return parent::getTable();
    }

    /**
     * @return MorphTo
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(config('wallet.wallet.model', WalletModel::class));
    }

    /**
     * @return int|float
     */
    public function getAmountFloatAttribute()
    {
        $decimalPlaces = make(WalletService::class)
            ->decimalPlaces($this->wallet);

        return make(Mathable::class)
            ->div($this->amount, $decimalPlaces);
    }

    /**
     * @param int|float $amount
     *
     * @return void
     */
    public function setAmountFloatAttribute($amount): void
    {
        $math = make(Mathable::class);
        $decimalPlaces = make(WalletService::class)
            ->decimalPlaces($this->wallet);

        $this->amount = $math->round($math->mul($amount, $decimalPlaces));
    }
}
