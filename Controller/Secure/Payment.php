<?php
/**
 * Copyright (c) 2020 Peach Payments. All rights reserved. Developed by Francois Raubenheimer
 */

namespace PeachPayments\Hosted\Controller\Secure;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\LayoutFactory;
use PeachPayments\Hosted\Helper\Data as HelperData;
use PeachPayments\Hosted\Model\Web\Hooks;
use PeachPayments\Hosted\Model\Web\HooksFactory;

class Payment extends AbstractSecure implements CsrfAwareActionInterface
{
    /**
     * @var HelperData
     */
    protected $helperData;

    public function __construct(Context $context,
        HooksFactory $webHooksFactory,
        LayoutFactory $viewLayoutFactory,
        HelperData $helperData)
    {
        $this->helperData = $helperData;

        parent::__construct($context, $webHooksFactory, $viewLayoutFactory);
    }

    /**
     * Payment action
     */
    public function execute()
    {
        /** @var string $incrementId */
        $incrementId = $this->getRequest()
            ->getParam('merchantTransactionId');
        /** @var Hooks $webHookM */
        $webHookM = $this->getWebHook()
            ->loadByIncrementId($incrementId);
        /** @var HelperData $helper */
        $helper = $this->helperData;

        if ($webHookM->getId()) {
            if (!strlen($webHookM->getData('checkout_id'))) {
                $response = $helper->processStatus($incrementId);
                if (!empty($response)) {
                    $insert = $this->getWebHookData($response);
                    foreach ($insert as $key => $item) {
                        $webHookM->setData($key, $item);
                    }
                    // save full request
                    $webHookM->setData('request', serialize($insert));
                    $webHookM->save();
                    $helper->processOrder($webHookM);
                }
            }

            if ($this->isSuccessful($webHookM->getData('result_code'))) {
                $this->_redirect('checkout/onepage/success');
                return;
            }

            if($this->isWaiting($webHookM->getData('result_code'))) {
                $this->_redirect('*/*/wait', ['real_order_id' => $incrementId]);
                return;
            }
        }

        $helper->restoreQuote();
        $this->_redirect('checkout/cart');
        return;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
