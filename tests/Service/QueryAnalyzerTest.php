<?php

namespace Tourze\DoctrinePaginatorBundle\Tests\Service;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrinePaginatorBundle\Service\QueryAnalyzer;

/**
 * @internal
 */
#[CoversClass(QueryAnalyzer::class)]
final class QueryAnalyzerTest extends TestCase
{
    private QueryAnalyzer $analyzer;

    public function testAnalyzeSimpleQuery(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('getDQLPart')->willReturnMap([
            ['join', []],
            ['where', null],
            ['groupBy', []],
            ['having', []],
            ['orderBy', []],
            ['select', []],
        ]);
        $qb->method('getRootAliases')->willReturn(['u']);
        $qb->method('getMaxResults')->willReturn(null);

        $result = $this->analyzer->analyze($qb);

        $this->assertFalse($result['hasJoins']);
        $this->assertFalse($result['hasComplexConditions']);
        $this->assertSame([], $result['orderByFields']);
        $this->assertFalse($result['hasIndexableOrderBy']);
        $this->assertSame(0, $result['estimatedRowCount']);
        $this->assertFalse($result['hasWhereClause']);
        $this->assertFalse($result['hasGroupBy']);
        $this->assertFalse($result['hasHaving']);
    }

    public function testHasJoins(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('getDQLPart')->willReturnMap([
            ['join', ['u' => [new \stdClass()]]],
            ['where', null],
            ['groupBy', []],
            ['having', []],
            ['orderBy', []],
            ['select', []],
        ]);
        $qb->method('getRootAliases')->willReturn(['u']);
        $qb->method('getMaxResults')->willReturn(null);

        $result = $this->analyzer->analyze($qb);

        $this->assertTrue($result['hasJoins']);
    }

    public function testHasComplexConditionsWithLike(): void
    {
        // 创建一个真实的 Expr 对象
        $expr = new Query\Expr();
        $whereExpr = $expr->like('u.name', "'%test%'");

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('getDQLPart')->willReturnMap([
            ['join', []],
            ['where', $whereExpr],
            ['groupBy', []],
            ['having', []],
            ['orderBy', []],
            ['select', []],
        ]);
        $qb->method('getRootAliases')->willReturn(['u']);
        $qb->method('getMaxResults')->willReturn(null);

        $result = $this->analyzer->analyze($qb);

        $this->assertTrue($result['hasComplexConditions']);
    }

    public function testHasComplexConditionsWithOr(): void
    {
        // 创建一个真实的 Expr 对象
        $expr = new Query\Expr();
        $whereExpr = $expr->orX(
            $expr->eq('u.status', '1'),
            $expr->eq('u.status', '2')
        );

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('getDQLPart')->willReturnMap([
            ['join', []],
            ['where', $whereExpr],
            ['groupBy', []],
            ['having', []],
            ['orderBy', []],
            ['select', []],
        ]);
        $qb->method('getRootAliases')->willReturn(['u']);
        $qb->method('getMaxResults')->willReturn(null);

        $result = $this->analyzer->analyze($qb);

        $this->assertTrue($result['hasComplexConditions']);
    }

    public function testGetOrderByFields(): void
    {
        // 测试没有排序的情况
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('getDQLPart')->willReturnMap([
            ['join', []],
            ['where', null],
            ['groupBy', []],
            ['having', []],
            ['orderBy', []],
            ['select', []],
        ]);
        $qb->method('getRootAliases')->willReturn(['u']);
        $qb->method('getMaxResults')->willReturn(null);

        $result = $this->analyzer->analyze($qb);

        $this->assertSame([], $result['orderByFields']);
    }

    public function testHasIndexableOrderByWithId(): void
    {
        // 测试没有排序的情况
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('getDQLPart')->willReturnMap([
            ['join', []],
            ['where', null],
            ['groupBy', []],
            ['having', []],
            ['orderBy', []],
            ['select', []],
        ]);
        $qb->method('getRootAliases')->willReturn(['u']);
        $qb->method('getMaxResults')->willReturn(null);

        $result = $this->analyzer->analyze($qb);

        $this->assertFalse($result['hasIndexableOrderBy']);
    }

    public function testRecommendStrategyForSmallOffset(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('getDQLPart')->willReturnMap([
            ['join', []],
            ['where', null],
            ['groupBy', []],
            ['having', []],
            ['orderBy', []],
            ['select', []],
        ]);
        $qb->method('getRootAliases')->willReturn(['u']);
        $qb->method('getMaxResults')->willReturn(null);

        $strategy = $this->analyzer->recommendStrategy($qb, 5000);

        $this->assertSame('offset', $strategy);
    }

    public function testRecommendStrategyForMediumOffsetWithComplexQuery(): void
    {
        // 创建一个真实的 Expr 对象
        $expr = new Query\Expr();
        $whereExpr = $expr->like('u.name', "'%test%'");

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('getDQLPart')->willReturnMap([
            ['join', ['u' => [new \stdClass()]]],
            ['where', $whereExpr],
            ['groupBy', []],
            ['having', []],
            ['orderBy', []],
            ['select', []],
        ]);
        $qb->method('getRootAliases')->willReturn(['u']);
        $qb->method('getMaxResults')->willReturn(null);

        $strategy = $this->analyzer->recommendStrategy($qb, 50000);

        $this->assertSame('sub_query', $strategy);
    }

    public function testRecommendStrategyForLargeOffsetWithIndexableOrder(): void
    {
        // 测试没有排序的情况
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('getDQLPart')->willReturnMap([
            ['join', []],
            ['where', null],
            ['groupBy', []],
            ['having', []],
            ['orderBy', []],
            ['select', []],
        ]);
        $qb->method('getRootAliases')->willReturn(['u']);
        $qb->method('getMaxResults')->willReturn(null);

        $strategy = $this->analyzer->recommendStrategy($qb, 2000000);

        // 没有可索引的排序，应该使用子查询
        $this->assertSame('sub_query', $strategy);
    }

    public function testRecommendStrategyWithLastCursor(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('getDQLPart')->willReturnMap([
            ['join', []],
            ['where', null],
            ['groupBy', []],
            ['having', []],
            ['orderBy', []],
            ['select', []],
        ]);
        $qb->method('getRootAliases')->willReturn(['u']);
        $qb->method('getMaxResults')->willReturn(null);

        $strategy = $this->analyzer->recommendStrategy($qb, 0, '123');

        $this->assertSame('search_after', $strategy);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new QueryAnalyzer();
    }
}
