<?php

class Loewenstark_CategoryOrder_CategoryController extends Mage_Core_Controller_Front_Action
{
    public $_category;

    function indexAction()
    {
        if ($this->getCurrentCategory())
        {
            Mage::register('current_category', $this->getCurrentCategory());
            $this->loadLayout();
            $this->_initLayoutMessages('checkout/session');
            $this->_initLayoutMessages('catalog/session');
            $this->renderLayout();
        } else
        {
            $this->_forward('noRoute');
        }
    }

    public function getCurrentCategory()
    {
        if (!$this->_category)
        {
            $id = Mage::app()->getRequest()->getParam('category_id');
            if (!$id)
                return false;

            $category = Mage::getModel('catalog/category')->load($id);
            if (!$category || !$category->getId())
                return false;

            $this->_category = $category;
        }

        return $this->_category;
    }

    public function postAction()
    {
        $cart = $this->_getCart();
        try {

            $products_data = $this->getRequest()->getParam('categoryorder_products', array());
            $productIds = array();

            foreach ($products_data as $product_id => $product_qty)
            {
                if (!$product_qty)
                    continue;

                $productIds[] = $product_id;
            }

            if (!is_array($productIds))
            {
                $this->_goBack();
                return;
            }

            $productsWithError = array();

            foreach ($productIds as $productId)
            {
                //If we not load the product here it will be loaded here...: Mage_Checkout_Model_Cart::getProduct
                $product = Mage::getModel('catalog/product')
                        ->setStoreId(Mage::app()->getStore()->getId())
                        ->load($productId);

                if (!$product)
                {
                    $this->_goBack();
                    return;
                }
                $params = array(
                    'qty' => (int) $products_data[$product->getId()],
                );

                try {
                    $cart->addProduct($product, $params);
                    $productNames[] = $product->getName();
                } catch (Exception $e) {
                    $msg = Mage::helper('core')->escapeHtml($e->getMessage());

                    if (!array_key_exists($msg, $productsWithError))
                        $productsWithError[$msg] = array();

                    $productsWithError[$msg][] = $product->getName();
                }
            }

            $cart->save();
            $this->_getSession()->setCartWasUpdated(true);
            if (!$this->_getSession()->getNoCartRedirect(true))
            {
                //add success messages
                if ($productNames)
                {
                    $message = $this->__('%s was added to your shopping cart.', Mage::helper('core')->escapeHtml(implode(",", $productNames)));
                    $this->_getSession()->addSuccess($message);
                }

                //add errors if exists
                $this->addErrorsToSession($productsWithError);

                //redirect to category page
                $this->_goBack();
            }
        } catch (Mage_Core_Exception $e) {
            if ($this->_getSession()->getUseNotice(true))
            {
                $this->_getSession()->addNotice(Mage::helper('core')->escapeHtml($e->getMessage()));
            } else
            {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message)
                {
                    $this->_getSession()->addError(Mage::helper('core')->escapeHtml($message));
                }
            }

            //add errors if exists
            $this->addErrorsToSession($productsWithError);

            //redirect to category page
            $this->_goBack();
        } catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('Cannot add the item to shopping cart.'));
            Mage::logException($e);
            $this->_goBack();
        }
    }

    public function addErrorsToSession($productsWithError)
    {
        if ($productsWithError)
        {
            $error_message = '';
            foreach ($productsWithError as $msg => $names)
            {
                $error_message .= $msg . ' (' . implode(',', $names) . ')<br />';
            }
            $this->_getSession()->addNotice($error_message);
        }
    }

    protected function _getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }

    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    protected function _goBack()
    {
        $category_id = $this->getRequest()->getParam('category_id', 0);
        $this->_redirect('loewenstark_categoryorder/category/index', array('category_id' => $category_id));
        return $this;
    }

}

?>