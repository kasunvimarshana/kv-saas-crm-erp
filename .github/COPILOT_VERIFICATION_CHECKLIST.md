# Copilot Instructions - Verification Checklist

Use this checklist to verify that GitHub Copilot instructions are working correctly in your development environment.

## ✅ Setup Verification

### 1. File Structure Check
- [ ] `.github/copilot-instructions.md` exists (28KB)
- [ ] `.github/instructions/` directory exists with 8 instruction files
- [ ] All instruction files have YAML frontmatter with `applyTo` field
- [ ] All instruction files are readable (not corrupted)

```bash
# Run this to verify
ls -lh .github/copilot-instructions.md
ls -lh .github/instructions/*.instructions.md
```

### 2. GitHub Copilot Extension Check (VS Code)
- [ ] GitHub Copilot extension is installed
- [ ] GitHub Copilot is signed in
- [ ] GitHub Copilot is active (check status bar)
- [ ] Repository is open in VS Code

### 3. Instructions Loading Check
Open VS Code and check:
- [ ] No errors in Output → GitHub Copilot logs
- [ ] Custom instructions are detected (check Copilot settings)

## 🧪 Functionality Tests

### Test 1: Repository-Wide Instructions

**File**: Create a new PHP file anywhere  
**Test**: Type `declare(strict_types=1);`

**Expected**: Copilot should suggest:
- Proper namespace
- Type hints on methods
- Return types

✅ Pass / ❌ Fail

---

### Test 2: Controller Pattern

**File**: `Modules/Sales/Http/Controllers/TestController.php`

**Test**: Type `class TestController extends Controller`

**Expected**: Copilot should suggest constructor with:
- Repository interface injection
- Service class injection
- Type hints

```php
public function __construct(
    private TestRepositoryInterface $testRepository,
    private TestService $testService
) {}
```

✅ Pass / ❌ Fail

---

### Test 3: Service Layer Pattern

**File**: `Modules/Sales/Services/TestService.php`

**Test**: Type `public function createTest`

**Expected**: Copilot should suggest:
- Database transaction wrapper
- Repository usage
- Event dispatching
- Return type

```php
public function createTest(array $data): Test
{
    DB::beginTransaction();
    try {
        $test = $this->testRepository->create($data);
        event(new TestCreated($test));
        DB::commit();
        return $test;
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

✅ Pass / ❌ Fail

---

### Test 4: Repository Pattern

**File**: `Modules/Sales/Repositories/TestRepository.php`

**Test**: Type `class TestRepository implements TestRepositoryInterface`

**Expected**: Copilot should suggest:
- Constructor with model injection
- CRUD methods
- Type hints and return types

```php
public function __construct(
    protected Test $model
) {}

public function findById(string $id): ?Test
{
    return $this->model->find($id);
}
```

✅ Pass / ❌ Fail

---

### Test 5: Migration Pattern

**File**: `database/migrations/2024_02_09_000000_create_tests_table.php`

**Test**: Type `Schema::create('tests', function`

**Expected**: Copilot should suggest:
- UUID primary key
- tenant_id column
- Indexes on foreign keys
- Timestamps and soft deletes

```php
Schema::create('tests', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id');
    $table->string('name');
    $table->timestamps();
    $table->softDeletes();
    
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->onDelete('cascade');
});
```

✅ Pass / ❌ Fail

---

### Test 6: Vue Component Pattern

**File**: `resources/js/components/TestComponent.vue`

**Test**: Type `<script setup lang="ts">`

**Expected**: Copilot should suggest:
- Import from 'vue' (ref, computed, onMounted)
- TypeScript types
- Props interface
- Composables usage

```vue
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { Test } from '@/types'

interface Props {
  test: Test
  readonly?: boolean
}
const props = withDefaults(defineProps<Props>(), {
  readonly: false
})
```

✅ Pass / ❌ Fail

---

### Test 7: Form Request Pattern

**File**: `Modules/Sales/Http/Requests/CreateTestRequest.php`

**Test**: Type `public function rules(): array`

**Expected**: Copilot should suggest:
- Validation rules array
- Required fields
- Type validations

```php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:tests,email'],
        'status' => ['required', 'in:active,inactive'],
    ];
}
```

✅ Pass / ❌ Fail

---

### Test 8: Event Pattern

**File**: `Modules/Sales/Events/TestCreated.php`

**Test**: Type `class TestCreated`

**Expected**: Copilot should suggest:
- Past tense name
- SerializesModels trait
- Constructor with readonly property

```php
class TestCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Test $test
    ) {}
}
```

✅ Pass / ❌ Fail

---

### Test 9: Test Pattern

**File**: `Modules/Sales/Tests/Unit/TestServiceTest.php`

**Test**: Type `public function test_it_creates_test`

**Expected**: Copilot should suggest:
- AAA pattern (Arrange, Act, Assert)
- Mock objects
- Assertions

```php
public function test_it_creates_test_successfully(): void
{
    // Arrange
    $mockRepository = Mockery::mock(TestRepositoryInterface::class);
    $service = new TestService($mockRepository);
    
    // Act
    $result = $service->createTest($data);
    
    // Assert
    $this->assertInstanceOf(Test::class, $result);
}
```

✅ Pass / ❌ Fail

---

### Test 10: Security Boundaries

**File**: Any PHP file

**Test**: Type `$password = 'hardcoded';`

**Expected**: Copilot should **NOT** suggest hardcoded credentials

**Test**: Type `DB::select("SELECT * FROM users WHERE id = " . $id)`

**Expected**: Copilot should suggest parameterized query instead

✅ Pass / ❌ Fail

---

## 📊 Results Summary

**Total Tests**: 10  
**Passed**: ___  
**Failed**: ___  
**Success Rate**: ____%

## 🔍 Troubleshooting

### If Tests Fail

1. **Check File Paths**: Ensure test files match the `applyTo` patterns
2. **Reload VS Code**: Sometimes instructions need a reload
3. **Check Logs**: Output → GitHub Copilot for error messages
4. **Update Extension**: Ensure GitHub Copilot is latest version
5. **Clear Cache**: Try closing and reopening VS Code

### Common Issues

#### Instructions Not Loading
- Verify `.github/copilot-instructions.md` is at repository root
- Check YAML frontmatter syntax (no tabs, proper indentation)
- Restart VS Code

#### Wrong Suggestions
- Check if file path matches `applyTo` pattern
- Verify YAML frontmatter syntax
- Multiple patterns might conflict - be specific

#### No Suggestions
- Check Copilot is active (status bar)
- Check you're signed in
- Try typing more context
- Check network connection

## 📞 Getting Help

### Documentation
- [COPILOT_INSTRUCTIONS_GUIDE.md](COPILOT_INSTRUCTIONS_GUIDE.md) - Usage guide
- [COPILOT_QUICK_REFERENCE.md](COPILOT_QUICK_REFERENCE.md) - Quick reference
- [COPILOT_SETUP_COMPLETE.md](COPILOT_SETUP_COMPLETE.md) - Setup details

### GitHub Resources
- [GitHub Copilot Docs](https://docs.github.com/en/copilot)
- [Custom Instructions](https://docs.github.com/en/copilot/customizing-copilot/adding-custom-instructions-for-github-copilot)
- [Troubleshooting](https://docs.github.com/en/copilot/troubleshooting-github-copilot)

### Team Support
- Ask in team chat
- Open an issue
- Request pair programming session

## ✅ Verification Complete

Once all tests pass, you can be confident that:
- GitHub Copilot is properly configured
- Custom instructions are being applied
- Code suggestions follow project patterns
- Security boundaries are enforced

Date Verified: ________________  
Verified By: ________________  
VS Code Version: ________________  
Copilot Version: ________________

---

**Last Updated**: 2024-02-09  
**Questions?**: See [COPILOT_INSTRUCTIONS_GUIDE.md](COPILOT_INSTRUCTIONS_GUIDE.md)
