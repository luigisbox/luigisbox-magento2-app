<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Luigisbox\Integration\Model\Resolver\Product;

/**
 * Requirements for GraphQL
 */
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;

/**
 * Requirements to handle custom attributes (these classes/interfaces are present in older Magento versions)
 */
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\FilterProductCustomAttribute;
use Magento\Catalog\Model\Product;
use Magento\CatalogGraphQl\Model\ProductDataProvider;
use Magento\Eav\Api\Data\AttributeInterface;

/**
 *
 * Format a product's custom attribute information to conform to GraphQL schema representation.
 * This class returns product's custom attributes as JSON string, even if some of them are null.
 * Only works in Magento v. 2.4.7. In older versions the response is always 'null' (as string).
 */
class ProductCustomAttributesLuigi implements ResolverInterface
{
    /**
     * These attributes are used in Magento v. 2.4.7, because these class and interface only exists from that version
     */
    private ?\Magento\EavGraphQl\Model\Output\Value\GetAttributeValueInterface $getAttributeValue = null;
    private ?\Magento\EavGraphQl\Model\Resolver\GetFilteredAttributes $getFilteredAttributes = null;

    /**
     * @var ProductDataProvider
     */
    private ProductDataProvider $productDataProvider;

    /**
     * @var FilterProductCustomAttribute
     */
    private FilterProductCustomAttribute $filterCustomAttribute;

    /**
     * @param ProductDataProvider $productDataProvider
     * @param FilterProductCustomAttribute $filterCustomAttribute
     */
    public function __construct(
        ProductDataProvider $productDataProvider,
        FilterProductCustomAttribute $filterCustomAttribute
    ) {
        $this->productDataProvider = $productDataProvider;
        $this->filterCustomAttribute = $filterCustomAttribute;

        // Check if Magento version has the necessary classes/interfaces
        if (interface_exists(\Magento\EavGraphQl\Model\Output\Value\GetAttributeValueInterface::class)) {
            $this->getAttributeValue = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\EavGraphQl\Model\Output\Value\GetAttributeValueInterface::class);
        }

        if (class_exists(\Magento\EavGraphQl\Model\Resolver\GetFilteredAttributes::class)) {
            $this->getFilteredAttributes = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\EavGraphQl\Model\Resolver\GetFilteredAttributes::class);
        }
    }

    /**
     * @inheritdoc
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @throws \Exception
     * @return array
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        // If the classes exist, we can compute product's custom attributes
        if ($this->getAttributeValue && $this->getFilteredAttributes) {
            $productCustomAttributes = $this->getFilteredAttributes->execute(
                [],
                ProductAttributeInterface::ENTITY_TYPE_CODE
            );

            $attributeCodes = array_map(
                function (AttributeInterface $customAttribute) {
                    return $customAttribute->getAttributeCode();
                },
                $productCustomAttributes['items']
            );

            $filteredAttributeCodes = $this->filterCustomAttribute->execute(array_flip($attributeCodes));

            /** @var Product $product */
            $product = $value['model'];
            $productData = $this->productDataProvider->getProductDataById((int)$product->getId());

            $customAttributes = [];
            foreach ($filteredAttributeCodes as $attributeCode => $value) {
                if (!array_key_exists($attributeCode, $productData)) {
                    continue;
                }
                $attributeValue = $productData[$attributeCode];
                if (is_array($attributeValue)) {
                    $attributeValue = implode(',', $attributeValue);
                }
                $customAttributes[] = [
                    'attribute_code' => $attributeCode,
                    'value' => $attributeValue
                ];
            }

            return json_encode([
                'items' => array_map(
                    function (array $customAttribute) {
                        if (!is_null($customAttribute['value'])) {
                            return $this->getAttributeValue->execute(
                                ProductAttributeInterface::ENTITY_TYPE_CODE,
                                $customAttribute['attribute_code'],
                                $customAttribute['value']
                            );
                        };
                    },
                    $customAttributes
                ),
                'errors' => $productCustomAttributes['errors']
            ]);
        }

        // Fallback for older Magento versions
        return "null";
    }
}
