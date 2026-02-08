# Additional Resource Analysis

## Overview

This document extends the [RESOURCE_ANALYSIS.md](RESOURCE_ANALYSIS.md) by analyzing additional resources that complement the core architectural concepts with practical implementation details for file management, storage systems, and reference implementations.

## Table of Contents

1. [Laravel Filesystem Abstraction](#1-laravel-filesystem-abstraction)
2. [File Upload Patterns in Laravel](#2-file-upload-patterns-in-laravel)
3. [Laravel Framework Reference](#3-laravel-framework-reference)
4. [Laravel Packages Development](#4-laravel-packages-development)
5. [Reference Implementations Analysis](#5-reference-implementations-analysis)
6. [Integration with Core Architecture](#6-integration-with-core-architecture)

---

## 1. Laravel Filesystem Abstraction

### Source
- **Laravel Documentation**: https://laravel.com/docs/12.x/filesystem
- **Flysystem Integration**: Multi-adapter file storage abstraction

### Key Concepts Extracted

#### Filesystem Abstraction Layer

Laravel provides a powerful abstraction over different storage systems through Flysystem, allowing seamless switching between storage backends without code changes.

```
Application Code
      ↓
Storage Facade (Laravel)
      ↓
Flysystem (Abstraction)
      ↓
┌─────────┬─────────┬─────────┬─────────┐
│ Local   │ S3      │ Azure   │ FTP     │
│ Disk    │ Bucket  │ Blob    │ Server  │
└─────────┴─────────┴─────────┴─────────┘
```

#### Storage Drivers

**Available Drivers:**
- **Local**: Server filesystem
- **S3**: Amazon S3 or compatible (MinIO, DigitalOcean Spaces)
- **Azure**: Microsoft Azure Blob Storage
- **FTP/SFTP**: Traditional file servers
- **Custom**: Extensible for any storage system

**Configuration Pattern:**
```php
// config/filesystems.php
'disks' => [
    'local' => [
        'driver' => 'local',
        'root' => storage_path('app'),
    ],
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
    ],
    'tenant' => [
        'driver' => 'local',
        'root' => storage_path('app/tenants/{tenant_id}'),
    ],
];
```

#### File Operations API

**Basic Operations:**
```php
// Store file
Storage::disk('s3')->put('path/to/file.jpg', $contents);

// Retrieve file
$contents = Storage::disk('s3')->get('path/to/file.jpg');

// Delete file
Storage::disk('s3')->delete('path/to/file.jpg');

// Check existence
if (Storage::disk('s3')->exists('path/to/file.jpg')) {
    // File exists
}

// Get file URL
$url = Storage::disk('s3')->url('path/to/file.jpg');

// Temporary URL (signed, expires)
$url = Storage::disk('s3')->temporaryUrl(
    'path/to/file.jpg', 
    now()->addMinutes(30)
);
```

**Advanced Operations:**
```php
// Copy file
Storage::copy('old/path.jpg', 'new/path.jpg');

// Move file
Storage::move('old/path.jpg', 'new/path.jpg');

// Get metadata
$size = Storage::size('path/to/file.jpg');
$time = Storage::lastModified('path/to/file.jpg');
$mime = Storage::mimeType('path/to/file.jpg');

// List files
$files = Storage::files('directory');
$files = Storage::allFiles('directory'); // Recursive

// List directories
$directories = Storage::directories('directory');
$directories = Storage::allDirectories('directory'); // Recursive

// Create directory
Storage::makeDirectory('path/to/directory');

// Delete directory
Storage::deleteDirectory('path/to/directory');
```

#### Visibility Control

```php
// Public file (accessible via URL)
Storage::disk('s3')->put('path/to/file.jpg', $contents, 'public');

// Private file (requires authentication)
Storage::disk('s3')->put('path/to/file.jpg', $contents, 'private');

// Change visibility
Storage::setVisibility('path/to/file.jpg', 'public');
```

### Application to kv-saas-crm-erp

#### Multi-Tenant File Storage Architecture

**Tenant Isolation Pattern:**
```
Storage Root
├── tenant_001/
│   ├── documents/
│   │   ├── invoices/
│   │   ├── contracts/
│   │   └── reports/
│   ├── images/
│   │   ├── products/
│   │   └── users/
│   └── exports/
├── tenant_002/
│   ├── documents/
│   ├── images/
│   └── exports/
└── shared/
    └── templates/
```

**Tenant-Aware Storage Service:**
```php
namespace App\Services\Storage;

class TenantStorageService
{
    public function disk(string $diskName = 'default'): Filesystem
    {
        $tenantId = Auth::user()->tenant_id;
        $root = config("filesystems.disks.{$diskName}.root");
        
        // Inject tenant ID into path
        $tenantRoot = str_replace(
            '{tenant_id}', 
            $tenantId, 
            $root
        );
        
        return Storage::build([
            'driver' => config("filesystems.disks.{$diskName}.driver"),
            'root' => $tenantRoot,
        ]);
    }
    
    public function store(
        UploadedFile $file, 
        string $path, 
        string $disk = 'default'
    ): string {
        $tenantDisk = $this->disk($disk);
        return $file->store($path, $tenantDisk);
    }
    
    public function url(string $path, string $disk = 'default'): string
    {
        return $this->disk($disk)->url($path);
    }
}
```

#### Document Management Entity

```php
namespace Modules\Documents\Entities;

class Document extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'file_path',
        'file_size',
        'mime_type',
        'disk',
        'module',
        'entity_type',
        'entity_id',
        'uploaded_by',
        'is_public',
    ];
    
    public function getUrlAttribute(): string
    {
        if ($this->is_public) {
            return Storage::disk($this->disk)->url($this->file_path);
        }
        
        // Generate temporary signed URL for private files
        return Storage::disk($this->disk)->temporaryUrl(
            $this->file_path,
            now()->addMinutes(60)
        );
    }
    
    public function download(): StreamedResponse
    {
        return Storage::disk($this->disk)->download(
            $this->file_path,
            $this->name
        );
    }
}
```

#### Storage Configuration for Multi-Cloud

```php
// Support for hybrid cloud storage
'disks' => [
    // Primary storage for active documents
    'documents' => [
        'driver' => 's3',
        'key' => env('AWS_S3_KEY'),
        'secret' => env('AWS_S3_SECRET'),
        'region' => env('AWS_S3_REGION'),
        'bucket' => env('AWS_S3_BUCKET'),
    ],
    
    // Archive storage for older documents
    'archive' => [
        'driver' => 's3',
        'key' => env('AWS_GLACIER_KEY'),
        'secret' => env('AWS_GLACIER_SECRET'),
        'region' => env('AWS_GLACIER_REGION'),
        'bucket' => env('AWS_GLACIER_BUCKET'),
    ],
    
    // Fast access for frequently used files
    'cache' => [
        'driver' => 'local',
        'root' => storage_path('app/cache'),
    ],
];
```

---

## 2. File Upload Patterns in Laravel

### Source
- **Laravel News**: https://laravel-news.com/uploading-files-laravel
- **Best Practices**: Validation, security, optimization

### Key Concepts Extracted

#### Secure File Upload Process

**1. Validation Layer**
```php
// Request validation
public function rules(): array
{
    return [
        'file' => [
            'required',
            'file',
            'max:10240', // 10MB
            'mimes:pdf,doc,docx,xls,xlsx,jpg,png',
        ],
        'document_type' => 'required|string',
    ];
}
```

**2. File Upload Controller Pattern**
```php
namespace App\Http\Controllers;

class DocumentUploadController extends Controller
{
    public function __construct(
        private TenantStorageService $storage,
        private DocumentService $documentService
    ) {}
    
    public function store(DocumentUploadRequest $request): JsonResponse
    {
        // Validate request
        $validated = $request->validated();
        
        // Get uploaded file
        $file = $request->file('file');
        
        // Generate secure filename
        $filename = $this->generateSecureFilename($file);
        
        // Determine storage path
        $path = $this->getStoragePath($validated['document_type']);
        
        // Store file with tenant isolation
        $filePath = $this->storage->store(
            $file, 
            $path,
            'documents'
        );
        
        // Create document record
        $document = $this->documentService->create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'disk' => 'documents',
            'document_type' => $validated['document_type'],
            'uploaded_by' => auth()->id(),
        ]);
        
        return response()->json([
            'success' => true,
            'document' => new DocumentResource($document),
        ], 201);
    }
    
    private function generateSecureFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return sprintf(
            '%s_%s.%s',
            Str::uuid(),
            time(),
            $extension
        );
    }
}
```

#### Image Optimization Pattern

**With Intervention Image:**
```php
use Intervention\Image\Facades\Image;

class ImageUploadService
{
    public function uploadAndOptimize(
        UploadedFile $file,
        array $sizes = ['thumbnail' => 150, 'medium' => 500, 'large' => 1200]
    ): array {
        $paths = [];
        
        // Store original
        $originalPath = $file->store('images/original', 'public');
        $paths['original'] = $originalPath;
        
        // Create optimized versions
        foreach ($sizes as $name => $width) {
            $image = Image::make($file);
            $image->resize($width, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            $optimizedPath = "images/{$name}/" . basename($originalPath);
            Storage::disk('public')->put(
                $optimizedPath,
                (string) $image->encode()
            );
            
            $paths[$name] = $optimizedPath;
        }
        
        return $paths;
    }
}
```

#### Chunked Upload for Large Files

**For files > 100MB:**
```php
namespace App\Http\Controllers;

class ChunkedUploadController extends Controller
{
    public function uploadChunk(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file',
            'chunk_index' => 'required|integer',
            'total_chunks' => 'required|integer',
            'upload_id' => 'required|string',
        ]);
        
        $uploadId = $request->input('upload_id');
        $chunkIndex = $request->input('chunk_index');
        $totalChunks = $request->input('total_chunks');
        
        // Store chunk temporarily
        $chunkPath = "uploads/chunks/{$uploadId}/{$chunkIndex}";
        $request->file('file')->storeAs(
            $chunkPath,
            'chunk',
            'temp'
        );
        
        // If all chunks uploaded, merge them
        if ($this->allChunksUploaded($uploadId, $totalChunks)) {
            $finalPath = $this->mergeChunks($uploadId, $totalChunks);
            
            return response()->json([
                'success' => true,
                'completed' => true,
                'path' => $finalPath,
            ]);
        }
        
        return response()->json([
            'success' => true,
            'completed' => false,
            'progress' => ($chunkIndex + 1) / $totalChunks * 100,
        ]);
    }
    
    private function mergeChunks(string $uploadId, int $totalChunks): string
    {
        $finalPath = "uploads/final/{$uploadId}";
        $finalFile = Storage::disk('temp')->path($finalPath);
        
        // Open final file for writing
        $finalHandle = fopen($finalFile, 'wb');
        
        // Merge all chunks
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = "uploads/chunks/{$uploadId}/{$i}/chunk";
            $chunkFile = Storage::disk('temp')->path($chunkPath);
            
            $chunkHandle = fopen($chunkFile, 'rb');
            stream_copy_to_stream($chunkHandle, $finalHandle);
            fclose($chunkHandle);
            
            // Delete chunk
            Storage::disk('temp')->delete($chunkPath);
        }
        
        fclose($finalHandle);
        
        // Move to permanent storage
        $permanentPath = Storage::disk('documents')->putFile(
            'large-files',
            new File($finalFile)
        );
        
        // Cleanup temp file
        Storage::disk('temp')->delete($finalPath);
        
        return $permanentPath;
    }
}
```

#### Direct Upload to S3 (Pre-signed URLs)

**Bypass server for large files:**
```php
namespace App\Http\Controllers;

class DirectUploadController extends Controller
{
    public function getPresignedUrl(Request $request): JsonResponse
    {
        $request->validate([
            'filename' => 'required|string',
            'mime_type' => 'required|string',
            'file_size' => 'required|integer|max:524288000', // 500MB
        ]);
        
        $tenantId = auth()->user()->tenant_id;
        $filename = Str::uuid() . '_' . $request->input('filename');
        $path = "tenants/{$tenantId}/uploads/{$filename}";
        
        // Generate pre-signed URL
        $s3Client = Storage::disk('s3')->getDriver()->getAdapter()->getClient();
        $bucket = config('filesystems.disks.s3.bucket');
        
        $command = $s3Client->getCommand('PutObject', [
            'Bucket' => $bucket,
            'Key' => $path,
            'ContentType' => $request->input('mime_type'),
        ]);
        
        $presignedUrl = (string) $s3Client->createPresignedRequest(
            $command,
            '+20 minutes'
        )->getUri();
        
        return response()->json([
            'upload_url' => $presignedUrl,
            'file_path' => $path,
            'expires_at' => now()->addMinutes(20)->toIso8601String(),
        ]);
    }
    
    public function confirmUpload(Request $request): JsonResponse
    {
        $request->validate([
            'file_path' => 'required|string',
            'document_type' => 'required|string',
        ]);
        
        // Verify file exists in S3
        if (!Storage::disk('s3')->exists($request->input('file_path'))) {
            return response()->json([
                'error' => 'File not found',
            ], 404);
        }
        
        // Create document record
        $document = Document::create([
            'tenant_id' => auth()->user()->tenant_id,
            'file_path' => $request->input('file_path'),
            'file_size' => Storage::disk('s3')->size($request->input('file_path')),
            'mime_type' => Storage::disk('s3')->mimeType($request->input('file_path')),
            'disk' => 's3',
            'document_type' => $request->input('document_type'),
            'uploaded_by' => auth()->id(),
        ]);
        
        return response()->json([
            'success' => true,
            'document' => new DocumentResource($document),
        ]);
    }
}
```

#### Virus Scanning Integration

```php
namespace App\Services;

use Xenolope\Quahog\Client as ClamAVClient;

class VirusScanService
{
    public function scan(string $filePath): bool
    {
        $clamav = new ClamAVClient(
            new \Socket(
                config('clamav.socket', '/var/run/clamav/clamd.ctl')
            )
        );
        
        $result = $clamav->scanFile($filePath);
        
        if ($result['status'] === 'OK') {
            return true;
        }
        
        // Virus found, delete file
        Storage::delete($filePath);
        
        throw new VirusDetectedException(
            "Virus detected: {$result['reason']}"
        );
    }
}
```

### Application to kv-saas-crm-erp

#### Document Module Architecture

```
Modules/Documents/
├── Entities/
│   ├── Document.php
│   ├── DocumentVersion.php
│   └── DocumentCategory.php
├── Services/
│   ├── DocumentService.php
│   ├── TenantStorageService.php
│   ├── ImageOptimizationService.php
│   └── VirusScanService.php
├── Http/
│   ├── Controllers/
│   │   ├── DocumentController.php
│   │   ├── DirectUploadController.php
│   │   └── ChunkedUploadController.php
│   ├── Requests/
│   │   ├── DocumentUploadRequest.php
│   │   └── DirectUploadRequest.php
│   └── Resources/
│       └── DocumentResource.php
├── Policies/
│   └── DocumentPolicy.php
└── Events/
    ├── DocumentUploaded.php
    ├── DocumentDeleted.php
    └── DocumentViewed.php
```

#### Integration with Other Modules

**1. Invoice Module - PDF Attachments**
```php
// When invoice is created, attach PDF
Event::listen(InvoiceCreated::class, function ($event) {
    $pdf = PDF::loadView('invoices.pdf', ['invoice' => $event->invoice]);
    $filename = "invoice_{$event->invoice->number}.pdf";
    
    $documentService = app(DocumentService::class);
    $documentService->createFromContent(
        $pdf->output(),
        $filename,
        'application/pdf',
        'invoice',
        $event->invoice->id
    );
});
```

**2. Product Module - Image Galleries**
```php
class Product extends Model
{
    public function images()
    {
        return $this->morphMany(Document::class, 'entity')
            ->where('document_type', 'product_image');
    }
    
    public function primaryImage(): ?Document
    {
        return $this->images()->where('is_primary', true)->first();
    }
}
```

**3. HR Module - Employee Documents**
```php
class Employee extends Model
{
    public function documents()
    {
        return $this->morphMany(Document::class, 'entity')
            ->where('document_type', 'employee_document');
    }
    
    public function contracts()
    {
        return $this->documents()->where('category', 'contract');
    }
    
    public function certifications()
    {
        return $this->documents()->where('category', 'certification');
    }
}
```

---

## 3. Laravel Framework Reference

### Source
- **GitHub**: https://github.com/laravel/laravel
- **Official Starter Project**: Base structure and conventions

### Key Concepts Extracted

#### Standard Directory Structure

```
laravel/
├── app/
│   ├── Console/           # Artisan commands
│   ├── Exceptions/        # Exception handlers
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   └── Kernel.php
│   ├── Models/            # Eloquent models
│   ├── Providers/         # Service providers
│   └── ...
├── bootstrap/
│   ├── app.php           # Application bootstrap
│   └── cache/
├── config/               # Configuration files
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── public/               # Web root
│   └── index.php
├── resources/
│   ├── views/
│   ├── lang/
│   └── js/
├── routes/
│   ├── web.php
│   ├── api.php
│   ├── console.php
│   └── channels.php
├── storage/
│   ├── app/
│   ├── framework/
│   └── logs/
├── tests/
│   ├── Feature/
│   └── Unit/
├── vendor/
├── .env
├── artisan
├── composer.json
└── phpunit.xml
```

#### Application Bootstrap Flow

```
1. public/index.php
   ↓
2. bootstrap/app.php
   ↓
3. Service Providers Registration
   ↓
4. Middleware Pipeline
   ↓
5. Route Resolution
   ↓
6. Controller Action
   ↓
7. Response
```

#### Service Container Pattern

**Binding and Resolution:**
```php
// In Service Provider
public function register(): void
{
    $this->app->bind(
        RepositoryInterface::class,
        EloquentRepository::class
    );
    
    $this->app->singleton(
        CacheManager::class,
        fn($app) => new CacheManager($app['cache'])
    );
}

// In Controller (automatic injection)
public function __construct(
    private RepositoryInterface $repository,
    private CacheManager $cache
) {}
```

### Application to kv-saas-crm-erp

#### Extended Directory Structure for Modular ERP

```
kv-saas-crm-erp/
├── app/
│   ├── Core/                      # Shared core functionality
│   │   ├── Entities/
│   │   ├── Services/
│   │   ├── Repositories/
│   │   └── ValueObjects/
│   ├── Http/
│   │   ├── Middleware/
│   │   │   ├── TenantResolution.php
│   │   │   ├── EnsureTenantActive.php
│   │   │   └── CheckModulePermission.php
│   │   └── Kernel.php
│   ├── Providers/
│   │   ├── TenancyServiceProvider.php
│   │   ├── ModuleServiceProvider.php
│   │   └── EventServiceProvider.php
│   └── ...
├── Modules/                       # Business modules
│   ├── Sales/
│   ├── Inventory/
│   ├── Accounting/
│   ├── HR/
│   ├── Procurement/
│   └── Documents/
├── config/
│   ├── tenancy.php
│   ├── modules.php
│   └── erp.php
├── database/
│   ├── tenants/                   # Tenant-specific migrations
│   └── system/                    # System-wide migrations
└── ...
```

---

## 4. Laravel Packages Development

### Source
- **Laravel Documentation**: https://laravel.com/docs/12.x/packages
- **Package Development Best Practices**

### Key Concepts Extracted

#### Package Structure

```
my-package/
├── src/
│   ├── PackageServiceProvider.php
│   ├── Facades/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   ├── Models/
│   ├── Commands/
│   └── config/
├── database/
│   └── migrations/
├── resources/
│   ├── views/
│   └── lang/
├── routes/
├── tests/
├── composer.json
└── README.md
```

#### Service Provider Pattern

```php
<?php

namespace Vendor\Package;

use Illuminate\Support\ServiceProvider;

class PackageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/config/package.php',
            'package'
        );
        
        // Register bindings
        $this->app->bind(
            'package',
            fn($app) => new Package($app)
        );
    }
    
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/config/package.php' => config_path('package.php'),
        ], 'package-config');
        
        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'package-migrations');
        
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        
        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'package');
        
        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'package');
        
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\InstallCommand::class,
            ]);
        }
    }
}
```

#### Package Auto-Discovery

```json
{
    "name": "vendor/package",
    "extra": {
        "laravel": {
            "providers": [
                "Vendor\\Package\\PackageServiceProvider"
            ],
            "aliases": {
                "Package": "Vendor\\Package\\Facades\\Package"
            }
        }
    }
}
```

### Application to kv-saas-crm-erp

#### Core ERP Package Structure

Each ERP module should be developed as a Laravel package for maximum reusability:

```
packages/
├── kv-sales/
│   ├── src/
│   │   ├── SalesServiceProvider.php
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Http/
│   ├── database/
│   ├── tests/
│   └── composer.json
├── kv-inventory/
├── kv-accounting/
├── kv-hr/
├── kv-procurement/
└── kv-core/              # Shared core functionality
    ├── src/
    │   ├── CoreServiceProvider.php
    │   ├── Tenancy/
    │   ├── Multiorganization/
    │   ├── Multicurrency/
    │   └── Multilanguage/
    └── composer.json
```

#### Package Dependencies

```json
{
    "name": "kv/sales",
    "require": {
        "php": "^8.1",
        "illuminate/support": "^11.0",
        "kv/core": "^1.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0",
        "orchestra/testbench": "^9.0"
    },
    "autoload": {
        "psr-4": {
            "KV\\Sales\\": "src/"
        }
    }
}
```

---

## 5. Reference Implementations Analysis

### Sources Analyzed

1. **kv-saas-erp-crm** (https://github.com/kasunvimarshana/kv-saas-erp-crm)
2. **PHP_POS** (https://github.com/kasunvimarshana/PHP_POS)
3. **kv-erp** (https://github.com/kasunvimarshana/kv-erp)

### Key Learnings from Reference Implementations

#### Common Patterns Identified

**1. Multi-Tenant Database Strategy**
- Use of tenant_id column in all tables
- Tenant-scoped queries via global scopes
- Middleware for tenant context injection

**2. Module Organization**
- Separation of concerns by business domain
- Clear boundaries between modules
- Event-driven inter-module communication

**3. Authentication & Authorization**
- Role-based access control (RBAC)
- Permission-based authorization
- Tenant-aware user authentication

**4. Data Models**
- Rich domain models with business logic
- Use of Eloquent relationships
- Repository pattern for data access

**5. API Design**
- RESTful API structure
- Resource transformers for API responses
- Consistent error handling

#### Lessons Learned

**From PHP_POS:**
- Point of Sale specific workflows
- Inventory management patterns
- Receipt and invoice generation
- Multi-location support

**From kv-erp:**
- ERP module structure
- Financial accounting patterns
- Procurement workflows
- HR management basics

**Integration Points:**
- How modules communicate
- Shared services (authentication, logging)
- Common UI components
- Unified configuration management

---

## 6. Integration with Core Architecture

### Unified Storage Strategy

#### Layered Storage Architecture

```
┌─────────────────────────────────────────┐
│         Application Layer               │
│    (Business Logic, Use Cases)          │
└─────────────────────────────────────────┘
                 ↓
┌─────────────────────────────────────────┐
│      Storage Abstraction Layer          │
│   (TenantStorageService, DocumentService)│
└─────────────────────────────────────────┘
                 ↓
┌─────────────────────────────────────────┐
│      Laravel Filesystem Layer           │
│       (Storage Facade, Flysystem)       │
└─────────────────────────────────────────┘
                 ↓
┌──────────┬──────────┬──────────┬────────┐
│  Local   │   S3     │  Azure   │ Custom │
│  Disk    │  Bucket  │   Blob   │ Driver │
└──────────┴──────────┴──────────┴────────┘
```

#### Document Management as Core Service

**Integration with All Modules:**

```php
// In any module
$document = app(DocumentService::class)->create([
    'file' => $uploadedFile,
    'entity_type' => 'invoice',
    'entity_id' => $invoice->id,
    'category' => 'financial_document',
]);

// Later retrieval
$invoiceDocuments = Document::forEntity('invoice', $invoice->id)->get();
```

**Polymorphic Relationship Pattern:**
```php
// Any entity can have documents
trait HasDocuments
{
    public function documents()
    {
        return $this->morphMany(Document::class, 'entity');
    }
}

// Use in any model
class Invoice extends Model
{
    use HasDocuments;
}

class Employee extends Model
{
    use HasDocuments;
}
```

### File Storage Configuration

#### Environment-Based Configuration

```env
# Development - Local Storage
FILESYSTEM_DRIVER=local

# Production - Cloud Storage
FILESYSTEM_DRIVER=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket

# Multi-Cloud Strategy
DOCUMENTS_DISK=s3
ARCHIVE_DISK=glacier
CACHE_DISK=local
```

#### Tenant-Specific Configuration

```php
// config/tenancy.php
return [
    'storage' => [
        'root_path' => 'tenants/{tenant_id}',
        'disks' => [
            'documents' => 's3',
            'images' => 's3',
            'exports' => 'local',
            'temp' => 'local',
        ],
        'max_upload_size' => 10 * 1024 * 1024, // 10MB
        'allowed_extensions' => [
            'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
            'images' => ['jpg', 'jpeg', 'png', 'gif'],
        ],
    ],
];
```

### Security Considerations

#### File Access Control

```php
class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        // Tenant isolation check
        if ($user->tenant_id !== $document->tenant_id) {
            return false;
        }
        
        // Permission check
        if ($document->is_public) {
            return true;
        }
        
        // Check if user has access to the parent entity
        return $user->can('view', $document->entity);
    }
    
    public function download(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }
    
    public function delete(User $user, Document $document): bool
    {
        return $user->tenant_id === $document->tenant_id
            && ($user->id === $document->uploaded_by 
                || $user->hasRole('admin'));
    }
}
```

#### File Upload Security

```php
class DocumentUploadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:' . config('tenancy.storage.max_upload_size'),
                function ($attribute, $value, $fail) {
                    // Validate MIME type
                    $allowedTypes = config('tenancy.storage.allowed_mime_types');
                    if (!in_array($value->getMimeType(), $allowedTypes)) {
                        $fail('File type not allowed.');
                    }
                    
                    // Validate file extension
                    $extension = $value->getClientOriginalExtension();
                    if (!in_array($extension, config('tenancy.storage.allowed_extensions'))) {
                        $fail('File extension not allowed.');
                    }
                },
            ],
        ];
    }
}
```

---

## Conclusion

This additional analysis complements the core architectural documentation by providing:

1. **Practical File Management**: Laravel's filesystem abstraction enables cloud-agnostic storage
2. **Secure Upload Patterns**: Best practices for handling file uploads with validation and security
3. **Package Development**: Modular approach to building reusable ERP components
4. **Reference Implementation Insights**: Lessons from existing implementations
5. **Integration Strategy**: How file storage integrates with the broader architecture

### Key Takeaways

1. **Storage Abstraction**: Use Laravel's Flysystem integration for flexible storage backends
2. **Tenant Isolation**: Implement file-level tenant isolation for security
3. **Multi-Cloud Support**: Design for hybrid storage strategies (hot/cold storage)
4. **Security First**: Always validate, scan, and control access to uploaded files
5. **Package-Based Modules**: Develop modules as Laravel packages for reusability
6. **Polymorphic Documents**: Use polymorphic relationships for universal document attachment

### Integration Points

- **Sales Module**: Invoice PDFs, quote documents, contracts
- **Inventory Module**: Product images, specifications, manuals
- **HR Module**: Employee documents, contracts, certifications
- **Accounting Module**: Financial reports, tax documents, receipts
- **Procurement Module**: Purchase orders, supplier documents, delivery notes

### Next Steps

1. Implement Document module as core service
2. Set up multi-cloud storage configuration
3. Create document policies and access control
4. Implement file upload API endpoints
5. Add virus scanning integration
6. Create document versioning system
7. Implement document search and indexing
8. Add document lifecycle management (retention, archival, deletion)
