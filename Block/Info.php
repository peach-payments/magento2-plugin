<?php
/**
 * Copyright (c) 2020 Peach Payments. All rights reserved. Developed by Francois Raubenheimer
 */

/**
 * Class \PeachPayments\Hosted\Block\Info
 */
namespace PeachPayments\Hosted\Block;


use Magento\Payment\Block\Info as CoreInfo;

/**
 * Class Info
 * @package PeachPayments\Hosted\Block
 */
class Info extends CoreInfo
{
    /**
     * @var string
     */
    protected $_template = 'Magento_Payment::info/default.phtml';

    /**
     * Render as PDF
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate('Magento_Payment::info/pdf/default.phtml');
        return $this->toHtml();
    }
}


