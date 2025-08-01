<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

abstract class BaseRepository
{
    protected const CACHE_TTL = 3600; // 1 hour
    protected const ENUM_CACHE_TTL = 86400; // 24 hours
    protected const STATISTICS_CACHE_TTL = 1800; // 30 minutes

    /**
     * Handle cache operations with error handling
     */
    protected function handleCacheOperation(string $key, callable $callback, ?int $ttl = null): mixed
    {
        try {
            return Cache::remember($key, $ttl ?? static::CACHE_TTL, $callback);
        } catch (\Exception $e) {
            Log::warning("Cache operation failed for key {$key}: " . $e->getMessage(), [
                'exception' => $e,
                'key' => $key,
                'repository' => static::class
            ]);
            
            // Fall back to direct execution
            return $callback();
        }
    }

    /**
     * Clear entity cache with pattern matching
     */
    protected function clearEntityCache(string $entity, mixed $identifier = null): void
    {
        try {
            if ($identifier !== null) {
                // Clear specific entity cache
                Cache::forget("{$entity}.{$identifier}");
                Cache::forget("{$entity}.{$identifier}.with_relations");
                Cache::forget("{$entity}.{$identifier}.with_apps");
            }
            
            // Clear general entity caches
            Cache::forget("{$entity}.all");
            Cache::forget("{$entity}.all_with_apps");
            Cache::forget("{$entity}.statistics");
            Cache::forget("{$entity}s.statistics"); // plural form
        } catch (\Exception $e) {
            Log::warning("Failed to clear cache for entity {$entity}: " . $e->getMessage(), [
                'entity' => $entity,
                'identifier' => $identifier,
                'repository' => static::class
            ]);
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
        
        // Handle special sorting cases (like joins)
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
        $key = $entity . '.' . $operation;
        
        foreach ($params as $param) {
            if (is_array($param)) {
                $key .= '.' . md5(serialize($param));
            } else {
                $key .= '.' . $param;
            }
        }
        
        return $key;
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