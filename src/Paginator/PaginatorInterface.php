<?php

declare(strict_types=1);

namespace Tourze\DoctrinePaginatorBundle\Paginator;

use Doctrine\ORM\QueryBuilder;
use Tourze\DoctrinePaginatorBundle\Model\PaginationResult;

/**
 * 分页器接口
 */
interface PaginatorInterface
{
    /**
     * 执行分页查询
     *
     * @param int $page 当前页码（从1开始）
     * @param int $pageSize 每页大小
     */
    public function paginate(int $page, int $pageSize): PaginationResult;

    /**
     * 执行游标分页查询
     *
     * @param mixed $lastCursor 上一页的最后一条记录的游标
     * @param int $pageSize 每页大小
     */
    public function paginateAfter(mixed $lastCursor, int $pageSize): PaginationResult;

    /**
     * 获取查询构建器
     */
    public function getQueryBuilder(): QueryBuilder;

    /**
     * 设置查询构建器
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder): void;

    /**
     * 获取分页策略名称
     */
    public function getStrategy(): string;
}
