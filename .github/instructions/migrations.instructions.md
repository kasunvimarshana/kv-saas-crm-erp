---
applyTo: "**/Database/Migrations/**/*.php"
---

# Database Migration Requirements

When writing database migrations, follow these guidelines for consistency and maintainability:

## Migration Standards

### 1. Use Descriptive Migration Names
Migration names should clearly describe what they do:

```bash
# Good
php artisan make:migration create_sales_orders_table
php artisan make:migration add_status_column_to_orders_table
php artisan make:migration create_order_items_table

# Avoid
php artisan make:migration update_orders
php artisan make:migration changes
```

### 2. Always Provide Rollback Logic
Every `up()` method must have a corresponding `down()` method:

```php
public function up(): void
{
    Schema::create('orders', function (Blueprint $table) {
        // Table definition
    });
}

public function down(): void
{
    Schema::dropIfExists('orders');
}
```

### 3. Use UUID/ULID for Primary Keys in Multi-Tenant Contexts
For tenant-specific tables, use UUIDs or ULIDs instead of auto-incrementing integers:

```php
public function up(): void
{
    Schema::create('orders', function (Blueprint $table) {
        $table->uuid('id')->primary();
        // or
        $table->ulid('id')->primary();
        
        $table->timestamps();
    });
}
```

### 4. Add Proper Foreign Key Constraints
Always define foreign keys with proper cascade rules:

```php
public function up(): void
{
    Schema::create('order_items', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('order_id');
        $table->uuid('product_id');
        
        // Define foreign keys with cascade
        $table->foreign('order_id')
            ->references('id')
            ->on('orders')
            ->onDelete('cascade');
        
        $table->foreign('product_id')
            ->references('id')
            ->on('products')
            ->onDelete('restrict');
        
        $table->timestamps();
    });
}
```

### 5. Add Indexes for Performance
Add indexes on columns used in WHERE clauses, JOINs, and foreign keys:

```php
public function up(): void
{
    Schema::create('orders', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('customer_id');
        $table->string('order_number')->unique();
        $table->string('status');
        $table->decimal('total', 15, 2);
        $table->timestamp('order_date');
        
        // Add indexes
        $table->index('customer_id');
        $table->index('status');
        $table->index('order_date');
        $table->index(['customer_id', 'status']); // Composite index
        
        $table->foreign('customer_id')
            ->references('id')
            ->on('customers')
            ->onDelete('restrict');
        
        $table->timestamps();
    });
}
```

### 6. Use Soft Deletes Where Appropriate
For records that should be retained for audit purposes:

```php
public function up(): void
{
    Schema::create('orders', function (Blueprint $table) {
        $table->uuid('id')->primary();
        // ... other columns
        $table->timestamps();
        $table->softDeletes(); // Adds deleted_at column
    });
}
```

### 7. Implement Multi-Language Support
For translatable fields, use JSON columns:

```php
public function up(): void
{
    Schema::create('products', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->json('name'); // Translatable: {"en": "Product", "es": "Producto"}
        $table->json('description'); // Translatable
        $table->string('sku')->unique();
        $table->decimal('price', 15, 2);
        $table->timestamps();
    });
}
```

### 8. Add Tenant Context for Multi-Tenancy
For tenant-specific tables, add tenant_id column:

```php
public function up(): void
{
    Schema::create('orders', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('tenant_id'); // Multi-tenancy
        $table->uuid('customer_id');
        
        // Indexes
        $table->index('tenant_id');
        $table->index(['tenant_id', 'customer_id']);
        
        // Foreign keys
        $table->foreign('tenant_id')
            ->references('id')
            ->on('tenants')
            ->onDelete('cascade');
        
        $table->timestamps();
    });
}
```

### 9. Use Proper Data Types
Choose appropriate column types:

```php
public function up(): void
{
    Schema::create('orders', function (Blueprint $table) {
        // Text fields
        $table->string('code', 50); // Fixed length
        $table->text('notes'); // Variable length text
        $table->json('metadata'); // JSON data
        
        // Numbers
        $table->decimal('amount', 15, 2); // For money (precision important)
        $table->integer('quantity'); // For counts
        $table->unsignedBigInteger('views'); // For large numbers
        $table->float('rating', 3, 2); // For ratings (e.g., 4.75)
        
        // Dates and times
        $table->date('birth_date');
        $table->datetime('appointment_at');
        $table->timestamp('created_at');
        $table->time('opening_time');
        
        // Boolean
        $table->boolean('is_active')->default(true);
        
        // Enums
        $table->enum('status', ['draft', 'pending', 'approved', 'rejected']);
    });
}
```

### 10. Add Default Values Where Appropriate
Set sensible defaults to avoid null issues:

```php
public function up(): void
{
    Schema::create('orders', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('status')->default('draft');
        $table->boolean('is_paid')->default(false);
        $table->decimal('discount', 15, 2)->default(0.00);
        $table->integer('quantity')->default(1);
        $table->timestamps();
    });
}
```

## Migration Patterns

### Adding Columns to Existing Tables

```php
public function up(): void
{
    Schema::table('orders', function (Blueprint $table) {
        $table->string('tracking_number')->nullable()->after('order_number');
        $table->index('tracking_number');
    });
}

public function down(): void
{
    Schema::table('orders', function (Blueprint $table) {
        $table->dropIndex(['tracking_number']);
        $table->dropColumn('tracking_number');
    });
}
```

### Renaming Columns

```php
public function up(): void
{
    Schema::table('orders', function (Blueprint $table) {
        $table->renameColumn('old_name', 'new_name');
    });
}

public function down(): void
{
    Schema::table('orders', function (Blueprint $table) {
        $table->renameColumn('new_name', 'old_name');
    });
}
```

### Polymorphic Relationships

```php
public function up(): void
{
    Schema::create('comments', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->text('content');
        $table->uuidMorphs('commentable'); // Creates commentable_type and commentable_id
        $table->timestamps();
        
        // Add indexes on polymorphic columns
        $table->index(['commentable_type', 'commentable_id']);
    });
}
```

### Pivot Tables for Many-to-Many Relationships

```php
public function up(): void
{
    Schema::create('order_product', function (Blueprint $table) {
        $table->uuid('order_id');
        $table->uuid('product_id');
        $table->integer('quantity')->default(1);
        $table->decimal('price', 15, 2);
        
        // Composite primary key
        $table->primary(['order_id', 'product_id']);
        
        // Foreign keys
        $table->foreign('order_id')
            ->references('id')
            ->on('orders')
            ->onDelete('cascade');
        
        $table->foreign('product_id')
            ->references('id')
            ->on('products')
            ->onDelete('restrict');
        
        $table->timestamps();
    });
}
```

## Running Migrations

```bash
# Run all pending migrations
php artisan migrate

# Rollback last batch
php artisan migrate:rollback

# Rollback all migrations
php artisan migrate:reset

# Rollback and re-run all migrations
php artisan migrate:refresh

# Drop all tables and re-run migrations
php artisan migrate:fresh

# Run migrations with seeding
php artisan migrate:fresh --seed

# Run module migrations
php artisan module:migrate ModuleName

# Check migration status
php artisan migrate:status
```

## Common Pitfalls to Avoid

1. **Don't modify existing migrations after deployment** - Create new migrations instead
2. **Don't forget foreign key constraints** - They enforce data integrity
3. **Don't forget indexes** - They are critical for performance
4. **Don't use auto-incrementing IDs in multi-tenant contexts** - Use UUIDs/ULIDs
5. **Don't forget to add down() methods** - Always provide rollback logic
6. **Don't use nullable columns excessively** - Use defaults where possible
7. **Don't forget tenant_id in multi-tenant tables** - Essential for data isolation
8. **Don't mix data changes with schema changes** - Use separate migrations
