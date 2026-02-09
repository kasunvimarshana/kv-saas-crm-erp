---
applyTo: "**/Http/Requests/**/*.php"
---

# Form Request Validation Requirements

When creating Form Request classes for validation, follow these guidelines to ensure data integrity and proper error handling.

## Overview

Form Requests provide:
- Centralized validation logic
- Authorization checks
- Custom error messages
- Clean controller code
- Reusable validation rules

## Basic Form Request

### 1. Create Form Request Class

```bash
php artisan make:request CreateCustomerRequest
```

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Customer::class);
    }

    /**
     * Get the validation rules that apply to the request
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:customers,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'type' => ['required', 'in:individual,business'],
            'tax_id' => ['required_if:type,business', 'string', 'max:50'],
            'address' => ['nullable', 'array'],
            'address.line1' => ['required_with:address', 'string', 'max:255'],
            'address.city' => ['required_with:address', 'string', 'max:100'],
            'address.postal_code' => ['required_with:address', 'string', 'max:20'],
            'address.country' => ['required_with:address', 'string', 'size:2'],
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Customer name is required',
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email address is already registered',
            'type.required' => 'Customer type is required',
            'type.in' => 'Customer type must be either individual or business',
            'tax_id.required_if' => 'Tax ID is required for business customers',
        ];
    }

    /**
     * Get custom attribute names for error messages
     */
    public function attributes(): array
    {
        return [
            'address.line1' => 'address line 1',
            'address.city' => 'city',
            'address.postal_code' => 'postal code',
            'address.country' => 'country code',
        ];
    }
}
```

## Update Form Request

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized
     */
    public function authorize(): bool
    {
        $customer = $this->route('customer');
        return $this->user()->can('update', $customer);
    }

    /**
     * Get the validation rules
     */
    public function rules(): array
    {
        $customerId = $this->route('customer')->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('customers')->ignore($customerId)
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'type' => ['sometimes', 'required', 'in:individual,business'],
            'status' => ['sometimes', 'required', 'in:active,inactive,blocked'],
        ];
    }
}
```

## Advanced Validation Rules

### 1. Custom Validation Rules

```php
public function rules(): array
{
    return [
        'product_id' => [
            'required',
            'uuid',
            'exists:products,id',
            function ($attribute, $value, $fail) {
                $product = Product::find($value);
                if ($product && !$product->is_active) {
                    $fail('The selected product is not active.');
                }
            }
        ],
        'quantity' => [
            'required',
            'integer',
            'min:1',
            function ($attribute, $value, $fail) {
                $productId = $this->input('product_id');
                $available = $this->getAvailableQuantity($productId);
                if ($value > $available) {
                    $fail("Only {$available} units available.");
                }
            }
        ],
    ];
}
```

### 2. Conditional Validation

```php
public function rules(): array
{
    return [
        'payment_method' => ['required', 'in:cash,card,transfer'],
        'card_number' => ['required_if:payment_method,card', 'string', 'size:16'],
        'card_expiry' => ['required_if:payment_method,card', 'date_format:m/y'],
        'card_cvv' => ['required_if:payment_method,card', 'string', 'size:3'],
        'bank_account' => ['required_if:payment_method,transfer', 'string'],
    ];
}
```

### 3. Array Validation

```php
public function rules(): array
{
    return [
        'items' => ['required', 'array', 'min:1'],
        'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
        'items.*.quantity' => ['required', 'integer', 'min:1'],
        'items.*.price' => ['required', 'numeric', 'min:0'],
        'items.*.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
    ];
}
```

### 4. Complex Business Rules

```php
public function rules(): array
{
    return [
        'order_date' => [
            'required',
            'date',
            'after_or_equal:today',
            function ($attribute, $value, $fail) {
                $orderDate = Carbon::parse($value);
                if ($orderDate->isWeekend()) {
                    $fail('Orders cannot be placed on weekends.');
                }
            }
        ],
        'delivery_date' => [
            'required',
            'date',
            'after:order_date',
            function ($attribute, $value, $fail) {
                $orderDate = Carbon::parse($this->input('order_date'));
                $deliveryDate = Carbon::parse($value);
                $diffInDays = $orderDate->diffInDays($deliveryDate);
                
                if ($diffInDays < 2) {
                    $fail('Delivery must be at least 2 days after order date.');
                }
            }
        ],
    ];
}
```

## Preparing Data

### 1. Prepare for Validation

```php
protected function prepareForValidation(): void
{
    $this->merge([
        'slug' => Str::slug($this->name),
        'email' => strtolower($this->email),
        'phone' => $this->formatPhoneNumber($this->phone),
    ]);
}

private function formatPhoneNumber(?string $phone): ?string
{
    if (!$phone) {
        return null;
    }
    
    return preg_replace('/[^0-9+]/', '', $phone);
}
```

### 2. After Validation

```php
public function withValidator($validator): void
{
    $validator->after(function ($validator) {
        if ($this->somethingElseIsInvalid()) {
            $validator->errors()->add('field', 'Something is wrong with this field!');
        }
    });
}
```

## Using Form Requests in Controllers

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Sales\Http\Requests\CreateCustomerRequest;
use Modules\Sales\Http\Requests\UpdateCustomerRequest;
use Modules\Sales\Http\Resources\CustomerResource;
use Modules\Sales\Services\CustomerService;

class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $customerService
    ) {}

    /**
     * Store a newly created customer
     */
    public function store(CreateCustomerRequest $request): JsonResponse
    {
        // Data is already validated
        $customer = $this->customerService->createCustomer($request->validated());
        
        return response()->json(new CustomerResource($customer), 201);
    }

    /**
     * Update the specified customer
     */
    public function update(UpdateCustomerRequest $request, string $id): JsonResponse
    {
        // Data is already validated and authorized
        $customer = $this->customerService->updateCustomer($id, $request->validated());
        
        return response()->json(new CustomerResource($customer));
    }
}
```

## Custom Validation Rules

### 1. Create Custom Rule Class

```bash
php artisan make:rule ValidTaxId
```

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidTaxId implements Rule
{
    /**
     * Determine if the validation rule passes
     */
    public function passes($attribute, $value): bool
    {
        // Example: Validate tax ID format
        // Format: XX-XXXXXXX (2 letters, dash, 7 digits)
        return preg_match('/^[A-Z]{2}-\d{7}$/', $value) === 1;
    }

    /**
     * Get the validation error message
     */
    public function message(): string
    {
        return 'The :attribute must be in format XX-XXXXXXX';
    }
}
```

### 2. Use Custom Rule

```php
use Modules\Sales\Rules\ValidTaxId;

public function rules(): array
{
    return [
        'tax_id' => ['required', 'string', new ValidTaxId],
    ];
}
```

### 3. Custom Rule with Parameters

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Rules;

use Illuminate\Contracts\Validation\Rule;

class MinimumQuantity implements Rule
{
    public function __construct(
        private int $minimum
    ) {}

    public function passes($attribute, $value): bool
    {
        return (int) $value >= $this->minimum;
    }

    public function message(): string
    {
        return "The :attribute must be at least {$this->minimum}.";
    }
}
```

```php
use Modules\Sales\Rules\MinimumQuantity;

public function rules(): array
{
    return [
        'quantity' => ['required', 'integer', new MinimumQuantity(5)],
    ];
}
```

## Common Validation Rules Reference

### String Validation
```php
'name' => ['required', 'string', 'max:255'],
'slug' => ['required', 'string', 'alpha_dash', 'unique:products,slug'],
'email' => ['required', 'email:rfc,dns'],
'url' => ['required', 'url', 'active_url'],
'phone' => ['required', 'regex:/^\+?[1-9]\d{1,14}$/'],
```

### Numeric Validation
```php
'age' => ['required', 'integer', 'min:18', 'max:100'],
'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
'discount' => ['nullable', 'numeric', 'between:0,100'],
```

### Date Validation
```php
'birth_date' => ['required', 'date', 'before:today'],
'start_date' => ['required', 'date', 'after_or_equal:today'],
'end_date' => ['required', 'date', 'after:start_date'],
'appointment' => ['required', 'date_format:Y-m-d H:i:s'],
```

### Array Validation
```php
'tags' => ['required', 'array', 'min:1', 'max:5'],
'tags.*' => ['required', 'string', 'max:50'],
'options' => ['nullable', 'array'],
'options.*.key' => ['required', 'string'],
'options.*.value' => ['required'],
```

### File Validation
```php
'avatar' => ['required', 'file', 'image', 'max:2048'],  // 2MB
'document' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],  // 10MB
'images.*' => ['required', 'image', 'dimensions:min_width=100,min_height=100'],
```

### Boolean Validation
```php
'is_active' => ['required', 'boolean'],
'accept_terms' => ['required', 'accepted'],
```

### Relationship Validation
```php
'customer_id' => ['required', 'uuid', 'exists:customers,id'],
'category_id' => ['nullable', 'exists:categories,id,deleted_at,NULL'],
```

## Error Handling

### 1. Automatic Error Response

Laravel automatically returns 422 response with validation errors:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email field must be a valid email address."]
  }
}
```

### 2. Custom Error Response

```php
protected function failedValidation(Validator $validator)
{
    throw new HttpResponseException(
        response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors()
        ], 422)
    );
}
```

### 3. Stop On First Failure

```php
protected $stopOnFirstFailure = true;
```

## Testing Form Requests

### 1. Test Validation Rules

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use Tests\TestCase;
use Modules\Sales\Http\Requests\CreateCustomerRequest;

class CreateCustomerRequestTest extends TestCase
{
    public function test_validation_passes_with_valid_data(): void
    {
        $request = new CreateCustomerRequest();
        
        $validator = Validator::make(
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'type' => 'individual'
            ],
            $request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_validation_fails_without_required_fields(): void
    {
        $request = new CreateCustomerRequest();
        
        $validator = Validator::make(
            [],
            $request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
        $this->assertTrue($validator->errors()->has('email'));
    }

    public function test_validation_fails_with_invalid_email(): void
    {
        $request = new CreateCustomerRequest();
        
        $validator = Validator::make(
            [
                'name' => 'John Doe',
                'email' => 'invalid-email',
                'type' => 'individual'
            ],
            $request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('email'));
    }
}
```

### 2. Test Authorization

```php
public function test_user_can_create_customer(): void
{
    $user = User::factory()->create();
    $user->givePermissionTo('create-customer');
    
    $request = new CreateCustomerRequest();
    $request->setUserResolver(fn() => $user);
    
    $this->assertTrue($request->authorize());
}

public function test_user_cannot_create_customer_without_permission(): void
{
    $user = User::factory()->create();
    
    $request = new CreateCustomerRequest();
    $request->setUserResolver(fn() => $user);
    
    $this->assertFalse($request->authorize());
}
```

## Best Practices

### 1. Keep Validation Logic in Form Requests
- Don't validate in controllers
- Don't validate in services
- Centralize all validation in form requests

### 2. Use Descriptive Rule Arrays
```php
// Good
'email' => [
    'required',
    'email',
    'max:255',
    'unique:users,email'
],

// Avoid
'email' => 'required|email|max:255|unique:users,email',
```

### 3. Provide Custom Error Messages
- Make error messages user-friendly
- Be specific about what's wrong
- Use the `messages()` method

### 4. Use Authorization
- Always implement the `authorize()` method
- Don't put authorization logic elsewhere
- Return proper boolean value

### 5. Test Your Validations
- Test with valid data
- Test with invalid data
- Test edge cases
- Test authorization

## Common Pitfalls to Avoid

1. **Don't forget authorization** - Always implement `authorize()` method
2. **Don't validate in controllers** - Use form requests
3. **Don't use string pipe syntax** - Use array syntax for clarity
4. **Don't forget to test** - Write tests for validation rules
5. **Don't hard-code error messages** - Use the `messages()` method
6. **Don't over-validate** - Only validate what's necessary
7. **Don't forget nullable rules** - Mark optional fields as `nullable`
8. **Don't mix concerns** - Keep business logic out of validation

## Checklist

- [x] Create form request class
- [x] Implement authorization logic
- [x] Define validation rules
- [x] Provide custom error messages
- [x] Use array syntax for rules
- [x] Test with valid data
- [x] Test with invalid data
- [x] Test authorization
- [x] Document complex rules
- [x] Use custom rules when needed
- [x] Prepare data when necessary
- [x] Handle nested array validation properly
