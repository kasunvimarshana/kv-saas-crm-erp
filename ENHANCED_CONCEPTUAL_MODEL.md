# Enhanced Conceptual Model: Laravel-Based Modular SaaS ERP/CRM

---

**⚠️ IMPLEMENTATION PRINCIPLE**: Rely strictly on native Laravel and Vue features. Always implement functionality manually instead of using third-party libraries.

---


## Overview

This document extends the existing architectural documentation by integrating Laravel-specific implementation patterns, Odoo-inspired modular plugin architecture, polymorphic translatable models, and proven multi-tenant SaaS strategies from real-world implementations like the Emmy Awards' Orthicon platform.

## Table of Contents

1. [Laravel Modular Architecture Patterns](#laravel-modular-architecture-patterns)
2. [Plugin Architecture Design (Odoo-Inspired)](#plugin-architecture-design-odoo-inspired)
3. [Polymorphic Translatable Models](#polymorphic-translatable-models)
4. [Multi-Tenant Implementation Patterns](#multi-tenant-implementation-patterns)
5. [API Design with Swagger/OpenAPI](#api-design-with-swaggeropenapi)
6. [Integration with Existing Architecture](#integration-with-existing-architecture)

---

## 1. Laravel Modular Architecture Patterns

### 1.1 Module Organization Structure

Based on Laravel best practices and nWidart/laravel-modules patterns:

```
src/
  Modules/
    Sales/
      ├── Config/
      │   └── config.php
      ├── Database/
      │   ├── Migrations/
      │   ├── Seeders/
      │   └── Factories/
      ├── Entities/          # Domain Models
      │   ├── Customer.php
      │   ├── SalesOrder.php
      │   └── Lead.php
      ├── Repositories/      # Data Access Layer
      │   ├── CustomerRepository.php
      │   └── Contracts/
      ├── Services/          # Business Logic
      │   ├── OrderProcessingService.php
      │   └── PricingService.php
      ├── Http/
      │   ├── Controllers/   # Interface Adapters
      │   ├── Requests/      # Validation
      │   ├── Resources/     # API Transformers
      │   └── Middleware/
      ├── Events/            # Domain Events
      │   ├── OrderPlaced.php
      │   └── CustomerCreated.php
      ├── Listeners/
      ├── Routes/
      │   ├── api.php
      │   └── web.php
      ├── Resources/
      │   ├── views/
      │   └── lang/
      ├── Tests/
      │   ├── Unit/
      │   ├── Feature/
      │   └── Integration/
      ├── Providers/
      │   └── SalesServiceProvider.php
      ├── module.json        # Module metadata
      └── composer.json      # Module dependencies
```

### 1.2 Module Service Provider Pattern

```php
<?php

namespace Modules\Sales\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Sales\Repositories\CustomerRepository;
use Modules\Sales\Repositories\Contracts\CustomerRepositoryInterface;

class SalesServiceProvider extends ServiceProvider
{
    /**
     * Register module services
     */
    public function register(): void
    {
        // Register repositories
        $this->app->bind(
            CustomerRepositoryInterface::class,
            CustomerRepository::class
        );
        
        // Register services
        $this->app->singleton(OrderProcessingService::class);
        
        // Load module config
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php', 
            'sales'
        );
    }

    /**
     * Boot module services
     */
    public function boot(): void
    {
        // Register routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        
        // Register migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        
        // Register translations
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'sales');
        
        // Register views
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'sales');
        
        // Publish assets
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('sales.php'),
        ], 'config');
    }
}
```

### 1.3 Module Metadata (module.json)

```json
{
  "name": "Sales",
  "alias": "sales",
  "description": "Sales and CRM management module",
  "version": "1.0.0",
  "keywords": ["sales", "crm", "customer"],
  "priority": 10,
  "providers": [
    "Modules\\Sales\\Providers\\SalesServiceProvider"
  ],
  "aliases": {},
  "files": [],
  "requires": {
    "php": "^8.1",
    "laravel/framework": "^10.0|^11.0"
  },
  "dependencies": [
    "Organization",
    "Product"
  ]
}
```

### 1.4 Inter-Module Communication Patterns

#### Event-Driven Communication (Preferred)

```php
// In Sales Module - Dispatch Event
namespace Modules\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPlaced
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $orderId,
        public array $items,
        public float $totalAmount
    ) {}
}

// In Inventory Module - Listen to Event
namespace Modules\Inventory\Listeners;

use Modules\Sales\Events\OrderPlaced;

class ReserveStock
{
    public function handle(OrderPlaced $event): void
    {
        foreach ($event->items as $item) {
            // Reserve stock for order
            $this->stockService->reserve(
                $item['product_id'],
                $item['quantity']
            );
        }
    }
}
```

#### Service Contract Pattern (When Direct Call Needed)

```php
// Define contract in core/shared module
namespace App\Contracts;

interface PricingServiceInterface
{
    public function calculatePrice(int $productId, int $quantity): float;
}

// Implement in Sales module
namespace Modules\Sales\Services;

class PricingService implements PricingServiceInterface
{
    public function calculatePrice(int $productId, int $quantity): float
    {
        // Implementation
    }
}

// Use in other modules
namespace Modules\Order\Services;

class OrderService
{
    public function __construct(
        private PricingServiceInterface $pricingService
    ) {}
    
    public function createOrder(array $items): Order
    {
        $total = 0;
        foreach ($items as $item) {
            $total += $this->pricingService->calculatePrice(
                $item['product_id'], 
                $item['quantity']
            );
        }
        // ...
    }
}
```

### 1.5 Module Testing Strategy

```php
// Tests/Feature/CustomerManagementTest.php
namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Sales\Entities\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function it_can_create_a_customer(): void
    {
        $response = $this->postJson('/api/customers', [
            'name' => 'Acme Corp',
            'email' => 'contact@acme.com',
            'type' => 'business'
        ]);
        
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'customer_number',
                    'name',
                    'email'
                ]
            ]);
            
        $this->assertDatabaseHas('customers', [
            'name' => 'Acme Corp',
            'email' => 'contact@acme.com'
        ]);
    }
}
```

---

## 2. Plugin Architecture Design (Odoo-Inspired)

### 2.1 Plugin System Architecture

```
┌─────────────────────────────────────────────┐
│         Core Framework (Laravel)            │
│  ┌───────────────────────────────────────┐  │
│  │      Plugin Manager                   │  │
│  │  - Discovery  - Loading  - Lifecycle  │  │
│  └───────────────────────────────────────┘  │
└─────────────────────────────────────────────┘
            │        │        │
    ┌───────┘        │        └───────┐
    ▼                ▼                ▼
┌─────────┐    ┌─────────┐    ┌─────────┐
│ Plugin  │    │ Plugin  │    │ Plugin  │
│  Sales  │    │Inventory│    │   HR    │
└─────────┘    └─────────┘    └─────────┘
```

### 2.2 Plugin Manifest Structure

```json
{
  "name": "Advanced Inventory Management",
  "slug": "advanced-inventory",
  "version": "2.1.0",
  "author": "Your Company",
  "description": "Enhanced inventory tracking with lot and serial number management",
  "license": "proprietary",
  "type": "extension",
  "extends": ["inventory"],
  "requires": {
    "core": "^1.0.0",
    "modules": {
      "inventory": "^1.5.0",
      "warehouse": "^1.2.0"
    },
    "php": "^8.1",
    "laravel": "^10.0"
  },
  "provides": {
    "features": [
      "lot_tracking",
      "serial_number_management",
      "batch_operations"
    ],
    "hooks": [
      "inventory.stock.received",
      "inventory.stock.issued"
    ]
  },
  "autoload": {
    "psr-4": {
      "Plugins\\AdvancedInventory\\": "src/"
    }
  },
  "config": {
    "publishable": true,
    "migrations": true,
    "seeders": false
  }
}
```

### 2.3 Plugin Manager Implementation

```php
<?php

namespace App\Core\Plugins;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class PluginManager
{
    protected Collection $plugins;
    protected array $loaded = [];
    
    public function __construct()
    {
        $this->plugins = collect();
    }
    
    /**
     * Discover all plugins in the plugins directory
     */
    public function discover(): void
    {
        $pluginPath = base_path('plugins');
        
        if (!File::isDirectory($pluginPath)) {
            return;
        }
        
        $directories = File::directories($pluginPath);
        
        foreach ($directories as $directory) {
            $manifestPath = $directory . '/plugin.json';
            
            if (File::exists($manifestPath)) {
                $manifest = json_decode(
                    File::get($manifestPath), 
                    true
                );
                
                $this->registerPlugin($manifest, $directory);
            }
        }
    }
    
    /**
     * Register a plugin
     */
    protected function registerPlugin(array $manifest, string $path): void
    {
        $plugin = new Plugin($manifest, $path);
        
        // Validate dependencies
        if (!$this->validateDependencies($plugin)) {
            throw new PluginDependencyException(
                "Plugin {$plugin->name} has unmet dependencies"
            );
        }
        
        $this->plugins->put($plugin->slug, $plugin);
    }
    
    /**
     * Load a specific plugin
     */
    public function load(string $slug): void
    {
        if (isset($this->loaded[$slug])) {
            return; // Already loaded
        }
        
        $plugin = $this->plugins->get($slug);
        
        if (!$plugin) {
            throw new PluginNotFoundException("Plugin $slug not found");
        }
        
        // Load dependencies first
        foreach ($plugin->getDependencies() as $dependency) {
            $this->load($dependency);
        }
        
        // Load the plugin
        $plugin->boot();
        $this->loaded[$slug] = true;
    }
    
    /**
     * Load all enabled plugins
     */
    public function loadAll(): void
    {
        $enabledPlugins = $this->getEnabledPlugins();
        
        foreach ($enabledPlugins as $plugin) {
            $this->load($plugin->slug);
        }
    }
    
    /**
     * Get plugins that are enabled for current tenant
     */
    protected function getEnabledPlugins(): Collection
    {
        // In multi-tenant system, check which plugins 
        // are enabled for current tenant
        return $this->plugins->filter(function ($plugin) {
            return $plugin->isEnabledForTenant(tenant()->id);
        });
    }
}
```

### 2.4 Plugin Extension Pattern

```php
<?php

namespace Plugins\AdvancedInventory;

use Modules\Inventory\Entities\Product;
use App\Core\Plugins\PluginBase;

class AdvancedInventoryPlugin extends PluginBase
{
    /**
     * Boot the plugin
     */
    public function boot(): void
    {
        // Extend existing models
        $this->extendModel(Product::class, function ($product) {
            $product->mergeFillable(['lot_number', 'serial_number']);
            
            // Add relationships
            $product->hasMany(LotNumber::class);
            $product->hasMany(SerialNumber::class);
        });
        
        // Register event listeners
        $this->listen('inventory.stock.received', function ($event) {
            $this->assignLotNumber($event->product, $event->quantity);
        });
        
        // Register routes
        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
        
        // Register migrations
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
    
    /**
     * Register plugin services
     */
    public function register(): void
    {
        $this->app->singleton(LotNumberService::class);
        $this->app->singleton(SerialNumberService::class);
    }
}
```

### 2.5 Hook System for Extensions

```php
<?php

namespace App\Core\Plugins;

class HookRegistry
{
    protected array $hooks = [];
    
    /**
     * Register a hook listener
     */
    public function register(string $hookName, callable $callback, int $priority = 10): void
    {
        if (!isset($this->hooks[$hookName])) {
            $this->hooks[$hookName] = [];
        }
        
        $this->hooks[$hookName][] = [
            'callback' => $callback,
            'priority' => $priority
        ];
        
        // Sort by priority
        usort($this->hooks[$hookName], function ($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
    }
    
    /**
     * Execute a hook
     */
    public function execute(string $hookName, mixed $data = null): mixed
    {
        if (!isset($this->hooks[$hookName])) {
            return $data;
        }
        
        foreach ($this->hooks[$hookName] as $hook) {
            $data = call_user_func($hook['callback'], $data);
        }
        
        return $data;
    }
}

// Usage in core module
$price = hook()->execute('sales.calculate_price', [
    'product' => $product,
    'quantity' => $quantity,
    'customer' => $customer
]);

// Plugin can modify behavior
hook()->register('sales.calculate_price', function ($data) {
    // Apply volume discount
    if ($data['quantity'] > 100) {
        return $data['price'] * 0.9; // 10% discount
    }
    return $data['price'];
}, priority: 20);
```

---

## 3. Polymorphic Translatable Models

### 3.1 Translation Table Structure

```php
// Migration
Schema::create('translations', function (Blueprint $table) {
    $table->id();
    $table->string('locale', 10); // en, es, fr, de, etc.
    $table->string('translatable_type'); // Product, Category, etc.
    $table->unsignedBigInteger('translatable_id');
    $table->json('translations'); // {"name": "...", "description": "..."}
    $table->timestamps();
    
    // Indexes
    $table->index(['translatable_type', 'translatable_id']);
    $table->index('locale');
    $table->unique(['locale', 'translatable_type', 'translatable_id']);
});
```

### 3.2 Translatable Trait

```php
<?php

namespace App\Traits;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;

trait Translatable
{
    /**
     * Translatable fields for this model
     */
    protected array $translatableFields = [];
    
    /**
     * Boot the trait
     */
    public static function bootTranslatable(): void
    {
        // Auto-load translations with model
        static::retrieved(function ($model) {
            $model->loadTranslations();
        });
        
        // Clear translation cache on save
        static::saved(function ($model) {
            $model->clearTranslationCache();
        });
    }
    
    /**
     * Get the translations relationship
     */
    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable');
    }
    
    /**
     * Get translation for a specific field and locale
     */
    public function translate(string $field, ?string $locale = null): mixed
    {
        $locale = $locale ?? app()->getLocale();
        
        // Check cache first
        $cacheKey = $this->getTranslationCacheKey($field, $locale);
        
        return Cache::remember($cacheKey, 3600, function () use ($field, $locale) {
            $translation = $this->translations()
                ->where('locale', $locale)
                ->first();
                
            if ($translation && isset($translation->translations[$field])) {
                return $translation->translations[$field];
            }
            
            // Fallback to default locale
            if ($locale !== config('app.fallback_locale')) {
                return $this->translate($field, config('app.fallback_locale'));
            }
            
            // Last resort: return original value
            return $this->getAttribute($field);
        });
    }
    
    /**
     * Set translation for a field
     */
    public function setTranslation(string $field, mixed $value, string $locale): self
    {
        // Validate field is translatable
        if (!in_array($field, $this->translatableFields)) {
            throw new \InvalidArgumentException("Field $field is not translatable");
        }
        
        $translation = $this->translations()
            ->firstOrCreate(['locale' => $locale], ['translations' => []]);
        
        $translations = $translation->translations;
        $translations[$field] = $value;
        $translation->translations = $translations;
        $translation->save();
        
        $this->clearTranslationCache($field, $locale);
        
        return $this;
    }
    
    /**
     * Get all translations for this model
     */
    public function getAllTranslations(): array
    {
        $result = [];
        
        foreach ($this->translations as $translation) {
            $result[$translation->locale] = $translation->translations;
        }
        
        return $result;
    }
    
    /**
     * Auto-translate using current locale
     */
    public function __get($key)
    {
        // If field is translatable, return translated value
        if (in_array($key, $this->translatableFields)) {
            return $this->translate($key);
        }
        
        return parent::__get($key);
    }
    
    /**
     * Load translations into memory
     */
    protected function loadTranslations(): void
    {
        $this->setRelation('translations', 
            $this->translations()->get()
        );
    }
    
    /**
     * Clear translation cache
     */
    protected function clearTranslationCache(?string $field = null, ?string $locale = null): void
    {
        if ($field && $locale) {
            Cache::forget($this->getTranslationCacheKey($field, $locale));
        } else {
            // Clear all translation cache for this model
            foreach ($this->translatableFields as $field) {
                foreach (config('app.available_locales') as $locale) {
                    Cache::forget($this->getTranslationCacheKey($field, $locale));
                }
            }
        }
    }
    
    /**
     * Get cache key for translation
     */
    protected function getTranslationCacheKey(string $field, string $locale): string
    {
        return sprintf(
            'translation:%s:%s:%s:%s',
            $this->getMorphClass(),
            $this->getKey(),
            $field,
            $locale
        );
    }
}
```

### 3.3 Usage Example

```php
<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Translatable;

class Product extends Model
{
    use Translatable;
    
    protected $fillable = [
        'sku',
        'name',
        'description',
        'price'
    ];
    
    /**
     * Fields that support translation
     */
    protected array $translatableFields = [
        'name',
        'description'
    ];
}

// Set translations
$product = Product::create([
    'sku' => 'WIDGET-001',
    'name' => 'Widget',
    'description' => 'A great widget',
    'price' => 99.99
]);

$product->setTranslation('name', 'Artículo', 'es');
$product->setTranslation('description', 'Un gran artículo', 'es');
$product->setTranslation('name', 'Widget', 'de');
$product->setTranslation('description', 'Ein tolles Widget', 'de');

// Get translations
app()->setLocale('es');
echo $product->name; // "Artículo"
echo $product->description; // "Un gran artículo"

// Explicit locale
echo $product->translate('name', 'de'); // "Widget"

// Query with translations
Product::whereTranslate('name', 'Artículo', 'es')->get();
```

### 3.4 Translation Management Service

```php
<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class TranslationManager
{
    /**
     * Bulk translate multiple models
     */
    public function bulkTranslate(
        Collection $models, 
        array $translations, 
        string $locale
    ): void {
        foreach ($models as $model) {
            foreach ($translations as $field => $value) {
                $model->setTranslation($field, $value, $locale);
            }
        }
    }
    
    /**
     * Export translations for a model
     */
    public function export(Model $model): array
    {
        return $model->getAllTranslations();
    }
    
    /**
     * Import translations for a model
     */
    public function import(Model $model, array $translations): void
    {
        foreach ($translations as $locale => $fields) {
            foreach ($fields as $field => $value) {
                $model->setTranslation($field, $value, $locale);
            }
        }
    }
    
    /**
     * Get translation completion percentage
     */
    public function getCompletionPercentage(Model $model, string $locale): float
    {
        $translatableFields = $model->translatableFields ?? [];
        
        if (empty($translatableFields)) {
            return 100.0;
        }
        
        $translated = 0;
        foreach ($translatableFields as $field) {
            $value = $model->translate($field, $locale);
            if (!empty($value)) {
                $translated++;
            }
        }
        
        return ($translated / count($translatableFields)) * 100;
    }
}
```

---

## 4. Multi-Tenant Implementation Patterns

### 4.1 Tenant Identification Strategies

#### Strategy 1: Subdomain-Based (Recommended for B2B SaaS)

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next)
    {
        // Extract subdomain: acme.app.com -> acme
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];
        
        // Skip for main domain and admin
        if (in_array($subdomain, ['www', 'admin', 'api'])) {
            return $next($request);
        }
        
        // Find tenant by subdomain
        $tenant = Tenant::where('subdomain', $subdomain)
            ->where('status', 'active')
            ->firstOrFail();
        
        // Set tenant in container
        app()->instance('tenant', $tenant);
        
        // Set tenant context
        tenancy()->initialize($tenant);
        
        return $next($request);
    }
}
```

#### Strategy 2: Header-Based (Recommended for API)

```php
public function handle(Request $request, Closure $next)
{
    $tenantId = $request->header('X-Tenant-ID');
    
    if (!$tenantId) {
        return response()->json([
            'error' => 'Tenant ID required'
        ], 400);
    }
    
    $tenant = Tenant::findOrFail($tenantId);
    
    app()->instance('tenant', $tenant);
    tenancy()->initialize($tenant);
    
    return $next($request);
}
```

### 4.2 Database-Per-Tenant Implementation

```php
<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class TenancyManager
{
    protected ?Tenant $currentTenant = null;
    
    /**
     * Initialize tenant context
     */
    public function initialize(Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
        
        // Switch database connection
        $this->switchDatabase($tenant);
        
        // Set tenant-specific cache prefix
        Config::set('cache.prefix', "tenant_{$tenant->id}_");
        
        // Dispatch tenant initialized event
        event(new TenantInitialized($tenant));
    }
    
    /**
     * Switch to tenant database
     */
    protected function switchDatabase(Tenant $tenant): void
    {
        // Configure tenant database connection
        Config::set('database.connections.tenant', [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '5432'),
            'database' => $tenant->database_name,
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
        ]);
        
        // Purge and reconnect
        DB::purge('tenant');
        DB::reconnect('tenant');
        
        // Set as default
        DB::setDefaultConnection('tenant');
    }
    
    /**
     * Get current tenant
     */
    public function tenant(): ?Tenant
    {
        return $this->currentTenant;
    }
    
    /**
     * Execute code in central database context
     */
    public function central(callable $callback): mixed
    {
        $previousConnection = DB::getDefaultConnection();
        DB::setDefaultConnection('central');
        
        try {
            return $callback();
        } finally {
            DB::setDefaultConnection($previousConnection);
        }
    }
}
```

### 4.3 Tenant Provisioning Service

```php
<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantProvisioner
{
    /**
     * Create a new tenant
     */
    public function provision(array $data): Tenant
    {
        DB::beginTransaction();
        
        try {
            // Create tenant record in central database
            $tenant = Tenant::create([
                'name' => $data['name'],
                'subdomain' => $data['subdomain'],
                'database_name' => 'tenant_' . Str::slug($data['subdomain']),
                'status' => 'provisioning'
            ]);
            
            // Create tenant database
            $this->createDatabase($tenant);
            
            // Run migrations
            $this->runMigrations($tenant);
            
            // Seed initial data
            $this->seedData($tenant);
            
            // Create admin user
            $this->createAdminUser($tenant, $data['admin']);
            
            // Update status
            $tenant->update(['status' => 'active']);
            
            DB::commit();
            
            event(new TenantProvisioned($tenant));
            
            return $tenant;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Cleanup on failure
            $this->cleanup($tenant);
            
            throw $e;
        }
    }
    
    /**
     * Create tenant database
     */
    protected function createDatabase(Tenant $tenant): void
    {
        DB::connection('central')->statement(
            "CREATE DATABASE {$tenant->database_name}"
        );
    }
    
    /**
     * Run migrations for tenant
     */
    protected function runMigrations(Tenant $tenant): void
    {
        tenancy()->initialize($tenant);
        
        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true
        ]);
    }
    
    /**
     * Seed initial tenant data
     */
    protected function seedData(Tenant $tenant): void
    {
        tenancy()->initialize($tenant);
        
        Artisan::call('db:seed', [
            '--database' => 'tenant',
            '--class' => 'TenantSeeder',
            '--force' => true
        ]);
    }
}
```

### 4.4 Global Scope for Row-Level Isolation (Alternative Strategy)

```php
<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply scope to query
     */
    public function apply(Builder $builder, Model $model): void
    {
        if ($tenant = tenant()) {
            $builder->where($model->getTable() . '.tenant_id', $tenant->id);
        }
    }
}

// Apply to models
namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
        
        // Auto-assign tenant on create
        static::creating(function ($model) {
            if (!$model->tenant_id && $tenant = tenant()) {
                $model->tenant_id = $tenant->id;
            }
        });
    }
}
```

### 4.5 Tenant-Aware Queue Jobs

```php
<?php

namespace App\Jobs;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessTenantReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(
        public int $tenantId,
        public string $reportType,
        public array $parameters
    ) {}
    
    public function handle(): void
    {
        // Load tenant
        $tenant = Tenant::find($this->tenantId);
        
        // Initialize tenant context
        tenancy()->initialize($tenant);
        
        // Process report in tenant context
        $report = app(ReportGenerator::class)
            ->generate($this->reportType, $this->parameters);
        
        // Store report
        $report->save();
    }
}

// Dispatch with tenant context
ProcessTenantReport::dispatch(
    tenant()->id,
    'sales_summary',
    ['period' => 'monthly']
);
```

---

## 5. API Design with Swagger/OpenAPI

### 5.1 OpenAPI Specification Structure

```yaml
openapi: 3.0.3
info:
  title: KV SaaS CRM/ERP API
  description: |
    Multi-tenant SaaS ERP/CRM system with modular architecture.
    Supports multi-organization, multi-currency, and multi-language operations.
  version: 1.0.0
  contact:
    name: API Support
    email: api@kv-saas.com
  license:
    name: Proprietary

servers:
  - url: https://{tenant}.api.kv-saas.com/v1
    description: Production API
    variables:
      tenant:
        default: demo
        description: Tenant subdomain
  - url: https://api.kv-saas.com/v1
    description: Multi-tenant API (requires X-Tenant-ID header)

security:
  - bearerAuth: []
  - tenantHeader: []

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
    tenantHeader:
      type: apiKey
      in: header
      name: X-Tenant-ID

  schemas:
    Customer:
      type: object
      required:
        - name
        - type
      properties:
        id:
          type: integer
          readOnly: true
        customer_number:
          type: string
          readOnly: true
        name:
          type: string
          maxLength: 255
        type:
          type: string
          enum: [individual, business]
        email:
          type: string
          format: email
        phone:
          type: string
        credit_limit:
          type: number
          format: float
        status:
          type: string
          enum: [active, inactive, blocked]
        created_at:
          type: string
          format: date-time
          readOnly: true

    SalesOrder:
      type: object
      required:
        - customer_id
        - items
      properties:
        id:
          type: integer
          readOnly: true
        order_number:
          type: string
          readOnly: true
        customer_id:
          type: integer
        status:
          type: string
          enum: [draft, confirmed, processing, shipped, delivered, cancelled]
        total_amount:
          type: number
          format: float
        currency:
          type: string
          pattern: '^[A-Z]{3}$'
        items:
          type: array
          items:
            $ref: '#/components/schemas/OrderItem'

    Error:
      type: object
      properties:
        message:
          type: string
        errors:
          type: object
          additionalProperties:
            type: array
            items:
              type: string

paths:
  /customers:
    get:
      summary: List customers
      tags: [Customers]
      parameters:
        - name: page
          in: query
          schema:
            type: integer
            default: 1
        - name: per_page
          in: query
          schema:
            type: integer
            default: 15
            maximum: 100
        - name: status
          in: query
          schema:
            type: string
            enum: [active, inactive, blocked]
      responses:
        '200':
          description: Successful response
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/Customer'
                  meta:
                    type: object
                    properties:
                      current_page:
                        type: integer
                      total:
                        type: integer

    post:
      summary: Create customer
      tags: [Customers]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/Customer'
      responses:
        '201':
          description: Customer created
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Customer'
        '422':
          description: Validation error
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Error'
```

### 5.2 Laravel API Resource Implementation

```php
<?php

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform resource into array
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'customer_number' => $this->customer_number,
            'name' => $this->name,
            'type' => $this->type,
            'email' => $this->email,
            'phone' => $this->phone,
            'credit_limit' => $this->credit_limit,
            'current_balance' => $this->current_balance,
            'status' => $this->status,
            
            // Conditional fields
            'addresses' => AddressResource::collection(
                $this->whenLoaded('addresses')
            ),
            
            // Relationships
            'assigned_salesperson' => new EmployeeResource(
                $this->whenLoaded('assignedSalesperson')
            ),
            
            // Computed fields
            'credit_available' => $this->when(
                $request->user()->can('view-financials'),
                $this->credit_limit - $this->current_balance
            ),
            
            // Metadata
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
```

### 5.3 API Controller with Documentation Annotations

```php
<?php

namespace Modules\Sales\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Sales\Entities\Customer;
use Modules\Sales\Http\Requests\StoreCustomerRequest;
use Modules\Sales\Http\Resources\CustomerResource;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Customers', description: 'Customer management endpoints')]
class CustomerController extends Controller
{
    /**
     * List customers
     */
    #[OA\Get(
        path: '/api/customers',
        summary: 'List customers',
        tags: ['Customers'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['active', 'inactive', 'blocked'])
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Customer')
                        )
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $customers = Customer::query()
            ->when($request->status, fn($q, $status) => 
                $q->where('status', $status)
            )
            ->paginate($request->per_page ?? 15);
        
        return CustomerResource::collection($customers)
            ->response();
    }
    
    /**
     * Create customer
     */
    #[OA\Post(
        path: '/api/customers',
        summary: 'Create a new customer',
        tags: ['Customers'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/Customer')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Customer created',
                content: new OA\JsonContent(ref: '#/components/schemas/Customer')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            )
        ]
    )]
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated());
        
        return (new CustomerResource($customer))
            ->response()
            ->setStatusCode(201);
    }
}
```

---

## 6. Integration with Existing Architecture

### 6.1 Mapping Laravel Modules to Clean Architecture Layers

```
┌─────────────────────────────────────────────────────┐
│             External Interfaces (Laravel)            │
│  Routes, Views, Console Commands, API Endpoints     │
└─────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────┐
│            Interface Adapters (Laravel)              │
│  Controllers, Requests, Resources, Middleware        │
│  Repositories (Implementation), Presenters           │
└─────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────┐
│         Application Business Rules (Laravel)         │
│  Services, Use Cases, Actions, Jobs, Events          │
│  Repository Interfaces, Service Contracts            │
└─────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────┐
│      Enterprise Business Rules (Framework-Free)      │
│  Entities, Value Objects, Domain Services            │
│  Aggregates, Domain Events, Business Logic           │
└─────────────────────────────────────────────────────┘
```

### 6.2 Module Implementation Checklist

When implementing a new module in Laravel following Clean Architecture:

- [ ] **Domain Layer (Core)**
  - [ ] Define Entities with business rules
  - [ ] Create Value Objects for complex attributes
  - [ ] Identify Aggregates and their roots
  - [ ] Define Domain Events
  - [ ] Implement Domain Services for complex operations
  - [ ] Write unit tests for all business logic

- [ ] **Application Layer**
  - [ ] Create Use Case/Action classes
  - [ ] Define Repository interfaces
  - [ ] Define Service contracts
  - [ ] Implement Application Services
  - [ ] Register Event Listeners
  - [ ] Write feature tests

- [ ] **Infrastructure Layer**
  - [ ] Implement Repository classes (Eloquent)
  - [ ] Create Database Migrations
  - [ ] Set up Model factories for testing
  - [ ] Implement external service adapters
  - [ ] Write integration tests

- [ ] **Presentation Layer**
  - [ ] Create Controllers (thin, delegate to services)
  - [ ] Define Request validation classes
  - [ ] Create API Resources for transformation
  - [ ] Set up Routes
  - [ ] Add OpenAPI documentation
  - [ ] Write API tests

### 6.3 Practical Example: Sales Order Module

```php
// 1. Domain Layer - Entity
namespace Modules\Sales\Domain\Entities;

class SalesOrder
{
    private array $lines = [];
    
    public function __construct(
        private int $customerId,
        private string $currency
    ) {}
    
    public function addLine(int $productId, int $quantity, float $unitPrice): void
    {
        // Business rule: cannot add line with zero quantity
        if ($quantity <= 0) {
            throw new \DomainException('Quantity must be positive');
        }
        
        $this->lines[] = new OrderLine($productId, $quantity, $unitPrice);
    }
    
    public function calculateTotal(): float
    {
        return array_sum(array_map(
            fn($line) => $line->getSubtotal(),
            $this->lines
        ));
    }
    
    public function confirm(): void
    {
        // Business rule: order must have at least one line
        if (empty($this->lines)) {
            throw new \DomainException('Cannot confirm order without lines');
        }
        
        $this->status = OrderStatus::CONFIRMED;
        
        // Emit domain event
        $this->recordEvent(new OrderConfirmed($this));
    }
}

// 2. Application Layer - Use Case
namespace Modules\Sales\Application\UseCases;

class CreateSalesOrder
{
    public function __construct(
        private SalesOrderRepositoryInterface $orderRepository,
        private CustomerRepositoryInterface $customerRepository,
        private PricingServiceInterface $pricingService
    ) {}
    
    public function execute(CreateSalesOrderCommand $command): SalesOrder
    {
        // Validate customer exists
        $customer = $this->customerRepository->findOrFail($command->customerId);
        
        // Create order
        $order = new SalesOrder($customer->id, $command->currency);
        
        // Add lines with calculated pricing
        foreach ($command->items as $item) {
            $price = $this->pricingService->calculatePrice(
                $item['product_id'],
                $item['quantity'],
                $customer
            );
            
            $order->addLine(
                $item['product_id'],
                $item['quantity'],
                $price
            );
        }
        
        // Persist
        $this->orderRepository->save($order);
        
        return $order;
    }
}

// 3. Infrastructure Layer - Repository
namespace Modules\Sales\Infrastructure\Repositories;

class EloquentSalesOrderRepository implements SalesOrderRepositoryInterface
{
    public function save(SalesOrder $order): void
    {
        $model = SalesOrderModel::create([
            'customer_id' => $order->getCustomerId(),
            'currency' => $order->getCurrency(),
            'status' => $order->getStatus(),
            'total_amount' => $order->calculateTotal()
        ]);
        
        foreach ($order->getLines() as $line) {
            $model->lines()->create([
                'product_id' => $line->getProductId(),
                'quantity' => $line->getQuantity(),
                'unit_price' => $line->getUnitPrice(),
                'subtotal' => $line->getSubtotal()
            ]);
        }
        
        // Dispatch domain events
        foreach ($order->getEvents() as $event) {
            event($event);
        }
    }
}

// 4. Presentation Layer - Controller
namespace Modules\Sales\Http\Controllers\Api;

class SalesOrderController extends Controller
{
    public function store(
        StoreSalesOrderRequest $request,
        CreateSalesOrder $useCase
    ): JsonResponse {
        $command = CreateSalesOrderCommand::fromRequest($request);
        
        $order = $useCase->execute($command);
        
        return (new SalesOrderResource($order))
            ->response()
            ->setStatusCode(201);
    }
}
```

---

## Conclusion

This enhanced conceptual model integrates:

1. **Laravel-specific patterns** for modular architecture with practical implementation examples
2. **Odoo-inspired plugin system** enabling extensibility without modifying core
3. **Polymorphic translatable models** for robust multi-language support
4. **Emmy-proven multi-tenant patterns** for scalable SaaS architecture
5. **OpenAPI/Swagger integration** for comprehensive API documentation
6. **Clean Architecture mapping** showing how all patterns work together

These patterns complement the existing ARCHITECTURE.md, DOMAIN_MODELS.md, and CONCEPTS_REFERENCE.md documents, providing concrete Laravel implementation guidance while maintaining architectural purity and following SOLID principles.

### Key Takeaways

1. Use **Laravel modules** as bounded contexts from DDD
2. Implement **plugin architecture** for extensibility
3. Leverage **polymorphic relationships** for flexible multi-language support
4. Choose the right **tenant isolation strategy** based on customer requirements
5. Document APIs with **OpenAPI/Swagger** for better developer experience
6. Map **Laravel structures to Clean Architecture layers** consistently
7. Maintain **clear boundaries** between modules using events and contracts
8. Test at **all layers** (unit, integration, feature, API)

This enhanced model serves as a bridge between the theoretical architecture documentation and practical Laravel implementation, enabling developers to build a world-class modular SaaS ERP/CRM system.
