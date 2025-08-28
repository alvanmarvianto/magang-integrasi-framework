<?php

namespace App\Repositories;

use App\Repositories\Exceptions\RepositoryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

abstract class BaseRepository
{
    /**
     * Handle cache operations with error handling
     */
    protected function handleCacheOperation(string $key, callable $callback, ?int $ttl = null): mixed
    {
        try {
            return Cache::remember($key, $ttl ?? CacheConfig::DEFAULT_TTL, $callback);
        } catch (\Exception $e) {
            Log::warning("Cache operation failed for key {$key}: " . $e->getMessage(), [
                'exception' => $e,
                'key' => $key,
                'repository' => static::class
            ]);
            
            try {
                return $callback();
            } catch (\Exception $directException) {
                throw RepositoryException::cacheOperationFailed('remember', $key, $directException->getMessage());
            }
        }
    }

    /**
     * Clear entity cache with pattern matching
     */
    protected function clearEntityCache(string $entity, mixed $identifier = null): void
    {
        try {
            if ($identifier !== null) {
                Cache::forget("{$entity}.{$identifier}");
                Cache::forget("{$entity}.{$identifier}.with_relations");
                Cache::forget("{$entity}.{$identifier}.with_apps");
            }
            
            Cache::forget("{$entity}.all");
            Cache::forget("{$entity}.all_with_apps");
            Cache::forget("{$entity}.statistics");
            Cache::forget("{$entity}s.statistics");
            
            $this->clearCacheByPattern($entity);
            
        } catch (\Exception $e) {
            Log::warning("Failed to clear cache for entity {$entity}: " . $e->getMessage(), [
                'entity' => $entity,
                'identifier' => $identifier,
                'repository' => static::class
            ]);
            
            throw RepositoryException::cacheOperationFailed('clear', $entity, $e->getMessage());
        }
    }

    /**
     * Clear cache entries by pattern
     * This is more aggressive but ensures no stale data remains
     */
    protected function clearCacheByPattern(string $pattern): void
    {
        try {
            $keysToForget = [
                'app.all',
                'apps.all',
                'apps.statistics', 
                'apps.with_integration_counts',
                
                'stream.apps',
                'stream.name_apps',
                
                'technology.components',
                'technology.mappings'
            ];
            
            foreach ($keysToForget as $key) {
                Cache::forget($key);
            }
            
            // Also clear any search result caches by forgetting common search patterns
            // Note: This is a limitation of Laravel cache - no wildcard support
            for ($i = 1; $i <= 10; $i++) {
                Cache::forget("apps.search.{$i}");
                Cache::forget("app.search.{$i}");
            }
            
        } catch (\Exception $e) {
            Log::warning("Failed to clear cache by pattern {$pattern}: " . $e->getMessage());
        }
    }

    /**
     * Validate ID parameter
     */
    protected function validateId(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID must be a positive integer');
        }
    }

    /**
     * Validate string is not empty
     */
    protected function validateNotEmpty(string $value, string $fieldName): void
    {
        if (empty(trim($value))) {
            throw new InvalidArgumentException("{$fieldName} cannot be empty");
        }
    }

    /**
     * Validate pagination parameters
     */
    protected function validatePaginationParams(int $perPage): void
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Per page must be between 1 and 100');
        }
    }

    /**
     * Apply sorting to query with validation
     */
    protected function applySorting($query, string $sortBy, string $direction = 'asc'): void
    {
        $allowedSortFields = $this->getAllowedSortFields();
        
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = $this->getDefaultSortField();
        }
        
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        
        $this->applySortingLogic($query, $sortBy, $direction);
    }

    /**
     * Apply actual sorting logic - can be overridden by repositories
     */
    protected function applySortingLogic($query, string $sortBy, string $direction): void
    {
        $query->orderBy($sortBy, $direction);
    }

    /**
     * Build cache key with consistent formatting
     */
    protected function buildCacheKey(string $entity, string $operation, ...$params): string
    {
        return CacheConfig::buildKey($entity, $operation, ...$params);
    }

    /**
     * Get allowed sort fields for the repository
     */
    abstract protected function getAllowedSortFields(): array;

    /**
     * Get default sort field for the repository
     */
    abstract protected function getDefaultSortField(): string;

    /**
     * Get the entity name for cache operations
     */
    abstract protected function getEntityName(): string;
}