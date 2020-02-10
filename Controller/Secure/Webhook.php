<?php
/**
 * Copyright (c) 2020 Peach Payments. All rights reserved. Developed by Francois Raubenheimer
 */

namespace PeachPayments\Hosted\Controller\Secure;

use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\LayoutFactory;
use PeachPayments\Hosted\Helper\Data as HelperData;
use PeachPayments\Hosted\Model\Web\Hooks;
use PeachPayments\Hosted\Model\Web\HooksFactory;

class Webhook extends AbstractSecure implements CsrfAwareActionInterface
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
     * Webhook action
     * @throws Exception
     */
    public function execute()
    {
        /** @var array $data */
        $data = $this->getRequest()->getParams();

        // Tests to see if webhook is active
        if(!count($this->getRequest()->getPost())){
            $this->getResponse()->setHttpResponseCode(200);
            return;
        }

        /** @var HelperData $helper */
        $helper = $this->helperData;

        /** @var array $signed */
        $signed = $helper->signData($data, false);

        /** @var string $incrementId */
        $incrementId = $this->getRequest()
            ->getParam('merchantTransactionId');

        /** @var Hooks $webHookM */
        $webHookM = $this->getWebHook()
            ->loadByIncrementId($incrementId);

        if ($webHookM->getId() && $signed['signature'] === $data['signature']) {
            $insert = $this->getWebHookData($data);
            foreach ($insert as $key => $item) {
                $webHookM->setData($key, $item);
            }
            // save full request
            $webHookM->setData('request', serialize($insert));
            $webHookM->save();
            $helper->processOrder($webHookM);
            $this->getResponse()->setHttpResponseCode(200);
            return;
        }


        $this->getResponse()->setHttpResponseCode(500);
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
