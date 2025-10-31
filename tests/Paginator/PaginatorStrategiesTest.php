<?php

declare(strict_types=1);

namespace Tourze\DoctrinePaginatorBundle\Tests\Paginator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrinePaginatorBundle\Paginator\PaginatorStrategies;

/**
 * @internal
 */
#[CoversClass(PaginatorStrategies::class)]
final class PaginatorStrategiesTest extends TestCase
{
    public function testGetAllStrategies(): void
    {
        $strategies = PaginatorStrategies::getAllStrategies();

        $this->assertIsArray($strategies);
        $this->assertContains(PaginatorStrategies::STRATEGY_AUTO, $strategies);
        $this->assertContains(PaginatorStrategies::STRATEGY_OFFSET, $strategies);
        $this->assertContains(PaginatorStrategies::STRATEGY_SUB_QUERY, $strategies);
        $this->assertContains(PaginatorStrategies::STRATEGY_SEARCH_AFTER, $strategies);
    }

    public function testStrategyConstants(): void
    {
        $this->assertSame('auto', PaginatorStrategies::STRATEGY_AUTO);
        $this->assertSame('offset', PaginatorStrategies::STRATEGY_OFFSET);
        $this->assertSame('sub_query', PaginatorStrategies::STRATEGY_SUB_QUERY);
        $this->assertSame('search_after', PaginatorStrategies::STRATEGY_SEARCH_AFTER);
    }
}
