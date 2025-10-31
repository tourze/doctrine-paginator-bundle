<?php

declare(strict_types=1);

namespace Tourze\DoctrinePaginatorBundle\Tests\Paginator;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrinePaginatorBundle\Model\PaginationResult;
use Tourze\DoctrinePaginatorBundle\Paginator\SearchAfterPaginator;

/**
 * @internal
 */
#[CoversClass(SearchAfterPaginator::class)]
final class SearchAfterPaginatorTest extends TestCase
{
    private SearchAfterPaginator $paginator;

    private QueryBuilder&MockObject $queryBuilder;

    private Query&MockObject $query;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);

        $this->queryBuilder->method('getQuery')->willReturn($this->query);
        $this->queryBuilder->method('getRootAliases')->willReturn(['o']);
        $this->queryBuilder->method('getDQLPart')->with('orderBy')->willReturn([]);
        $this->queryBuilder->method('orderBy')->willReturnSelf();
        $this->queryBuilder->method('setMaxResults')->willReturnSelf();
        $this->queryBuilder->method('andWhere')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();

        $this->paginator = new SearchAfterPaginator($this->queryBuilder);
    }

    public function testPaginateFirstPage(): void
    {
        $items = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
        ];

        $this->queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(11) // pageSize + 1 to check for more data
        ;

        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('o.id', 'ASC')
        ;

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($items)
        ;

        $result = $this->paginator->paginate(1, 10);

        $this->assertInstanceOf(PaginationResult::class, $result);
        $this->assertSame($items, $result->getItems());
        $this->assertSame(0, $result->getTotalItems());
        $this->assertSame(1, $result->getCurrentPage());
        $this->assertSame(10, $result->getPageSize());
        $this->assertSame(0, $result->getTotalPages());
        $this->assertSame(['id' => 2], $result->getLastCursor());
    }

    public function testPaginateAfterWithSingleCursor(): void
    {
        $items = [
            ['id' => 11, 'name' => 'Item 11'],
            ['id' => 12, 'name' => 'Item 12'],
        ];

        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('o.id > :cursor_id')
        ;

        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('cursor_id', 10)
        ;

        $this->queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(11) // pageSize + 1 to check for more data
        ;

        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('o.id', 'ASC')
        ;

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($items)
        ;

        $result = $this->paginator->paginateAfter(['id' => 10], 10);

        $this->assertInstanceOf(PaginationResult::class, $result);
        $this->assertSame($items, $result->getItems());
        $this->assertSame(0, $result->getTotalItems());
        $this->assertSame(1, $result->getCurrentPage());
        $this->assertSame(10, $result->getPageSize());
        $this->assertSame(['id' => 12], $result->getLastCursor());
    }

    public function testPaginateAfterWithMultipleCursors(): void
    {
        $items = [
            ['created_at' => '2024-01-01', 'id' => 101],
            ['created_at' => '2024-01-01', 'id' => 102],
        ];

        $andWhereCallCount = 0;
        $this->queryBuilder->expects($this->exactly(2))
            ->method('andWhere')
            ->willReturnCallback(function ($condition) use (&$andWhereCallCount) {
                ++$andWhereCallCount;
                if (1 === $andWhereCallCount) {
                    $this->assertSame('(o.created_at > :cursor_created_at OR (o.created_at = :cursor_created_at AND o.id > :cursor_id))', $condition);
                }

                return $this->queryBuilder;
            })
        ;

        $setParameterCallCount = 0;
        $this->queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use (&$setParameterCallCount) {
                ++$setParameterCallCount;
                if (1 === $setParameterCallCount) {
                    $this->assertSame('cursor_created_at', $name);
                    $this->assertSame('2024-01-01', $value);
                } elseif (2 === $setParameterCallCount) {
                    $this->assertSame('cursor_id', $name);
                    $this->assertSame(100, $value);
                }

                return $this->queryBuilder;
            })
        ;

        $this->queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(11) // pageSize + 1 to check for more data
        ;

        $orderByCallCount = 0;
        $this->queryBuilder->expects($this->exactly(2))
            ->method('orderBy')
            ->willReturnCallback(function ($field, $order = null) use (&$orderByCallCount) {
                ++$orderByCallCount;
                if (1 === $orderByCallCount) {
                    $this->assertSame('o.created_at', $field);
                    $this->assertSame('ASC', $order);
                }

                return $this->queryBuilder;
            })
        ;

        $this->queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('o.id', 'ASC')
        ;

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($items)
        ;

        $result = $this->paginator->paginateAfter(['created_at' => '2024-01-01', 'id' => 100], 10);

        $this->assertInstanceOf(PaginationResult::class, $result);
        $this->assertSame($items, $result->getItems());
        $this->assertSame(['created_at' => '2024-01-01', 'id' => 102], $result->getLastCursor());
    }

    public function testPaginateEmptyResult(): void
    {
        $this->queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(11) // pageSize + 1 to check for more data
        ;

        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('o.id', 'ASC')
        ;

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([])
        ;

        $result = $this->paginator->paginate(1, 10);

        $this->assertInstanceOf(PaginationResult::class, $result);
        $this->assertSame([], $result->getItems());
        $this->assertSame(0, $result->getTotalItems());
        $this->assertSame(1, $result->getCurrentPage());
        $this->assertSame(10, $result->getPageSize());
        $this->assertSame(0, $result->getTotalPages());
        $this->assertNull($result->getLastCursor());
    }

    public function testPaginateWithCustomOrderField(): void
    {
        $items = [
            ['name' => 'Alpha'],
            ['name' => 'Beta'],
        ];

        $this->queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(11) // pageSize + 1 to check for more data
        ;

        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('o.name', 'ASC')
        ;

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($items)
        ;

        $paginator = new SearchAfterPaginator($this->queryBuilder, ['name']);
        $result = $paginator->paginate(1, 10);

        $this->assertInstanceOf(PaginationResult::class, $result);
        $this->assertSame($items, $result->getItems());
        $this->assertSame(['name' => 'Beta'], $result->getLastCursor());
    }
}
