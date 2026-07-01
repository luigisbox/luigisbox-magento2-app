<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Luigisbox\Integration\Model\Resolver\Product;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Query\ResolverInterface;

use Magento\CatalogInventory\Api\StockRegistryInterface;

class ProductQuantityResolver implements ResolverInterface
{
    protected $stockRegistry;

    public function __construct(
        StockRegistryInterface $stockRegistry
    ) {
        $this->stockRegistry = $stockRegistry;
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        if (!isset($value['sku'])) {
            return null;
        }

        $sku = $value['sku'];
        $quantity = null;

        try {
            $stockItem = $this->stockRegistry->getStockItemBySku($sku);
            $quantity = $stockItem->getQty();

            return $quantity;
        } catch (\Exception $e) {
            return null;
        }
    }
}
