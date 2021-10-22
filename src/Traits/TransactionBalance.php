<?php 

namespace Xtwoend\Wallet\Traits;

use Hyperf\Database\Model\Events\Saving;


trait TransactionBalance
{
    public function saving(Saving $event)
    {
        $model = $event->getModel();
        if ($model) {
            $model->balance = $model->getBalance($model);
        }
    }
    
    public function getBalance($model)
    {
        $wallet = $model->wallet;
        $balance = $wallet->balance + $model->amount;

        return $balance;
    }
}