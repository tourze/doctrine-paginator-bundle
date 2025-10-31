<?php

declare(strict_types=1);

namespace Tourze\DoctrinePaginatorBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DoctrinePaginatorBundle\Exception\InvalidPaginationException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(InvalidPaginationException::class)]
final class InvalidPaginationExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $exception = new InvalidPaginationException('Test error message');

        $this->assertSame('Test error message', $exception->getMessage());
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new InvalidPaginationException('Test error', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
