<?php
/**
 * Copyright (c) 2020 Peach Payments. All rights reserved. Developed by Francois Raubenheimer
 */

/**
 * Class \PeachPayments\Hosted\Model\System\Config\Source\Method
 */
namespace PeachPayments\Hosted\Model\System\Config\Source;

class Method
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'CARD', 'label' => __('CARD')],
            ['value' => 'EFTSECURE', 'label' => __('EFTSECURE')],
            ['value' => 'MASTERPASS', 'label' => __('MASTERPASS')],
            ['value' => 'MOBICRED', 'label' => __('MOBICRED')],
            ['value' => 'MPESA', 'label' => __('MPESA')],
            ['value' => 'OZOW', 'label' => __('OZOW')],
        ];
    }
}
