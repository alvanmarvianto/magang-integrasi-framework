<?php

namespace App\Repositories\Exceptions;

use Exception;

class RepositoryException extends Exception
{
    public static function entityNotFound(string $entity, mixed $identifier): self
    {
        return new self("Entity '{$entity}' with identifier '{$identifier}' not found.");
    }
    
    public static function createFailed(string $entity, string $reason = ''): self
    {
        $message = "Failed to create entity '{$entity}'";
        if ($reason) {
            $message .= ": {$reason}";
        }
        return new self($message);
    }
    
    public static function updateFailed(string $entity, mixed $identifier, string $reason = ''): self
    {
        $message = "Failed to update entity '{$entity}' with identifier '{$identifier}'";
        if ($reason) {
            $message .= ": {$reason}";
        }
        return new self($message);
    }
    
    public static function deleteFailed(string $entity, mixed $identifier, string $reason = ''): self
    {
        $message = "Failed to delete entity '{$entity}' with identifier '{$identifier}'";
        if ($reason) {
            $message .= ": {$reason}";
        }
        return new self($message);
    }
    
    public static function cacheOperationFailed(string $operation, string $key, string $reason = ''): self
    {
        $message = "Cache operation '{$operation}' failed for key '{$key}'";
        if ($reason) {
            $message .= ": {$reason}";
        }
        return new self($message);
    }
}
