# GitHub Copilot Instructions Guide

This repository is configured with comprehensive GitHub Copilot custom instructions to help you develop efficiently while maintaining code quality and architectural consistency.

## 📋 What Are Custom Instructions?

Custom instructions are markdown files that tell GitHub Copilot about your project's:
- Tech stack and frameworks
- Architectural patterns and principles
- Coding conventions and style
- Build and test commands
- Boundaries and security rules

## 📁 Instruction Files in This Repository

### 1. Repository-Wide Instructions
**File**: `.github/copilot-instructions.md`

This is the main instruction file that applies to **all files** in the repository. It covers:
- Project overview and architecture
- Tech stack (Laravel 11, Vue.js 3, PostgreSQL, Redis)
- Native implementation philosophy (NO unnecessary third-party packages)
- Build, test, and validation commands
- Architectural principles (Clean Architecture, SOLID, DDD)
- Coding guidelines and best practices
- Security rules and boundaries

**When to read**: Start here if you're new to the project.

### 2. Path-Specific Instructions

These files apply only to specific file types or directories:

| File | Applies To | Purpose |
|------|-----------|---------|
| `api-controllers.instructions.md` | `**/Modules/**/Http/Controllers/**/*.php` | API controller patterns |
| `migrations.instructions.md` | `**/Database/Migrations/**/*.php` | Database migration standards |
| `module-tests.instructions.md` | `**/Modules/**/Tests/**/*.php` | Testing patterns |
| `vue-components.instructions.md` | `**/*.vue` | Vue.js 3 component development |
| `form-requests.instructions.md` | `**/Http/Requests/**/*.php` | Form validation patterns |
| `event-driven.instructions.md` | Events, Listeners, Observers | Event-driven architecture |
| `repository-pattern.instructions.md` | `**/Repositories/**/*.php` | Repository pattern implementation |
| `service-layer.instructions.md` | `**/Services/**/*.php` | Service layer architecture |

**When to read**: Copilot automatically applies these when you work on matching files.

## 🚀 How to Use These Instructions

### For Developers

1. **No Action Required**: GitHub Copilot automatically reads these instructions when you're coding
2. **Review Patterns**: Read the relevant instruction files to understand best practices
3. **Follow Suggestions**: Copilot will suggest code that follows these patterns
4. **Validate Changes**: Always run the validation commands before committing

### For GitHub Copilot Agent

When using Copilot as an agent (e.g., via `/chat` or PR comments):

```
@copilot create a new customer repository following the repository pattern
```

Copilot will automatically:
- Read `.github/copilot-instructions.md` for general guidance
- Read `.github/instructions/repository-pattern.instructions.md` for specific patterns
- Generate code that follows native Laravel conventions
- Use proper type hints, interfaces, and dependency injection

## 🎯 Key Principles Enforced

### 1. Native Implementation First
**Always** use native Laravel/Vue features before considering third-party packages.

✅ **Good**:
```php
// Use native Laravel features
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
```

❌ **Avoid**:
```php
// Don't add unnecessary packages
use Spatie\SomethingPackage\Something;
```

### 2. Clean Architecture
- Controllers are thin (delegate to services)
- Services contain business logic
- Repositories handle data access
- Events enable cross-module communication

### 3. Type Safety
```php
declare(strict_types=1);

public function createOrder(CreateOrderRequest $request): SalesOrder
{
    // Type hints everywhere
}
```

### 4. Test Everything
- 80%+ code coverage required
- Unit tests with mocked dependencies
- Feature tests for HTTP endpoints
- Integration tests for module interactions

## 🔒 Security Boundaries

### Never Modify
- `vendor/` - Composer dependencies
- `node_modules/` - NPM dependencies
- `storage/` - Runtime storage
- `.env` - Environment config (NEVER commit)

### Modify with Care
- `composer.json` - Only add dependencies after review
- `config/*.php` - Configuration files
- `docker-compose.yml` - Infrastructure

### Security Rules
- ✅ Always validate and sanitize user input
- ✅ Always use parameterized queries
- ✅ Always check authentication/authorization
- ❌ Never hardcode credentials
- ❌ Never bypass security features
- ❌ Never commit sensitive data

## 📝 Common Copilot Commands

### In Code Comments
```php
// Create a repository for the Customer entity following the repository pattern
```

Copilot will generate code following the repository pattern instructions.

### In Chat
```
@copilot explain the service layer pattern used in this project
@copilot create a new Vue component for customer list
@copilot write tests for the CustomerService class
@copilot review this PR for architectural compliance
```

### In Pull Requests
```
@copilot review this code for security issues
@copilot suggest improvements based on our patterns
```

## 🧪 Validation Workflow

Before committing, always run:

```bash
# 1. Format code
./vendor/bin/pint

# 2. Clear caches
php artisan config:clear && php artisan cache:clear

# 3. Run tests
php artisan test

# 4. Build frontend
npm run build
```

## 📚 Learning Paths

### For New Backend Developers
1. Read `.github/copilot-instructions.md` (overview)
2. Study `repository-pattern.instructions.md`
3. Study `service-layer.instructions.md`
4. Study `api-controllers.instructions.md`
5. Review `event-driven.instructions.md`

### For New Frontend Developers
1. Read `.github/copilot-instructions.md` (overview)
2. Study `vue-components.instructions.md`
3. Review backend patterns for API integration

### For QA/Test Engineers
1. Read `.github/copilot-instructions.md` (overview)
2. Study `module-tests.instructions.md`
3. Review specific pattern files for test examples

## 🔄 Updating Instructions

### When to Update
- New architectural patterns are introduced
- New tools or frameworks are added
- Team discovers better practices
- Security requirements change

### How to Update
1. Edit the relevant instruction file
2. Add code examples
3. Update references in other instruction files
4. Test with Copilot
5. Create PR for review

### File Naming Convention
- Repository-wide: `copilot-instructions.md`
- Path-specific: `{pattern-name}.instructions.md`
- Must include YAML frontmatter with `applyTo` field

## 📖 Additional Resources

### Project Documentation
- [ARCHITECTURE.md](../ARCHITECTURE.md) - Complete architecture documentation
- [NATIVE_FEATURES.md](../NATIVE_FEATURES.md) - Native implementation guide
- [MODULE_DEVELOPMENT_GUIDE.md](../MODULE_DEVELOPMENT_GUIDE.md) - Module development
- [DOCUMENTATION_INDEX.md](../DOCUMENTATION_INDEX.md) - All documentation

### External Resources
- [GitHub Copilot Documentation](https://docs.github.com/en/copilot)
- [Custom Instructions Guide](https://docs.github.com/en/copilot/customizing-copilot/adding-custom-instructions-for-github-copilot)
- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js 3 Documentation](https://vuejs.org/)

## 💡 Tips for Best Results

### 1. Be Specific
```
❌ "create a controller"
✅ "create a RESTful API controller for Customer following the repository pattern"
```

### 2. Reference Patterns
```
❌ "add validation"
✅ "add validation using Form Request following our form-requests pattern"
```

### 3. Include Context
```
❌ "fix this bug"
✅ "fix this bug while maintaining tenant isolation and following our multi-tenancy guidelines"
```

### 4. Review Generated Code
- Always review Copilot's suggestions
- Ensure they follow the instruction patterns
- Run tests to validate behavior
- Check for security issues

## 🎯 Success Metrics

Good Copilot instructions should result in:
- ✅ Less time spent on code review corrections
- ✅ Consistent code style across the team
- ✅ Fewer architectural pattern violations
- ✅ Faster onboarding for new developers
- ✅ Reduced security vulnerabilities
- ✅ Better test coverage

## 🆘 Getting Help

### If Copilot Generates Incorrect Code
1. Review the relevant instruction file
2. Check if instructions are clear enough
3. Add more specific examples
4. Update YAML frontmatter if targeting is wrong

### If Instructions Are Unclear
1. Ask in team chat or PR comments
2. Request clarification from maintainers
3. Propose improvements via PR

### If Pattern Needs Discussion
1. Open an issue for discussion
2. Reference architectural documentation
3. Propose changes with examples
4. Get team consensus before updating

---

**Last Updated**: 2024-02-09  
**Maintained By**: Development Team  
**Questions?**: Open an issue or ask in team chat
