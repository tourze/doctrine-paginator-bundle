<?php

declare(strict_types=1);

namespace Tourze\DoctrinePaginatorBundle\Helper;

use Doctrine\ORM\QueryBuilder;
use Tourze\DoctrinePaginatorBundle\Model\PaginationResult;
use Tourze\DoctrinePaginatorBundle\Paginator\PaginatorInterface;
use Tourze\DoctrinePaginatorBundle\Service\PaginatorFactory;

/**
 * 分页助手类 - 提供便捷的分页操作方法
 */
class PaginationHelper
{
    private PaginatorFactory $paginatorFactory;

    public function __construct(PaginatorFactory $paginatorFactory)
    {
        $this->paginatorFactory = $paginatorFactory;
    }

    /**
     * 自动分页 - 根据查询特征自动选择最优策略
     */
    public function paginateAuto(QueryBuilder $queryBuilder, int $page, int $pageSize): PaginationResult
    {
        $paginator = $this->paginatorFactory->createAuto($queryBuilder);

        return $paginator->paginate($page, $pageSize);
    }

    /**
     * 智能分页 - 自动判断使用哪种方式
     * 如果有 lastCursor 则使用游标分页，否则使用页码分页
     */
    public function paginate(QueryBuilder $queryBuilder, int $page, int $pageSize, mixed $lastCursor = null): PaginationResult
    {
        if (null !== $lastCursor) {
            return $this->paginateAfter($queryBuilder, $lastCursor, $pageSize);
        }

        return $this->paginateAuto($queryBuilder, $page, $pageSize);
    }

    /**
     * 游标分页 - 基于上一页最后一条记录
     */
    public function paginateAfter(QueryBuilder $queryBuilder, mixed $lastCursor, int $pageSize): PaginationResult
    {
        $paginator = $this->paginatorFactory->createSearchAfter($queryBuilder);

        return $paginator->paginateAfter($lastCursor, $pageSize);
    }

    /**
     * Offset 分页 - 传统分页方式
     */
    public function paginateOffset(QueryBuilder $queryBuilder, int $page, int $pageSize): PaginationResult
    {
        $paginator = $this->paginatorFactory->createOffset($queryBuilder);

        return $paginator->paginate($page, $pageSize);
    }

    /**
     * 创建分页器实例
     */
    public function createPaginator(QueryBuilder $queryBuilder, ?string $strategy = null): PaginatorInterface
    {
        return $this->paginatorFactory->create($queryBuilder, $strategy);
    }

    /**
     * 快速分页 - 适用于简单的 Controller 场景
     * @param array{page?: int, pageSize?: int, lastCursor?: mixed} $params
     * @return array<string, mixed>
     */
    public function quickPaginate(QueryBuilder $queryBuilder, array $params = []): array
    {
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['pageSize'] ?? 10);
        $lastCursor = $params['lastCursor'] ?? null;

        return $this->paginateToArray($queryBuilder, $page, $pageSize, $lastCursor);
    }

    /**
     * 获取分页数据（数组格式）
     * @return array<string, mixed>
     */
    public function paginateToArray(QueryBuilder $queryBuilder, int $page, int $pageSize, mixed $lastCursor = null): array
    {
        $result = $this->paginate($queryBuilder, $page, $pageSize, $lastCursor);

        return $result->toArray();
    }
}
