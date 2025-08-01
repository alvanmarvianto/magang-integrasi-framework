<?php

namespace App\Repositories;

class CacheConfig
{
    public const DEFAULT_TTL = 3600; // 1 hour
    public const ENUM_TTL = 86400; // 24 hours
    public const STATISTICS_TTL = 1800; // 30 minutes
    public const SEARCH_TTL = 900; // 15 minutes
    
    /**
     * Get TTL for specific cache type
     */
    public static function getTTL(string $type): int
    {
        return config("cache.repositories.{$type}", match($type) {
            'enum', 'technology' => self::ENUM_TTL,
            'statistics' => self::STATISTICS_TTL,
            'search' => self::SEARCH_TTL,
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
}
