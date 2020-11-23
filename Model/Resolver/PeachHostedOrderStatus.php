<?php
/*
 * Copyright (c) Peach Payments. All rights reserved. Developed by Francois Raubenheimer
 */

namespace PeachPayments\Hosted\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use PeachPayments\Hosted\Model\Web\HooksFactory;

/**
 * Class PeachHostedOrderStatus
 */
class PeachHostedOrderStatus implements ResolverInterface
{
    /**
     * @var HooksFactory
     */
    private $webHooksFactory;

    /**
     * PeachHostedOrderStatus constructor.
     * @param HooksFactory $webHooksFactory
     */
    public function __construct(
        HooksFactory $webHooksFactory
    ) {
        $this->webHooksFactory = $webHooksFactory;
    }

    /**
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return bool|\Magento\Framework\GraphQl\Query\Resolver\Value|mixed
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $orderId = $args['input']['order_id'] ?? '';
        $webHook = $this->getWebHook()->loadByOrderId($orderId);

        if (!strlen($webHook->getData('result_code'))) {
            return ['status' => 3];
        }

        return ['status' => ($webHook->getId() && $this->isSuccessful($webHook->getData('result_code'))) ? 1 : 2];
    }

    /**
     * @return \PeachPayments\Hosted\Model\Web\Hooks
     */
    public function getWebHook()
    {
        return $this->webHooksFactory->create();
    }

    /**
     * @param string $resultCode
     * @return bool
     */
    protected function isSuccessful(string $resultCode): bool
    {
        return $resultCode === '000.000.000'
            || $resultCode === '000.100.110'
            || $resultCode === '000.100.111'
            || $resultCode === '000.100.112';
    }
}
