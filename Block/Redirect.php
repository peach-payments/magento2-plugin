<?php
/**
 * Copyright (c) 2020 Peach Payments. All rights reserved. Developed by Francois Raubenheimer
 */

/**
 * Class BlockRedirect
 *
 * @method getOrderId
 * @method getOrderIncrementId
 */
namespace PeachPayments\Hosted\Block;

use Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use PeachPayments\Hosted\Helper\Data as HelperData;

class Redirect extends Template
{
    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    public function __construct(Context $context,
        HelperData $helperData,
        RemoteAddress $remoteAddress,
        array $data = []
    ) {
        $this->helperData = $helperData;
        $this->remoteAddress = $remoteAddress;
        parent::__construct($context, $data);
    }

    /**
     * Redirect template
     */
    protected function _construct()
    {
        $this->setTemplate('PeachPayments_Hosted::peachpayments_hosted/redirect.phtml');
    }

    /**
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->helperData->getCheckoutUrl();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getFormData()
    {
        return $this->helperData
            ->signData($this->getUnsortedFormData());
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return ObjectManager::getInstance()->get('Magento\Checkout\Model\Session')
            ->getLastRealOrder();
    }

    /**
     * @return array
     */
    private function getUnsortedFormData()
    {
        /** @var Order $order */
        $order = $this->getOrder();
        /** @var HelperData $helper */
        $helper = $this->helperData;
        /** @var int $amount */
        $amount = number_format(
            $order->getPayment()->getAmountOrdered(),
            2,
            '.',
            ''
        );

        $methodCode = strtoupper(
            str_replace(
                'peachpayments_hosted_',
                '',
                $order->getPayment()->getMethodInstance()->getCode()
            )
        );

        $billingStreet = $order->getBillingAddress()->getStreet();
        $billingStreetOne = '';
        $billingStreetTwo = 'N/A';

        if(!empty($billingStreet)) {
            if(array_key_exists(0, $billingStreet)){
                $billingStreetOne = $billingStreet[0];
            }

            if(array_key_exists(1, $billingStreet)){
                $billingStreetTwo = $billingStreet[1];
            }
        }

        $shippingStreet = $order->getShippingAddress()->getStreet();
        $shippingStreetOne = '';
        $shippingStreetTwo = 'N/A';

        if(!empty($shippingStreet)) {
            if(array_key_exists(0, $shippingStreet)){
                $shippingStreetOne = $shippingStreet[0];
            }

            if(array_key_exists(1, $shippingStreet)){
                $shippingStreetTwo = $shippingStreet[1];
            }
        }



        return [
            'authentication.entityId' => $helper->getEntityId(),
            'amount' => $amount,
            'paymentType' => 'DB',
            'currency' => $order->getOrderCurrencyCode(),
            'shopperResultUrl' => $this->getUrl('*/*/payment'),
            'merchantTransactionId' => $order->getIncrementId(),
            'defaultPaymentMethod' => $methodCode,
            'plugin' => $helper->getPlatformName(),

            'customer.givenName' => $order->getBillingAddress()->getFirstname(),
            'customer.surname' => $order->getBillingAddress()->getLastname(),
            'customer.mobile' => $order->getBillingAddress()->getTelephone(),
            'customer.email' => $order->getBillingAddress()->getEmail(),
            'customer.status'=> $order->getCustomerIsGuest() ? 'EXISTING' : 'NEW',
            'customer.ip' => $this->remoteAddress->getRemoteAddress(),

            'billing.street1' => $billingStreetOne,
            'billing.street2' => $billingStreetTwo,
            'billing.city' => $order->getBillingAddress()->getCity(),
            'billing.country' => $order->getBillingAddress()->getCountryId(),

            'shipping.street1' => $shippingStreetOne,
            'shipping.street2' => $shippingStreetTwo,
            'shipping.city' => $order->getShippingAddress()->getCity(),
            'shipping.country' => $order->getShippingAddress()->getCountryId(),
        ];
    }
}
