<?php

declare(strict_types=1);

namespace Tourze\DoctrinePaginatorBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DoctrinePaginatorBundle\Exception\InvalidPaginationStrategyException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(InvalidPaginationStrategyException::class)]
final class InvalidPaginationStrategyExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $exception = new InvalidPaginationStrategyException('Invalid strategy: test');

        $this->assertSame('Invalid strategy: test', $exception->getMessage());
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
    }

    public function testExceptionCode(): void
    {
        $exception = new InvalidPaginationStrategyException('Test error', 42);

        $this->assertSame(42, $exception->getCode());
    }
}
