# Copilot Instructions - Quick Reference

This document provides a quick reference for all GitHub Copilot instruction files in this repository.

## 📖 Full Documentation
For complete details, see [COPILOT_INSTRUCTIONS_GUIDE.md](COPILOT_INSTRUCTIONS_GUIDE.md)

## 📋 Instruction Files

### Repository-Wide
| File | Purpose | Size |
|------|---------|------|
| [copilot-instructions.md](copilot-instructions.md) | Main instructions for entire repository | 28KB |

### Path-Specific Instructions

| File | Applies To | Purpose | Size |
|------|-----------|---------|------|
| [api-controllers.instructions.md](instructions/api-controllers.instructions.md) | `**/Modules/**/Http/Controllers/**/*.php` | API controller patterns | 9.1KB |
| [migrations.instructions.md](instructions/migrations.instructions.md) | `**/Database/Migrations/**/*.php` | Database migration standards | 8.8KB |
| [module-tests.instructions.md](instructions/module-tests.instructions.md) | `**/Modules/**/Tests/**/*.php` | Testing patterns | 5.5KB |
| [vue-components.instructions.md](instructions/vue-components.instructions.md) | `**/*.vue` | Vue.js 3 component development | 14KB |
| [form-requests.instructions.md](instructions/form-requests.instructions.md) | `**/Http/Requests/**/*.php` | Form validation patterns | 16KB |
| [event-driven.instructions.md](instructions/event-driven.instructions.md) | `**/Events/**/*.php`<br>`**/Listeners/**/*.php`<br>`**/Observers/**/*.php` | Event-driven architecture | 17KB |
| [repository-pattern.instructions.md](instructions/repository-pattern.instructions.md) | `**/Repositories/**/*.php` | Repository pattern | 16KB |
| [service-layer.instructions.md](instructions/service-layer.instructions.md) | `**/Services/**/*.php` | Service layer architecture | 19KB |

## 🎯 Key Patterns by File Type

### Backend Files

```
Working on a Controller?
→ api-controllers.instructions.md
  - Use Repository Pattern
  - Delegate to Services
  - Return API Resources

Working on a Service?
→ service-layer.instructions.md
  - Business logic only
  - Use DB transactions
  - Trigger domain events

Working on a Repository?
→ repository-pattern.instructions.md
  - Inject interfaces
  - Data access only
  - Use Eloquent properly

Working on a Form Request?
→ form-requests.instructions.md
  - Validation rules
  - Authorization logic
  - Custom messages

Working on Events/Listeners?
→ event-driven.instructions.md
  - Past tense names
  - Queue heavy operations
  - Cross-module communication

Working on a Migration?
→ migrations.instructions.md
  - UUID primary keys
  - Foreign keys with cascade
  - Add indexes
  - Rollback logic

Writing Tests?
→ module-tests.instructions.md
  - AAA pattern
  - Use factories
  - 80%+ coverage
```

### Frontend Files

```
Working on a .vue file?
→ vue-components.instructions.md
  - Composition API only
  - TypeScript interfaces
  - Native Vue 3 features
  - No component libraries
```

## 🚀 Quick Commands

```bash
# Before committing
./vendor/bin/pint              # Format code
php artisan test               # Run tests
npm run build                  # Build frontend

# Validation workflow
./vendor/bin/pint && \
php artisan config:clear && \
php artisan cache:clear && \
php artisan test && \
npm run build
```

## 🔒 Security Boundaries

### ⛔ Never Touch
- `vendor/` (Composer dependencies)
- `node_modules/` (NPM dependencies)
- `storage/` (Runtime storage)
- `.env` (NEVER commit)

### 🔐 Security Rules
- ❌ Never hardcode credentials
- ❌ Never bypass auth/authz
- ❌ Never use raw SQL concatenation
- ✅ Always validate user input
- ✅ Always use parameterized queries
- ✅ Always check permissions

## 💡 Copilot Usage Tips

### In Code
```php
// Type a comment describing what you want
// Create a repository for Customer entity following repository pattern

// Copilot will suggest code matching the pattern
```

### In Chat
```
@copilot create a Vue component for customer list
@copilot write tests for CustomerService
@copilot explain the repository pattern
```

### In PR Review
```
@copilot review for security issues
@copilot check architectural compliance
```

## 📚 Learning Paths

### New Backend Developer
1. Read `copilot-instructions.md`
2. Study `repository-pattern.instructions.md`
3. Study `service-layer.instructions.md`
4. Study `api-controllers.instructions.md`

### New Frontend Developer
1. Read `copilot-instructions.md`
2. Study `vue-components.instructions.md`

### QA Engineer
1. Read `copilot-instructions.md`
2. Study `module-tests.instructions.md`

## 📖 Additional Resources

- [COPILOT_INSTRUCTIONS_GUIDE.md](COPILOT_INSTRUCTIONS_GUIDE.md) - Complete usage guide
- [COPILOT_SETUP_COMPLETE.md](COPILOT_SETUP_COMPLETE.md) - Setup details
- [ARCHITECTURE.md](../ARCHITECTURE.md) - Architecture documentation
- [NATIVE_FEATURES.md](../NATIVE_FEATURES.md) - Native implementations

## ✨ Benefits

This setup provides:
- ✅ Automatic code generation following patterns
- ✅ Consistent code style across team
- ✅ Faster development (30-50% for common tasks)
- ✅ Better onboarding for new developers
- ✅ Reduced code review cycles
- ✅ Living documentation

---

**Last Updated**: 2024-02-09  
**Questions?**: See [COPILOT_INSTRUCTIONS_GUIDE.md](COPILOT_INSTRUCTIONS_GUIDE.md)
