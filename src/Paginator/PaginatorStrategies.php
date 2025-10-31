<?php

declare(strict_types=1);

namespace Tourze\DoctrinePaginatorBundle\Paginator;

/**
 * 分页策略常量
 */
final class PaginatorStrategies
{
    /**
     * Offset 分页策略：使用 LIMIT offset, size
     * 适用于小数据量场景（offset < 10,000）
     */
    public const STRATEGY_OFFSET = 'offset';

    /**
     * 子查询分页策略：先定位再查询
     * 适用于中等数据量场景（offset 在 10,000-1,000,000 之间）
     */
    public const STRATEGY_SUB_QUERY = 'sub_query';

    /**
     * 游标分页策略：基于上一页最后一条记录查询
     * 适用于大数据量场景（offset > 1,000,000）
     */
    public const STRATEGY_SEARCH_AFTER = 'search_after';

    /**
     * 自动选择策略：根据查询特征自动选择最优策略
     */
    public const STRATEGY_AUTO = 'auto';

    /**
     * 获取所有支持的策略
     * @return array<string>
     */
    public static function getAllStrategies(): array
    {
        return [
            self::STRATEGY_OFFSET,
            self::STRATEGY_SUB_QUERY,
            self::STRATEGY_SEARCH_AFTER,
            self::STRATEGY_AUTO,
        ];
    }
}
