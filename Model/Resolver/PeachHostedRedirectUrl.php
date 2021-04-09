<?php
/*
 * Copyright (c) Peach Payments. All rights reserved. Developed by Francois Raubenheimer
 */

namespace PeachPayments\Hosted\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use PeachPayments\Hosted\Helper\Data as Helper;
use PeachPayments\Hosted\Model\Web\HooksFactory;

/**
 * Class PeachHostedRedirectUrl
 */
class PeachHostedRedirectUrl implements ResolverInterface
{
    /**
     * @var CollectionFactoryInterface
     */
    private $orderCollectionFactory;
    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;
    /**
     * @var Helper
     */
    private $helper;
    /**
     * @var HooksFactory
     */
    private $webHooksFactory;

    /**
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param CollectionFactoryInterface $orderCollectionFactory
     * @param Helper $helper
     * @param HooksFactory $webHooksFactory
     */
    public function __construct(
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        CollectionFactoryInterface $orderCollectionFactory,
        Helper $helper,
        HooksFactory $webHooksFactory
    )
    {
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->helper = $helper;
        $this->webHooksFactory = $webHooksFactory;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $customerId = $context->getUserId();
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $maskedCartId = $args['input']['cart_id'] ?? '';
        $returnUrl = $args['input']['return_url'] ?? '';

        try {
            $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }

        return [
            'form_link' => $this->helper->getCheckoutUrl(),
            'form_data' => json_encode($this->helper->signData($this->getFormData($cartId, $returnUrl, $customerId, $storeId))),
        ];
    }

    /**
     * @param int $quoteId
     * @param string $redirectUrl
     * @param int $customerId
     * @param int $storeId
     * @return array
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     */
    private function getFormData(int $quoteId, string $redirectUrl, int $customerId, int $storeId)
    {
        $orderCollection = $this->orderCollectionFactory->create($customerId ?? null);
        $orderCollection->addFilter(Order::QUOTE_ID, $quoteId);
        $orderCollection->addFilter(Order::STATUS, Order::STATE_PENDING_PAYMENT);
        $orderCollection->addFilter(Order::STORE_ID, $storeId);

        if ($orderCollection->getTotalCount() !== 1) {
            throw new GraphQlNoSuchEntityException(__('Could not find payment information for cart.'));
        }
        /** @var Order $order */
        $order = $orderCollection->getFirstItem();
        $helper = $this->helper;

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

        // @TODO fix duplicated
        $billingStreet = $order->getBillingAddress()->getStreet();
        $billingStreetOne = '';
        $billingStreetTwo = 'N/A';

        if (!empty($billingStreet)) {
            if (array_key_exists(0, $billingStreet)) {
                $billingStreetOne = $billingStreet[0];
            }

            if (array_key_exists(1, $billingStreet)) {
                $billingStreetTwo = $billingStreet[1];
            }
        }


        $shippingDetails = [];

        // @note exclude shipping address on virtual products
        if ($order->getShippingAddress()) {
            $shippingStreet = $order->getShippingAddress()->getStreet();
            $shippingCity = $order->getShippingAddress()->getCity();
            $shippingCountry = $order->getShippingAddress()->getCountryId();

            $shippingStreetOne = '';
            $shippingStreetTwo = 'N/A';

            if (!empty($shippingStreet)) {
                if (array_key_exists(0, $shippingStreet)) {
                    $shippingStreetOne = $shippingStreet[0];
                }

                if (array_key_exists(1, $shippingStreet)) {
                    $shippingStreetTwo = $shippingStreet[1];
                }
            }

            $shippingDetails = [
                'shipping.street1' => $shippingStreetOne,
                'shipping.street2' => $shippingStreetTwo,
                'shipping.city' => $shippingCity,
                'shipping.country' => $shippingCountry,
            ];
        }

        // setup webhook and insert tracking incremental ids
        $orderId = $order->getId();
        $orderIncrementId = $order->getRealOrderId();

        $webHook = $this->getWebHook()->loadByOrderId($order->getId());

        if (!$webHook->getId()) {
            $webHook->addData([
                'order_id' => $orderId,
                'order_increment_id' => $orderIncrementId
            ]);
        } else {
            $webHook->setData('order_id', $orderId);
            $webHook->setData('order_increment_id', $orderIncrementId);
        }

        $webHook->save();

        try {
            return array_merge([
                'authentication.entityId' => $helper->getEntityId(),
                'amount' => $amount,
                'paymentType' => 'DB',
                'currency' => $order->getOrderCurrencyCode(),
                'shopperResultUrl' => $redirectUrl,
                'merchantTransactionId' => $order->getIncrementId(),
                'defaultPaymentMethod' => $methodCode,
                'plugin' => $helper->getPlatformName(),

                'customer.givenName' => $order->getBillingAddress()->getFirstname(),
                'customer.surname' => $order->getBillingAddress()->getLastname(),
                'customer.mobile' => $order->getBillingAddress()->getTelephone(),
                'customer.email' => $order->getBillingAddress()->getEmail(),
                'customer.status' => $order->getCustomerIsGuest() ? 'NEW' : 'EXISTING',

                'billing.street1' => $billingStreetOne,
                'billing.street2' => $billingStreetTwo,
                'billing.city' => $order->getBillingAddress()->getCity(),
                'billing.country' => $order->getBillingAddress()->getCountryId(),

            ], $shippingDetails);

        } catch (\Exception $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }
    }

    /**
     * @return \PeachPayments\Hosted\Model\Web\Hooks
     */
    public function getWebHook()
    {
        return $this->webHooksFactory->create();
    }
}
