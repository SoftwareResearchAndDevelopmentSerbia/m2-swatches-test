<?php

namespace SoftwareResearchAndDevelopment\CoreRewrites\Model\Catalog;

class Product extends \Magento\Catalog\Model\Product
{
    /**
     * Configurable product ids which urls should be changed
     */
    private const PRODUCT_IDS = [
        2056 => 'blue',
        2060 => 'yellow',
        2061 => 'red'
    ];

    /**
     * Url value which should be swapped with original product url
     */
    private const SWAP_URL = '/stellar-solar-jacket-all.html';

    /**
     * Product name value which should be swapped with original product name
     */
    private const SWAP_NAME = 'Stellar Solar Jacket All';

    /**
     * Retrieve Product URL
     *
     * @param  bool $useSid
     * @return string
     */
    public function getProductUrl($useSid = null)
    {
        if (in_array($this->getId(), array_keys(self::PRODUCT_IDS))) {
            $url = self::SWAP_URL."?color=".self::PRODUCT_IDS[$this->getId()];
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
     */
    public function getName()
    {
        if (in_array($this->getId(), self::PRODUCT_IDS)) {
            $name = self::SWAP_NAME;
        } else {
            $name = $this->_getData(self::NAME);
        }
        return $name;
    }
}
