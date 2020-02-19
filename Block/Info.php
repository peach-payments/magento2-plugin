<?php
/**
 * Copyright (c) 2020 Peach Payments. All rights reserved. Developed by Francois Raubenheimer
 */

/**
 * Class \PeachPayments\Hosted\Block\Info
 */
namespace PeachPayments\Hosted\Block;

use Magento\Framework\View\Element\Template\Context;

/**
 * Class Info
 * @package PeachPayments\Hosted\Block
 */
class Info extends \Magento\Payment\Block\Info
{

    /**
     * Info constructor.
     * @param Context $context
     * @param array $data
     */
    public function __construct(Context $context, array $data = []) {

        parent::__construct($context, $data);
    }

    /**
     * @var string
     */
    protected $_template = 'PeachPayments_Hosted::info/default.phtml';
}
