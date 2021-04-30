<?php

declare(strict_types=1);

namespace Xtwoend\Wallet\Services;

use Xtwoend\Wallet\Interfaces\Product;
use Xtwoend\Wallet\Objects\Cart;

/** @deprecated */
final class MetaService
{
    public function getMeta(Cart $cart, Product $product): ?array
    {
        $metaCart = $cart->getMeta();
        $metaProduct = $product->getMetaProduct();

        if ($metaProduct !== null) {
            return array_merge($metaCart, $metaProduct);
        }

        if (count($metaCart) > 0) {
            return $metaCart;
        }

        return null;
    }
}
