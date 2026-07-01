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

class ProductWarehousesResolver implements ResolverInterface
{
    private ?\Magento\InventoryApi\Api\GetSourceItemsBySkuInterface $getSourceItemsBySku = null;

    public function __construct() {
        if (interface_exists(\Magento\InventoryApi\Api\GetSourceItemsBySkuInterface::class)) {
            $this->getSourceItemsBySku = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\InventoryApi\Api\GetSourceItemsBySkuInterface::class);
        }
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        if ($this->getSourceItemsBySku) {
            if (!isset($value['sku'])) {
                return [];
            }

            $sku = $value['sku'];
            $warehouses = [];
            try {
                $sourceItems = $this->getSourceItemsBySku->execute($sku);
                foreach ($sourceItems as $sourceItem) {
                    $warehouses[] = [
                        'source_code' => $sourceItem->getSourceCode(),
                        'quantity' => $sourceItem->getQuantity(),
                        'status' => $sourceItem->getStatus() ? 'IN_STOCK' : 'OUT_OF_STOCK',
                    ];
                }
                return $warehouses;
            } catch (\Exception $e) {
                return [];
            }
        }

        return [];
    }
}
