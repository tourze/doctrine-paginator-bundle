<?php

declare(strict_types=1);

namespace Tourze\DoctrinePaginatorBundle\Paginator;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Tourze\DoctrinePaginatorBundle\Model\PaginationResult;

/**
 * Offset 分页器 - 使用传统的 LIMIT offset, size 方式
 * 适用于小数据量场景
 */
class OffsetPaginator implements PaginatorInterface
{
    private QueryBuilder $queryBuilder;

    private bool $fetchJoinCollection = true;

    private ?int $totalItems = null;

    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function setQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $this->queryBuilder = $queryBuilder;
        $this->totalItems = null;
    }

    public function getStrategy(): string
    {
        return PaginatorStrategies::STRATEGY_OFFSET;
    }

    /**
     * 设置是否获取关联集合
     */
    public function setFetchJoinCollection(bool $fetchJoinCollection): void
    {
        $this->fetchJoinCollection = $fetchJoinCollection;
    }

    public function paginateAfter(mixed $lastCursor, int $pageSize): PaginationResult
    {
        // Offset 分页器不支持游标分页，回退到第一页
        return $this->paginate(1, $pageSize);
    }

    public function paginate(int $page, int $pageSize): PaginationResult
    {
        $offset = ($page - 1) * $pageSize;

        // 创建分页查询
        $qb = clone $this->queryBuilder;

        // 设置分页
        $qb->setFirstResult($offset)
            ->setMaxResults($pageSize)
        ;

        // 执行查询
        $doctrinePaginator = $this->createDoctrinePaginator($qb);
        $items = iterator_to_array($doctrinePaginator);

        // 获取总条数
        $total = $this->getTotalItems($doctrinePaginator);

        // 获取最后一项（用于游标分页）
        $lastItem = [] !== $items ? end($items) : null;

        return new PaginationResult(
            items: $items,
            currentPage: $page,
            pageSize: $pageSize,
            total: $total,
            totalPages: (int) ceil($total / $pageSize),
            hasMore: ($offset + $pageSize) < $total,
            lastItem: $lastItem
        );
    }

    /**
     * 获取总条数（带缓存）
     * @param DoctrinePaginator<object> $paginator
     */
    private function getTotalItems(DoctrinePaginator $paginator): int
    {
        if (null !== $this->totalItems) {
            return $this->totalItems;
        }

        $this->totalItems = count($paginator);

        return $this->totalItems;
    }

    /**
     * 清除总条数缓存
     */
    public function clearCache(): void
    {
        $this->totalItems = null;
    }

    /**
     * 创建 Doctrine Paginator 实例
     * 提取为方法以便于测试
     * @return DoctrinePaginator<object>
     */
    protected function createDoctrinePaginator(QueryBuilder $qb): DoctrinePaginator
    {
        return new DoctrinePaginator($qb, $this->fetchJoinCollection);
    }
}
