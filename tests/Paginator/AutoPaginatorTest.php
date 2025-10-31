<?php

declare(strict_types=1);

namespace Tourze\DoctrinePaginatorBundle\Tests\Paginator;

use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrinePaginatorBundle\Model\PaginationResult;
use Tourze\DoctrinePaginatorBundle\Paginator\AutoPaginator;
use Tourze\DoctrinePaginatorBundle\Paginator\OffsetPaginator;
use Tourze\DoctrinePaginatorBundle\Paginator\SearchAfterPaginator;
use Tourze\DoctrinePaginatorBundle\Paginator\SubQueryPaginator;
use Tourze\DoctrinePaginatorBundle\Service\QueryAnalyzer;

/**
 * @internal
 */
#[CoversClass(AutoPaginator::class)]
final class AutoPaginatorTest extends TestCase
{
    private AutoPaginator&MockObject $paginator;

    private QueryAnalyzer&MockObject $queryAnalyzer;

    private QueryBuilder&MockObject $queryBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queryAnalyzer = $this->createMock(QueryAnalyzer::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);

        $this->queryBuilder->method('getRootAliases')->willReturn(['o']);

        // Create a partial mock that allows us to override the factory method
        $this->paginator = $this->getMockBuilder(AutoPaginator::class)
            ->setConstructorArgs([$this->queryBuilder, $this->queryAnalyzer])
            ->onlyMethods(['createPaginatorForStrategy'])
            ->getMock()
        ;
    }

    public function testPaginateWithOffsetStrategy(): void
    {
        $items = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
        ];

        $this->queryAnalyzer->expects($this->once())
            ->method('recommendStrategy')
            ->with($this->queryBuilder, 0, null)
            ->willReturn('offset')
        ;

        // Create a mock OffsetPaginator
        $offsetPaginator = $this->createMock(OffsetPaginator::class);
        $offsetPaginator->expects($this->once())
            ->method('paginate')
            ->with(1, 10)
            ->willReturn(new PaginationResult(
                items: $items,
                currentPage: 1,
                pageSize: 10,
                total: 42,
                totalPages: 5
            ))
        ;

        $this->paginator->expects($this->once())
            ->method('createPaginatorForStrategy')
            ->with('offset')
            ->willReturn($offsetPaginator)
        ;

        $result = $this->paginator->paginate(1, 10);

        $this->assertInstanceOf(PaginationResult::class, $result);
        $this->assertSame($items, $result->getItems());
        $this->assertSame(42, $result->getTotalItems());
        $this->assertSame(1, $result->getCurrentPage());
        $this->assertSame(10, $result->getPageSize());
        $this->assertSame(5, $result->getTotalPages());
    }

    public function testPaginateWithSubQueryStrategy(): void
    {
        $items = [
            ['id' => 3, 'name' => 'Item 3'],
            ['id' => 4, 'name' => 'Item 4'],
        ];

        $this->queryAnalyzer->expects($this->once())
            ->method('recommendStrategy')
            ->with($this->queryBuilder, 40, null)
            ->willReturn('subquery')
        ;

        // Create a mock SubQueryPaginator
        $subQueryPaginator = $this->createMock(SubQueryPaginator::class);
        $subQueryPaginator->expects($this->once())
            ->method('paginate')
            ->with(5, 10)
            ->willReturn(new PaginationResult(
                items: $items,
                currentPage: 5,
                pageSize: 10,
                total: 100,
                totalPages: 10
            ))
        ;

        $this->paginator->expects($this->once())
            ->method('createPaginatorForStrategy')
            ->with('subquery')
            ->willReturn($subQueryPaginator)
        ;

        $result = $this->paginator->paginate(5, 10);

        $this->assertInstanceOf(PaginationResult::class, $result);
        $this->assertSame($items, $result->getItems());
        $this->assertSame(100, $result->getTotalItems());
        $this->assertSame(5, $result->getCurrentPage());
        $this->assertSame(10, $result->getPageSize());
        $this->assertSame(10, $result->getTotalPages());
    }

    public function testPaginateAfterWithSearchAfterStrategy(): void
    {
        $items = [
            ['id' => 11, 'name' => 'Item 11'],
            ['id' => 12, 'name' => 'Item 12'],
        ];

        $lastCursor = ['id' => 10];

        $this->queryAnalyzer->expects($this->once())
            ->method('recommendStrategy')
            ->with($this->queryBuilder, 0, json_encode($lastCursor))
            ->willReturn('search_after')
        ;

        // Create a mock SearchAfterPaginator
        $searchAfterPaginator = $this->createMock(SearchAfterPaginator::class);
        $searchAfterPaginator->expects($this->once())
            ->method('paginateAfter')
            ->with($lastCursor, 10)
            ->willReturn(new PaginationResult(
                items: $items,
                currentPage: 1,
                pageSize: 10,
                total: 0,
                totalPages: 0,
                hasMore: true,
                lastItem: ['id' => 12]
            ))
        ;

        $this->paginator->expects($this->once())
            ->method('createPaginatorForStrategy')
            ->with('search_after')
            ->willReturn($searchAfterPaginator)
        ;

        $result = $this->paginator->paginateAfter($lastCursor, 10);

        $this->assertInstanceOf(PaginationResult::class, $result);
        $this->assertSame($items, $result->getItems());
        $this->assertSame(['id' => 12], $result->getLastCursor());
    }

    public function testForceStrategy(): void
    {
        $items = [
            ['id' => 5, 'name' => 'Item 5'],
        ];

        // Force offset strategy
        $this->paginator->forceStrategy('offset');

        // QueryAnalyzer should not be called when strategy is forced
        $this->queryAnalyzer->expects($this->never())
            ->method('recommendStrategy')
        ;

        // Create a mock OffsetPaginator
        $offsetPaginator = $this->createMock(OffsetPaginator::class);
        $offsetPaginator->expects($this->once())
            ->method('paginate')
            ->with(1, 10)
            ->willReturn(new PaginationResult(
                items: $items,
                currentPage: 1,
                pageSize: 10,
                total: 5,
                totalPages: 1
            ))
        ;

        $this->paginator->expects($this->once())
            ->method('createPaginatorForStrategy')
            ->with('offset')
            ->willReturn($offsetPaginator)
        ;

        $result = $this->paginator->paginate(1, 10);

        $this->assertInstanceOf(PaginationResult::class, $result);
        $this->assertSame($items, $result->getItems());
    }

    public function testGetAndSetQueryBuilder(): void
    {
        $realPaginator = new AutoPaginator($this->queryBuilder, $this->queryAnalyzer);

        $this->assertSame($this->queryBuilder, $realPaginator->getQueryBuilder());

        $newQueryBuilder = $this->createMock(QueryBuilder::class);
        $realPaginator->setQueryBuilder($newQueryBuilder);
        $this->assertSame($newQueryBuilder, $realPaginator->getQueryBuilder());
    }

    public function testGetStrategy(): void
    {
        $realPaginator = new AutoPaginator($this->queryBuilder, $this->queryAnalyzer);
        $this->assertSame('auto', $realPaginator->getStrategy());
    }
}
