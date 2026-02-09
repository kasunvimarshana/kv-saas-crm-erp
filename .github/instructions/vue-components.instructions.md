# Vue.js 3 Component Development Requirements

When developing Vue.js 3 components, follow these guidelines to ensure consistency, maintainability, and adherence to native Vue features.

## Native Implementation Principle

**CRITICAL**: Use only native Vue 3 features. NO third-party component libraries (Vuetify, Element, Ant Design, etc.). Build custom, reusable components.

## Component Structure Standards

### 1. Use Composition API with Script Setup

Always use the Composition API with `<script setup>` syntax:

```vue
<script setup lang="ts">
// TypeScript is recommended but optional
import { ref, computed, onMounted } from 'vue'
import type { Product } from '@/types'

// Component logic here
</script>

<template>
  <!-- Template code -->
</template>

<style scoped>
/* Component-specific styles */
</style>
```

### 2. Component File Organization

```vue
<script setup lang="ts">
// 1. Imports (external libraries, Vue composables, types)
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import type { Customer } from '@/types'

// 2. Props & Emits Definitions
interface Props {
  customer: Customer
  readonly?: boolean
}
const props = withDefaults(defineProps<Props>(), {
  readonly: false
})

interface Emits {
  update: [customer: Customer]
  delete: [id: string]
  cancel: []
}
const emit = defineEmits<Emits>()

// 3. Composables
const router = useRouter()
const { isLoading, error, save } = useCustomerApi()

// 4. Reactive State
const formData = ref({ ...props.customer })
const isDirty = ref(false)
const errors = ref<Record<string, string>>({})

// 5. Computed Properties
const displayName = computed(() => 
  `${formData.value.firstName} ${formData.value.lastName}`
)

const isValid = computed(() => 
  Object.keys(errors.value).length === 0
)

// 6. Watchers
watch(() => props.customer, (newVal) => {
  formData.value = { ...newVal }
}, { deep: true })

// 7. Methods
const validateForm = (): boolean => {
  errors.value = {}
  if (!formData.value.firstName) {
    errors.value.firstName = 'First name is required'
  }
  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) return
  
  try {
    await save(formData.value)
    emit('update', formData.value)
  } catch (e) {
    error.value = e.message
  }
}

const handleCancel = () => {
  formData.value = { ...props.customer }
  isDirty.value = false
  emit('cancel')
}

// 8. Lifecycle Hooks
onMounted(() => {
  // Component initialization
})
</script>
```

### 3. Props and Emits with TypeScript

```vue
<script setup lang="ts">
// Define prop types with interface
interface Props {
  modelValue: string
  label?: string
  required?: boolean
  disabled?: boolean
  placeholder?: string
}

const props = withDefaults(defineProps<Props>(), {
  label: '',
  required: false,
  disabled: false,
  placeholder: ''
})

// Define emit types
interface Emits {
  'update:modelValue': [value: string]
  'blur': []
  'focus': []
}

const emit = defineEmits<Emits>()

// Use v-model pattern
const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value)
}
</script>

<template>
  <div class="input-field">
    <label v-if="label">{{ label }}</label>
    <input
      :value="modelValue"
      @input="handleInput"
      @blur="emit('blur')"
      @focus="emit('focus')"
      :disabled="disabled"
      :placeholder="placeholder"
      :required="required"
    />
  </div>
</template>
```

## Composables Pattern (Reusable Logic)

### 1. Create Composables for Shared Logic

```typescript
// composables/useApi.ts
import { ref } from 'vue'

export function useApi<T>(endpoint: string) {
  const data = ref<T | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const fetchData = async () => {
    isLoading.value = true
    error.value = null
    try {
      const response = await fetch(`/api/v1${endpoint}`)
      if (!response.ok) throw new Error(response.statusText)
      data.value = await response.json()
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
    } finally {
      isLoading.value = false
    }
  }

  const postData = async (payload: Partial<T>) => {
    isLoading.value = true
    error.value = null
    try {
      const response = await fetch(`/api/v1${endpoint}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      if (!response.ok) throw new Error(response.statusText)
      data.value = await response.json()
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
    } finally {
      isLoading.value = false
    }
  }

  return {
    data,
    isLoading,
    error,
    fetchData,
    postData
  }
}
```

### 2. Use Composables in Components

```vue
<script setup lang="ts">
import { onMounted } from 'vue'
import { useApi } from '@/composables/useApi'
import type { Customer } from '@/types'

const { data: customers, isLoading, error, fetchData } = useApi<Customer[]>('/customers')

onMounted(() => {
  fetchData()
})
</script>

<template>
  <div>
    <div v-if="isLoading">Loading...</div>
    <div v-else-if="error">Error: {{ error }}</div>
    <div v-else-if="customers">
      <div v-for="customer in customers" :key="customer.id">
        {{ customer.name }}
      </div>
    </div>
  </div>
</template>
```

## Native Vue 3 Features

### 1. Teleport (for Modals and Overlays)

```vue
<script setup lang="ts">
import { ref } from 'vue'

const isOpen = ref(false)
</script>

<template>
  <button @click="isOpen = true">Open Modal</button>
  
  <Teleport to="body">
    <div v-if="isOpen" class="modal-backdrop">
      <div class="modal">
        <h2>Modal Title</h2>
        <p>Modal content</p>
        <button @click="isOpen = false">Close</button>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  max-width: 500px;
  width: 90%;
}
</style>
```

### 2. Suspense (for Async Components)

```vue
<script setup lang="ts">
import { defineAsyncComponent } from 'vue'

const AsyncCustomerList = defineAsyncComponent(() => 
  import('./CustomerList.vue')
)
</script>

<template>
  <Suspense>
    <template #default>
      <AsyncCustomerList />
    </template>
    <template #fallback>
      <div>Loading customers...</div>
    </template>
  </Suspense>
</template>
```

### 3. Provide/Inject (Dependency Injection)

```vue
<!-- Parent Component -->
<script setup lang="ts">
import { provide, ref } from 'vue'
import type { Tenant } from '@/types'

const currentTenant = ref<Tenant | null>(null)
provide('tenant', currentTenant)
</script>

<!-- Child Component (any level deep) -->
<script setup lang="ts">
import { inject } from 'vue'
import type { Ref } from 'vue'
import type { Tenant } from '@/types'

const tenant = inject<Ref<Tenant | null>>('tenant')
</script>

<template>
  <div v-if="tenant">
    Current Tenant: {{ tenant.name }}
  </div>
</template>
```

## Form Handling

### 1. Custom Form Components

```vue
<!-- BaseInput.vue -->
<script setup lang="ts">
interface Props {
  modelValue: string | number
  label: string
  type?: 'text' | 'email' | 'password' | 'number'
  error?: string
  required?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  required: false
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
}>()
</script>

<template>
  <div class="form-field">
    <label>
      {{ label }}
      <span v-if="required" class="required">*</span>
    </label>
    <input
      :type="type"
      :value="modelValue"
      @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      :class="{ error: error }"
    />
    <span v-if="error" class="error-message">{{ error }}</span>
  </div>
</template>
```

### 2. Form Validation

```vue
<script setup lang="ts">
import { ref, computed } from 'vue'
import type { Customer } from '@/types'

const formData = ref<Partial<Customer>>({
  firstName: '',
  lastName: '',
  email: '',
  phone: ''
})

const errors = ref<Record<string, string>>({})

const isValid = computed(() => Object.keys(errors.value).length === 0)

const validateField = (field: keyof Customer, value: any): string | null => {
  switch (field) {
    case 'firstName':
    case 'lastName':
      return !value || value.length < 2 
        ? 'Must be at least 2 characters' 
        : null
    case 'email':
      return !value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
        ? 'Invalid email address'
        : null
    case 'phone':
      return !value || !/^\+?[\d\s-()]+$/.test(value)
        ? 'Invalid phone number'
        : null
    default:
      return null
  }
}

const validateForm = (): boolean => {
  errors.value = {}
  for (const [key, value] of Object.entries(formData.value)) {
    const error = validateField(key as keyof Customer, value)
    if (error) {
      errors.value[key] = error
    }
  }
  return isValid.value
}

const handleSubmit = async () => {
  if (!validateForm()) return
  
  // Submit form
}
</script>
```

## Component Naming Conventions

1. **Component Files**: PascalCase (e.g., `CustomerList.vue`, `OrderForm.vue`)
2. **Composables**: camelCase with "use" prefix (e.g., `useAuth.ts`, `useCustomers.ts`)
3. **Types**: PascalCase (e.g., `Customer`, `Order`)
4. **Props**: camelCase (e.g., `modelValue`, `isLoading`)
5. **Events**: kebab-case (e.g., `update:modelValue`, `item-selected`)

## Styling Guidelines

### 1. Use Scoped Styles

Always use `<style scoped>` to prevent style leakage:

```vue
<style scoped>
.customer-card {
  padding: 1rem;
  border: 1px solid #ddd;
  border-radius: 4px;
}
</style>
```

### 2. Use Tailwind CSS Utility Classes

Prefer Tailwind utility classes for common styles:

```vue
<template>
  <div class="p-4 border border-gray-300 rounded-lg">
    <h2 class="text-xl font-bold mb-2">{{ title }}</h2>
    <p class="text-gray-600">{{ description }}</p>
  </div>
</template>
```

### 3. Custom CSS for Complex Components

For complex, reusable components, use scoped CSS:

```vue
<style scoped>
.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
}

.data-table th {
  background-color: #f9fafb;
  font-weight: 600;
}

.data-table tr:hover {
  background-color: #f9fafb;
}
</style>
```

## Testing Vue Components

### 1. Component Test Structure

```typescript
// CustomerCard.test.ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import CustomerCard from '@/components/CustomerCard.vue'
import type { Customer } from '@/types'

describe('CustomerCard', () => {
  const mockCustomer: Customer = {
    id: '1',
    firstName: 'John',
    lastName: 'Doe',
    email: 'john@example.com',
    status: 'active'
  }

  it('renders customer name', () => {
    const wrapper = mount(CustomerCard, {
      props: { customer: mockCustomer }
    })
    expect(wrapper.text()).toContain('John Doe')
  })

  it('emits update event on edit', async () => {
    const wrapper = mount(CustomerCard, {
      props: { customer: mockCustomer }
    })
    await wrapper.find('.edit-button').trigger('click')
    expect(wrapper.emitted('update')).toBeTruthy()
  })

  it('applies readonly class when readonly prop is true', () => {
    const wrapper = mount(CustomerCard, {
      props: { customer: mockCustomer, readonly: true }
    })
    expect(wrapper.classes()).toContain('readonly')
  })
})
```

### 2. Composable Testing

```typescript
// useCustomers.test.ts
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useCustomers } from '@/composables/useCustomers'

describe('useCustomers', () => {
  beforeEach(() => {
    global.fetch = vi.fn()
  })

  it('fetches customers successfully', async () => {
    const mockCustomers = [{ id: '1', name: 'John Doe' }]
    ;(global.fetch as any).mockResolvedValueOnce({
      ok: true,
      json: async () => mockCustomers
    })

    const { customers, fetchCustomers } = useCustomers()
    await fetchCustomers()

    expect(customers.value).toEqual(mockCustomers)
  })

  it('handles fetch errors', async () => {
    ;(global.fetch as any).mockRejectedValueOnce(new Error('Network error'))

    const { error, fetchCustomers } = useCustomers()
    await fetchCustomers()

    expect(error.value).toBe('Network error')
  })
})
```

## Common Pitfalls to Avoid

1. **Don't use Options API** - Always use Composition API with `<script setup>`
2. **Don't use third-party component libraries** - Build custom components
3. **Don't mutate props directly** - Emit events to parent or use v-model
4. **Don't forget to use `scoped` in styles** - Prevents style leakage
5. **Don't overuse watchers** - Prefer computed properties or methods
6. **Don't forget TypeScript types** - Helps catch errors early
7. **Don't create too many reactive refs** - Use computed for derived state
8. **Don't forget to clean up side effects** - Use `onUnmounted` hook
9. **Don't use inline styles** - Use Tailwind classes or scoped CSS
10. **Don't forget accessibility** - Add aria labels, keyboard navigation

## Best Practices Checklist

- [x] Use Composition API with `<script setup>`
- [x] Define TypeScript interfaces for Props and Emits
- [x] Use composables for reusable logic
- [x] Use native Vue 3 features (Teleport, Suspense, Provide/Inject)
- [x] Follow component file organization (imports → props → state → computed → methods → lifecycle)
- [x] Use scoped styles
- [x] Implement proper form validation
- [x] Write component tests
- [x] Add accessibility features
- [x] Use meaningful component and prop names
- [x] Keep components small and focused
- [x] Document complex logic with comments
