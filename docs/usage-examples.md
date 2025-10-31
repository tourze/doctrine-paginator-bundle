# 使用示例

## 基础用法

### 1. 在 Controller 中使用

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Tourze\DoctrinePaginatorBundle\Helper\PaginationHelper;

class UserController extends AbstractController
{
    public function __construct(
        private PaginationHelper $paginationHelper
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        // 创建 QueryBuilder
        $qb = $this->getDoctrine()
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.active = :active')
            ->setParameter('active', true)
            ->orderBy('u.createTime', 'DESC');

        // 自动分页 - 会根据查询特征自动选择最佳策略
        $result = $this->paginationHelper->paginate(
            $qb,
            $request->query->getInt('page', 1),
            $request->query->getInt('pageSize', 20)
        );

        return $this->json([
            'success' => true,
            'data' => $result->toArray()
        ]);
    }

    public function cursor(Request $request): JsonResponse
    {
        $qb = $this->getDoctrine()
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->orderBy('u.id', 'ASC');

        // 游标分页 - 适用于大数据量
        $lastCursor = $request->query->get('lastCursor');
        $result = $this->paginationHelper->paginateAfter(
            $qb,
            $lastCursor,
            20
        );

        return $this->json([
            'success' => true,
            'data' => $result->toArray()
        ]);
    }
}
```

### 2. 在 Service 中使用

```php
<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;use Tourze\DoctrinePaginatorBundle\Model\PaginationResult;use Tourze\DoctrinePaginatorBundle\Service\PaginatorFactory;

class UserService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PaginatorFactory $paginatorFactory
    ) {
    }

    public function getActiveUsers(int $page, int $pageSize): PaginationResult
    {
        $qb = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.active = :active')
            ->setParameter('active', true)
            ->orderBy('u.name', 'ASC');

        // 创建自动分页器
        $paginator = $this->paginatorFactory->createAuto($qb);
        
        return $paginator->paginate($page, $pageSize);
    }

    public function searchUsers(string $keyword, int $page, int $pageSize): array
    {
        $qb = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.name LIKE :keyword OR u.email LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('u.createTime', 'DESC');

        // 复杂查询可能更适合使用子查询分页
        $paginator = $this->paginatorFactory->create($qb, 'sub_query');
        
        return $paginator->paginate($page, $pageSize)->toArray();
    }
}
```

### 3. 强制使用特定策略

```php
<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\DoctrinePaginatorBundle\Model\PaginationResult;
use Tourze\DoctrinePaginatorBundle\Paginator\PaginatorStrategies;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findWithOffsetPagination(int $page, int $pageSize): PaginationResult
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.id', 'ASC');

        // 强制使用 Offset 分页
        $paginator = $this->paginatorFactory->create($qb, PaginatorStrategies::STRATEGY_OFFSET);
        
        return $paginator->paginate($page, $pageSize);
    }

    public function findWithSearchAfter(?int $lastId, int $pageSize): PaginationResult
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.id', 'ASC');

        // 强制使用游标分页
        $paginator = $this->paginatorFactory->create($qb, PaginatorStrategies::STRATEGY_SEARCH_AFTER);
        
        return $paginator->paginateAfter($lastId, $pageSize);
    }
}
```

## 高级用法

### 1. 获取查询分析结果

```php
<?php

use Tourze\DoctrinePaginatorBundle\Service\QueryAnalyzer;

class DebugService
{
    public function __construct(
        private QueryAnalyzer $queryAnalyzer
    ) {
    }

    public function analyzeQuery(QueryBuilder $qb): array
    {
        // 获取查询分析结果
        $analysis = $this->queryAnalyzer->analyze($qb);
        
        // 获取推荐策略
        $strategy = $this->queryAnalyzer->recommendStrategy($qb, 50000);
        
        return [
            'analysis' => $analysis,
            'recommended_strategy' => $strategy,
            'features' => [
                'has_joins' => $analysis['hasJoins'],
                'has_complex_conditions' => $analysis['hasComplexConditions'],
                'estimated_rows' => $analysis['estimatedRowCount'],
            ]
        ];
    }
}
```

### 2. 自定义 AutoPaginator

```php
<?php

use Tourze\DoctrinePaginatorBundle\Paginator\AutoPaginator;

class CustomService
{
    public function getPaginatedResults(QueryBuilder $qb, int $page, int $pageSize)
    {
        // 创建自动分页器
        $paginator = new AutoPaginator($qb, $this->queryAnalyzer);
        
        // 强制使用特定策略
        $paginator->forceStrategy('search_after');
        
        // 获取当前使用的分页器
        $currentPaginator = $paginator->getCurrentPaginator();
        
        // 获取分析结果
        $analysis = $paginator->getAnalysisResult();
        
        $result = $paginator->paginate($page, $pageSize);
        
        return [
            'data' => $result,
            'strategy_used' => $currentPaginator->getStrategy(),
            'query_analysis' => $analysis
        ];
    }
}
```

### 3. 处理游标分页的 JSON 响应

```php
<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tourze\DoctrinePaginatorBundle\Helper\PaginationHelper;

class UserApiController extends AbstractController
{
    public function __construct(
        private PaginationHelper $paginationHelper
    ) {
    }

    public function list(): JsonResponse
    {
        $qb = $this->getDoctrine()
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->orderBy('u.id', 'ASC');

        $result = $this->paginationHelper->quickPaginate($qb, [
            'page' => 1,
            'pageSize' => 10
        ]);

        return $this->json([
            'success' => true,
            'data' => $result['items'],
            'pagination' => [
                'current_page' => $result['currentPage'],
                'page_size' => $result['pageSize'],
                'total' => $result['total'],
                'total_pages' => $result['totalPages'],
                'has_more' => $result['hasMore'],
            ],
            'cursor' => $result['lastItem'] ? [
                'id' => $result['lastItem']->getId(),
                'name' => $result['lastItem']->getName(),
            ] : null
        ]);
    }
}
```

## 性能优化建议

1. **监控查询性能**
   ```php
   $start = microtime(true);
   $result = $paginator->paginate($page, $pageSize);
   $duration = microtime(true) - $start;
   
   if ($duration > 0.1) { // 超过100ms
       $this->logger->warning('Slow pagination query', [
           'duration' => $duration,
           'strategy' => $paginator->getStrategy(),
           'page' => $page,
           'page_size' => $pageSize
       ]);
   }
   ```

2. **缓存总条数**
   ```php
   // 对于不常变的数据，可以缓存总条数
   $cacheKey = 'user_count_' . md5($qb->getDQL());
   $total = $this->cache->get($cacheKey, fn() => $result->getTotal());
   ```

3. **限制最大页数**
   ```php
   $maxPage = 1000;
   $page = min($request->query->getInt('page', 1), $maxPage);
   ```

4. **使用适当的索引**
   - 确保 ORDER BY 字段有索引
   - 复合索引要符合最左前缀原则
   - 游标分页需要索引支持