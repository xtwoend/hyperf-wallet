<?php

namespace Xtwoend\Wallet\Test\Objects;

class Operation extends \Xtwoend\Wallet\Objects\Operation
{
    /**
     * @return array
     */
    public function toArray(): array
    {
        return \array_merge(parent::toArray(), [
            'bank_method' => $this->meta['bank_method'] ?? null,
        ]);
    }
}
