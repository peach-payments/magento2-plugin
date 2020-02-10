<?php
/**
 * Copyright (c) 2020 Peach Payments. All rights reserved. Developed by Francois Raubenheimer
 */

/**
 * Class \PeachPayments\Hosted\Model\ResourceModel\Web\Hooks
 */
namespace PeachPayments\Hosted\Model\ResourceModel\Web;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Hooks extends AbstractDb
{
    /**
     * {@inheritdoc}
     */
    public function _construct()
    {
        $this->_init('peachpayments_hosted_web_hooks', 'entity_id');
    }
}
