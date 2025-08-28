<?php

namespace App\Repositories;

class CacheConfig
{
    public const DEFAULT_TTL = 3600; // 1 hour
    public const ENUM_TTL = 86400; // 24 hours
    public const STATISTICS_TTL = 1800; // 30 minutes
    public const RELATIONS_TTL = 1800; // 30 minutes
    
    /**
     * Get TTL for specific cache type
     */
    public static function getTTL(string $type): int
    {
        return config("cache.repositories.{$type}", match($type) {
            'enum', 'technology', 'long' => self::ENUM_TTL,
            'statistics' => self::STATISTICS_TTL,
            'relations' => self::RELATIONS_TTL,
            default => self::DEFAULT_TTL
        });
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
}
