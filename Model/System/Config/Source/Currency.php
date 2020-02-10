<?php
/**
 * Copyright (c) 2020 Peach Payments. All rights reserved. Developed by Francois Raubenheimer
 */

/**
 * Class \PeachPayments\Hosted\Model\System\Config\Source\Currency
 */
namespace PeachPayments\Hosted\Model\System\Config\Source;



class Currency
{
    /** @var array */
    protected $options = [];

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {

//            $this->options = Mage::app()
//                ->getLocale()
//                ->getOptionCurrencies();

//            foreach ($this->options as &$currencyOption) {
//                $currencyOption['label'] = $currencyOption['label'] . ' (' . $currencyOption['value'] . ')';
//            }
        };
        return $this->options;
    }
}
