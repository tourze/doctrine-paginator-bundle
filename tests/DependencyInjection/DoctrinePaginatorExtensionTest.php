<?php

declare(strict_types=1);

namespace Tourze\DoctrinePaginatorBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DoctrinePaginatorBundle\DependencyInjection\DoctrinePaginatorExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(DoctrinePaginatorExtension::class)]
final class DoctrinePaginatorExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
}
