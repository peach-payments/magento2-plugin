<?php
/**
 * Copyright (c) 2020 Peach Payments. All rights reserved. Developed by Francois Raubenheimer
 */

/**
 * Class \PeachPayments\Hosted\Model\Web\Hooks
 */
namespace PeachPayments\Hosted\Model\Web;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use \Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class Hooks extends AbstractModel
{
    /**
     * @var Date
     */
    protected $modelDate;

    public function __construct(Context $context,
        Registry $registry,
        DateTime $modelDate,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [])
    {
        $this->modelDate = $modelDate;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Entity code
     */
    const ENTITY = 'peachpayments_hosted_web_hooks';
    const CACHE_TAG = 'PeachPayments\Hosted\web\hooks';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'peachpayments_hosted_web_hooks';

    /**
     * Parameter name in event
     *
     * @var string
     */
    protected $_eventObject = 'web_hooks';

    /**
     * {@inheritdoc}
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('PeachPayments\Hosted\Model\ResourceModel\Web\Hooks');
    }

    /**
     * @param integer | string $orderId
     *
     * @return \PeachPayments\Hosted\Model\Web\Hooks
     */
    public function loadByOrderId($orderId)
    {
        return $this->load($orderId, 'order_id');
    }

    /**
     * @param string $incrementId
     *
     * @return \PeachPayments\Hosted\Model\Web\Hooks
     */
    public function loadByIncrementId($incrementId)
    {
        return $this->load($incrementId, 'order_increment_id');
    }

    /**
     * Before save
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();
        $now = $this->modelDate->gmtDate();

        if ($this->isObjectNew()) {
            $this->setData('created_at', $now);
        }

        $timestamp = $this->_getData('timestamp');

        if ($timestamp !== '') {
            $this->setData(
                'timestamp',
                $this->modelDate
                    ->gmtDate(null, $timestamp)
            );
        }

        return $this;
    }
}
