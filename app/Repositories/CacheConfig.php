<?php

namespace App\Repositories;

class CacheConfig
{
    public const DEFAULT_TTL = 3600; // 1 hour
    public const ENUM_TTL = 86400; // 24 hours
    public const STATISTICS_TTL = 1800; // 30 minutes
    public const SEARCH_TTL = 900; // 15 minutes
    public const PAGINATION_TTL = 600; // 10 minutes
    public const RELATIONS_TTL = 1800; // 30 minutes
    
    /**
     * Get TTL for specific cache type
     */
    public static function getTTL(string $type): int
    {
        return config("cache.repositories.{$type}", match($type) {
            'enum', 'technology' => self::ENUM_TTL,
            'statistics' => self::STATISTICS_TTL,
            'search' => self::SEARCH_TTL,
            'pagination' => self::PAGINATION_TTL,
            'relations' => self::RELATIONS_TTL,
            default => self::DEFAULT_TTL
        });
    }
    
    /**
     * Get cache key prefix for repository
     */
    public static function getKeyPrefix(string $repository): string
    {
        return strtolower(str_replace(['Repository', 'Interface'], '', class_basename($repository)));
    }
    
    /**
     * Build a standardized cache key
     */
    public static function buildKey(string $entity, string $operation, ...$params): string
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
     * Get TTL based on entity and operation type
     */
    public static function getTTLForOperation(string $entity, string $operation): int
    {
        return match(true) {
            str_contains($operation, 'statistics') => self::STATISTICS_TTL,
            str_contains($operation, 'search') => self::SEARCH_TTL,
            str_contains($operation, 'enum') => self::ENUM_TTL,
            str_contains($operation, 'paginate') => self::PAGINATION_TTL,
            str_contains($operation, 'with_') => self::RELATIONS_TTL,
            default => self::DEFAULT_TTL
        };
    }
}
