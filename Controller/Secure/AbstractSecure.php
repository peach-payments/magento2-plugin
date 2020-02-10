<?php
/**
 * Copyright (c) 2020 Peach Payments. All rights reserved. Developed by Francois Raubenheimer
 */

/**
 * Class \PeachPayments\Hosted\Controller\Secure\AbstractSecure
 */
namespace PeachPayments\Hosted\Controller\Secure;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\LayoutFactory;
use PeachPayments\Hosted\Model\Web\HooksFactory;

abstract class AbstractSecure extends Action
{
    /**
     * @var HooksFactory
     */
    protected $webHooksFactory;

    /**
     * @var LayoutFactory
     */
    protected $viewLayoutFactory;

    public function __construct(Context $context,
        HooksFactory $webHooksFactory,
        LayoutFactory $viewLayoutFactory)
    {
        $this->webHooksFactory = $webHooksFactory;
        $this->viewLayoutFactory = $viewLayoutFactory;

        parent::__construct($context);
    }


    /**
     * @param array $whData
     *
     * @return array
     */
    protected function getWebHookData($whData = [])
    {
        $data = [];
        foreach ($whData as $key => $datum) {
            if ($key === 'id') {
                $data['peach_id'] = $datum;
            } else {
                $data[$this->snakeCase($key)] = $datum;
            }
        }

        return $data;
    }

    /**
     * @param string $str
     *
     * @return string
     */
    private function snakeCase($str)
    {
        return strtolower(
            preg_replace(
                ['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'],
                '$1_$2',
                str_replace('.', '_', $str)
            )
        );
    }


    /**
     * @param string $resultCode
     * @return bool
     */
    protected function isSuccessful($resultCode)
    {
        return $resultCode === '000.000.000' || $resultCode === '000.100.110' ? true : false;
    }

    /**
     * @param string $resultCode
     * @return bool
     */
    protected function isWaiting($resultCode)
    {
        return $resultCode === '000.200.000' || $resultCode === '000.200.100' ? true : false;
    }


    /**
     * @return \PeachPayments\Hosted\Model\Web\Hooks
     */
    public function getWebHook()
    {
        return $this->webHooksFactory->create();
    }

    /**
     * @return \Magento\Checkout\Model\Session
     */
    protected function getSession()
    {
        return ObjectManager::getInstance()->get('Magento\Checkout\Model\Session');
    }

    /**
     * @param string $orderId
     * @param string $orderIncrementId
     */
    protected function getInternalRedirect($orderId, $orderIncrementId)
    {

        $block = $this->viewLayoutFactory->create()
            ->createBlock(
                'PeachPayments\Hosted\Block\Redirect',
                'redirect',
                ['order_id' => $orderId, 'order_increment_id' => $orderIncrementId]
            )->toHtml();

        $this->getResponse()
            ->setBody($block);
    }
}
