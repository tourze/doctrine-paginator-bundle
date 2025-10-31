<?php

namespace Tourze\DoctrinePaginatorBundle\Tests\Service;

use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrinePaginatorBundle\Paginator\AutoPaginator;
use Tourze\DoctrinePaginatorBundle\Paginator\OffsetPaginator;
use Tourze\DoctrinePaginatorBundle\Paginator\PaginatorStrategies;
use Tourze\DoctrinePaginatorBundle\Paginator\SearchAfterPaginator;
use Tourze\DoctrinePaginatorBundle\Paginator\SubQueryPaginator;
use Tourze\DoctrinePaginatorBundle\Service\PaginatorFactory;
use Tourze\DoctrinePaginatorBundle\Service\QueryAnalyzer;

/**
 * @internal
 */
#[CoversClass(PaginatorFactory::class)]
final class PaginatorFactoryTest extends TestCase
{
    private PaginatorFactory $factory;

    private QueryAnalyzer $queryAnalyzer;

    public function testCreateAuto(): void
    {
        $qb = $this->createMock(QueryBuilder::class);

        $paginator = $this->factory->createAuto($qb);

        $this->assertInstanceOf(AutoPaginator::class, $paginator);
    }

    public function testCreateWithNullStrategy(): void
    {
        $qb = $this->createMock(QueryBuilder::class);

        $paginator = $this->factory->create($qb, null);

        $this->assertInstanceOf(AutoPaginator::class, $paginator);
    }

    public function testCreateWithOffsetStrategy(): void
    {
        $qb = $this->createMock(QueryBuilder::class);

        $paginator = $this->factory->create($qb, PaginatorStrategies::STRATEGY_OFFSET);

        $this->assertInstanceOf(OffsetPaginator::class, $paginator);
    }

    public function testCreateWithSubQueryStrategy(): void
    {
        $qb = $this->createMock(QueryBuilder::class);

        $paginator = $this->factory->create($qb, PaginatorStrategies::STRATEGY_SUB_QUERY);

        $this->assertInstanceOf(SubQueryPaginator::class, $paginator);
    }

    public function testCreateWithSearchAfterStrategy(): void
    {
        $qb = $this->createMock(QueryBuilder::class);

        $paginator = $this->factory->create($qb, PaginatorStrategies::STRATEGY_SEARCH_AFTER);

        $this->assertInstanceOf(SearchAfterPaginator::class, $paginator);
    }

    public function testCreateWithAutoStrategy(): void
    {
        $qb = $this->createMock(QueryBuilder::class);

        $paginator = $this->factory->create($qb, PaginatorStrategies::STRATEGY_AUTO);

        $this->assertInstanceOf(AutoPaginator::class, $paginator);
    }

    public function testCreateOffset(): void
    {
        $qb = $this->createMock(QueryBuilder::class);

        $paginator = $this->factory->createOffset($qb);

        $this->assertInstanceOf(OffsetPaginator::class, $paginator);
    }

    public function testCreateSubQuery(): void
    {
        $qb = $this->createMock(QueryBuilder::class);

        $paginator = $this->factory->createSubQuery($qb);

        $this->assertInstanceOf(SubQueryPaginator::class, $paginator);
    }

    public function testCreateSearchAfter(): void
    {
        $qb = $this->createMock(QueryBuilder::class);

        $paginator = $this->factory->createSearchAfter($qb);

        $this->assertInstanceOf(SearchAfterPaginator::class, $paginator);
    }

    public function testGetQueryAnalyzer(): void
    {
        $analyzer = $this->factory->getQueryAnalyzer();

        $this->assertSame($this->queryAnalyzer, $analyzer);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->queryAnalyzer = $this->createMock(QueryAnalyzer::class);
        $this->factory = new PaginatorFactory($this->queryAnalyzer);
    }
}
