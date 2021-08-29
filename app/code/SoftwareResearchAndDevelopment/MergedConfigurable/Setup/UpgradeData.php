<?php
namespace SoftwareResearchAndDevelopment\MergedConfigurable\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\Catalog\Api\Data\ProuctInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $_resourceModel;
    /**
     * @var \Magento\InventoryApi\Api\SourceItemsSaveInterface
     */
    protected $sourceItemsSaveInterface;
    /**
     * @var \Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory
     */
    protected $sourceItem;
    /**
     * @var \Magento\Eav\Api\AttributeRepositoryInterface
     */
    protected $attributeRepository;
    /**
     * @var \Magento\ConfigurableProduct\Helper\Product\Options\Factory
     */
    protected $_optionsFactory;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \Magento\Catalog\Setup\CategorySetup
     */
    protected $categorySetup;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /** @var \Magento\Framework\App\State **/
    protected $state;

    /** @var \Magento\Framework\Filesystem **/
    protected $_filesystem;

    /** @var \Magento\Catalog\Api\Data\ProductInterface */
    protected $productInterface;

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\ResourceModel\Product $resourceModel,
        \Magento\InventoryApi\Api\SourceItemsSaveInterface $sourceItemsSaveInterface,
        \Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory $sourceItem,
        \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository,
        \Magento\ConfigurableProduct\Helper\Product\Options\Factory $optionsFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Setup\CategorySetup $categorySetup,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\State $state,
        \Magento\Framework\Filesystem $_filesystem,
        \Magento\Catalog\Api\Data\ProductInterface $productInterface
    )
    {
        $this->_productFactory = $productFactory;
        $this->_resourceModel = $resourceModel;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
        $this->sourceItem = $sourceItem;
        $this->attributeRepository = $attributeRepository;
        $this->_optionsFactory = $optionsFactory;
        $this->productRepository = $productRepository;
        $this->categorySetup = $categorySetup;
        $this->storeManager = $storeManager;
        $this->state = $state;
        $this->_filesystem = $_filesystem;
        $this->productInterface = $productInterface;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '0.2.0') < 0) {
            try {
                $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML); // or \Magento\Framework\App\Area::AREA_ADMINHTML, depending on your needs
                $product = $this->_productFactory->create();
                $colorAttr = $this->attributeRepository->get(Product::ENTITY, 'color');
                $sizeAttr = $this->attributeRepository->get(Product::ENTITY, 'size');
                $isSwatchColorAttr = $this->attributeRepository->get(Product::ENTITY, 'is_swatch_color');
                $mergedSwatchesProductIdAttr = $this->attributeRepository->get(Product::ENTITY, 'merged_swatches_product_id');
                $topSetId = $this->categorySetup->getAttributeSetId(Product::ENTITY, 'Top');

                $colorOptions = $colorAttr->getOptions();
                $sizeOptions = $sizeAttr->getOptions();

                array_shift($colorOptions);
                array_shift($sizeOptions);

                $product_images = [
                    'Red'       => 'wj01-red_main.jpg',
                    'Blue'      => 'wj01-blue_main.jpg',
                    'Yellow'    => 'wj01-yellow_main.jpg'
                ];
                $approvedColorOptions   = ['Red', 'Blue', 'Yellow'];
                $approvedSizeOptions    = ['S', 'M', 'L'];

                $products_categories = [2,8,34,23]; // Default Category, New Luma Yoga Collection, Erin Recommends, Jackets

                $name_part = "Test Product";
                $sku_part = "TEST";
                $simple_products = [];
                $sourceItems = [];
                $configurable_swatch_color_product_ids = [];

                //Create Simple product
                foreach ($colorOptions as $colorOption) {
                    if (in_array($colorOption->getLabel(), $approvedColorOptions)) {
                        foreach ($sizeOptions as $sizeIndex => $sizeOption) {
                            if (in_array($sizeOption->getLabel(), $approvedSizeOptions)) {
                                /**
                                 * Create Simple Product
                                 */
                                /** @var Product $product */
                                $product->unsetData();
                                $product->setTypeId(Type::TYPE_SIMPLE)
                                    ->setAttributeSetId($topSetId)
                                    ->setWebsiteIds([$this->storeManager->getDefaultStoreView()->getWebsiteId()])
                                    ->setName($name_part .'-'. $sizeOption->getLabel() .'-'. $colorOption->getLabel())
                                    ->setSku($sku_part .'-'. $sizeOption->getLabel() .'-'. $colorOption->getLabel())
                                    ->setPrice(35)
                                    ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
                                    ->setStatus(Status::STATUS_ENABLED)
                                    // Assign category for product
                                    ->setCategoryIds($products_categories); // Default Category, New Luma Yoga Collection, Erin Recommends, Jackets
                                $product->setCustomAttribute(
                                    $colorAttr->getAttributeCode(),
                                    $colorOption->getValue()
                                );
                                $product->setCustomAttribute(
                                    $sizeAttr->getAttributeCode(),
                                    $sizeOption->getValue()
                                );
                                $media_path = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
                                $image = $product_images[$colorOption->getLabel()];
                                $image_path = $media_path .'catalog/product/w/j/'.$image; // path of the image
                                $product->addImageToMediaGallery($image_path, array('image', 'small_image', 'thumbnail'), false, false);

                                $newProduct = $this->productRepository->save($product);
                                $new_pid = $newProduct->getId();

                                $simple_products[$colorOption->getLabel()][] = (int)$new_pid;

                                // Update Stock Data
                                $sourceItem = $this->sourceItem->create();
                                $sourceItem->setSourceCode('default');
                                $sourceItem->setQuantity(100);
                                $sourceItem->setSku($sku_part .'-'. $sizeOption->getLabel() .'-'. $colorOption->getLabel());
                                $sourceItem->setStatus(SourceItemInterface::STATUS_IN_STOCK);
                                array_push($sourceItems, $sourceItem);
                            }
                        }
                        //Execute Update Stock Data
                        $this->sourceItemsSaveInterface->execute($sourceItems);

                        /**
                         * Create Configurable Color Swatch Piece Products
                         */
                        $configurable_product = $this->_productFactory->create();
                        $configurable_product->setSku($sku_part .'-'. $colorOption->getLabel()); // set sku
                        $configurable_product->setName($name_part .' - '. $colorOption->getLabel()); // set name
                        $configurable_product->setAttributeSetId($topSetId);
                        $configurable_product->setStatus(Status::STATUS_ENABLED);
                        $configurable_product->setTypeId('configurable');
                        $configurable_product->setPrice(0);
                        $configurable_product->setWebsiteIds([$this->storeManager->getDefaultStoreView()->getWebsiteId()]); // set website
                        $configurable_product->setVisibility(Visibility::VISIBILITY_BOTH);
                        $configurable_product->setCategoryIds($products_categories); // set category
                        $configurable_product->setStockData([
                                'use_config_manage_stock' => 1, //'Use config settings' checkbox
                                'manage_stock' => 0, //manage stock
                                'is_in_stock' => 1, //Stock Availability
                            ]
                        );
                        $configurable_product->setCustomAttribute(
                            $isSwatchColorAttr->getAttributeCode(),
                            1
                        );

                        $media_path = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
                        $image = $product_images[$colorOption->getLabel()];
                        $image_path = $media_path .'catalog/product/w/j/'.$image; // path of the image
                        $configurable_product->addImageToMediaGallery($image_path, array('image', 'small_image', 'thumbnail'), false, false);

                        // super attribute
                        $size_attr_id = $sizeAttr->getId();
                        $color_attr_id = $colorAttr->getId();

                        $configurable_product->getTypeInstance()->setUsedProductAttributeIds([$color_attr_id, $size_attr_id], $configurable_product); //attribute ID of attribute 'size_general' in my store
                        $configurableAttributesData = $configurable_product->getTypeInstance()->getConfigurableAttributesAsArray($configurable_product);
                        $configurable_product->setCanSaveConfigurableAttributes(true);
                        $configurable_product->setConfigurableAttributesData($configurableAttributesData);
                        $configurableProductsData = [];
                        $configurable_product->setConfigurableProductsData($configurableProductsData);
                        $newConfigurableProduct = $this->productRepository->save($configurable_product);

                        // assign simple product ids
                        $newConfigurableProduct->setAssociatedProductIds($simple_products[$colorOption->getLabel()]); // Setting Associated Products
                        $newConfigurableProduct->setCanSaveConfigurableAttributes(true);
                        $newConfigurableSwatchColorProduct = $this->productRepository->save($newConfigurableProduct);
                        array_push($configurable_swatch_color_product_ids, $newConfigurableSwatchColorProduct->getId());
                    }
                }

                /**
                 * Create Merged Configurable Product
                 */
                $simple_products_flatten = call_user_func_array('array_merge', $simple_products);

                $configurable_product = $this->_productFactory->create();
                $configurable_product->setSku($sku_part .'-'. 'All'); // set sku
                $configurable_product->setName($name_part .' - '. 'All'); // set name
                $configurable_product->setAttributeSetId($topSetId);
                $configurable_product->setStatus(Status::STATUS_ENABLED);
                $configurable_product->setTypeId('configurable');
                $configurable_product->setPrice(0);
                $configurable_product->setWebsiteIds([$this->storeManager->getDefaultStoreView()->getWebsiteId()]); // set website
                $configurable_product->setVisibility(Visibility::VISIBILITY_IN_CATALOG);
                $configurable_product->setCategoryIds([]); // set category
                $configurable_product->setStockData([
                        'use_config_manage_stock' => 1, //'Use config settings' checkbox
                        'manage_stock' => 0, //manage stock
                        'is_in_stock' => 1, //Stock Availability
                    ]
                );

                $media_path = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
                $image = $product_images['Blue'];
                $image_path = $media_path .'catalog/product/w/j/'.$image; // path of the image
                $configurable_product->addImageToMediaGallery($image_path, array('image', 'small_image', 'thumbnail'), false, false);

                // super attribute
                $size_attr_id = $sizeAttr->getId();
                $color_attr_id = $colorAttr->getId();

                $configurable_product->getTypeInstance()->setUsedProductAttributeIds([$color_attr_id, $size_attr_id], $configurable_product); //attribute ID of attribute 'size_general' in my store
                $configurableAttributesData = $configurable_product->getTypeInstance()->getConfigurableAttributesAsArray($configurable_product);
                $configurable_product->setCanSaveConfigurableAttributes(true);
                $configurable_product->setConfigurableAttributesData($configurableAttributesData);
                $configurableProductsData = [];
                $configurable_product->setConfigurableProductsData($configurableProductsData);
                $newConfigurableProduct = $this->productRepository->save($configurable_product);

                // assign simple product ids
                $newConfigurableProduct->setAssociatedProductIds($simple_products_flatten); // Setting Associated Products
                $newConfigurableProduct->setCanSaveConfigurableAttributes(true);
                $mergedConfigurableProduct = $this->productRepository->save($newConfigurableProduct);
                $merged_configurable_id = $mergedConfigurableProduct->getId();

                /**
                 * Update custom attributes Color Swatch Configurables
                 */
                foreach ($configurable_swatch_color_product_ids as $configurable_swatch_color_product_id) {
                    $configurableProduct = $this->productRepository->getById($configurable_swatch_color_product_id);
                    $configurableProduct->setCustomAttribute(
                        $mergedSwatchesProductIdAttr->getAttributeCode(),
                        $merged_configurable_id
                    );
                    $this->productRepository->save($configurableProduct);
                }
            } catch (\Exception $e) {}
        }

        $setup->endSetup();
    }
}
