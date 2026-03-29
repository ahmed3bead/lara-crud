# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Run tests
composer test

# Run tests with coverage
composer test-coverage

# Static analysis
composer analyse

# Code formatting
composer format

# Automated refactoring
composer refactor

# Run a single test file
vendor/bin/pest tests/path/to/TestFile.php

# Run tests matching a name
vendor/bin/pest --filter "test name"
```

## Architecture Overview

**Lara-CRUD** is a Laravel package that generates complete CRUD scaffolding and provides a set of base classes for a layered service architecture.

### Layer Structure

Every generated module follows the same layered structure:

```
HTTP Request → Controller → Service → Repository → Model
```

- **Controller** (`BaseController` / `BaseWebController`) — Injects a service, delegates all logic to it, and returns responses.
- **Service** (`BaseService`) — Contains all business logic. Every CRUD method (`create`, `update`, `delete`, `show`, `paginate`) is wrapped with the hook system via `ServiceHookTrait`.
- **Repository** (`BaseRepository`) — Data-access abstraction built on top of [Spatie QueryBuilder](https://github.com/spatie/laravel-query-builder) for filtering, field selection, and relation inclusion.
- **Model** (`BaseModel` / `BaseUuidModel` / `BaseUlidModel`) — Standard Eloquent models; UUID/ULID variants swap the primary key type.
- **Request** (`BaseRequest`) — Validation rules.
- **Resource** — Laravel API resource for serialization.
- **DTO / DTOMapper** (`BaseDTO` / `BaseDTOMapper`) — Optional data transfer objects.

All service methods return a `BaseResponse`, which is a standardized envelope containing `data`, `errors`, `metadata`, and optional debug info.

### Hook System

The hook system is the most complex part of the codebase. It lives in `src/BaseClasses/Hooks/` and provides lifecycle callbacks for any service method.

**Core concepts:**

- **`HookContext`** — Passed to every hook. Exposes the method name, phase (`before`/`after`/`error`), raw input, request data, and the result. Use `getModelFromResult()`, `getDataFromResult()`, and `getResourceFromResult()` to safely extract results.
- **`BaseHookJob`** — Abstract base for all hooks. Override `handle(HookContext $context)`. Optionally override `shouldExecute(HookContext $context): bool` for conditional execution, and set `$priority` for ordering.
- **`HookManager`** — Central facade. Registered as a singleton. Used to register hooks against a service class/method/phase combination.
- **`HookRegistry`** — Stores hook registrations; injected into `HookManager`.
- **`ServiceHookTrait`** — Mixed into `BaseService`. Calls `executeWithHooks()` which runs before-hooks, the core method, and after/error hooks.

**Execution strategies** (how an after-hook runs):
| Class | Behavior |
|---|---|
| `SyncHookStrategy` | Immediate, blocking |
| `QueuedHookStrategy` | Dispatched to a queue |
| `DelayedHookStrategy` | Queued with a delay |
| `BatchedHookStrategy` | Accumulated into a batch |
| `ConditionalHookStrategy` | Wraps another strategy with a condition |

Before-hooks are always synchronous. After-hooks use the strategy registered with the hook.

### Code Generation

The `lara-crud:go` Artisan command launches an interactive wizard that generates the full module (controller, model, service, repository, request, resource, policy, events, notifications, etc.) from the stub templates in `src/templates/stubs/`.

The config file (`config/lara_crud.php`) controls which components are generated, the primary key type (`id`, `uuid`, `ulid`), base class namespaces, directory layout, and hook defaults.

### Key Files

| File | Purpose |
|---|---|
| `src/LaraCrudServiceProvider.php` | Package entry point; registers singletons and commands |
| `src/BaseClasses/BaseService.php` | Core CRUD methods with hook integration |
| `src/BaseClasses/Hooks/ServiceHookTrait.php` | `executeWithHooks()` implementation |
| `src/BaseClasses/Hooks/HookManager.php` | Hook registration and dispatch |
| `src/BaseClasses/Hooks/HookContext.php` | Context object passed to all hooks |
| `src/BaseClasses/BaseResponse.php` | Standardized API response wrapper |
| `src/BaseClasses/BaseRepository.php` | QueryBuilder-backed data access |
| `src/Console/Commands/CrudBlueprint.php` | Main `lara-crud:go` generation command |
| `src/templates/stubs/` | All Blade/PHP stub templates |
| `config/lara_crud.php` | Package configuration |

### Artisan Commands

| Command | Purpose |
|---|---|
| `lara-crud:go` | Interactive CRUD generator wizard |
| `lara-crud:hook {name}` | Generate a hook class |
| `lara-crud:hooks` | Manage hooks (list/stats/debug/clear/enable/disable/test/export) |
| `lara-crud:api-controller` | Generate API controller only |
| `lara-crud:model` | Generate model only |
| `lara-crud:test` | Generate unit test |
| `lara-crud:export-table` | Export table schema to JSON |

### Dependencies

- **Required:** `spatie/laravel-query-builder ^5.0\|^6.0`
- **Optional suggestions** (not autoloaded): `spatie/laravel-activitylog`, `spatie/laravel-permission`, `laravel/sanctum`, `jeroennoten/laravel-adminlte`, and others listed in `composer.json` under `suggest`.
