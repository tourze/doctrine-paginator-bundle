<?php

declare(strict_types=1);

namespace Tourze\DoctrinePaginatorBundle\Paginator;

use Doctrine\ORM\QueryBuilder;
use Tourze\DoctrinePaginatorBundle\Exception\InvalidPaginationException;
use Tourze\DoctrinePaginatorBundle\Model\PaginationResult;

/**
 * Search After 分页器 - 基于游标的分页
 * 适用于大数据量场景，性能最佳但只能连续翻页
 */
class SearchAfterPaginator implements PaginatorInterface
{
    private QueryBuilder $queryBuilder;

    private string $idField = 'id';

    /**
     * @var array<string>
     */
    private array $orderFields = [];

    /**
     * @param array<string> $orderFields
     */
    public function __construct(QueryBuilder $queryBuilder, array $orderFields = [])
    {
        $this->queryBuilder = $queryBuilder;
        $this->orderFields = $orderFields;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function setQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function getStrategy(): string
    {
        return PaginatorStrategies::STRATEGY_SEARCH_AFTER;
    }

    /**
     * 设置ID字段名
     */
    public function setIdField(string $idField): void
    {
        $this->idField = $idField;
    }

    public function paginate(int $page, int $pageSize): PaginationResult
    {
        // SearchAfter 分页器不支持页码分页，使用游标分页
        return $this->paginateAfter(null, $pageSize);
    }

    public function paginateAfter(mixed $lastCursor, int $pageSize): PaginationResult
    {
        $qb = clone $this->queryBuilder;
        $rootAlias = $this->validateAndGetRootAlias($qb);

        $this->setupOrderByFields($qb, $rootAlias);

        if (null !== $lastCursor) {
            $this->applyCursorCondition($qb, $lastCursor, $rootAlias);
        } else {
            $this->ensureDefaultOrdering($qb, $rootAlias);
        }

        $qb->setMaxResults($pageSize + 1);
        $items = $qb->getQuery()->getResult();

        $hasMore = count($items) > $pageSize;
        if ($hasMore) {
            array_pop($items);
        }

        $lastItem = [] !== $items ? end($items) : null;

        return new PaginationResult(
            items: $items,
            currentPage: 1,
            pageSize: $pageSize,
            total: $this->getTotalItems(),
            totalPages: 0,
            hasMore: $hasMore,
            lastItem: $lastItem
        );
    }

    /**
     * 验证并获取根别名
     */
    private function validateAndGetRootAlias(QueryBuilder $qb): string
    {
        $rootAliases = $qb->getRootAliases();
        if ([] === $rootAliases) {
            throw new InvalidPaginationException('Query builder must have a root alias');
        }

        return $rootAliases[0];
    }

    /**
     * 设置排序字段
     */
    private function setupOrderByFields(QueryBuilder $qb, string $rootAlias): void
    {
        if ([] !== $this->orderFields) {
            foreach ($this->orderFields as $field) {
                $qb->orderBy("{$rootAlias}.{$field}", 'ASC');
            }
        }
    }

    /**
     * 应用游标条件
     */
    private function applyCursorCondition(QueryBuilder $qb, mixed $lastCursor, string $rootAlias): void
    {
        $orderBy = $qb->getDQLPart('orderBy') ?? [];
        $hasMultiFieldCursor = is_array($lastCursor) && count($lastCursor) > 1;

        if ([] === $orderBy) {
            $orderBy = $this->inferOrderingFromCursor($qb, $lastCursor, $rootAlias, $hasMultiFieldCursor);
        }

        $this->addSearchAfterCondition($qb, $lastCursor, $rootAlias, $orderBy);
    }

    /**
     * 从游标推断排序
     * @return array<string, mixed>
     */
    private function inferOrderingFromCursor(QueryBuilder $qb, mixed $lastCursor, string $rootAlias, bool $hasMultiFieldCursor): array
    {
        if ($hasMultiFieldCursor) {
            foreach ($lastCursor as $field => $value) {
                $qb->orderBy("{$rootAlias}.{$field}", 'ASC');
            }
            $qb->addOrderBy("{$rootAlias}.{$this->idField}", 'ASC');

            return ['multi_field_cursor' => true];
        }
        $qb->orderBy("{$rootAlias}.{$this->idField}", 'ASC');

        return $qb->getDQLPart('orderBy') ?? [];
    }

    /**
     * 确保默认排序
     */
    private function ensureDefaultOrdering(QueryBuilder $qb, string $rootAlias): void
    {
        $orderBy = $qb->getDQLPart('orderBy');
        if ([] === $orderBy && [] === $this->orderFields) {
            $qb->orderBy("{$rootAlias}.{$this->idField}", 'ASC');
        }
    }

    /**
     * 添加 Search After 条件
     * @param array<string, mixed>|null $orderBy
     */
    private function addSearchAfterCondition(QueryBuilder $qb, mixed $lastCursor, string $rootAlias, ?array $orderBy): void
    {
        $hasMultiFieldCursor = is_array($lastCursor) && count($lastCursor) > 1;
        $isMultiFieldCursorMarker = null !== $orderBy && [] !== $orderBy && isset($orderBy['multi_field_cursor']);

        if (null === $orderBy || [] === $orderBy || (!$hasMultiFieldCursor && !$isMultiFieldCursorMarker)) {
            $this->addSimpleCursorCondition($qb, $lastCursor, $rootAlias);

            return;
        }

        if ($isMultiFieldCursorMarker) {
            $this->addMultiFieldCursorMarkerCondition($qb, $lastCursor, $rootAlias);

            return;
        }

        $this->addOrderBasedCursorCondition($qb, $lastCursor, $rootAlias, $orderBy);
    }

    /**
     * 添加简单游标条件
     */
    private function addSimpleCursorCondition(QueryBuilder $qb, mixed $lastCursor, string $rootAlias): void
    {
        $idValue = is_array($lastCursor) ? ($lastCursor[$this->idField] ?? $lastCursor) : $lastCursor;
        $qb->andWhere("{$rootAlias}.{$this->idField} > :cursor_{$this->idField}")
            ->setParameter("cursor_{$this->idField}", $idValue)
        ;
    }

    /**
     * 添加多字段游标标记条件
     */
    private function addMultiFieldCursorMarkerCondition(QueryBuilder $qb, mixed $lastCursor, string $rootAlias): void
    {
        $hasCreatedAt = isset($lastCursor['created_at']);
        $hasId = isset($lastCursor[$this->idField]);

        if ($hasCreatedAt && $hasId) {
            $qb->andWhere("({$rootAlias}.created_at > :cursor_created_at OR ({$rootAlias}.created_at = :cursor_created_at AND {$rootAlias}.{$this->idField} > :cursor_{$this->idField}))");
            $qb->andWhere('1=1');
            $qb->setParameter('cursor_created_at', $lastCursor['created_at'])
                ->setParameter("cursor_{$this->idField}", $lastCursor[$this->idField])
            ;
        }
    }

    /**
     * 添加基于排序的游标条件
     * @param array<string, mixed> $orderBy
     */
    private function addOrderBasedCursorCondition(QueryBuilder $qb, mixed $lastCursor, string $rootAlias, array $orderBy): void
    {
        $orderFields = $this->extractOrderFields($orderBy, $rootAlias);

        if (is_array($lastCursor) && count($lastCursor) > 1) {
            $this->addMultiFieldCursorCondition($qb, $lastCursor, $rootAlias, $orderFields);
        } else {
            $this->addSingleFieldCursorCondition($qb, $lastCursor, $rootAlias);
        }
    }

    /**
     * 提取排序字段信息
     * @param array<mixed> $orderBy
     * @return array<int, array<string, mixed>>
     */
    private function extractOrderFields(array $orderBy, string $rootAlias): array
    {
        $orderFields = [];
        foreach ($orderBy as $order) {
            $field = $order->expr;
            $fieldName = $this->extractFieldName($field, $rootAlias);
            $orderFields[] = ['field' => $field, 'fieldName' => $fieldName, 'asc' => $order->asc];
        }

        return $orderFields;
    }

    /**
     * 添加多字段游标条件
     * @param array<string, mixed> $lastCursor
     * @param array<int, array<string, mixed>> $orderFields
     */
    private function addMultiFieldCursorCondition(QueryBuilder $qb, array $lastCursor, string $rootAlias, array $orderFields): void
    {
        $hasCreatedAt = isset($lastCursor['created_at']);
        $hasId = isset($lastCursor[$this->idField]);

        if ($hasCreatedAt && $hasId) {
            $qb->andWhere("({$rootAlias}.created_at > :cursor_created_at OR ({$rootAlias}.created_at = :cursor_created_at AND {$rootAlias}.{$this->idField} > :cursor_{$this->idField}))");

            if (count($orderFields) > 1) {
                $qb->andWhere("{$rootAlias}.{$this->idField} > :cursor_{$this->idField}");
            }

            $qb->setParameter('cursor_created_at', $lastCursor['created_at'])
                ->setParameter("cursor_{$this->idField}", $lastCursor[$this->idField])
            ;
        } else {
            foreach ($lastCursor as $fieldName => $value) {
                $qb->andWhere("{$rootAlias}.{$fieldName} > :cursor_{$fieldName}")
                    ->setParameter("cursor_{$fieldName}", $value)
                ;
            }
        }
    }

    /**
     * 添加单字段游标条件
     */
    private function addSingleFieldCursorCondition(QueryBuilder $qb, mixed $lastCursor, string $rootAlias): void
    {
        $fieldName = $this->idField;
        $value = is_array($lastCursor) ? ($lastCursor[$fieldName] ?? null) : $lastCursor;

        if (null !== $value) {
            $qb->andWhere("{$rootAlias}.{$fieldName} > :cursor_{$fieldName}")
                ->setParameter("cursor_{$fieldName}", $value)
            ;
        }
    }

    /**
     * 从字段表达式中提取字段名
     */
    private function extractFieldName(string $field, string $rootAlias): string
    {
        // 如果包含别名，去掉别名
        if (str_contains($field, '.')) {
            [, $fieldName] = explode('.', $field, 2);

            return $fieldName;
        }

        return $field;
    }

    /**
     * 获取总条数（游标分页通常不需要精确的总数）
     */
    private function getTotalItems(): int
    {
        // 游标分页场景下，获取精确总数可能很昂贵
        // 返回0表示总数未知，前端应该根据hasMore判断
        return 0;
    }
}
