<?php

declare(strict_types=1);

namespace Tourze\DoctrinePaginatorBundle\Tests\Paginator;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrinePaginatorBundle\Model\PaginationResult;
use Tourze\DoctrinePaginatorBundle\Paginator\OffsetPaginator;

/**
 * @internal
 */
#[CoversClass(OffsetPaginator::class)]
final class OffsetPaginatorTest extends TestCase
{
    private OffsetPaginator&MockObject $paginator;

    private QueryBuilder&MockObject $queryBuilder;

    private Query&MockObject $query;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);

        $this->queryBuilder->method('getQuery')->willReturn($this->query);

        // Create a partial mock that stubs createDoctrinePaginator
        $this->paginator = $this->getMockBuilder(OffsetPaginator::class)
            ->setConstructorArgs([$this->queryBuilder])
            ->onlyMethods(['createDoctrinePaginator'])
            ->getMock()
        ;
    }

    public function testPaginateFirstPage(): void
    {
        $items = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
        ];

        // The paginate method clones the QueryBuilder, but clone retains mock expectations
        // We expect these methods to be called on the cloned object
        $this->queryBuilder->expects($this->once())
            ->method('setFirstResult')
            ->with(0)
            ->willReturnSelf()
        ;

        $this->queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(10)
            ->willReturnSelf()
        ;

        // Mock DoctrinePaginator
        $doctrinePaginator = $this->createMock(DoctrinePaginator::class);
        $doctrinePaginator->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($items))
        ;
        $doctrinePaginator->expects($this->once())
            ->method('count')
            ->willReturn(42)
        ;

        $this->paginator->expects($this->once())
            ->method('createDoctrinePaginator')
            ->willReturn($doctrinePaginator)
        ;

        $result = $this->paginator->paginate(1, 10);

        $this->assertInstanceOf(PaginationResult::class, $result);
        $this->assertSame($items, $result->getItems());
        $this->assertSame(42, $result->getTotalItems());
        $this->assertSame(1, $result->getCurrentPage());
        $this->assertSame(10, $result->getPageSize());
        $this->assertSame(5, $result->getTotalPages());
    }

    public function testPaginateMiddlePage(): void
    {
        $items = [
            ['id' => 3, 'name' => 'Item 3'],
            ['id' => 4, 'name' => 'Item 4'],
        ];

        // The paginate method clones the QueryBuilder, but clone retains mock expectations
        $this->queryBuilder->expects($this->once())
            ->method('setFirstResult')
            ->with(20)
            ->willReturnSelf()
        ;

        $this->queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(10)
            ->willReturnSelf()
        ;

        // Mock DoctrinePaginator
        $doctrinePaginator = $this->createMock(DoctrinePaginator::class);
        $doctrinePaginator->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($items))
        ;
        $doctrinePaginator->expects($this->once())
            ->method('count')
            ->willReturn(100)
        ;

        $this->paginator->expects($this->once())
            ->method('createDoctrinePaginator')
            ->willReturn($doctrinePaginator)
        ;

        $result = $this->paginator->paginate(3, 10);

        $this->assertInstanceOf(PaginationResult::class, $result);
        $this->assertSame($items, $result->getItems());
        $this->assertSame(100, $result->getTotalItems());
        $this->assertSame(3, $result->getCurrentPage());
        $this->assertSame(10, $result->getPageSize());
        $this->assertSame(10, $result->getTotalPages());
    }

    public function testPaginateAfter(): void
    {
        $items = [
            ['id' => 101, 'name' => 'Item 101'],
            ['id' => 102, 'name' => 'Item 102'],
        ];

        // The paginateAfter method calls paginate(1, 10), which clones the QueryBuilder
        $this->queryBuilder->expects($this->once())
            ->method('setFirstResult')
            ->with(0)
            ->willReturnSelf()
        ;

        $this->queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(10)
            ->willReturnSelf()
        ;

        // Mock DoctrinePaginator
        $doctrinePaginator = $this->createMock(DoctrinePaginator::class);
        $doctrinePaginator->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($items))
        ;
        $doctrinePaginator->expects($this->once())
            ->method('count')
            ->willReturn(0)
        ;

        $this->paginator->expects($this->once())
            ->method('createDoctrinePaginator')
            ->willReturn($doctrinePaginator)
        ;

        $result = $this->paginator->paginateAfter(['id' => 100], 10);

        $this->assertInstanceOf(PaginationResult::class, $result);
        $this->assertSame($items, $result->getItems());
        $this->assertSame(0, $result->getTotalItems());
        $this->assertSame(1, $result->getCurrentPage());
        $this->assertSame(10, $result->getPageSize());
        $this->assertSame(0, $result->getTotalPages());
    }

    public function testPaginateEmptyResult(): void
    {
        // The paginate method clones the QueryBuilder, but clone retains mock expectations
        $this->queryBuilder->expects($this->once())
            ->method('setFirstResult')
            ->with(0)
            ->willReturnSelf()
        ;

        $this->queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(10)
            ->willReturnSelf()
        ;

        // Mock DoctrinePaginator
        $doctrinePaginator = $this->createMock(DoctrinePaginator::class);
        $doctrinePaginator->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([]))
        ;
        $doctrinePaginator->expects($this->once())
            ->method('count')
            ->willReturn(0)
        ;

        $this->paginator->expects($this->once())
            ->method('createDoctrinePaginator')
            ->willReturn($doctrinePaginator)
        ;

        $result = $this->paginator->paginate(1, 10);

        $this->assertInstanceOf(PaginationResult::class, $result);
        $this->assertSame([], $result->getItems());
        $this->assertSame(0, $result->getTotalItems());
        $this->assertSame(1, $result->getCurrentPage());
        $this->assertSame(10, $result->getPageSize());
        $this->assertSame(0, $result->getTotalPages());
    }

    public function testClearCache(): void
    {
        // Create a real instance for testing clearCache
        $realPaginator = new OffsetPaginator($this->queryBuilder);

        // Clear cache returns void
        $realPaginator->clearCache();
        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    public function testGetAndSetQueryBuilder(): void
    {
        $newQueryBuilder = $this->createMock(QueryBuilder::class);

        $realPaginator = new OffsetPaginator($this->queryBuilder);

        $this->assertSame($this->queryBuilder, $realPaginator->getQueryBuilder());

        $realPaginator->setQueryBuilder($newQueryBuilder);

        // setQueryBuilder returns void, no need to check return value
        $this->assertSame($newQueryBuilder, $realPaginator->getQueryBuilder());
    }

    public function testGetStrategy(): void
    {
        $realPaginator = new OffsetPaginator($this->queryBuilder);
        $this->assertSame('offset', $realPaginator->getStrategy());
    }
}
