<?php

declare(strict_types=1);

namespace Tourze\DoctrinePaginatorBundle\Paginator;

use Doctrine\ORM\QueryBuilder;
use Tourze\DoctrinePaginatorBundle\Exception\InvalidPaginationStrategyException;
use Tourze\DoctrinePaginatorBundle\Model\PaginationResult;
use Tourze\DoctrinePaginatorBundle\Service\QueryAnalyzer;

/**
 * 自动分页器 - 根据查询特征自动选择最优的分页策略
 */
class AutoPaginator implements PaginatorInterface
{
    private QueryBuilder $queryBuilder;

    private QueryAnalyzer $queryAnalyzer;

    private ?string $forcedStrategy = null;

    private ?PaginatorInterface $currentPaginator = null;

    public function __construct(QueryBuilder $queryBuilder, QueryAnalyzer $queryAnalyzer)
    {
        $this->queryBuilder = $queryBuilder;
        $this->queryAnalyzer = $queryAnalyzer;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function setQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $this->queryBuilder = $queryBuilder;
        $this->currentPaginator = null;
    }

    public function getStrategy(): string
    {
        if (null !== $this->forcedStrategy) {
            return $this->forcedStrategy;
        }

        return PaginatorStrategies::STRATEGY_AUTO;
    }

    /**
     * 强制使用指定的分页策略
     */
    public function forceStrategy(?string $strategy): void
    {
        if (null !== $strategy && !in_array($strategy, PaginatorStrategies::getAllStrategies(), true)) {
            throw new InvalidPaginationStrategyException(sprintf('Invalid strategy "%s". Available strategies: %s', $strategy, implode(', ', PaginatorStrategies::getAllStrategies())));
        }

        $this->forcedStrategy = $strategy;
        $this->currentPaginator = null;
    }

    public function paginate(int $page, int $pageSize): PaginationResult
    {
        $paginator = $this->getPaginatorForOffset($page, $pageSize);

        return $paginator->paginate($page, $pageSize);
    }

    /**
     * 根据 offset 获取适合的分页器
     */
    private function getPaginatorForOffset(int $page, int $pageSize): PaginatorInterface
    {
        if (null !== $this->currentPaginator && null === $this->forcedStrategy) {
            return $this->currentPaginator;
        }

        $offset = ($page - 1) * $pageSize;
        $strategy = $this->forcedStrategy ?? $this->queryAnalyzer->recommendStrategy($this->queryBuilder, $offset);

        return $this->createPaginator($strategy);
    }

    /**
     * 创建指定策略的分页器
     */
    private function createPaginator(string $strategy): PaginatorInterface
    {
        $paginator = $this->createPaginatorForStrategy($strategy);
        $this->currentPaginator = $paginator;

        return $paginator;
    }

    /**
     * 创建指定策略的分页器实例
     * 提取为方法以便于测试
     */
    protected function createPaginatorForStrategy(string $strategy): PaginatorInterface
    {
        switch ($strategy) {
            case PaginatorStrategies::STRATEGY_OFFSET:
                $paginator = new OffsetPaginator($this->queryBuilder);
                break;

            case PaginatorStrategies::STRATEGY_SUB_QUERY:
                $paginator = new SubQueryPaginator($this->queryBuilder);
                break;

            case PaginatorStrategies::STRATEGY_SEARCH_AFTER:
                $paginator = new SearchAfterPaginator($this->queryBuilder);
                break;

            case PaginatorStrategies::STRATEGY_AUTO:
            default:
                // 如果是 AUTO 策略，重新分析并选择
                $strategy = $this->queryAnalyzer->recommendStrategy($this->queryBuilder, 0);

                return $this->createPaginatorForStrategy($strategy);
        }

        return $paginator;
    }

    public function paginateAfter(mixed $lastCursor, int $pageSize): PaginationResult
    {
        $paginator = $this->getPaginatorForCursor($lastCursor);

        return $paginator->paginateAfter($lastCursor, $pageSize);
    }

    /**
     * 根据游标获取适合的分页器
     */
    private function getPaginatorForCursor(mixed $lastCursor): PaginatorInterface
    {
        if (null !== $this->currentPaginator && null === $this->forcedStrategy) {
            return $this->currentPaginator;
        }

        $cursorString = is_array($lastCursor) ? (false !== json_encode($lastCursor) ? json_encode($lastCursor) : null) : (string) $lastCursor;
        $strategy = $this->forcedStrategy ?? $this->queryAnalyzer->recommendStrategy($this->queryBuilder, 0, $cursorString);

        return $this->createPaginator($strategy);
    }

    /**
     * 获取当前使用的分页器实例
     */
    public function getCurrentPaginator(): ?PaginatorInterface
    {
        return $this->currentPaginator;
    }

    /**
     * 获取查询分析结果
     * @return array<string, mixed>
     */
    public function getAnalysisResult(): array
    {
        return $this->queryAnalyzer->analyze($this->queryBuilder);
    }
}
