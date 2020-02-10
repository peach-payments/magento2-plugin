<?php
/**
 * Copyright (c) 2020 Peach Payments. All rights reserved. Developed by Francois Raubenheimer
 */

/**
 * Class \PeachPayments\Hosted\Block\Redirect
 */
namespace PeachPayments\Hosted\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use PeachPayments\Hosted\Helper\Data as HelperData;

class Wait extends Template
{
    /**
     * @var HelperData
     */
    protected $helperData;

    public function __construct(Context $context,
        HelperData $helperData,
        array $data = [])
    {
        $this->helperData = $helperData;

        parent::__construct($context, $data);
    }

    /**
     * Redirect template
     */
    protected function _construct()
    {
        $this->setTemplate('PeachPayments_Hosted::peachpayments_hosted/wait.phtml');
    }

    /**
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->helperData->getWaitingUrl();
    }
}
