<?php

declare(strict_types=1);

namespace Tourze\DoctrinePaginatorBundle\Service;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Tourze\DoctrinePaginatorBundle\Paginator\PaginatorStrategies;

/**
 * 查询分析器 - 用于分析 QueryBuilder 的特征
 */
class QueryAnalyzer
{
    /**
     * 推荐最佳的分页策略
     */
    public function recommendStrategy(QueryBuilder $queryBuilder, int $offset, ?string $lastCursor = null): string
    {
        // 如果有游标，优先使用游标分页
        if (null !== $lastCursor) {
            return PaginatorStrategies::STRATEGY_SEARCH_AFTER;
        }

        // 获取查询特征
        $features = $this->analyze($queryBuilder);

        // 基于数据量选择策略
        if ($offset < 10000) {
            return PaginatorStrategies::STRATEGY_OFFSET;
        }
        if ($offset < 1000000) {
            // 中等数据量，如果查询复杂则使用子查询
            if ((bool) $features['hasJoins'] || (bool) $features['hasComplexConditions']) {
                return PaginatorStrategies::STRATEGY_SUB_QUERY;
            }

            return PaginatorStrategies::STRATEGY_OFFSET;
        }
        // 大数据量
        if ((bool) $features['hasIndexableOrderBy']) {
            return PaginatorStrategies::STRATEGY_SEARCH_AFTER;
        }

        return PaginatorStrategies::STRATEGY_SUB_QUERY;
    }

    /**
     * 分析查询并返回特征信息
     * @return array<string, mixed>
     */
    public function analyze(QueryBuilder $queryBuilder): array
    {
        return [
            'hasJoins' => $this->hasJoins($queryBuilder),
            'hasComplexConditions' => $this->hasComplexConditions($queryBuilder),
            'orderByFields' => $this->getOrderByFields($queryBuilder),
            'hasIndexableOrderBy' => $this->hasIndexableOrderBy($queryBuilder),
            'estimatedRowCount' => $this->estimateRowCount($queryBuilder),
            'hasWhereClause' => null !== $queryBuilder->getDQLPart('where'),
            'hasGroupBy' => [] !== $queryBuilder->getDQLPart('groupBy'),
            'hasHaving' => [] !== $queryBuilder->getDQLPart('having'),
            'hasDistinct' => $this->hasDistinct($queryBuilder),
            'hasLimit' => $this->hasLimit($queryBuilder),
        ];
    }

    /**
     * 检查是否有 JOIN 操作
     */
    private function hasJoins(QueryBuilder $queryBuilder): bool
    {
        $joinParts = $queryBuilder->getDQLPart('join');

        foreach ($joinParts as $joins) {
            if (is_array($joins) && [] !== $joins) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查是否有复杂的 WHERE 条件
     */
    private function hasComplexConditions(QueryBuilder $queryBuilder): bool
    {
        $where = $queryBuilder->getDQLPart('where');
        if (null === $where) {
            return false;
        }

        // 如果 where 是数组而不是对象，说明没有实际的查询条件
        if (is_array($where)) {
            return false;
        }

        $whereString = (string) $where;

        // 检查是否包含可能导致全表扫描的条件
        $complexPatterns = [
            'LIKE \'%',
            'OR ',
            'NOT LIKE',
            '!=',
            '<>',
            'IN (SELECT',
            'EXISTS (SELECT',
        ];

        foreach ($complexPatterns as $pattern) {
            if (false !== stripos($whereString, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取排序字段
     * @return array<string, string>
     */
    private function getOrderByFields(QueryBuilder $queryBuilder): array
    {
        $orderBy = $queryBuilder->getDQLPart('orderBy');
        $fields = [];

        if ([] !== $orderBy) {
            foreach ($orderBy as $order) {
                $fields[$order->getExpr()] = $order->getDirection();
            }
        }

        return $fields;
    }

    /**
     * 检查是否有适合索引的排序字段
     */
    private function hasIndexableOrderBy(QueryBuilder $queryBuilder): bool
    {
        $orderByFields = $this->getOrderByFields($queryBuilder);

        if ([] === $orderByFields) {
            return false;
        }

        // 如果按主键排序，通常有索引
        $rootAlias = $queryBuilder->getRootAliases()[0] ?? '';
        foreach (array_keys($orderByFields) as $field) {
            if (str_contains($field, '.id')) {
                return true;
            }
        }

        return false;
    }

    /**
     * 估算查询的行数
     * 注意：这是一个估算值，不准确但用于决策足够
     */
    private function estimateRowCount(QueryBuilder $queryBuilder): int
    {
        try {
            // 尝试使用 COUNT 查询
            $countQb = clone $queryBuilder;

            // 重置 select 和 order by
            $countQb->resetDQLPart('select');
            $countQb->resetDQLPart('orderBy');

            // 设置 COUNT
            $rootAliases = $countQb->getRootAliases();
            if ([] !== $rootAliases) {
                $rootAlias = $rootAliases[0];
                $countQb->select("COUNT({$rootAlias})");

                // 获取查询结果
                $query = $countQb->getQuery();
                // 设置只获取标量结果
                $query->setHydrationMode(Query::HYDRATE_SINGLE_SCALAR);

                return (int) $query->getSingleResult();
            }
        } catch (\Exception $e) {
            // 如果 COUNT 查询失败，返回一个较大的估算值
        }

        return 1000000; // 默认估算值
    }

    /**
     * 检查是否使用了 DISTINCT
     */
    private function hasDistinct(QueryBuilder $queryBuilder): bool
    {
        $selectParts = $queryBuilder->getDQLPart('select');
        if (null === $selectParts || !is_array($selectParts)) {
            return false;
        }

        foreach ($selectParts as $select) {
            $selectExpression = (string) $select;
            if (false !== stripos($selectExpression, 'DISTINCT')) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查是否设置了 LIMIT
     */
    private function hasLimit(QueryBuilder $queryBuilder): bool
    {
        return null !== $queryBuilder->getMaxResults();
    }
}
