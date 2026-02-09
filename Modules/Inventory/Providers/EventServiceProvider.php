<?php

declare(strict_types=1);

namespace Modules\Inventory\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Inventory\Events\LowStockAlert;
use Modules\Inventory\Events\StockLevelChanged;
use Modules\Inventory\Events\StockMovementRecorded;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        StockLevelChanged::class => [
            // Add listeners here
        ],
        LowStockAlert::class => [
            // Add listeners here
        ],
        StockMovementRecorded::class => [
            // Add listeners here
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
