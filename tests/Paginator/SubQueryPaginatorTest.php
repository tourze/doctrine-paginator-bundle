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
use Tourze\DoctrinePaginatorBundle\Paginator\SubQueryPaginator;

/**
 * @internal
 */
#[CoversClass(SubQueryPaginator::class)]
final class SubQueryPaginatorTest extends TestCase
{
    private SubQueryPaginator&MockObject $paginator;

    private QueryBuilder&MockObject $queryBuilder;

    private Query&MockObject $query;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);

        $this->queryBuilder->method('getQuery')->willReturn($this->query);
        $this->queryBuilder->method('getRootAliases')->willReturn(['o']);
        $this->queryBuilder->method('setFirstResult')->willReturnSelf();
        $this->queryBuilder->method('setMaxResults')->willReturnSelf();
        $this->query->method('setHint')->willReturnSelf();

        // Create a partial mock that stubs createDoctrinePaginator and createCountQueryBuilder
        $this->paginator = $this->getMockBuilder(SubQueryPaginator::class)
            ->setConstructorArgs([$this->queryBuilder])
            ->onlyMethods(['createDoctrinePaginator', 'createCountQueryBuilder'])
            ->getMock()
        ;
    }

    public function testPaginateFirstPage(): void
    {
        $items = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
        ];

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

        $this->query->expects($this->once())
            ->method('setHint')
            ->with('doctrine.customOutputWalker', 'Doctrine\ORM\Tools\Pagination\CountOutputWalker')
        ;

        // Mock DoctrinePaginator
        $doctrinePaginator = $this->createMock(DoctrinePaginator::class);
        $doctrinePaginator->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($items))
        ;

        $this->paginator->expects($this->once())
            ->method('createDoctrinePaginator')
            ->willReturn($doctrinePaginator)
        ;

        // Mock count query
        $countQuery = $this->createMock(Query::class);
        $countQuery->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(50)
        ;

        $countQb = $this->createMock(QueryBuilder::class);
        $countQb->expects($this->once())
            ->method('getQuery')
            ->willReturn($countQuery)
        ;

        $this->paginator->expects($this->once())
            ->method('createCountQueryBuilder')
            ->willReturn($countQb)
        ;

        $result = $this->paginator->paginate(1, 10);

        $this->assertInstanceOf(PaginationResult::class, $result);
        $this->assertSame($items, $result->getItems());
        $this->assertSame(50, $result->getTotalItems());
        $this->assertSame(1, $result->getCurrentPage());
        $this->assertSame(10, $result->getPageSize());
        $this->assertSame(5, $result->getTotalPages());
    }

    public function testPaginateMiddlePage(): void
    {
        $items = [
            ['id' => 21, 'name' => 'Item 21'],
            ['id' => 22, 'name' => 'Item 22'],
        ];

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

        $this->query->expects($this->once())
            ->method('setHint')
            ->with('doctrine.customOutputWalker', 'Doctrine\ORM\Tools\Pagination\CountOutputWalker')
        ;

        // Mock DoctrinePaginator
        $doctrinePaginator = $this->createMock(DoctrinePaginator::class);
        $doctrinePaginator->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($items))
        ;

        $this->paginator->expects($this->once())
            ->method('createDoctrinePaginator')
            ->willReturn($doctrinePaginator)
        ;

        // Mock count query
        $countQuery = $this->createMock(Query::class);
        $countQuery->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(100)
        ;

        $countQb = $this->createMock(QueryBuilder::class);
        $countQb->expects($this->once())
            ->method('getQuery')
            ->willReturn($countQuery)
        ;

        $this->paginator->expects($this->once())
            ->method('createCountQueryBuilder')
            ->willReturn($countQb)
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

        $this->query->expects($this->once())
            ->method('setHint')
            ->with('doctrine.customOutputWalker', 'Doctrine\ORM\Tools\Pagination\CountOutputWalker')
        ;

        // Mock DoctrinePaginator
        $doctrinePaginator = $this->createMock(DoctrinePaginator::class);
        $doctrinePaginator->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($items))
        ;

        $this->paginator->expects($this->once())
            ->method('createDoctrinePaginator')
            ->willReturn($doctrinePaginator)
        ;

        // For paginateAfter, we don't use count
        $this->paginator->expects($this->never())
            ->method('createCountQueryBuilder')
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

        $this->query->expects($this->once())
            ->method('setHint')
            ->with('doctrine.customOutputWalker', 'Doctrine\ORM\Tools\Pagination\CountOutputWalker')
        ;

        // Mock DoctrinePaginator
        $doctrinePaginator = $this->createMock(DoctrinePaginator::class);
        $doctrinePaginator->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([]))
        ;

        $this->paginator->expects($this->once())
            ->method('createDoctrinePaginator')
            ->willReturn($doctrinePaginator)
        ;

        // Mock count query
        $countQuery = $this->createMock(Query::class);
        $countQuery->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(0)
        ;

        $countQb = $this->createMock(QueryBuilder::class);
        $countQb->expects($this->once())
            ->method('getQuery')
            ->willReturn($countQuery)
        ;

        $this->paginator->expects($this->once())
            ->method('createCountQueryBuilder')
            ->willReturn($countQb)
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
        $realPaginator = new SubQueryPaginator($this->queryBuilder);

        // Clear cache should not return anything (void)
        $realPaginator->clearCache();

        // Verify that clearCache executed without throwing exceptions
        $this->assertTrue(true);
    }

    public function testGetAndSetQueryBuilder(): void
    {
        $newQueryBuilder = $this->createMock(QueryBuilder::class);

        $realPaginator = new SubQueryPaginator($this->queryBuilder);

        $this->assertSame($this->queryBuilder, $realPaginator->getQueryBuilder());

        $realPaginator->setQueryBuilder($newQueryBuilder);
        $this->assertSame($newQueryBuilder, $realPaginator->getQueryBuilder());
    }

    public function testGetStrategy(): void
    {
        $realPaginator = new SubQueryPaginator($this->queryBuilder);
        $this->assertSame('sub_query', $realPaginator->getStrategy());
    }
}
