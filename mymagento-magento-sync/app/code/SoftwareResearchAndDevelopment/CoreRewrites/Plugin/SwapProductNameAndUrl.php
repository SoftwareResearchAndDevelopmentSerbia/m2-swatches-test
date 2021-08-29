<?php

namespace SoftwareResearchAndDevelopment\CoreRewrites\Plugin;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\FilterProductCustomAttribute;
use Magento\Catalog\Model\Product\Attribute\Backend\Media\EntryConverterPool;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Api\AttributeValueFactory;

class SwapProductNameAndUrl
{
    /**
     *
     */
    private const COLOR_ATTRIBUTE_CODE = 'color';

    /**
     * is_swatch_color attribute key
     */
    private const IS_SWATCH_COLOR_ATTRIBUTE = 'is_swatch_color';

    /**
     * merged_swatches_product_id attribute key
     */
    private const MERGED_SWATCHES_PRODUCT_ID_ATTRIBUTE = 'merged_swatches_product_id';

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product $productResourceModel
     */
    protected $productResourceModel;

    /**
     * @var \Magento\Catalog\Model\ProductFactory $productFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     */
    protected $productCollectionFactory;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product $productResourceModel,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
    ) {
        $this->productResourceModel = $productResourceModel;
        $this->productFactory = $productFactory;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * Retrieve Product URL
     *
     * @param bool $useSid
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundGetProductUrl(\Magento\Catalog\Model\Product $subject, callable $proceed, $useSid = null)
    {
        if ($this->getIsSwatchColor($subject)) {
            $swatch_color_piece_product_url = $this->getSwatchColorPieceProductUrl($subject);
            if ($swatch_color_piece_product_url != '') {
                $url = "/".$swatch_color_piece_product_url.".html?".self::COLOR_ATTRIBUTE_CODE."=".$this->getSwatchColorPiece($subject);
            } else {
                $url = $subject->getUrlModel()->getProductUrl($subject, $useSid);
            }
        } else {
            $url = $proceed($useSid);
        }
        return $url;
    }

    /**
     * Get product name
     *
     * @return string
     * @codeCoverageIgnoreStart
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundGetName(\Magento\Catalog\Model\Product $subject, callable $proceed)
    {
        if ($this->getIsSwatchColor($subject)) {
            $name = $this->getSwatchColorPieceProductName($subject);
            if ($name == '') {
                $name = $proceed();
            }
        } else {
            $name = $proceed();
        }
        return $name;
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getIsSwatchColor(\Magento\Catalog\Model\Product $product): bool
    {
        return (bool)$this->productResourceModel->getAttributeRawValue(
            $product->getId(),
            self::IS_SWATCH_COLOR_ATTRIBUTE,
            $product->getStore()->getId()
        );
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getSwatchColorPiece(\Magento\Catalog\Model\Product $mainProduct): string
    {
        $swatch_color_piece = '';
        $children_Ids = $mainProduct->getTypeInstance()->getChildrenIds($mainProduct->getId());
        $productCollectionFactory = $this->productCollectionFactory->create()->addIdFilter($children_Ids);
        $productCollectionFactory->addAttributeToSelect(self::COLOR_ATTRIBUTE_CODE);
        $productCollectionFactory->setPageSize(count($children_Ids))->setCurPage(1);
        $productCollectionFactory->groupByAttribute(self::COLOR_ATTRIBUTE_CODE);
        $product = $productCollectionFactory->getFirstItem();
        if ($product instanceof \Magento\Catalog\Model\Product) {
            $swatch_color_piece = lcfirst($this->productResourceModel->getAttribute(
                self::COLOR_ATTRIBUTE_CODE)->getFrontend()->getValue($product)
            );
        }
        return $swatch_color_piece;
    }

    /**
     * @return int
     */
    private function getMergedSwatchesProductId(\Magento\Catalog\Model\Product $product): int
    {
        $value = $this->productResourceModel->getAttributeRawValue(
            $product->getId(),
            self::MERGED_SWATCHES_PRODUCT_ID_ATTRIBUTE,
            $product->getStore()->getId()
        );
        if (is_null($value)) {
            $value = 0;
        }
        return $value;
    }

    /**
     * @return \Magento\Catalog\Model\Product|null
     */
    private function getMergedSwatchProduct(\Magento\Catalog\Model\Product $mainProduct)
    {
        $product = null;
        $merged_swatches_product_id = $this->getMergedSwatchesProductId($mainProduct);
        if ($merged_swatches_product_id) {
            /**
             * @var \Magento\Catalog\Model\Product $product
             */
            $product = $this->productFactory->create();
            $this->productResourceModel->load($product, $merged_swatches_product_id);
        }
        return $product;
    }

    /**
     * @return string
     */
    private function getSwatchColorPieceProductName(\Magento\Catalog\Model\Product $mainProduct): string
    {
        $name = '';
        $product = $this->getMergedSwatchProduct($mainProduct);
        if ($product instanceof \Magento\Catalog\Model\Product) {
            $name = $product->getName();
        }
        return $name;
    }

    /**
     * @return string
     */
    private function getSwatchColorPieceProductUrl(\Magento\Catalog\Model\Product $mainProduct): string
    {
        $url = '';
        $product = $this->getMergedSwatchProduct($mainProduct);
        if ($product instanceof \Magento\Catalog\Model\Product) {
            $url = $product->getUrlKey();
        }
        return $url;
    }
}
