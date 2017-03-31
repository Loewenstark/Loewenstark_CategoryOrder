<?php

class Loewenstark_CategoryOrder_Block_Table extends Mage_Catalog_Block_Product_Abstract
{
    public $_category;
    public $_products;
    public $_cartItemsQty;

    public function getCurrentCategory()
    {
        return Mage::registry('current_category');
    }

    public function getProductCollection()
    {
        if (is_null($this->_products))
        {
            $attrs = Mage::getSingleton('catalog/config')->getProductAttributes();
            $attrs[] = 'sku';

            $collection = Mage::getModel('catalog/product')->getCollection()
                    ->addAttributeToFilter('status', 1)
                    ->addAttributeToFilter('visibility', 4)
                    ->addCategoryFilter($this->getCurrentCategory())
                    ->addAttributeToSelect($attrs)
                    //->setPageSize(100)
                    //->setCurPage(1)
                    ->addStoreFilter()
                    ->addTaxPercents()
                    ->addUrlRewrite(0);

            $collection = $this->addInCartQtyToCollection($collection);

            $this->_products = $collection;
        }

        return $this->_products;
    }

    public function addInCartQtyToCollection($collection)
    {
        $cart_qty = $this->getCartItemsQty();

        foreach ($collection as $product)
        {
            $qty = 0;
            if (array_key_exists($product->getId(), $cart_qty))
                $qty = $cart_qty[$product->getId()];
            
            $product->setCartQty($qty);
        }

        return $collection;
    }

    public function getCartItemsQty()
    {
        if (!$this->_cartItemsQty)
        {
            $cart = Mage::getModel('checkout/cart')->getQuote();
            foreach ($cart->getAllItems() as $item)
            {
                $this->_cartItemsQty[$item->getProductId()] = $item->getQty();
            }
        }

        return $this->_cartItemsQty;
    }

    public function getFormAction()
    {
        return Mage::getUrl('loewenstark_categoryorder/category/post', array('category_id' => $this->getCurrentCategory()->getId()));
    }

}
