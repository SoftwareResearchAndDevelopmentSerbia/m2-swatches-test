<?php

namespace SoftwareResearchAndDevelopment\CoreRewrites\Model\Catalog;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\FilterProductCustomAttribute;
use Magento\Catalog\Model\Product\Attribute\Backend\Media\EntryConverterPool;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Api\AttributeValueFactory;

class Product extends \Magento\Catalog\Model\Product
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

    public function __construct(\Magento\Framework\Model\Context $context,
                                \Magento\Framework\Registry $registry,
                                \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
                                AttributeValueFactory $customAttributeFactory,
                                \Magento\Store\Model\StoreManagerInterface $storeManager,
                                \Magento\Catalog\Api\ProductAttributeRepositoryInterface $metadataService,
                                \Magento\Catalog\Model\Product\Url $url,
                                \Magento\Catalog\Model\Product\Link $productLink,
                                \Magento\Catalog\Model\Product\Configuration\Item\OptionFactory $itemOptionFactory,
                                \Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory $stockItemFactory,
                                \Magento\Catalog\Model\Product\OptionFactory $catalogProductOptionFactory,
                                \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
                                Status $catalogProductStatus,
                                \Magento\Catalog\Model\Product\Media\Config $catalogProductMediaConfig,
                                \Magento\Catalog\Model\Product\Type $catalogProductType,
                                \Magento\Framework\Module\Manager $moduleManager,
                                \Magento\Catalog\Helper\Product $catalogProduct,
                                \Magento\Catalog\Model\ResourceModel\Product $resource,
                                \Magento\Catalog\Model\ResourceModel\Product\Collection $resourceCollection,
                                \Magento\Framework\Data\CollectionFactory $collectionFactory,
                                \Magento\Framework\Filesystem $filesystem,
                                \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
                                \Magento\Catalog\Model\Indexer\Product\Flat\Processor $productFlatIndexerProcessor,
                                \Magento\Catalog\Model\Indexer\Product\Price\Processor $productPriceIndexerProcessor,
                                \Magento\Catalog\Model\Indexer\Product\Eav\Processor $productEavIndexerProcessor,
                                CategoryRepositoryInterface $categoryRepository,
                                \Magento\Catalog\Model\Product\Image\CacheFactory $imageCacheFactory,
                                \Magento\Catalog\Model\ProductLink\CollectionProvider $entityCollectionProvider,
                                \Magento\Catalog\Model\Product\LinkTypeProvider $linkTypeProvider,
                                \Magento\Catalog\Api\Data\ProductLinkInterfaceFactory $productLinkFactory,
                                \Magento\Catalog\Api\Data\ProductLinkExtensionFactory $productLinkExtensionFactory,
                                EntryConverterPool $mediaGalleryEntryConverterPool,
                                \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
                                \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $joinProcessor,
                                \Magento\Catalog\Model\ResourceModel\Product $productResourceModel,
                                \Magento\Catalog\Model\ProductFactory $productFactory,
                                \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
                                array $data = [],
                                \Magento\Eav\Model\Config $config = null,
                                FilterProductCustomAttribute $filterCustomAttribute = null
    )
    {
        $this->productResourceModel = $productResourceModel;
        $this->productFactory = $productFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $storeManager,
            $metadataService,
            $url,
            $productLink,
            $itemOptionFactory,
            $stockItemFactory,
            $catalogProductOptionFactory,
            $catalogProductVisibility,
            $catalogProductStatus,
            $catalogProductMediaConfig,
            $catalogProductType,
            $moduleManager,
            $catalogProduct,
            $resource,
            $resourceCollection,
            $collectionFactory,
            $filesystem,
            $indexerRegistry,
            $productFlatIndexerProcessor,
            $productPriceIndexerProcessor,
            $productEavIndexerProcessor,
            $categoryRepository,
            $imageCacheFactory,
            $entityCollectionProvider,
            $linkTypeProvider,
            $productLinkFactory,
            $productLinkExtensionFactory,
            $mediaGalleryEntryConverterPool,
            $dataObjectHelper,
            $joinProcessor,
            $data,
            $config,
            $filterCustomAttribute
        );
    }

    /**
     * Retrieve Product URL
     *
     * @param bool $useSid
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getProductUrl($useSid = null): string
    {
        if ($this->getIsSwatchColor()) {
            $swatch_color_piece_product_url = $this->getSwatchColorPieceProductUrl();
            if ($swatch_color_piece_product_url != '') {
                $url = "/".$swatch_color_piece_product_url.".html?".self::COLOR_ATTRIBUTE_CODE."=".$this->getSwatchColorPiece();
            } else {
                $url = $this->getUrlModel()->getProductUrl($this, $useSid);
            }
        } else {
            $url = $this->getUrlModel()->getProductUrl($this, $useSid);
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
    public function getName(): string
    {
        if ($this->getIsSwatchColor()) {
            $name = $this->getSwatchColorPieceProductName();

            if ($name == '') {
                $name = $this->_getData(self::NAME);
            }
        } else {
            $name = $this->_getData(self::NAME);
        }
        return $name;
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getIsSwatchColor(): bool
    {
        return (bool)$this->_resource->getAttributeRawValue($this->getId(), self::IS_SWATCH_COLOR_ATTRIBUTE, $this->getStore()->getId());
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getSwatchColorPiece(): string
    {
        $swatch_color_piece = '';
        $children_Ids = $this->getTypeInstance()->getChildrenIds($this->getId());
        $productCollectionFactory = $this->productCollectionFactory->create()->addIdFilter($children_Ids);
        $productCollectionFactory->addAttributeToSelect(self::COLOR_ATTRIBUTE_CODE);
        $productCollectionFactory->setPageSize(count($children_Ids))->setCurPage(1);
        $productCollectionFactory->groupByAttribute(self::COLOR_ATTRIBUTE_CODE);
        $product = $productCollectionFactory->getFirstItem();
        if ($product instanceof \Magento\Catalog\Model\Product) {
            $swatch_color_piece = lcfirst($this->_resource->getAttribute(self::COLOR_ATTRIBUTE_CODE)->getFrontend()->getValue($product));
        }
        return $swatch_color_piece;
    }

    /**
     * @return int
     */
    private function getMergedSwatchesProductId(): int
    {
        $value = $this->_resource->getAttributeRawValue($this->getId(), self::MERGED_SWATCHES_PRODUCT_ID_ATTRIBUTE, $this->getStore()->getId());
        if (is_null($value) || (is_array($value) && empty($value))) {
            $value = 0;
        }
        return $value;
    }

    /**
     * @return \Magento\Catalog\Model\Product|null
     */
    private function getMergedSwatchProduct()
    {
        $product = null;
        $merged_swatches_product_id = $this->getMergedSwatchesProductId();
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
    private function getSwatchColorPieceProductName(): string
    {
        $name = '';
        $product = $this->getMergedSwatchProduct();
        if ($product instanceof \Magento\Catalog\Model\Product) {
            $name = $product->getName();
        }
        return $name;
    }

    /**
     * @return string
     */
    private function getSwatchColorPieceProductUrl(): string
    {
        $url = '';
        $product = $this->getMergedSwatchProduct();
        if ($product instanceof \Magento\Catalog\Model\Product) {
            $url = $product->getUrlKey();
        }

        return $url;
    }
}
