<?php
/**
 * Copyright (c) 2020 Peach Payments. All rights reserved. Developed by Francois Raubenheimer
 */

/**
 * Class \PeachPayments\Hosted\Model\System\Config\Source\Mode
 */
namespace PeachPayments\Hosted\Model\System\Config\Source;



class Mode
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 1,
                'label' => __('Production')
            ],
            [
                'value' => 0,
                'label' => __('Sandbox')
            ],
        ];
    }
}
