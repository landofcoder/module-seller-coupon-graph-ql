<?php
/**
 * Copyright © landofcoder.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lofmp\CouponCodeGraphQl\Model\Resolver\DataProvider;

use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\Exception\InputException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Search\Model\Query;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Lofmp\CouponCode\Api\CouponManagementInterfaceFactory;
use Lofmp\CouponCode\Api\RuleRepositoryInterface;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder as SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\ArgumentApplier\Filter;

class LofCouponCodes
{

    /**
     * @var string
     */
    private const SPECIAL_CHARACTERS = '-+~/\\<>\'":*$#@()!,.?`=%&^';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var CouponManagementInterfaceFactory
     */
    private $modelRepositoryFactory;

    /**
     * @var RuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * construct class
     *
     * @param CouponManagementInterfaceFactory $modelRepositoryFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param GetCustomer $getCustomer
     * @param RuleRepositoryInterface $ruleRepository
     */
    public function __construct(
        CouponManagementInterfaceFactory $modelRepositoryFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ScopeConfigInterface $scopeConfig,
        GetCustomer $getCustomer,
        RuleRepositoryInterface $ruleRepository
    )
    {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->modelRepositoryFactory = $modelRepositoryFactory;
        $this->scopeConfig = $scopeConfig;
        $this->getCustomer = $getCustomer;
        $this->ruleRepository = $ruleRepository;
    }

    /**
     * @inheritdoc
     */
    public function getMyCouponCodes($args, $context)
    {
        $customer = $this->getCustomer->execute($context);
        if (!$customer || !$customer->getId()) {
            throw new GraphQlInputException(__('please login with your account before.'));
        }
        if ($args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }
        if ($args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }
        if(isset($args['filters']) && (!isset($args['filters']['status']) || !$args['filters']['status'])){
            $args['filters']['status'] = ['eq' => 1];
        }
        $availableTypes = [
            "all",
            "available",
            "expired",
            "used"
        ];
        $filterType = isset($args['type']) ? trim($args["type"]) : "all";
        if (!in_array($filterType, $availableTypes)) {
            $filterType = "all";
        }
        $store = $context->getExtensionAttributes()->getStore();
        $args[Filter::ARGUMENT_NAME] = $this->formatMatchFilters($args['filters'], $store);
        $searchCriteria = $this->searchCriteriaBuilder->build( 'lofCouponCodes', $args );
        $searchCriteria->setCurrentPage( $args['currentPage'] );
        $searchCriteria->setPageSize( $args['pageSize'] );

        switch ($filterType) {
            case "available":
                $searchResult = $this->modelRepositoryFactory->create()
                                ->getAvailableCoupons($customer->getId(), $searchCriteria );
                break;
            case "expired":
                $searchResult = $this->modelRepositoryFactory->create()
                                ->getExpiredCoupons($customer->getId(), $searchCriteria );
                break;
            case "used":
                $searchResult = $this->modelRepositoryFactory->create()
                                ->getUsedCoupons($customer->getId(), $searchCriteria );
                break;
            case "all":
            default:
                $searchResult = $this->modelRepositoryFactory->create()
                            ->getCouponByConditions($customer->getId(), $searchCriteria );
                break;
        }

        $totalPages = $args['pageSize'] ? ((int)ceil($searchResult->getTotalCount() / $args['pageSize'])) : 0;
        $resultItems = $searchResult->getItems();
        $items = [];
        if ($resultItems) {
            foreach ($resultItems as $_item) {
                $newItem = $_item->__toArray();
                $newItem["coupon_rule"] = [];
                if ($_item->getRuleId()) {
                    $ruleItem = $this->ruleRepository->getById($_item->getRuleId() );
                    $newItem["coupon_rule"] = $ruleItem->__toArray();
                }
                $items[] = $newItem;
            }
        }
        return [
            'total_count' => $searchResult->getTotalCount(),
            'items'       => $items,
            'page_info' => [
                'page_size' => $args['pageSize'],
                'current_page' => $args['currentPage'],
                'total_pages' => $totalPages
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function getSellerCouponCodes($args, $context)
    {
        $customer = $this->getCustomer->execute($context);
        if (!$customer || !$customer->getId()) {
            throw new GraphQlInputException(__('please login with your account before.'));
        }
        if ($args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }
        if ($args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }
        if(isset($args['filters']) && (!isset($args['filters']['status']) || !$args['filters']['status'])){
            $args['filters']['status'] = ['eq' => 1];
        }

        $store = $context->getExtensionAttributes()->getStore();
        $args[Filter::ARGUMENT_NAME] = $this->formatMatchFilters($args['filters'], $store);
        $searchCriteria = $this->searchCriteriaBuilder->build( 'lofCouponCodes', $args );
        $searchCriteria->setCurrentPage( $args['currentPage'] );
        $searchCriteria->setPageSize( $args['pageSize'] );

        $searchResult = $this->modelRepositoryFactory->create()
                            ->getPublicCoupons($args["sellerUrl"], $searchCriteria );

        $totalPages = $args['pageSize'] ? ((int)ceil($searchResult->getTotalCount() / $args['pageSize'])) : 0;
        $resultItems = $searchResult->getItems();
        $items = [];
        if ($resultItems) {
            foreach ($resultItems as $_item) {
                $newItem = $_item->__toArray();
                $newItem["coupon_rule"] = [];
                if ($_item->getRuleId()) {
                    $ruleItem = $this->ruleRepository->getById($_item->getRuleId() );
                    $newItem["coupon_rule"] = $ruleItem->__toArray();
                }
                $items[] = $newItem;
            }
        }
        return [
            'total_count' => $searchResult->getTotalCount(),
            'items'       => $items,
            'page_info' => [
                'page_size' => $args['pageSize'],
                'current_page' => $args['currentPage'],
                'total_pages' => $totalPages
            ]
        ];
    }
    /**
     * Format match filters to behave like fuzzy match
     *
     * @param array $filters
     * @param StoreInterface $store
     * @return array
     * @throws InputException
     */
    private function formatMatchFilters(array $filters, StoreInterface $store): array
    {
        $minQueryLength = $this->scopeConfig->getValue(
            Query::XML_PATH_MIN_QUERY_LENGTH,
            ScopeInterface::SCOPE_STORE,
            $store
        );
        $availableMatchFilters = ["store_id"];
        foreach ($filters as $filter => $condition) {
            $conditionType = current(array_keys($condition));
            $tmpminQueryLength = $minQueryLength;
            if (in_array($filter, $availableMatchFilters)) {
                $tmpminQueryLength = 1;
            }
            if ($conditionType === 'match') {
                $searchValue = trim(str_replace(self::SPECIAL_CHARACTERS, '', $condition[$conditionType]));
                $matchLength = strlen($searchValue);
                if ($matchLength < $tmpminQueryLength) {
                    throw new InputException(__('Invalid match filter. Minimum length is %1.', $tmpminQueryLength));
                }
                unset($filters[$filter]['match']);
                if($filter == "store_id"){
                    $searchValue = (int)$searchValue;
                }
                $filters[$filter]['like'] = '%' . $searchValue . '%';
            }
        }
        return $filters;
    }
}

