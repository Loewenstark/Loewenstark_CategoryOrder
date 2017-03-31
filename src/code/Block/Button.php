<?php

class Loewenstark_CategoryOrder_Block_Button extends Mage_Core_Block_Template
{
    public function getUrl()
    {
        $category = $this->getCurrentCategory();

        if (!$category || !$category->getId())
            return false;

        return Mage::getUrl('loewenstark_categoryorder/category/index', array('category_id' => $category->getId()));
    }

    public function getCurrentCategory()
    {
        return Mage::registry('current_category');
    }

}
