<?php
/**
 * Copyright (c) 2020 Peach Payments. All rights reserved. Developed by Francois Raubenheimer
 */

/**
 * Class \PeachPayments\Hosted\Model\Method\Hosted
 */
namespace PeachPayments\Hosted\Model\Method;

use Exception;
use Magento\Directory\Helper\Data as DirectoryHelperData;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as HelperData;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use PeachPayments\Hosted\Helper\Data as HostedHelperData;
use Zend_Http_Client_Exception;

abstract class Hosted extends AbstractMethod
{
    /**
     * @var HostedHelperData
     */
    protected $helperData;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        HelperData $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        HostedHelperData $helperData,
        UrlInterface $urlBuilder,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        DirectoryHelperData $directory = null)
    {
        $this->helperData = $helperData;
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data,
            $directory
        );
    }

    protected $_canManageRecurringProfiles = false;
    protected $_canOrder = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canUseInternal = false;
    protected $_canVoid = true;
    protected $_code = 'peachpayments_hosted';
    protected $_isGateway = true;
    protected $_isInitializeNeeded = true;
    protected $_canUseCheckout = true;

    /**
     * @var string
     */
    protected $_infoBlockType = 'PeachPayments\Hosted\Block\Info';

    /**
     * Method that will be executed instead of authorize or capture
     * if flag isInitializeNeeded set to true
     *
     * @param string $paymentAction
     * @param DataObject $stateObject
     *
     * @return void
     * @throws LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        /** @var Payment $payment */
        $payment = $this->getInfoInstance();
        /** @var Order $order */
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        $stateCode = Order::STATE_PENDING_PAYMENT;

        $message = __(
            'Customer redirected to PeachPayments with total amount due of '
            .  $this->formatPrice($order->getBaseTotalDue())
        );

        $order->addStatusToHistory($stateCode, $message, false);

        $stateObject->setData('state', $stateCode);
        $stateObject->setData('status', $stateCode);
        $stateObject->setData('is_notified', false);
    }

    /**
     * Format price with currency sign
     *
     * @param float $amount
     * @param null|string $currency
     *
     * @return string
     * @throws LocalizedException
     */
    protected function formatPrice($amount, $currency = null)
    {
        /** @var Payment $payment */
        $payment = $this->getInfoInstance();

        return $payment->getOrder()->getBaseCurrency()->formatTxt(
            $amount,
            $currency ? ['currency' => $currency] : []
        );
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws Zend_Http_Client_Exception
     * @throws Exception
     */
    public function refund(InfoInterface $payment, $amount)
    {
        $helper = $this->helperData;
        /** @var Creditmemo $creditmemo */
        $creditmemo = $payment->getData('creditmemo');

        $helper->processRefund(
            $payment->getLastTransId(),
            $amount,
            $creditmemo->getOrderCurrencyCode()
        );

        return $this;
    }

    /**
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return $this->urlBuilder->getUrl('peachpayments_hosted/secure/redirect', ['_secure' => true]);
    }

    /**
     * Check method for processing with base currency
     *
     * @param string $currencyCode
     * @return boolean
     */
    public function canUseForCurrency($currencyCode)
    {
        $currencies = explode(
            ',',
            $this->scopeConfig->getValue('payment/peachpayments_hosted/currency', ScopeInterface::SCOPE_STORE)
        );

        return in_array($currencyCode, $currencies);
    }

    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param int|string|null|Store $storeId
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function getConfigData($field, $storeId = null)
    {

        if (null === $storeId) {
            $storeId = $this->getData('store');
        }

        $code = $this->getCode();

        if ($field !== 'title') {
            $code = 'peachpayments_hosted';
        }

        $path = 'payment/' . $code . '/' . $field;

        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    public function canUseCheckout()
    {
        $methods = explode(',', $this->scopeConfig->getValue('payment/peachpayments_hosted/methods', ScopeInterface::SCOPE_STORE));
        $code = strtoupper(str_replace('peachpayments_hosted_', '', $this->getCode()));

        if (!in_array($code, $methods)) {
            return false;
        }

        return parent::canUseCheckout();
    }
}
