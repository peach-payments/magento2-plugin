<?php
/**
 * Copyright (c) 2020 Peach Payments. All rights reserved. Developed by Francois Raubenheimer
 */

namespace PeachPayments\Hosted\Controller\Secure;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\LayoutFactory;
use PeachPayments\Hosted\Model\Web\HooksFactory;

class Wait extends AbstractSecure
{

    public function __construct(Context $context,
        HooksFactory $webHooksFactory,
        LayoutFactory $viewLayoutFactory)
    {


        parent::__construct($context, $webHooksFactory, $viewLayoutFactory);
    }

    /**
     * Wait action
     */
    public function execute()
    {
        $block = $this->viewLayoutFactory->create()
            ->createBlock(
                'PeachPayments\Hosted\Block\Wait',
                'wait',
                [
                    'id' => $this->getRequest()->getParam('merchantTransactionId')
                ]
            )->toHtml();

        $this->getResponse()
            ->setBody($block);
    }
}
