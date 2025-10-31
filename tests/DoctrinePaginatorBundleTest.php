<?php

declare(strict_types=1);

namespace Tourze\DoctrinePaginatorBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DoctrinePaginatorBundle\DoctrinePaginatorBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(DoctrinePaginatorBundle::class)]
#[RunTestsInSeparateProcesses]
final class DoctrinePaginatorBundleTest extends AbstractBundleTestCase
{
}
