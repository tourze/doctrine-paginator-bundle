# Doctrine 分页增强 Bundle

[English](README.md) | [中文](README.zh-CN.md)

一个强大的 Doctrine 分页增强 Symfony Bundle，针对不同数据量提供多种优化的分页策略。

## 功能特性

- **智能策略选择** - 根据查询特征自动选择最优的分页策略
- **多种分页策略** - 支持 Offset、SubQuery 和 SearchAfter 分页方式
- **高性能** - 针对不同规模的数据集优化不同策略
- **易于集成** - 提供简单的助手类，便于在控制器中快速实现
- **游标分页** - 支持现代化的无限滚动模式

## 安装

```bash
composer require tourze/doctrine-paginator-bundle
```

## 基本用法

### 在控制器中使用 PaginationHelper

```php
use Tourze\DoctrinePaginatorBundle\Helper\PaginationHelper;

class UserController extends AbstractController
{
    public function __construct(
        private PaginationHelper $paginationHelper
    ) {
    }

    public function list(Request $request): JsonResponse
    {
        $qb = $this->getDoctrine()
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->orderBy('u.createTime', 'DESC');

        $result = $this->paginationHelper->paginate(
            $qb,
            $request->query->getInt('page', 1),
            $request->query->getInt('pageSize', 10)
        );

        return $this->json($result->toArray());
    }
}
```

### 在服务中使用 PaginatorFactory

```php
use Tourze\DoctrinePaginatorBundle\Service\PaginatorFactory;

class UserService
{
    public function __construct(
        private PaginatorFactory $paginatorFactory
    ) {
    }

    public function getUsers(int $page, int $pageSize): array
    {
        $qb = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u');

        // 自动策略选择
        $paginator = $this->paginatorFactory->createAuto($qb);

        return $paginator->paginate($page, $pageSize)->toArray();
    }
}
```

### 游标分页

```php
// 使用 SearchAfter 策略
$paginator = $this->paginatorFactory->create($qb, 'search_after');
$result = $paginator->paginateAfter($lastCursor, 10);
```

### 上一页/下一页分页

```php
// 第一页
$result = $paginator->paginateAfter(null, 10);

// 下一页
$lastItem = $result->getLastItem();
$nextResult = $paginator->paginateAfter($lastItem, 10);
```

## 分页策略

### Offset 分页
- **适用场景**: 小数据集（offset < 10,000）
- **SQL**: `LIMIT offset, size`
- **性能**: 传统分页，包含总数统计

### SubQuery 分页
- **适用场景**: 中等数据集（offset 在 10,000-1,000,000 之间）
- **SQL**: 优化的子查询方式
- **性能**: 对中等数据集优于 Offset 策略

### SearchAfter 分页
- **适用场景**: 大数据集（offset > 1,000,000）
- **SQL**: `WHERE id > last_id LIMIT size`
- **性能**: 非常适合无限滚动

## 自动策略选择

Bundle 会根据以下因素自动选择最优策略：

- **查询复杂度**: 分析 JOIN 条件和 WHERE 子句
- **偏移量**: 根据请求的页码偏移量选择策略
- **性能指标**: 考虑数据库性能特征

```php
// 自动策略（推荐）
$paginator = $this->paginatorFactory->createAuto($qb);

// 手动策略选择
$paginator = $this->paginatorFactory->create($qb, 'offset');
$paginator = $this->paginatorFactory->create($qb, 'sub_query');
$paginator = $this->paginatorFactory->create($qb, 'search_after');
```

## API 参考

### PaginationResult

分页结果对象提供以下方法：

```php
class PaginationResult
{
    public function getItems(): array;           // 当前页数据
    public function getCurrentPage(): int;       // 当前页码
    public function getPageSize(): int;          // 每页大小
    public function getTotal(): int;             // 总记录数
    public function getTotalPages(): int;        // 总页数
    public function hasMore(): bool;             // 是否有更多数据（游标分页）
    public function getLastItem(): mixed;        // 最后一条数据（游标分页）
    public function getLastCursor(): ?array;     // 最后的游标（用于下一页）
    public function toArray(): array;            // 转换为数组
}
```

### PaginationHelper 方法

```php
// 智能分页，自动策略选择
public function paginate(QueryBuilder $qb, int $page, int $pageSize, mixed $lastCursor = null): PaginationResult;

// 自动策略分页
public function paginateAuto(QueryBuilder $qb, int $page, int $pageSize): PaginationResult;

// 游标分页
public function paginateAfter(QueryBuilder $qb, mixed $lastCursor, int $pageSize): PaginationResult;

// 传统偏移分页
public function paginateOffset(QueryBuilder $qb, int $page, int $pageSize): PaginationResult;

// 快速分页，适用于简单场景
public function quickPaginate(QueryBuilder $qb, array $params = []): array;

// 直接返回数组格式
public function paginateToArray(QueryBuilder $qb, int $page, int $pageSize, mixed $lastCursor = null): array;
```

## 测试

```bash
# 运行所有测试
./vendor/bin/phpunit

# 仅运行单元测试
./vendor/bin/phpunit tests/Unit

# 仅运行集成测试
./vendor/bin/phpunit tests/Integration
```

## 性能建议

1. **小数据集**（< 1万条记录）: 使用 Offset 分页
2. **中等数据集**（1万-10万条记录）: 使用 SubQuery 分页
3. **大数据集**（> 10万条记录）: 使用 SearchAfter 分页
4. **自动选择**: 让 Bundle 为您的用例选择最佳策略

## 配置

此 Bundle 开箱即用，与 Symfony 的依赖注入系统完美集成。无需配置。

## 许可证

MIT