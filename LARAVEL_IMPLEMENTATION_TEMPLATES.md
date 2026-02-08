# Laravel Implementation Templates

## Overview

This document provides ready-to-use code templates and configuration examples for implementing the kv-saas-crm-erp system using Laravel. These templates follow the architectural principles outlined in the core documentation and can be used as starting points for actual development.

## Table of Contents

1. [Project Structure Setup](#1-project-structure-setup)
2. [Multi-Tenancy Implementation](#2-multi-tenancy-implementation)
3. [Module Manifest System (Odoo-Inspired)](#3-module-manifest-system-odoo-inspired)
4. [Polymorphic Translatable Models](#4-polymorphic-translatable-models)
5. [Repository Pattern Implementation](#5-repository-pattern-implementation)
6. [Domain Event System](#6-domain-event-system)
7. [API Resources and Controllers](#7-api-resources-and-controllers)
8. [File Storage Configuration](#8-file-storage-configuration)
9. [Testing Templates](#9-testing-templates)
10. [Deployment Configuration](#10-deployment-configuration)

---

## 1. Project Structure Setup

### composer.json (Root Project)

```json
{
    "name": "kv/saas-crm-erp",
    "description": "Multi-tenant modular SaaS ERP/CRM system",
    "type": "project",
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "nwidart/laravel-modules": "^11.0",
        "stancl/tenancy": "^3.8",
        "spatie/laravel-permission": "^6.0",
        "spatie/laravel-translatable": "^6.0",
        "intervention/image": "^3.0",
        "league/flysystem-aws-s3-v3": "^3.0",
        "darkaonline/l5-swagger": "^8.5",
        "predis/predis": "^2.2"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0",
        "mockery/mockery": "^1.6",
        "fakerphp/faker": "^1.23",
        "laravel/sail": "^1.27",
        "nunomaduro/collision": "^8.0",
        "pestphp/pest": "^2.0",
        "pestphp/pest-plugin-laravel": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Modules\\": "Modules/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi"
        ],
        "post-update-cmd": [
            "@php artisan vendor:publish --tag=laravel-assets --ansi --force"
        ],
        "post-root-package-install": [
            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""
        ],
        "post-create-project-cmd": [
            "@php artisan key:generate --ansi"
        ],
        "test": "pest",
        "test-coverage": "pest --coverage"
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

### modules_statuses.json

```json
{
    "Core": true,
    "Tenancy": true,
    "Authentication": true,
    "Sales": true,
    "Inventory": true,
    "Accounting": true,
    "HR": true,
    "Procurement": true,
    "Warehouse": true,
    "Documents": true,
    "Reporting": true,
    "Notifications": true
}
```

---

## 2. Multi-Tenancy Implementation

### config/tenancy.php

```php
<?php

return [
    /**
     * Tenant model to use
     */
    'tenant_model' => \App\Models\Tenant::class,

    /**
     * Tenant identification method
     * Options: subdomain, domain, header, path
     */
    'identification' => [
        'method' => env('TENANT_IDENTIFICATION', 'subdomain'),
        'header_name' => 'X-Tenant-ID',
        'path_prefix' => 'tenant',
    ],

    /**
     * Database configuration
     */
    'database' => [
        /**
         * Tenant isolation strategy
         * Options: database, schema, row_level
         */
        'strategy' => env('TENANT_DB_STRATEGY', 'database'),
        
        /**
         * Database prefix for tenant databases
         */
        'prefix' => 'tenant_',
        
        /**
         * Template database for creating new tenants
         */
        'template' => env('TENANT_DB_TEMPLATE'),
        
        /**
         * Central database connection
         */
        'central_connection' => 'central',
        
        /**
         * Tenant database connection template
         */
        'tenant_connection' => 'tenant',
    ],

    /**
     * Cache configuration
     */
    'cache' => [
        /**
         * Cache prefix for tenant-specific data
         */
        'prefix' => 'tenant_{tenant_id}',
        
        /**
         * Cache tags for tenant data
         */
        'tags_enabled' => true,
    ],

    /**
     * Storage configuration
     */
    'storage' => [
        'root_path' => 'tenants/{tenant_id}',
        'disks' => ['documents', 'images', 'exports'],
    ],

    /**
     * Features configuration
     */
    'features' => [
        'database_creation' => true,
        'database_deletion' => true,
        'migrations' => true,
        'seeders' => true,
    ],

    /**
     * Queue configuration
     */
    'queue' => [
        'tenant_aware' => true,
        'queue_prefix' => 'tenant_{tenant_id}',
    ],

    /**
     * Session configuration
     */
    'session' => [
        'tenant_aware' => true,
        'cookie_prefix' => 'tenant_{tenant_id}',
    ],
];
```

### app/Models/Tenant.php

```php
<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $fillable = [
        'id',
        'name',
        'email',
        'domain',
        'database',
        'plan',
        'status',
        'settings',
        'features',
    ];

    protected $casts = [
        'settings' => 'array',
        'features' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get custom columns to be returned by the tenant identification middleware
     */
    public function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'plan',
            'status',
        ];
    }

    /**
     * Check if tenant is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if tenant has a specific feature
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    /**
     * Get tenant storage path
     */
    public function getStoragePath(string $disk = 'documents'): string
    {
        return str_replace(
            '{tenant_id}',
            $this->id,
            config("tenancy.storage.root_path")
        );
    }
}
```

### app/Http/Middleware/InitializeTenancy.php

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Tenancy;
use Stancl\Tenancy\Resolvers\DomainTenantResolver;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancy
{
    public function __construct(
        protected Tenancy $tenancy,
        protected DomainTenantResolver $resolver
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Try to identify tenant
        $tenant = $this->resolveTenant($request);

        if (!$tenant) {
            return response()->json([
                'error' => 'Tenant not found',
            ], 404);
        }

        // Check if tenant is active
        if (!$tenant->isActive()) {
            return response()->json([
                'error' => 'Tenant is not active',
            ], 403);
        }

        // Initialize tenant context
        $this->tenancy->initialize($tenant);

        // Add tenant to request
        $request->merge(['tenant' => $tenant]);

        return $next($request);
    }

    protected function resolveTenant(Request $request): ?\App\Models\Tenant
    {
        $method = config('tenancy.identification.method');

        return match ($method) {
            'subdomain' => $this->resolveBySubdomain($request),
            'header' => $this->resolveByHeader($request),
            'domain' => $this->resolver->resolve($request),
            default => null,
        };
    }

    protected function resolveBySubdomain(Request $request): ?\App\Models\Tenant
    {
        $domain = $request->getHost();
        $subdomain = explode('.', $domain)[0];

        return \App\Models\Tenant::where('id', $subdomain)->first();
    }

    protected function resolveByHeader(Request $request): ?\App\Models\Tenant
    {
        $headerName = config('tenancy.identification.header_name');
        $tenantId = $request->header($headerName);

        return \App\Models\Tenant::find($tenantId);
    }
}
```

### app/Providers/TenancyServiceProvider.php

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\Tenancy;

class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Configure tenant-aware models
        $this->configureTenantAwareModels();

        // Register tenant lifecycle events
        $this->registerTenantEvents();

        // Configure tenant storage
        $this->configureTenantStorage();
    }

    protected function configureTenantAwareModels(): void
    {
        // Add global scope to all tenant models
        foreach ($this->getTenantModels() as $model) {
            $model::addGlobalScope(new \App\Scopes\TenantScope);
        }
    }

    protected function registerTenantEvents(): void
    {
        // When tenant is created
        Tenancy::created(function ($tenant) {
            // Create tenant database
            $tenant->createDatabase();

            // Run migrations
            $tenant->runMigrations();

            // Seed initial data
            $tenant->runSeeder();

            // Create storage directories
            $this->createTenantStorage($tenant);
        });

        // When tenant is deleted
        Tenancy::deleted(function ($tenant) {
            // Delete tenant database
            $tenant->deleteDatabase();

            // Delete tenant storage
            $this->deleteTenantStorage($tenant);
        });
    }

    protected function configureTenantStorage(): void
    {
        // Configure tenant-specific storage disks
        $disks = config('tenancy.storage.disks');

        foreach ($disks as $disk) {
            config([
                "filesystems.disks.tenant_{$disk}" => array_merge(
                    config("filesystems.disks.{$disk}"),
                    [
                        'root' => config('tenancy.storage.root_path'),
                    ]
                ),
            ]);
        }
    }

    protected function getTenantModels(): array
    {
        return [
            \Modules\Sales\Entities\Customer::class,
            \Modules\Sales\Entities\SalesOrder::class,
            \Modules\Inventory\Entities\Product::class,
            // Add all tenant-aware models
        ];
    }

    protected function createTenantStorage($tenant): void
    {
        $disks = config('tenancy.storage.disks');

        foreach ($disks as $disk) {
            $path = str_replace('{tenant_id}', $tenant->id, config('tenancy.storage.root_path'));
            \Storage::disk($disk)->makeDirectory($path);
        }
    }

    protected function deleteTenantStorage($tenant): void
    {
        $disks = config('tenancy.storage.disks');

        foreach ($disks as $disk) {
            $path = str_replace('{tenant_id}', $tenant->id, config('tenancy.storage.root_path'));
            \Storage::disk($disk)->deleteDirectory($path);
        }
    }
}
```

---

## 3. Module Manifest System (Odoo-Inspired)

### Modules/Sales/module.json

```json
{
    "name": "Sales",
    "alias": "sales",
    "description": "Sales and CRM management module",
    "version": "1.0.0",
    "category": "business",
    "author": "KV Team",
    "license": "MIT",
    "active": true,
    "priority": 10,
    "providers": [
        "Modules\\Sales\\Providers\\SalesServiceProvider"
    ],
    "aliases": {},
    "files": [],
    "requires": [
        "Core",
        "Tenancy"
    ],
    "dependencies": {
        "Inventory": "^1.0",
        "Accounting": "^1.0"
    ],
    "permissions": [
        "sales.view",
        "sales.create",
        "sales.edit",
        "sales.delete",
        "sales.export"
    ],
    "menu": [
        {
            "name": "Sales",
            "icon": "shopping-cart",
            "route": "sales.dashboard",
            "permission": "sales.view",
            "order": 10,
            "children": [
                {
                    "name": "Customers",
                    "route": "sales.customers.index",
                    "permission": "sales.view"
                },
                {
                    "name": "Orders",
                    "route": "sales.orders.index",
                    "permission": "sales.view"
                },
                {
                    "name": "Quotes",
                    "route": "sales.quotes.index",
                    "permission": "sales.view"
                }
            ]
        }
    ],
    "widgets": [
        {
            "name": "SalesOverview",
            "component": "SalesOverviewWidget",
            "permissions": ["sales.view"]
        }
    ],
    "jobs": [
        {
            "name": "SyncCustomers",
            "schedule": "daily",
            "time": "02:00"
        }
    ],
    "events": [
        {
            "event": "Modules\\Sales\\Events\\OrderPlaced",
            "listeners": [
                "Modules\\Inventory\\Listeners\\ReserveStock",
                "Modules\\Accounting\\Listeners\\CreateInvoice"
            ]
        }
    ]
}
```

### app/Services/ModuleManager.php

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module;

class ModuleManager
{
    protected array $manifests = [];

    public function loadManifests(): void
    {
        $modules = Module::all();

        foreach ($modules as $module) {
            $manifestPath = $module->getPath() . '/module.json';

            if (File::exists($manifestPath)) {
                $this->manifests[$module->getName()] = json_decode(
                    File::get($manifestPath),
                    true
                );
            }
        }
    }

    public function getManifest(string $module): ?array
    {
        return $this->manifests[$module] ?? null;
    }

    public function getDependencies(string $module): array
    {
        $manifest = $this->getManifest($module);
        return $manifest['dependencies'] ?? [];
    }

    public function checkDependencies(string $module): array
    {
        $dependencies = $this->getDependencies($module);
        $missing = [];

        foreach ($dependencies as $dependency => $version) {
            if (!Module::has($dependency) || !Module::isEnabled($dependency)) {
                $missing[] = $dependency;
            }
        }

        return $missing;
    }

    public function getPermissions(string $module): array
    {
        $manifest = $this->getManifest($module);
        return $manifest['permissions'] ?? [];
    }

    public function getMenu(string $module): array
    {
        $manifest = $this->getManifest($module);
        return $manifest['menu'] ?? [];
    }

    public function registerPermissions(): void
    {
        foreach ($this->manifests as $moduleName => $manifest) {
            $permissions = $manifest['permissions'] ?? [];

            foreach ($permissions as $permission) {
                \Spatie\Permission\Models\Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'web',
                ]);
            }
        }
    }

    public function getModulesOrderedByPriority(): array
    {
        $modules = $this->manifests;

        uasort($modules, function ($a, $b) {
            $priorityA = $a['priority'] ?? 100;
            $priorityB = $b['priority'] ?? 100;
            return $priorityA <=> $priorityB;
        });

        return array_keys($modules);
    }

    public function validateModule(string $module): array
    {
        $errors = [];
        $manifest = $this->getManifest($module);

        if (!$manifest) {
            $errors[] = "Manifest file not found for module {$module}";
            return $errors;
        }

        // Check required fields
        $requiredFields = ['name', 'version', 'description'];
        foreach ($requiredFields as $field) {
            if (!isset($manifest[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        // Check dependencies
        $missing = $this->checkDependencies($module);
        if (!empty($missing)) {
            $errors[] = "Missing dependencies: " . implode(', ', $missing);
        }

        return $errors;
    }
}
```

---

## 4. Polymorphic Translatable Models

### app/Models/Concerns/Translatable.php

```php
<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Translatable
{
    /**
     * Get all translations for the model
     */
    public function translations(): MorphMany
    {
        return $this->morphMany(\App\Models\Translation::class, 'translatable');
    }

    /**
     * Get translation for specific locale
     */
    public function translate(string $locale = null, bool $fallback = true): ?object
    {
        $locale = $locale ?? app()->getLocale();

        $translation = $this->translations()
            ->where('locale', $locale)
            ->first();

        if (!$translation && $fallback) {
            $translation = $this->translations()
                ->where('locale', config('app.fallback_locale'))
                ->first();
        }

        return $translation;
    }

    /**
     * Get translated attribute
     */
    public function getTranslation(string $key, string $locale = null, bool $fallback = true): ?string
    {
        $translation = $this->translate($locale, $fallback);

        if (!$translation) {
            return null;
        }

        return $translation->$key ?? null;
    }

    /**
     * Set translation for specific locale
     */
    public function setTranslation(string $locale, array $attributes): void
    {
        $this->translations()->updateOrCreate(
            ['locale' => $locale],
            $attributes
        );
    }

    /**
     * Delete translation for specific locale
     */
    public function deleteTranslation(string $locale): void
    {
        $this->translations()->where('locale', $locale)->delete();
    }

    /**
     * Get all locales that have translations
     */
    public function getAvailableLocales(): array
    {
        return $this->translations()->pluck('locale')->toArray();
    }

    /**
     * Magic getter for translated attributes
     */
    public function __get($key)
    {
        // Check if attribute is translatable
        if (in_array($key, $this->translatable ?? [])) {
            return $this->getTranslation($key);
        }

        return parent::__get($key);
    }
}
```

### app/Models/Translation.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Translation extends Model
{
    protected $fillable = [
        'translatable_type',
        'translatable_id',
        'locale',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    /**
     * Get the parent translatable model
     */
    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by locale
     */
    public function scopeForLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }
}
```

### Modules/Sales/Entities/Product.php (Example Usage)

```php
<?php

namespace Modules\Inventory\Entities;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use Translatable;

    protected $fillable = [
        'sku',
        'price',
        'cost',
        'stock_quantity',
        'status',
    ];

    /**
     * Translatable attributes
     */
    protected array $translatable = [
        'name',
        'description',
        'specifications',
    ];

    /**
     * Get product name in current locale
     */
    public function getNameAttribute(): string
    {
        return $this->getTranslation('name') ?? $this->sku;
    }

    /**
     * Get product description in current locale
     */
    public function getDescriptionAttribute(): ?string
    {
        return $this->getTranslation('description');
    }
}
```

### database/migrations/create_translations_table.php

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->morphs('translatable');
            $table->string('locale', 10)->index();
            $table->json('value');
            $table->timestamps();

            $table->unique(['translatable_type', 'translatable_id', 'locale'], 'translations_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
```

---

## 5. Repository Pattern Implementation

### app/Repositories/Contracts/RepositoryInterface.php

```php
<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RepositoryInterface
{
    /**
     * Get all records
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Find record by ID
     */
    public function find(int|string $id, array $columns = ['*']): ?Model;

    /**
     * Find record by ID or fail
     */
    public function findOrFail(int|string $id, array $columns = ['*']): Model;

    /**
     * Find by specific field
     */
    public function findBy(string $field, mixed $value, array $columns = ['*']): Collection;

    /**
     * Paginate results
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Create new record
     */
    public function create(array $data): Model;

    /**
     * Update record
     */
    public function update(int|string $id, array $data): bool;

    /**
     * Delete record
     */
    public function delete(int|string $id): bool;

    /**
     * Search records
     */
    public function search(string $query, array $columns = []): Collection;

    /**
     * Apply filters
     */
    public function filter(array $filters): self;

    /**
     * Order by
     */
    public function orderBy(string $column, string $direction = 'asc'): self;

    /**
     * Load relationships
     */
    public function with(array|string $relations): self;
}
```

### app/Repositories/BaseRepository.php

```php
<?php

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;
    protected $query;

    public function __construct()
    {
        $this->model = $this->resolveModel();
        $this->query = $this->model->newQuery();
    }

    abstract protected function resolveModel(): Model;

    public function all(array $columns = ['*']): Collection
    {
        return $this->query->get($columns);
    }

    public function find(int|string $id, array $columns = ['*']): ?Model
    {
        return $this->query->find($id, $columns);
    }

    public function findOrFail(int|string $id, array $columns = ['*']): Model
    {
        return $this->query->findOrFail($id, $columns);
    }

    public function findBy(string $field, mixed $value, array $columns = ['*']): Collection
    {
        return $this->query->where($field, $value)->get($columns);
    }

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->query->paginate($perPage, $columns);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(int|string $id, array $data): bool
    {
        $model = $this->findOrFail($id);
        return $model->update($data);
    }

    public function delete(int|string $id): bool
    {
        $model = $this->findOrFail($id);
        return $model->delete();
    }

    public function search(string $query, array $columns = []): Collection
    {
        if (empty($columns)) {
            $columns = $this->model->getFillable();
        }

        $this->query->where(function ($q) use ($query, $columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'LIKE', "%{$query}%");
            }
        });

        return $this->query->get();
    }

    public function filter(array $filters): self
    {
        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                $this->query->whereIn($field, $value);
            } else {
                $this->query->where($field, $value);
            }
        }

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->query->orderBy($column, $direction);
        return $this;
    }

    public function with(array|string $relations): self
    {
        $this->query->with($relations);
        return $this;
    }

    protected function resetQuery(): void
    {
        $this->query = $this->model->newQuery();
    }
}
```

### Modules/Sales/Repositories/CustomerRepository.php (Example)

```php
<?php

namespace Modules\Sales\Repositories;

use App\Repositories\BaseRepository;
use Modules\Sales\Entities\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class CustomerRepository extends BaseRepository
{
    protected function resolveModel(): Model
    {
        return new Customer();
    }

    /**
     * Get active customers
     */
    public function getActive(): Collection
    {
        return $this->query
            ->where('status', 'active')
            ->get();
    }

    /**
     * Get customers by type
     */
    public function getByType(string $type): Collection
    {
        return $this->query
            ->where('type', $type)
            ->get();
    }

    /**
     * Get customers with outstanding balances
     */
    public function getWithOutstandingBalances(): Collection
    {
        return $this->query
            ->where('balance', '>', 0)
            ->get();
    }

    /**
     * Search customers by name, email, or phone
     */
    public function searchCustomers(string $query): Collection
    {
        return $this->query
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->orWhere('phone', 'LIKE', "%{$query}%")
            ->get();
    }
}
```

---

## 6. Domain Event System

### app/Events/DomainEvent.php

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class DomainEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly array $payload,
        public readonly string $tenantId,
        public readonly \DateTimeImmutable $occurredAt
    ) {}

    public static function create(array $payload): static
    {
        return new static(
            payload: $payload,
            tenantId: tenant('id'),
            occurredAt: new \DateTimeImmutable()
        );
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
```

### Modules/Sales/Events/OrderPlaced.php

```php
<?php

namespace Modules\Sales\Events;

use App\Events\DomainEvent;
use Modules\Sales\Entities\SalesOrder;

class OrderPlaced extends DomainEvent
{
    public static function fromOrder(SalesOrder $order): static
    {
        return static::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
            'items' => $order->items->map(fn($item) => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
            ])->toArray(),
        ]);
    }

    public function getOrderId(): int
    {
        return $this->payload['order_id'];
    }

    public function getCustomerId(): int
    {
        return $this->payload['customer_id'];
    }

    public function getTotalAmount(): float
    {
        return $this->payload['total_amount'];
    }

    public function getItems(): array
    {
        return $this->payload['items'];
    }
}
```

### Modules/Inventory/Listeners/ReserveStock.php

```php
<?php

namespace Modules\Inventory\Listeners;

use Modules\Sales\Events\OrderPlaced;
use Modules\Inventory\Services\StockService;
use Illuminate\Contracts\Queue\ShouldQueue;

class ReserveStock implements ShouldQueue
{
    public function __construct(
        private StockService $stockService
    ) {}

    public function handle(OrderPlaced $event): void
    {
        foreach ($event->getItems() as $item) {
            $this->stockService->reserve(
                productId: $item['product_id'],
                quantity: $item['quantity'],
                reference: "order:{$event->getOrderId()}"
            );
        }
    }
}
```

### app/Providers/EventServiceProvider.php

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings
     */
    protected $listen = [
        \Modules\Sales\Events\OrderPlaced::class => [
            \Modules\Inventory\Listeners\ReserveStock::class,
            \Modules\Accounting\Listeners\CreateInvoice::class,
            \Modules\Notifications\Listeners\SendOrderConfirmation::class,
        ],

        \Modules\Sales\Events\OrderCancelled::class => [
            \Modules\Inventory\Listeners\ReleaseStock::class,
            \Modules\Accounting\Listeners\VoidInvoice::class,
        ],

        \Modules\Inventory\Events\StockLowAlert::class => [
            \Modules\Procurement\Listeners\CreatePurchaseRequisition::class,
            \Modules\Notifications\Listeners\NotifyInventoryManager::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();

        // Load event-listener mappings from module manifests
        app(\App\Services\ModuleManager::class)->loadEventMappings();
    }
}
```

---

## 7. API Resources and Controllers

### app/Http/Resources/BaseResource.php

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseResource extends JsonResource
{
    /**
     * Include tenant ID in all resources
     */
    protected function withTenantId(array $data): array
    {
        return array_merge($data, [
            'tenant_id' => tenant('id'),
        ]);
    }

    /**
     * Include timestamps
     */
    protected function withTimestamps(array $data): array
    {
        return array_merge($data, [
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ]);
    }
}
```

### Modules/Sales/Http/Resources/CustomerResource.php

```php
<?php

namespace Modules\Sales\Http\Resources;

use App\Http\Resources\BaseResource;

class CustomerResource extends BaseResource
{
    public function toArray($request): array
    {
        return $this->withTimestamps([
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'type' => $this->type,
            'status' => $this->status,
            'balance' => $this->balance,
            'currency' => $this->currency,
            'addresses' => AddressResource::collection($this->whenLoaded('addresses')),
            'contacts' => ContactResource::collection($this->whenLoaded('contacts')),
            'orders_count' => $this->when(
                isset($this->orders_count),
                $this->orders_count
            ),
        ]);
    }
}
```

### Modules/Sales/Http/Controllers/Api/CustomerController.php

```php
<?php

namespace Modules\Sales\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Sales\Http\Requests\CreateCustomerRequest;
use Modules\Sales\Http\Requests\UpdateCustomerRequest;
use Modules\Sales\Http\Resources\CustomerResource;
use Modules\Sales\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $customerService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/customers",
     *     tags={"Customers"},
     *     summary="Get list of customers",
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(): AnonymousResourceCollection
    {
        $customers = $this->customerService->paginate(
            perPage: request('per_page', 15)
        );

        return CustomerResource::collection($customers);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/customers",
     *     tags={"Customers"},
     *     summary="Create a new customer",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Customer created successfully"
     *     )
     * )
     */
    public function store(CreateCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->create($request->validated());

        return (new CustomerResource($customer))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/customers/{id}",
     *     tags={"Customers"},
     *     summary="Get customer details",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found"
     *     )
     * )
     */
    public function show(int $id): CustomerResource
    {
        $customer = $this->customerService->findOrFail($id);

        return new CustomerResource($customer);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/customers/{id}",
     *     tags={"Customers"},
     *     summary="Update customer",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer updated successfully"
     *     )
     * )
     */
    public function update(UpdateCustomerRequest $request, int $id): CustomerResource
    {
        $customer = $this->customerService->update($id, $request->validated());

        return new CustomerResource($customer);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/customers/{id}",
     *     tags={"Customers"},
     *     summary="Delete customer",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Customer deleted successfully"
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->customerService->delete($id);

        return response()->json(null, 204);
    }
}
```

---

## 8. File Storage Configuration

### config/filesystems.php (Extended)

```php
<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

        // Tenant-specific disks
        'tenant_documents' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_DOCUMENTS_BUCKET'),
            'root' => 'tenants/{tenant_id}/documents',
            'visibility' => 'private',
        ],

        'tenant_images' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_IMAGES_BUCKET'),
            'root' => 'tenants/{tenant_id}/images',
            'visibility' => 'public',
        ],

        'tenant_exports' => [
            'driver' => 'local',
            'root' => storage_path('app/tenants/{tenant_id}/exports'),
            'visibility' => 'private',
        ],

        'archive' => [
            'driver' => 's3',
            'key' => env('AWS_GLACIER_ACCESS_KEY_ID'),
            'secret' => env('AWS_GLACIER_SECRET_ACCESS_KEY'),
            'region' => env('AWS_GLACIER_REGION'),
            'bucket' => env('AWS_GLACIER_BUCKET'),
            'storage_class' => 'GLACIER',
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
```

---

## 9. Testing Templates

### tests/Feature/Api/CustomerTest.php

```php
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Modules\Sales\Entities\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize tenant context
        $this->initializeTenant();

        // Authenticate user
        $this->actingAs($this->createUser());
    }

    public function test_can_list_customers(): void
    {
        Customer::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/customers');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta',
            ]);
    }

    public function test_can_create_customer(): void
    {
        $data = [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'type' => 'business',
        ];

        $response = $this->postJson('/api/v1/customers', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Test Customer']);

        $this->assertDatabaseHas('customers', [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
        ]);
    }

    public function test_can_show_customer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->getJson("/api/v1/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $customer->id,
                'name' => $customer->name,
            ]);
    }

    public function test_can_update_customer(): void
    {
        $customer = Customer::factory()->create();

        $data = [
            'name' => 'Updated Customer',
            'email' => 'updated@example.com',
        ];

        $response = $this->putJson("/api/v1/customers/{$customer->id}", $data);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Customer']);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Customer',
        ]);
    }

    public function test_can_delete_customer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->deleteJson("/api/v1/customers/{$customer->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('customers', [
            'id' => $customer->id,
        ]);
    }
}
```

---

## 10. Deployment Configuration

### docker-compose.yml

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    image: kv-saas-crm-erp:latest
    container_name: kv-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./docker/php/local.ini:/usr/local/etc/php/conf.d/local.ini
    networks:
      - kv-network
    depends_on:
      - postgres
      - redis

  nginx:
    image: nginx:alpine
    container_name: kv-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www
      - ./docker/nginx/conf.d/:/etc/nginx/conf.d/
      - ./docker/nginx/ssl/:/etc/nginx/ssl/
    networks:
      - kv-network
    depends_on:
      - app

  postgres:
    image: postgres:16-alpine
    container_name: kv-postgres
    restart: unless-stopped
    environment:
      POSTGRES_DB: ${DB_DATABASE}
      POSTGRES_USER: ${DB_USERNAME}
      POSTGRES_PASSWORD: ${DB_PASSWORD}
      PGDATA: /var/lib/postgresql/data
    volumes:
      - postgres-data:/var/lib/postgresql/data
    ports:
      - "5432:5432"
    networks:
      - kv-network

  redis:
    image: redis:7-alpine
    container_name: kv-redis
    restart: unless-stopped
    command: redis-server --appendonly yes --requirepass "${REDIS_PASSWORD}"
    volumes:
      - redis-data:/data
    ports:
      - "6379:6379"
    networks:
      - kv-network

  rabbitmq:
    image: rabbitmq:3-management-alpine
    container_name: kv-rabbitmq
    restart: unless-stopped
    environment:
      RABBITMQ_DEFAULT_USER: ${RABBITMQ_USER}
      RABBITMQ_DEFAULT_PASS: ${RABBITMQ_PASSWORD}
    volumes:
      - rabbitmq-data:/var/lib/rabbitmq
    ports:
      - "5672:5672"
      - "15672:15672"
    networks:
      - kv-network

networks:
  kv-network:
    driver: bridge

volumes:
  postgres-data:
  redis-data:
  rabbitmq-data:
```

### Dockerfile

```dockerfile
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . /var/www

# Install PHP dependencies
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

# Expose port 9000
EXPOSE 9000

CMD ["php-fpm"]
```

---

## Conclusion

These templates provide a solid foundation for implementing the kv-saas-crm-erp system using Laravel. Each template follows the architectural principles outlined in the core documentation and can be customized based on specific requirements.

### Next Steps

1. Set up the project using the provided composer.json
2. Configure multi-tenancy with the provided templates
3. Create modules using the manifest system
4. Implement domain models with translatable support
5. Set up repositories for data access
6. Implement domain events and listeners
7. Create API endpoints with proper resources
8. Configure file storage for tenant isolation
9. Write comprehensive tests
10. Deploy using Docker configuration
