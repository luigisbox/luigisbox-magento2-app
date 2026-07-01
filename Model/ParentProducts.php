<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Luigisbox\Integration\Model;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Bundle\Model\ResourceModel\Selection;

class ParentProducts implements \Luigisbox\Integration\Api\ParentProductsInterface
{
    protected $request;

    /**
     * @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable
     */
    private $configurable;

    /**
     * @var \Magento\GroupedProduct\Model\Product\Type\Grouped
     */
    private $grouped;

    /**
     * @var \Magento\Bundle\Model\ResourceModel\Selection
     */
    private $bundle;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    public function __construct(
        \Magento\Framework\Webapi\Rest\Request $request,
        Configurable $configurable,
        Grouped $grouped,
        Selection $bundle,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->request = $request;
        $this->configurable = $configurable;
        $this->grouped = $grouped;
        $this->bundle = $bundle;
        $this->productRepository = $productRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getParentSkusByChildIds()
    {
        $body = $this->request->getBodyParams();
        $childIds = $body["child_ids"];
        $requestedParentTypes = $body["types"];
        $parentsSkus = [];

        foreach ($childIds as $childId) {
            if (in_array("configurable", $requestedParentTypes)) {
                $parentIdsConfigurable = $this->configurable->getParentIdsByChild($childId);
            } else {
                $parentIdsConfigurable = [];
            }

            if (in_array("bundle", $requestedParentTypes)) {
                $parentIdsBundle = $this->bundle->getParentIdsByChild($childId);
            } else {
                $parentIdsBundle = [];
            }

            if (in_array("grouped", $requestedParentTypes)) {
                $parentIdsGrouped = $this->grouped->getParentIdsByChild($childId);
            } else {
                $parentIdsGrouped = [];
            }

            $parentIds = array_merge($parentIdsConfigurable, $parentIdsGrouped, $parentIdsBundle);
            $currentParentsSkus = [];
            foreach ($parentIds as $parentId) {
                $parentProduct = $this->productRepository->getById($parentId);
                $parentVisibility = $parentProduct->getVisibility();
                if (!is_null($parentVisibility) && $parentVisibility > 1) {
                    $currentParentsSkus[] = $parentProduct->getSku();
                }
            }
            $parentsSkus[$childId] = $currentParentsSkus;
        }

        return json_encode($parentsSkus);
    }
}
