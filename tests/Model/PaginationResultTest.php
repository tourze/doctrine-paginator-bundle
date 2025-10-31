<?php

namespace Tourze\DoctrinePaginatorBundle\Tests\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrinePaginatorBundle\Model\PaginationResult;

/**
 * @internal
 */
#[CoversClass(PaginationResult::class)]
final class PaginationResultTest extends TestCase
{
    public function testCreateEmptyResult(): void
    {
        $result = PaginationResult::empty();

        $this->assertSame([], $result->getItems());
        $this->assertSame(1, $result->getCurrentPage());
        $this->assertSame(10, $result->getPageSize());
        $this->assertSame(0, $result->getTotal());
        $this->assertSame(0, $result->getTotalPages());
        $this->assertFalse($result->hasMore());
        $this->assertNull($result->getLastItem());
    }

    public function testCreateWithCustomPageAndSize(): void
    {
        $result = PaginationResult::empty(2, 20);

        $this->assertSame(2, $result->getCurrentPage());
        $this->assertSame(20, $result->getPageSize());
    }

    public function testCreateWithItems(): void
    {
        $items = ['item1', 'item2', 'item3'];
        $result = new PaginationResult(
            items: $items,
            currentPage: 1,
            pageSize: 10,
            total: 100,
            totalPages: 10,
            hasMore: true,
            lastItem: 'item3'
        );

        $this->assertSame($items, $result->getItems());
        $this->assertSame(1, $result->getCurrentPage());
        $this->assertSame(10, $result->getPageSize());
        $this->assertSame(100, $result->getTotal());
        $this->assertSame(10, $result->getTotalPages());
        $this->assertTrue($result->hasMore());
        $this->assertSame('item3', $result->getLastItem());
    }

    public function testToArray(): void
    {
        $items = ['item1', 'item2'];
        $result = new PaginationResult(
            items: $items,
            currentPage: 2,
            pageSize: 5,
            total: 12,
            totalPages: 3,
            hasMore: false,
            lastItem: 'item2'
        );

        $expected = [
            'items' => $items,
            'currentPage' => 2,
            'pageSize' => 5,
            'total' => 12,
            'totalPages' => 3,
            'hasMore' => false,
            'lastItem' => 'item2',
        ];

        $this->assertSame($expected, $result->toArray());
    }

    public function testTotalPagesCalculation(): void
    {
        // 测试整除
        $result1 = new PaginationResult(
            items: [],
            currentPage: 1,
            pageSize: 10,
            total: 100,
        );
        $this->assertSame(10, $result1->getTotalPages());

        // 测试有余数
        $result2 = new PaginationResult(
            items: [],
            currentPage: 1,
            pageSize: 10,
            total: 105,
        );
        $this->assertSame(11, $result2->getTotalPages());

        // 测试空结果
        $result3 = new PaginationResult(
            items: [],
            currentPage: 1,
            pageSize: 0,
            total: 100,
        );
        $this->assertSame(0, $result3->getTotalPages());
    }
}
