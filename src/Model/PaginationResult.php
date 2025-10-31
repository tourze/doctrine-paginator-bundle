<?php

declare(strict_types=1);

namespace Tourze\DoctrinePaginatorBundle\Model;

/**
 * 分页结果
 */
class PaginationResult
{
    /**
     * @param array<int, mixed> $items 当前页的数据
     * @param int $currentPage 当前页码
     * @param int $pageSize 每页大小
     * @param int $total 总记录数
     * @param int $totalPages 总页数
     * @param bool $hasMore 是否还有更多数据（用于游标分页）
     * @param mixed $lastItem 最后一项的数据（用于游标分页）
     */
    public function __construct(
        private array $items = [],
        private int $currentPage = 1,
        private int $pageSize = 10,
        private int $total = 0,
        private int $totalPages = 0,
        private bool $hasMore = false,
        private mixed $lastItem = null,
    ) {
        $this->totalPages = $pageSize > 0 ? (int) ceil($total / $pageSize) : 0;
    }

    /**
     * 创建空分页结果
     */
    public static function empty(int $currentPage = 1, int $pageSize = 10): self
    {
        return new self([], $currentPage, $pageSize, 0, 0, false);
    }

    /**
     * 创建分页结果
     * @param array<int, mixed> $items
     */
    public static function create(
        array $items = [],
        int $currentPage = 1,
        int $pageSize = 10,
        int $total = 0,
        int $totalPages = 0,
        bool $hasMore = false,
        mixed $lastItem = null,
    ): self {
        return new self($items, $currentPage, $pageSize, $total, $totalPages, $hasMore, $lastItem);
    }

    /**
     * 获取当前页的数据
     * @return array<int, mixed>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * 获取当前页码
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * 获取每页大小
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * 获取总记录数
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * 获取总记录数（getTotalItems是getTotal的别名）
     */
    public function getTotalItems(): int
    {
        return $this->total;
    }

    /**
     * 获取总页数
     */
    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    /**
     * 是否还有更多数据（用于游标分页）
     */
    public function hasMore(): bool
    {
        return $this->hasMore;
    }

    /**
     * 获取最后一项的数据（用于游标分页）
     */
    public function getLastItem(): mixed
    {
        return $this->lastItem;
    }

    /**
     * 获取最后的游标（用于游标分页）
     * @return array<string, mixed>|null
     */
    public function getLastCursor(): ?array
    {
        if (null === $this->lastItem) {
            return null;
        }

        // 如果lastItem已经是数组格式
        if (is_array($this->lastItem)) {
            // 如果数组包含created_at字段，这是多字段游标，返回完整的数组
            if (isset($this->lastItem['created_at'])) {
                return $this->lastItem;
            }

            // 如果数组包含id字段，只返回id作为游标
            if (isset($this->lastItem['id'])) {
                return ['id' => $this->lastItem['id']];
            }

            // 否则返回整个数组
            return $this->lastItem;
        }

        // 如果是对象，尝试从中提取id作为游标
        if (is_object($this->lastItem)) {
            if (method_exists($this->lastItem, 'getId')) {
                return ['id' => $this->lastItem->getId()];
            }
        }

        // 如果是基本类型，包装成数组
        return ['value' => $this->lastItem];
    }

    /**
     * 转换为数组
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'currentPage' => $this->currentPage,
            'pageSize' => $this->pageSize,
            'total' => $this->total,
            'totalPages' => $this->totalPages,
            'hasMore' => $this->hasMore,
            'lastItem' => $this->lastItem,
        ];
    }
}
