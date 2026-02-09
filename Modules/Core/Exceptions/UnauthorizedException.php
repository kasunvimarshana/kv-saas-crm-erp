<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

use Exception;

/**
 * Exception thrown when user is not authorized to perform an action
 */
class UnauthorizedException extends Exception
{
    /**
     * Create a new unauthorized exception
     *
     * @param string $message
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = "Unauthorized action",
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 403, $previous);
    }

    /**
     * Create exception for missing permission
     *
     * @param string $permission
     * @return static
     */
    public static function missingPermission(string $permission): static
    {
        return new static("Missing required permission: {$permission}");
    }

    /**
     * Create exception for missing role
     *
     * @param string $role
     * @return static
     */
    public static function missingRole(string $role): static
    {
        return new static("Missing required role: {$role}");
    }

    /**
     * Create exception for tenant access violation
     *
     * @param string $resourceType
     * @return static
     */
    public static function tenantAccessViolation(string $resourceType): static
    {
        return new static("Cannot access {$resourceType} from different tenant");
    }

    /**
     * Create exception for action not allowed
     *
     * @param string $action
     * @param string $resource
     * @return static
     */
    public static function actionNotAllowed(string $action, string $resource): static
    {
        return new static("Not authorized to {$action} {$resource}");
    }
}
