---
applyTo: 
  - "**/Events/**/*.php"
  - "**/Listeners/**/*.php"
  - "**/Observers/**/*.php"
---

# Event-Driven Architecture Requirements

When implementing event-driven patterns, follow these guidelines to achieve loose coupling and maintainable cross-module communication.

## Overview

Event-Driven Architecture enables:
- Loose coupling between modules
- Asynchronous processing
- Scalability and flexibility
- Clear separation of concerns
- Easy addition of new features

## Domain Events

### 1. Creating Domain Events

Domain events represent something significant that happened in the system:

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Sales\Entities\SalesOrder;

class OrderCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance
     */
    public function __construct(
        public readonly SalesOrder $order
    ) {}
}
```

### 2. Event Naming Conventions

- Use **past tense** to indicate something has occurred
- Be specific and descriptive
- Follow the pattern: `{Entity}{Action}`

```php
// Good examples
OrderCreated
PaymentReceived
CustomerStatusChanged
StockLevelUpdated
InvoiceSent

// Avoid
CreateOrder        // Present tense
NewOrder          // Not specific
OrderEvent        // Too generic
```

### 3. Event with Additional Context

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Sales\Entities\SalesOrder;

class OrderStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly SalesOrder $order,
        public readonly string $previousStatus,
        public readonly string $newStatus,
        public readonly ?string $changedBy = null
    ) {}
}
```

## Event Listeners

### 1. Synchronous Listener

Executes immediately when event is fired:

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Sales\Events\OrderCreated;

class LogOrderCreation
{
    /**
     * Handle the event
     */
    public function handle(OrderCreated $event): void
    {
        Log::info('Order created', [
            'order_id' => $event->order->id,
            'customer_id' => $event->order->customer_id,
            'total' => $event->order->total
        ]);
    }
}
```

### 2. Asynchronous Listener (Queued)

Executes in background via queue:

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Modules\Sales\Events\OrderCreated;
use Modules\Sales\Mail\OrderConfirmationMail;

class SendOrderConfirmationEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying
     */
    public int $backoff = 10;

    /**
     * Handle the event
     */
    public function handle(OrderCreated $event): void
    {
        Mail::to($event->order->customer->email)
            ->send(new OrderConfirmationMail($event->order));
    }

    /**
     * Handle a job failure
     */
    public function failed(OrderCreated $event, \Throwable $exception): void
    {
        // Log failure, notify admin, etc.
    }
}
```

### 3. Listener with Dependency Injection

```php
<?php

declare(strict_types=1);

namespace Modules\Accounting\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Sales\Events\OrderCreated;
use Modules\Accounting\Services\AccountingService;
use Modules\Accounting\Repositories\Contracts\JournalEntryRepositoryInterface;

class CreateAccountingEntry implements ShouldQueue
{
    /**
     * Create a new listener instance
     */
    public function __construct(
        private AccountingService $accountingService,
        private JournalEntryRepositoryInterface $journalEntryRepository
    ) {}

    /**
     * Handle the event
     */
    public function handle(OrderCreated $event): void
    {
        // Create journal entry for the order
        $this->accountingService->recordSalesOrder($event->order);
    }
}
```

## Event Registration

### 1. Register in EventServiceProvider

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Sales\Events\OrderCreated;
use Modules\Sales\Events\OrderStatusChanged;
use Modules\Sales\Listeners\LogOrderCreation;
use Modules\Sales\Listeners\SendOrderConfirmationEmail;
use Modules\Accounting\Listeners\CreateAccountingEntry;
use Modules\Inventory\Listeners\ReserveStock;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the module
     */
    protected $listen = [
        OrderCreated::class => [
            LogOrderCreation::class,
            SendOrderConfirmationEmail::class,
            CreateAccountingEntry::class,
            ReserveStock::class,
        ],
        OrderStatusChanged::class => [
            NotifyCustomerOfStatusChange::class,
            UpdateInventoryReservation::class,
        ],
    ];

    /**
     * Register any events for your module
     */
    public function boot(): void
    {
        parent::boot();
    }
}
```

### 2. Auto-Discovery

Laravel can auto-discover listeners if placed in the correct location:

```
Modules/
  Sales/
    Events/
      OrderCreated.php
    Listeners/
      LogOrderCreation.php     # Automatically discovered
      SendOrderConfirmation.php
```

## Dispatching Events

### 1. From Service Layer

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\Sales\Events\OrderCreated;
use Modules\Sales\Repositories\Contracts\SalesOrderRepositoryInterface;

class OrderProcessingService
{
    public function __construct(
        private SalesOrderRepositoryInterface $orderRepository
    ) {}

    public function createOrder(array $data): SalesOrder
    {
        $order = $this->orderRepository->create($data);

        // Dispatch event
        event(new OrderCreated($order));

        return $order;
    }
}
```

### 2. Using Event Facade

```php
use Illuminate\Support\Facades\Event;
use Modules\Sales\Events\OrderCreated;

Event::dispatch(new OrderCreated($order));
```

### 3. Conditional Dispatching

```php
if ($order->total > 10000) {
    event(new LargeOrderCreated($order));
} else {
    event(new OrderCreated($order));
}
```

### 4. Dispatching Multiple Events

```php
event(new OrderCreated($order));
event(new InventoryReserved($order));
event(new CustomerNotified($order->customer));
```

## Event Subscribers

For grouping related event handlers:

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Listeners;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Modules\Sales\Events\OrderCreated;
use Modules\Sales\Events\OrderShipped;
use Modules\Sales\Events\OrderDelivered;

class OrderEventSubscriber
{
    /**
     * Handle order created events
     */
    public function handleOrderCreated(OrderCreated $event): void
    {
        Log::info('Order created', ['order_id' => $event->order->id]);
    }

    /**
     * Handle order shipped events
     */
    public function handleOrderShipped(OrderShipped $event): void
    {
        Log::info('Order shipped', ['order_id' => $event->order->id]);
    }

    /**
     * Handle order delivered events
     */
    public function handleOrderDelivered(OrderDelivered $event): void
    {
        Log::info('Order delivered', ['order_id' => $event->order->id]);
    }

    /**
     * Register the listeners for the subscriber
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            OrderCreated::class,
            [OrderEventSubscriber::class, 'handleOrderCreated']
        );

        $events->listen(
            OrderShipped::class,
            [OrderEventSubscriber::class, 'handleOrderShipped']
        );

        $events->listen(
            OrderDelivered::class,
            [OrderEventSubscriber::class, 'handleOrderDelivered']
        );
    }
}
```

Register subscriber in EventServiceProvider:

```php
protected $subscribe = [
    OrderEventSubscriber::class,
];
```

## Model Events

Use Eloquent model events for database-related triggers:

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Sales\Events\OrderCreated;
use Modules\Sales\Events\OrderUpdated;

class SalesOrder extends Model
{
    /**
     * The "booted" method of the model
     */
    protected static function booted(): void
    {
        static::created(function (SalesOrder $order) {
            event(new OrderCreated($order));
        });

        static::updated(function (SalesOrder $order) {
            if ($order->wasChanged('status')) {
                event(new OrderStatusChanged(
                    $order,
                    $order->getOriginal('status'),
                    $order->status
                ));
            }
        });
    }
}
```

## Model Observers

For more complex model event handling:

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Observers;

use Modules\Sales\Entities\SalesOrder;
use Modules\Sales\Events\OrderCreated;
use Modules\Sales\Events\OrderDeleted;

class SalesOrderObserver
{
    /**
     * Handle the SalesOrder "created" event
     */
    public function created(SalesOrder $order): void
    {
        event(new OrderCreated($order));
    }

    /**
     * Handle the SalesOrder "updated" event
     */
    public function updated(SalesOrder $order): void
    {
        if ($order->wasChanged('status')) {
            event(new OrderStatusChanged($order));
        }
    }

    /**
     * Handle the SalesOrder "deleting" event
     */
    public function deleting(SalesOrder $order): void
    {
        // Can prevent deletion
        if ($order->status === 'shipped') {
            throw new \Exception('Cannot delete shipped order');
        }
    }

    /**
     * Handle the SalesOrder "deleted" event
     */
    public function deleted(SalesOrder $order): void
    {
        event(new OrderDeleted($order));
    }
}
```

Register observer in ServiceProvider:

```php
use Modules\Sales\Entities\SalesOrder;
use Modules\Sales\Observers\SalesOrderObserver;

public function boot(): void
{
    SalesOrder::observe(SalesOrderObserver::class);
}
```

## Cross-Module Communication

### Example: Sales → Accounting

```php
// Sales Module
namespace Modules\Sales\Events;

class OrderCreated
{
    public function __construct(public SalesOrder $order) {}
}

// Accounting Module
namespace Modules\Accounting\Listeners;

class CreateAccountingEntry implements ShouldQueue
{
    public function handle(OrderCreated $event): void
    {
        // Access order from sales module
        $order = $event->order;
        
        // Create accounting entries
        $this->accountingService->recordSalesOrder($order);
    }
}
```

## Testing Events

### 1. Test Event is Dispatched

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Modules\Sales\Events\OrderCreated;
use Modules\Sales\Services\OrderProcessingService;

class OrderProcessingServiceTest extends TestCase
{
    public function test_it_dispatches_order_created_event(): void
    {
        // Fake all events
        Event::fake();

        // Create order
        $service = app(OrderProcessingService::class);
        $order = $service->createOrder($orderData);

        // Assert event was dispatched
        Event::assertDispatched(OrderCreated::class, function ($event) use ($order) {
            return $event->order->id === $order->id;
        });
    }

    public function test_it_does_not_dispatch_event_on_failure(): void
    {
        Event::fake();

        // Cause service to fail
        $this->expectException(\Exception::class);
        $service->createOrder($invalidData);

        // Assert event was not dispatched
        Event::assertNotDispatched(OrderCreated::class);
    }
}
```

### 2. Test Listener

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use Tests\TestCase;
use Modules\Sales\Events\OrderCreated;
use Modules\Sales\Listeners\SendOrderConfirmationEmail;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmationEmailTest extends TestCase
{
    public function test_it_sends_confirmation_email(): void
    {
        Mail::fake();

        $order = SalesOrder::factory()->create();
        $event = new OrderCreated($order);

        $listener = new SendOrderConfirmationEmail();
        $listener->handle($event);

        Mail::assertSent(OrderConfirmationMail::class, function ($mail) use ($order) {
            return $mail->hasTo($order->customer->email);
        });
    }
}
```

### 3. Test Event Subscriber

```php
public function test_subscriber_handles_all_order_events(): void
{
    Event::fake();

    $subscriber = new OrderEventSubscriber();
    $dispatcher = app(Dispatcher::class);
    $subscriber->subscribe($dispatcher);

    // Dispatch events
    event(new OrderCreated($order));
    event(new OrderShipped($order));

    // Assert handled
    Event::assertDispatched(OrderCreated::class);
    Event::assertDispatched(OrderShipped::class);
}
```

## Event Patterns

### 1. Chain of Events

One event triggers another:

```php
class OrderCreated
{
    // Initial event
}

class CreateAccountingEntry implements ShouldQueue
{
    public function handle(OrderCreated $event): void
    {
        $entry = $this->accountingService->recordSalesOrder($event->order);
        
        // Trigger another event
        event(new JournalEntryCreated($entry));
    }
}
```

### 2. Conditional Event Handling

```php
class SendOrderConfirmationEmail implements ShouldQueue
{
    public function handle(OrderCreated $event): void
    {
        // Only send email if customer wants notifications
        if ($event->order->customer->email_notifications_enabled) {
            Mail::to($event->order->customer->email)
                ->send(new OrderConfirmationMail($event->order));
        }
    }
}
```

### 3. Event Versioning

For evolving events:

```php
// Version 1
class OrderCreated
{
    public function __construct(public SalesOrder $order) {}
}

// Version 2 (additional data)
class OrderCreatedV2
{
    public function __construct(
        public SalesOrder $order,
        public string $source,
        public ?string $referrer = null
    ) {}
}
```

## Best Practices

### 1. Keep Events Simple
Events should only carry necessary data:
```php
// Good
class OrderCreated
{
    public function __construct(public SalesOrder $order) {}
}

// Avoid
class OrderCreated
{
    public function __construct(
        public SalesOrder $order,
        public Customer $customer,    // Already in $order
        public array $lines,          // Already in $order
        public array $metadata        // Unnecessary
    ) {}
}
```

### 2. Use Queued Listeners for Heavy Operations
```php
// Email sending - queue it
class SendOrderConfirmationEmail implements ShouldQueue

// Logging - sync is fine
class LogOrderCreation
```

### 3. Handle Failures Gracefully
```php
class SendOrderConfirmationEmail implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 10;

    public function failed(OrderCreated $event, \Throwable $exception): void
    {
        Log::error('Failed to send order confirmation', [
            'order_id' => $event->order->id,
            'error' => $exception->getMessage()
        ]);
    }
}
```

### 4. Document Event Contracts
```php
/**
 * Fired when a new sales order is created
 *
 * @property SalesOrder $order The created order
 */
class OrderCreated
{
    // ...
}
```

## Common Pitfalls to Avoid

1. **Don't put business logic in events** - Keep events as data carriers
2. **Don't create circular event dependencies** - A→B→A creates infinite loop
3. **Don't forget to queue heavy operations** - Email, external APIs, etc.
4. **Don't ignore listener failures** - Implement proper error handling
5. **Don't over-use events** - Not everything needs to be event-driven
6. **Don't forget to test events** - Test both dispatch and handling
7. **Don't make events too large** - Only include necessary data

## Checklist

- [x] Use past tense for event names
- [x] Keep events simple (data carriers only)
- [x] Implement ShouldQueue for heavy operations
- [x] Handle listener failures gracefully
- [x] Register events in EventServiceProvider
- [x] Test event dispatching
- [x] Test listener behavior
- [x] Document event contracts
- [x] Use model observers for complex model events
- [x] Implement proper error handling
- [x] Log important event operations
- [x] Consider using event subscribers for related handlers
