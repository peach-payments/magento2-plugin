<?php
/**
 * Copyright (c) 2020 Peach Payments. All rights reserved. Developed by Francois Raubenheimer
 */

/**
 * Class \PeachPayments\Hosted\Helper\Data
 */

namespace PeachPayments\Hosted\Helper;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\ScopeInterface;
use PeachPayments\Hosted\Model\Web\Hooks;
use Psr\Log\LoggerInterface;
use Zend\Http\Response;

class Data extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     */
    protected $storeConfig;

    /**
     * @var RequestInterface
     */
    protected $appRequestInterface;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var LoggerInterface
     */
    protected $logLoggerInterface;

    /**
     * @var OrderFactory
     */
    protected $modelOrderFactory;

    /**
     * @var QuoteFactory
     */
    protected $modelQuoteFactory;
    /**
     * @var ZendClientFactory
     */
    private $httpClientFactory;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * Data constructor.
     * @param Context $context
     * @param ScopeConfigInterface $storeConfig
     * @param RequestInterface $appRequestInterface
     * @param UrlInterface $urlBuilder
     * @param LoggerInterface $logLoggerInterface
     * @param OrderFactory $modelOrderFactory
     * @param QuoteFactory $modelQuoteFactory
     * @param ZendClientFactory $httpClientFactory
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $storeConfig,
        RequestInterface $appRequestInterface,
        UrlInterface $urlBuilder,
        LoggerInterface $logLoggerInterface,
        OrderFactory $modelOrderFactory,
        QuoteFactory $modelQuoteFactory,
        ZendClientFactory $httpClientFactory,
        EventManager $eventManager)
    {
        $this->storeConfig = $storeConfig;
        $this->appRequestInterface = $appRequestInterface;
        $this->urlBuilder = $urlBuilder;
        $this->logLoggerInterface = $logLoggerInterface;
        $this->modelOrderFactory = $modelOrderFactory;
        $this->modelQuoteFactory = $modelQuoteFactory;
        $this->httpClientFactory = $httpClientFactory;
        $this->eventManager = $eventManager;

        parent::__construct($context);

    }

    const API_LIVE = 'https://api.peachpayments.com/v1/checkout/';
    const API_TEST = 'https://testapi.peachpayments.com/v1/checkout/';
    const CHECKOUT_LIVE = 'https://secure.peachpayments.com/checkout';
    const CHECKOUT_TEST = 'https://testsecure.peachpayments.com/checkout';
    const PLATFORM = 'MAGENTO';
    const XML_CONF = 'payment/peachpayments_hosted/';

    /** @var ZendClient */
    private $client;

    /** @var array */
    private $sandboxVariables = [
        'entity_id',
        'sign_key',
    ];

    /**
     * @param $path
     * @param bool $isBool
     * @return bool|string
     */
    private function getConfig($path, $isBool = false)
    {
        if (!$this->getMode() && in_array($path, $this->sandboxVariables)) {
            $path .= '_sandbox';
        }

        if ($isBool) {
            return $this->storeConfig->isSetFlag(self::XML_CONF . $path, ScopeInterface::SCOPE_STORE);
        }

        return $this->storeConfig->getValue(self::XML_CONF . $path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    private function getMode()
    {
        return $this->storeConfig->isSetFlag(self::XML_CONF . 'mode', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param string $loc
     * @return string
     */
    public function getApiUrl($loc = '')
    {
        $url = $this->getMode() ? self::API_LIVE : self::API_TEST;

        return $url . $loc;
    }

    /**
     * @return string
     */
    public function getCheckoutUrl()
    {
        return $this->getMode() ? self::CHECKOUT_LIVE : self::CHECKOUT_TEST;
    }

    /**
     * @return string
     */
    public function getWaitingUrl()
    {
        $orderId = $this->appRequestInterface->getParam('id');
        return $this->urlBuilder->getUrl('peachpayments_hosted/secure/payment', ['merchantTransactionId' => $orderId]);
    }

    /**
     * @return string
     */
    public function getEntityId()
    {
        return $this->getConfig('entity_id');
    }

    /**
     * @return string
     */
    private function getSignKey()
    {
        return $this->getConfig('sign_key');
    }

    /**
     * @return string
     */
    public function getPlatformName()
    {
        return self::PLATFORM;
    }

    /**
     * @param string $id
     * @param float $amount
     * @param string $currency
     * @return array
     * @throws \Zend_Http_Client_Exception
     * @throws Exception
     */
    public function processRefund($id, $amount, $currency)
    {

        $client = $this->getHttpClient($this->getApiUrl('refund'));

        $params = [
            'authentication.entityId' => $this->getEntityId(),
            'amount' => $amount,
            'paymentType' => 'RF',
            'currency' => $currency,
            'id' => $id,
        ];

        $client->setParameterPost($this->signData($params, false));

        try {
            /** @var Response $response */
            $response = $client->request(\Zend_Http_Client::POST);
            /** @var array $json */
            $json = json_decode($response->getRawBody(), true);
            return $json;

        } catch (Exception $e) {
            $this->logLoggerInterface->error($e);
        }

        return [];
    }

    /**
     * @param int $merchantTransactionId
     * @return array
     * @throws \Zend_Http_Client_Exception
     * @throws Exception
     */
    public function processStatus($merchantTransactionId)
    {

        $client = $this->getHttpClient($this->getApiUrl('status'));

        $params = [
            'authentication.entityId' => $this->getEntityId(),
            'merchantTransactionId' => $merchantTransactionId,
        ];

        $client->setParameterGet($this->signData($params, false));

        try {
            /** @var Response $response */
            $response = $client->request(\Zend_Http_Client::GET);
            /** @var array $json */
            $json = json_decode($response->getRawBody(), true);
            return $json;

        } catch (Exception $e) {
            $this->logLoggerInterface->error($e);
        }

        return [];
    }

    /**
     * @param string $url
     * @return ZendClient
     * @throws \Zend_Http_Client_Exception
     */
    public function getHttpClient($url = '')
    {
        /** @var \Magento\Framework\HTTP\ZendClient $client */
        $client = $this->httpClientFactory->create();
        $client->setUri($url);
        return $client;
    }

    /**
     * @param array $data unsigned data
     * @param bool $includeNonce
     *
     * @return array signed data
     * @throws Exception
     */
    public function signData($data = [], $includeNonce = true)
    {

        assert(count($data) !== 0, 'Error: Sign data can not be empty');
        assert(function_exists('hash_hmac'), 'Error: hash_hmac function does not exist');

        if ($includeNonce) {
            $nonce = $this->getUuid();
            assert(strlen($nonce) !== 0, 'Error: Nonce can not be empty, something went horribly wrong');
            $data = array_merge($data, ['nonce' => $this->getUuid()]);
        }

        $tmp = [];
        foreach ($data as $key => $datum) {
            // NOTE: Zend framework s/./_/g fix
            $tmp[str_replace('_', '.', $key)] = $datum;
        }

        ksort($tmp, SORT_STRING);

        $signDataRaw = '';
        foreach ($tmp as $key => $datum) {
            if ($key !== 'signature') {
                // NOTE: Zend framework s/./_/g fix
                $signDataRaw .= str_replace('_', '.', $key) . $datum;
            }
        }

        $signData = hash_hmac('sha256', $signDataRaw, $this->getSignKey());

        return array_merge($data, ['signature' => $signData]);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getUuid()
    {
        $data = random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Restore last active quote based on checkout session
     *
     * @return bool True if quote restored successfully, false otherwise
     * @throws Exception
     */
    public function restoreQuote()
    {
        $session = $this->getSession();
        $order = $session->getLastRealOrder();
        if ($order->getId()) {
            $quote = $this->getQuoteById($order->getQuoteId());
            if ($quote->getId()) {
                return $session->restoreQuote();
            }
        }
        return false;
    }

    /**
     * @param Hooks $result
     *
     * @return bool
     */
    public function processOrder($result)
    {
        /** @var string $resultCode */
        $resultCode = $result->getData('result_code');
        /** @var Order $order */
        $order = $this->modelOrderFactory->create()
            ->load($result->getData('order_id'));
        /** @var Payment $payment */
        $payment = $order->getPayment();

        if (($resultCode === '000.000.000' || $resultCode === '000.100.110' || $resultCode === '000.100.111' || $resultCode === '000.100.112')
            && $order instanceof Order
            && $payment instanceof Payment
        ) {
            try {

                $payment->setData('transaction_id', $result->getData('peach_id'));
                $payment->registerCaptureNotification($result->getData('amount'), true);
                $payment->setAdditionalInformation('peach_request', $result->getData('request'));

                $paymentBrand = strtolower($result->getData('payment_brand'));

                // ugly catch-all approach ~ for now
                switch ($paymentBrand) {
                    case 'eftsecure':
                        $methodCode = 'eftsecure';
                        break;
                    case 'masterpass':
                        $methodCode = 'masterpass';
                        break;
                    case 'mobicred':
                        $methodCode = 'mobicred';
                        break;
                    case 'mpesa':
                        $methodCode = 'mpesa';
                        break;
                    case 'ozow':
                        $methodCode = 'ozow';
                        break;
                    default:
                        $methodCode = 'card';
                        break;
                }

                $payment->setMethod('peachpayments_hosted_' . $methodCode);

                if ($order->getCanSendNewEmailFlag() && $this->getConfig('send_order_email', true)) {
                    $orderSender = ObjectManager::getInstance()->get('Magento\Sales\Model\Order\Email\Sender\OrderSender');
                    $orderSender->send($order);
                }

                if ($this->getConfig('send_invoice_email', true)) {
                    foreach ($order->getInvoiceCollection() as $invoice) {
                        if ($invoice && !$invoice->getEmailSent()) {

                            $invoiceSender = ObjectManager::getInstance()->get('Magento\Sales\Model\Order\Email\Sender\InvoiceSender');
                            $invoiceSender->send($invoice);

                            $order->addRelatedObject($invoice);
                            $order->addStatusHistoryComment(__('You notified customer about invoice #%1.', $invoice->getIncrementId()))
                                ->setIsCustomerNotified(true)
                                ->save();
                        }
                    }
                }

                $order->setState(Order::STATE_PROCESSING)
                    ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING))
                    ->addStatusHistoryComment(__('Approved payment online at PeachPayments.'));


                $order->save();

                // dispatch event to say order succeeded
                $this->eventManager->dispatch('peachpayments_order_succeed', ['result' => $result]);
            } catch (Exception $e) {
                $this->logLoggerInterface->error($e);
            }

            return true;
        }

        if ($resultCode !== '000.200.000' && $resultCode !== '000.200.100') {
            try {
                $order->cancel();
                $order->save();
            } catch (Exception $e) {
                $this->logLoggerInterface->error($e);
            }
        }

        // dispatch event to say order failed
        $this->eventManager->dispatch('peachpayments_order_failed', ['result' => $result]);
        return false;
    }

    /**
     * @return \Magento\Checkout\Model\Session
     */
    protected function getSession()
    {
        return ObjectManager::getInstance()->get('Magento\Checkout\Model\Session');
    }

    /**
     * Return sales quote instance for specified ID
     *
     * @param int $quoteId Quote identifier
     * @return \Magento\Quote\Model\Quote
     */
    protected function getQuoteById($quoteId)
    {
        return $this->modelQuoteFactory->create()->load($quoteId);
    }
}
