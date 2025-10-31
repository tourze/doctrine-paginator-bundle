<?php

declare(strict_types=1);

namespace Tourze\DoctrinePaginatorBundle\Tests\Helper;

use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrinePaginatorBundle\Helper\PaginationHelper;
use Tourze\DoctrinePaginatorBundle\Model\PaginationResult;
use Tourze\DoctrinePaginatorBundle\Paginator\AutoPaginator;
use Tourze\DoctrinePaginatorBundle\Paginator\OffsetPaginator;
use Tourze\DoctrinePaginatorBundle\Paginator\PaginatorInterface;
use Tourze\DoctrinePaginatorBundle\Paginator\SearchAfterPaginator;
use Tourze\DoctrinePaginatorBundle\Service\PaginatorFactory;

/**
 * @internal
 */
#[CoversClass(PaginationHelper::class)]
final class PaginationHelperTest extends TestCase
{
    private PaginationHelper $helper;

    private PaginatorFactory $paginatorFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paginatorFactory = $this->createMock(PaginatorFactory::class);
        $this->helper = new PaginationHelper($this->paginatorFactory);
    }

    public function testPaginateAuto(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $paginator = $this->createMock(AutoPaginator::class);
        $expectedResult = PaginationResult::empty();

        $this->paginatorFactory->expects($this->once())
            ->method('createAuto')
            ->with($queryBuilder)
            ->willReturn($paginator)
        ;

        $paginator->expects($this->once())
            ->method('paginate')
            ->with(2, 20)
            ->willReturn($expectedResult)
        ;

        $result = $this->helper->paginateAuto($queryBuilder, 2, 20);

        $this->assertSame($expectedResult, $result);
    }

    public function testPaginateWithPage(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $paginator = $this->createMock(AutoPaginator::class);
        $expectedResult = PaginationResult::empty();

        $this->paginatorFactory->expects($this->once())
            ->method('createAuto')
            ->with($queryBuilder)
            ->willReturn($paginator)
        ;

        $paginator->expects($this->once())
            ->method('paginate')
            ->with(1, 10)
            ->willReturn($expectedResult)
        ;

        $result = $this->helper->paginate($queryBuilder, 1, 10);

        $this->assertSame($expectedResult, $result);
    }

    public function testPaginateWithCursor(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $paginator = $this->createMock(SearchAfterPaginator::class);
        $expectedResult = PaginationResult::empty();
        $cursor = ['id' => 100];

        $this->paginatorFactory->expects($this->once())
            ->method('createSearchAfter')
            ->with($queryBuilder)
            ->willReturn($paginator)
        ;

        $paginator->expects($this->once())
            ->method('paginateAfter')
            ->with($cursor, 15)
            ->willReturn($expectedResult)
        ;

        $result = $this->helper->paginate($queryBuilder, 1, 15, $cursor);

        $this->assertSame($expectedResult, $result);
    }

    public function testPaginateAfter(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $paginator = $this->createMock(SearchAfterPaginator::class);
        $expectedResult = PaginationResult::empty();
        $lastCursor = ['id' => 50];

        $this->paginatorFactory->expects($this->once())
            ->method('createSearchAfter')
            ->with($queryBuilder)
            ->willReturn($paginator)
        ;

        $paginator->expects($this->once())
            ->method('paginateAfter')
            ->with($lastCursor, 25)
            ->willReturn($expectedResult)
        ;

        $result = $this->helper->paginateAfter($queryBuilder, $lastCursor, 25);

        $this->assertSame($expectedResult, $result);
    }

    public function testPaginateOffset(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $paginator = $this->createMock(OffsetPaginator::class);
        $expectedResult = PaginationResult::empty();

        $this->paginatorFactory->expects($this->once())
            ->method('createOffset')
            ->with($queryBuilder)
            ->willReturn($paginator)
        ;

        $paginator->expects($this->once())
            ->method('paginate')
            ->with(3, 30)
            ->willReturn($expectedResult)
        ;

        $result = $this->helper->paginateOffset($queryBuilder, 3, 30);

        $this->assertSame($expectedResult, $result);
    }

    public function testCreatePaginator(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $paginator = $this->createMock(PaginatorInterface::class);

        $this->paginatorFactory->expects($this->once())
            ->method('create')
            ->with($queryBuilder, 'sub_query')
            ->willReturn($paginator)
        ;

        $result = $this->helper->createPaginator($queryBuilder, 'sub_query');

        $this->assertSame($paginator, $result);
    }

    public function testQuickPaginate(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $paginator = $this->createMock(AutoPaginator::class);
        $paginationResult = PaginationResult::create(
            items: [['id' => 1], ['id' => 2]],
            currentPage: 2,
            pageSize: 20,
            total: 100,
            totalPages: 5
        );

        $this->paginatorFactory->expects($this->once())
            ->method('createAuto')
            ->with($queryBuilder)
            ->willReturn($paginator)
        ;

        $paginator->expects($this->once())
            ->method('paginate')
            ->with(2, 20)
            ->willReturn($paginationResult)
        ;

        $result = $this->helper->quickPaginate($queryBuilder, ['page' => 2, 'pageSize' => 20]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('currentPage', $result);
        $this->assertArrayHasKey('pageSize', $result);
        $this->assertArrayHasKey('totalPages', $result);
    }

    public function testPaginateToArray(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $paginator = $this->createMock(AutoPaginator::class);
        $paginationResult = PaginationResult::create(
            items: [['id' => 1], ['id' => 2]],
            currentPage: 1,
            pageSize: 10,
            total: 50,
            totalPages: 5
        );

        $this->paginatorFactory->expects($this->once())
            ->method('createAuto')
            ->with($queryBuilder)
            ->willReturn($paginator)
        ;

        $paginator->expects($this->once())
            ->method('paginate')
            ->with(1, 10)
            ->willReturn($paginationResult)
        ;

        $result = $this->helper->paginateToArray($queryBuilder, 1, 10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertSame([['id' => 1], ['id' => 2]], $result['items']);
        $this->assertSame(50, $result['total']);
        $this->assertSame(1, $result['currentPage']);
        $this->assertSame(10, $result['pageSize']);
        $this->assertSame(5, $result['totalPages']);
    }
}
