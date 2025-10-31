<?php

declare(strict_types=1);

namespace Tourze\DoctrinePaginatorBundle\Service;

use Doctrine\ORM\QueryBuilder;
use Tourze\DoctrinePaginatorBundle\Paginator\AutoPaginator;
use Tourze\DoctrinePaginatorBundle\Paginator\OffsetPaginator;
use Tourze\DoctrinePaginatorBundle\Paginator\PaginatorInterface;
use Tourze\DoctrinePaginatorBundle\Paginator\PaginatorStrategies;
use Tourze\DoctrinePaginatorBundle\Paginator\SearchAfterPaginator;
use Tourze\DoctrinePaginatorBundle\Paginator\SubQueryPaginator;

/**
 * 分页器工厂 - 创建各种类型的分页器
 */
class PaginatorFactory
{
    private QueryAnalyzer $queryAnalyzer;

    public function __construct(QueryAnalyzer $queryAnalyzer)
    {
        $this->queryAnalyzer = $queryAnalyzer;
    }

    /**
     * 创建指定策略的分页器
     */
    public function create(QueryBuilder $queryBuilder, ?string $strategy = null): PaginatorInterface
    {
        if (null === $strategy) {
            return $this->createAuto($queryBuilder);
        }

        switch ($strategy) {
            case PaginatorStrategies::STRATEGY_OFFSET:
                return new OffsetPaginator($queryBuilder);

            case PaginatorStrategies::STRATEGY_SUB_QUERY:
                return new SubQueryPaginator($queryBuilder);

            case PaginatorStrategies::STRATEGY_SEARCH_AFTER:
                return new SearchAfterPaginator($queryBuilder);

            case PaginatorStrategies::STRATEGY_AUTO:
            default:
                return $this->createAuto($queryBuilder);
        }
    }

    /**
     * 创建自动分页器（默认）
     */
    public function createAuto(QueryBuilder $queryBuilder): AutoPaginator
    {
        return new AutoPaginator($queryBuilder, $this->queryAnalyzer);
    }

    /**
     * 创建 Offset 分页器
     */
    public function createOffset(QueryBuilder $queryBuilder): OffsetPaginator
    {
        return new OffsetPaginator($queryBuilder);
    }

    /**
     * 创建 SubQuery 分页器
     */
    public function createSubQuery(QueryBuilder $queryBuilder): SubQueryPaginator
    {
        return new SubQueryPaginator($queryBuilder);
    }

    /**
     * 创建 SearchAfter 分页器
     */
    public function createSearchAfter(QueryBuilder $queryBuilder): SearchAfterPaginator
    {
        return new SearchAfterPaginator($queryBuilder);
    }

    /**
     * 获取查询分析器
     */
    public function getQueryAnalyzer(): QueryAnalyzer
    {
        return $this->queryAnalyzer;
    }
}
