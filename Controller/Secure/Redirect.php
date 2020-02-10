<?php
/**
 * Copyright (c) 2020 Peach Payments. All rights reserved. Developed by Francois Raubenheimer
 */

namespace PeachPayments\Hosted\Controller\Secure;

use PeachPayments\Hosted\Model\Web\Hooks;

class Redirect extends AbstractSecure
{
    /**
     * Redirect to PeachPayments hosted forms.
     */
    public function execute()
    {
        /** @var string $orderId */
        $orderId = $this->getSession()
            ->getData('last_order_id');
        /** @var string $orderIncrementId */
        $orderIncrementId = $this->getSession()
            ->getData('last_real_order_id');
        /** @var Hooks $webHookM */
        $webHookM = $this->getWebHook()->loadByOrderId($orderId);

        if (!$webHookM->getId()) {
            $webHookM->addData([
                'order_id' => $orderId,
                'order_increment_id' => $orderIncrementId
            ]);
        } else {
            $webHookM->setData('order_id', $orderId);
            $webHookM->setData('order_increment_id', $orderIncrementId);
        }
        $webHookM->save();

        $this->getInternalRedirect($orderId, $orderIncrementId);
    }
}
