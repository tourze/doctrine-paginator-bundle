<?php

declare(strict_types=1);

namespace Tourze\DoctrinePaginatorBundle\Paginator;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Tourze\DoctrinePaginatorBundle\Model\PaginationResult;

/**
 * SubQuery 分页器 - 使用子查询优化深度分页
 * 适用于中等数据量场景，通过子查询减少回表操作
 */
class SubQueryPaginator implements PaginatorInterface
{
    private QueryBuilder $queryBuilder;

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
        return PaginatorStrategies::STRATEGY_SUB_QUERY;
    }

    public function paginateAfter(mixed $lastCursor, int $pageSize): PaginationResult
    {
        // SubQuery 分页器不支持游标分页，返回第一页但不计算总数
        $qb = clone $this->queryBuilder;
        $qb->setFirstResult(0)
            ->setMaxResults($pageSize)
        ;

        $qb->getQuery()->setHint('doctrine.customOutputWalker', 'Doctrine\ORM\Tools\Pagination\CountOutputWalker');

        $doctrinePaginator = $this->createDoctrinePaginator($qb);
        $items = iterator_to_array($doctrinePaginator);

        // 获取最后一项
        $lastItem = [] !== $items ? end($items) : null;

        return new PaginationResult(
            items: $items,
            currentPage: 1,
            pageSize: $pageSize,
            total: 0,
            totalPages: 0,
            hasMore: count($items) === $pageSize,
            lastItem: $lastItem
        );
    }

    public function paginate(int $page, int $pageSize): PaginationResult
    {
        $offset = ($page - 1) * $pageSize;

        // 使用 DoctrinePaginator 进行分页
        $qb = clone $this->queryBuilder;
        $qb->setFirstResult($offset)
            ->setMaxResults($pageSize)
        ;

        $qb->getQuery()->setHint('doctrine.customOutputWalker', 'Doctrine\ORM\Tools\Pagination\CountOutputWalker');

        $doctrinePaginator = $this->createDoctrinePaginator($qb);
        $items = iterator_to_array($doctrinePaginator);

        // 获取总条数
        $total = $this->getTotalItems();

        // 获取最后一项
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
     */
    private function getTotalItems(): int
    {
        if (null !== $this->totalItems) {
            return $this->totalItems;
        }

        $countQb = $this->createCountQueryBuilder();
        $this->totalItems = (int) $countQb->getQuery()->getSingleScalarResult();

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
        return new DoctrinePaginator($qb);
    }

    /**
     * 创建计数查询构建器
     */
    protected function createCountQueryBuilder(): QueryBuilder
    {
        $countQb = clone $this->queryBuilder;
        $countQb->resetDQLPart('select')
            ->resetDQLPart('orderBy')
        ;

        $rootAliases = $countQb->getRootAliases();
        if ([] !== $rootAliases) {
            $rootAlias = $rootAliases[0];
            $countQb->select("COUNT({$rootAlias}.id)");
        }

        return $countQb;
    }
}
