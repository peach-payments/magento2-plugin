<?php
/**
 * Copyright (c) 2020 Peach Payments. All rights reserved. Developed by Francois Raubenheimer
 */

/**
 * Class \PeachPayments\Hosted\Model\ResourceModel\Web\Hooks\Collection
 */
namespace PeachPayments\Hosted\Model\ResourceModel\Web\Hooks;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('PeachPayments\Hosted\Model\Web\Hooks', 'PeachPayments\Hosted\Model\ResourceModel\Web\Hooks');
    }
}
