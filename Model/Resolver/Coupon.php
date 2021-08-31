<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lofmp\CouponCodeGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use Magento\SalesGraphQl\Model\Formatter\Order as OrderFormatter;
use Magento\SalesGraphQl\Model\Resolver\CustomerOrders\Query\OrderFilter;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Coupon data reslover
 */
class Coupon implements ResolverInterface
{
    /**
     * @var CollectionFactoryInterface
     */
    private $collectionFactory;

    /**
     * @var OrderFormatter
     */
    private $orderFormatter;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Lofmp\CouponCode\Helper\Data
     */
    protected $_dataHelper;

    /**
     * @param CollectionFactoryInterface $collectionFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderFormatter $orderFormatter
     * @param \Lofmp\CouponCode\Helper\Data $data
     */
    public function __construct(
        CollectionFactoryInterface $collectionFactory,
        OrderRepositoryInterface $orderRepository,
        OrderFormatter $orderFormatter,
        \Lofmp\CouponCode\Helper\Data $data
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->orderFormatter = $orderFormatter;
        $this->orderRepository = $orderRepository;
        $this->_dataHelper = $data;
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        //write code at here
    }
}
