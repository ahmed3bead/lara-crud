# Lara-CRUD

A Laravel package that generates complete CRUD scaffolding and provides a layered service architecture out of the box.

**Requires:** PHP 8.2+, Laravel 10–13, `spatie/laravel-query-builder` ^5|^6|^7

```bash
composer require ahmedebead/lara-crud
```

---

## Table of Contents

1. [How it works](#how-it-works)
2. [Generate a module](#generate-a-module)
3. [What gets generated](#what-gets-generated)
4. [Layer by layer](#layer-by-layer)
   - [Model](#model)
   - [Repository](#repository)
   - [Service — Resource mode (default)](#service--resource-mode-default)
   - [Service — Mapper mode](#service--mapper-mode)
   - [Controller](#controller)
   - [Request](#request)
   - [Response envelope](#response-envelope)
5. [Hook system](#hook-system)
6. [Configuration reference](#configuration-reference)
7. [Artisan commands](#artisan-commands)
8. [Contributing / License](#contributing--license)

---

## How it works

Every generated module follows the same layered flow:

```
HTTP Request → Controller → Service → Repository → Model
```

| Layer | Responsibility |
|---|---|
| Controller | Resolves request class, delegates to service, returns JSON |
| Service | Business logic, wraps results in `BaseResponse` |
| Repository | Data access via Spatie QueryBuilder |
| Model | Eloquent model with allowed filters/includes/sorts |

---

## Generate a module

```bash
php artisan lara-crud:go
```

The wizard asks for the table name and generates everything. Publish the config and stubs if you want to customise the defaults:

```bash
php artisan vendor:publish --provider="Ahmed3bead\LaraCrud\LaraCrudServiceProvider"
```

Config lands at `config/lara_crud.php`. Stubs land at `resources/stubs/vendor/lara-crud/`.

---

## What gets generated

```
app/{MainContainer}/{ModuleName}/
├── Controllers/       ProductsController.php
├── Models/            Product.php
├── Repositories/      ProductsRepository.php
├── Services/          ProductsService.php
├── Requests/          CreateProductRequest.php  UpdateProductRequest.php  ...
├── Resources/         ProductShowResource.php   ProductListResource.php
├── DTOs/              ProductDTO.php
├── Mappers/           ProductDTOMapper.php
├── Filters/           ProductFilter.php
├── Policies/          ProductPolicy.php
├── Events/            ProductCreated.php  ...
├── Notifications/
├── Scopes/
└── Traits/
```

---

## Layer by layer

### Model

`BaseModel` (integer PK) · `BaseUuidModel` · `BaseUlidModel`

Override these static methods to control filtering and sorting:

```php
class Product extends BaseModel
{
    protected $fillable = ['name', 'price', 'category_id'];

    public static function getAllowedFilters(): array
    {
        return ['name', 'category_id', AllowedFilter::scope('active')];
    }

    public static function getAllowedIncludes(): array
    {
        return ['category', 'tags'];
    }

    // Sort direction is passed in by the repository — no request() calls here
    public static function getDefaultSort(bool $sortAsc = false): string
    {
        return $sortAsc ? 'created_at' : '-created_at';
    }
}
```

---

### Repository

`BaseRepository` provides all common data access methods. Inject it; do not call it directly from controllers.

```php
// Available out of the box:
$repo->paginate($requestQuery, $perPage);
$repo->all();
$repo->find($id);                          // findOrFail
$repo->create(array $data);
$repo->update($model, array $data);
$repo->delete($model);
$repo->count(array $filters = []);
$repo->exists(array $filters);
$repo->findMany(array $ids);
$repo->createMany(array $records);         // bulk insert
$repo->findWhere(array $conditions);
$repo->firstWhere(array $conditions);
$repo->minimalListWithFilter(with: [], where: [], limit: 250);
```

Extend it to add module-specific queries:

```php
class ProductsRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Product, new ProductSelector);
    }

    public function findByCategory(int $categoryId): Collection
    {
        return $this->getModel()->where('category_id', $categoryId)->get();
    }
}
```

---

### Service — Resource mode (default)

The generated service uses Laravel API Resources by default. `ShowResource` wraps single items; `ListResource` wraps collections.

```php
class ProductsService extends BaseService
{
    protected string $resourceClass     = ProductShowResource::class;
    protected string $listResourceClass = ProductListResource::class;

    public function __construct(ProductsRepository $repository)
    {
        parent::__construct($repository);
    }
}
```

`BaseService` provides these methods automatically:

| Method | HTTP verb | Wraps with |
|---|---|---|
| `paginate($request)` | GET /index | `$listResourceClass::collection()` |
| `all()` | GET ?getAllRecords | `$listResourceClass::collection()` |
| `show($id)` | GET /show | `new $resourceClass()` |
| `create($data)` | POST | `new $resourceClass()` |
| `update($data, $id)` | PUT/PATCH | `new $resourceClass()` |
| `delete($id)` | DELETE | 204 message |

---

### Service — Mapper mode

Replace the resource properties with a DTOMapper if you prefer manual transformation:

```php
class ProductsService extends BaseService
{
    public function __construct(
        ProductsRepository $repository,
        ProductDTOMapper   $mapper
    ) {
        parent::__construct($repository, $mapper);
    }
}
```

The mapper must extend `BaseDTOMapper` and implement `fromModel()`. The base class handles `fromCollection()`, `fromArray()`, and `fromPaginator()` automatically.

---

### Service — custom transformation

Override `getResourceByType()` for full control:

```php
public function getResourceByType(string $type, $data = null): mixed
{
    return match($type) {
        'list'  => ProductListResource::collection($data),
        default => new ProductShowResource($data),
    };
}
```

---

### Controller

`BaseController` resolves the request class from `$requestMap`, validates it, and calls the service. You never write try-catch boilerplate — Laravel's exception handler takes care of it.

```php
class ProductsController extends BaseController
{
    protected array $requestMap = [
        'index'  => IndexProductRequest::class,
        'create' => CreateProductRequest::class,
        'update' => UpdateProductRequest::class,
        'delete' => DeleteProductRequest::class,
        'show'   => ShowProductRequest::class,
    ];

    public function __construct(ProductsService $service)
    {
        parent::__construct($service);
    }
}
```

If a key is missing from `$requestMap` a descriptive `RuntimeException` is thrown immediately.

---

### Request

`BaseRequest` defaults to `authorize(): bool { return true; }`. Override it per request class to enforce policies:

```php
class CreateProductRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }

    public function rules(): array
    {
        return [
            'name'  => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ];
    }
}
```

---

### Response envelope

Every service method returns a `BaseResponse`. The controller's `tryAndResponse()` converts it to JSON.

```json
{
    "status_code": 200,
    "data": { ... },
    "extra_data": {},
    "meta": {
        "currentPage": 1,
        "lastPage": 5,
        "perPage": 20,
        "total": 98
    },
    "errors": {},
    "message": "",
    "source": "OPs"
}
```

The `debug` block (query log) is **never included** unless you opt in via config — it will never leak to production responses:

```php
// config/lara_crud.php
'expose_debug_in_response' => env('LARA_CRUD_EXPOSE_DEBUG', false),
```

---

## Hook system

Hooks let you attach code to any service method lifecycle without touching the method itself.

### Execution flow

```
Before hooks (sync)   → validation, authorization, rate-limit checks
       ↓
Core service method   → the actual create/update/delete/show/paginate
       ↓
After hooks           → notifications, audit log, cache invalidation
       ↓
Error hooks           → alerting, rollback side-effects
```

### Creating a hook

```bash
php artisan lara-crud:hook ProductAuditHook
```

```php
class ProductAuditHook extends BaseHookJob
{
    public function handle(HookContext $context): void
    {
        $model = $context->getModelFromResult();

        ActivityLog::record(
            user:    $context->user,
            action:  $context->method,
            subject: $model,
        );
    }

    public function shouldExecute(HookContext $context): bool
    {
        return $context->isAfter() && $context->isSuccessful();
    }
}
```

### Registering hooks in a service

Override `registerHooks()` in your service:

```php
class ProductsService extends BaseService
{
    protected string $resourceClass     = ProductShowResource::class;
    protected string $listResourceClass = ProductListResource::class;

    public function __construct(ProductsRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function registerHooks(): void
    {
        parent::registerHooks();

        // Runs synchronously before every create
        $this->addServiceSyncHook('before', 'create', ValidateSkuHook::class);

        // Queued after create/update/delete — does not slow down the response
        $this->addServiceQueuedHook('after', 'create', SendProductCreatedEmailHook::class);
        $this->addServiceQueuedHook('after', 'update', InvalidateProductCacheHook::class);
        $this->addServiceQueuedHook('after', 'delete', InvalidateProductCacheHook::class);
    }
}
```

### Hook execution strategies

| Method | Behavior |
|---|---|
| `addServiceSyncHook` | Immediate, blocks the request |
| `addServiceQueuedHook` | Dispatched to a queue |
| `addServiceDelayedHook` | Queued with a delay (seconds) |
| `addServiceBatchedHook` | Accumulated into a batch job |

### Extension points in BaseService

Override these empty methods instead of registering hooks manually when you want category-level grouping:

```php
// Runs when hooks.default_service_hooks.global = true
protected function registerGlobalServiceHooks(): void
{
    $this->addServiceSyncHook('before', 'create', AuthorizationHook::class);
}

// Runs when hooks.default_service_hooks.crud = true
protected function registerCrudHooks(): void
{
    $this->addServiceQueuedHook('after', 'create', NotifyAdminHook::class);
}

// Runs when hooks.default_service_hooks.performance = true
protected function registerPerformanceHooks(): void {}

// Runs when hooks.default_service_hooks.caching = true
protected function registerCachingHooks(): void {}
```

### HookContext reference

`HookContext` is passed to every hook's `handle()` method:

```php
$context->method            // 'create', 'update', 'delete', 'show', 'paginate', ...
$context->phase             // 'before' | 'after' | 'error'
$context->data              // raw input passed to the service method
$context->parameters        // extra parameters array
$context->result            // the BaseResponse (after phase only)
$context->service           // the service instance
$context->user              // Auth::user()

// Helpers
$context->isBefore()
$context->isAfter()
$context->isSuccessful()    // status 2xx
$context->getModelFromResult()      // extracts Eloquent model from result
$context->getDataFromResult()       // extracts data payload from result
$context->getStatusCode()
$context->getMessage()
$context->getModelAttributes()
$context->getModelChanges()
$context->wasModelRecentlyCreated()
```

### Hook management commands

```bash
php artisan lara-crud:hooks list          # list all registered hooks
php artisan lara-crud:hooks stats         # execution statistics
php artisan lara-crud:hooks debug         # debug a specific service
php artisan lara-crud:hooks enable        # enable hooks globally
php artisan lara-crud:hooks disable       # disable hooks globally
php artisan lara-crud:hooks clear         # remove all hooks
php artisan lara-crud:hooks test          # test-fire a hook
php artisan lara-crud:hooks export        # export hook config to JSON
```

---

## Configuration reference

`config/lara_crud.php`

```php
'api_version'              => 'V1',
'dto_enabled'              => false,
'api_resource_enabled'     => true,
'primary_key_fields_type'  => 'id',   // 'id' | 'uuid' | 'ulid'
'ui_mode'                  => 'bootstrap', // 'bootstrap' | 'adminlte'
'policies_enabled'         => false,

// Set to true only in local/staging — query logs will appear in every API response
'expose_debug_in_response' => env('LARA_CRUD_EXPOSE_DEBUG', false),

'hooks' => [
    'enabled'          => env('LARA_CRUD_HOOKS_ENABLED', true),
    'debug'            => env('LARA_CRUD_HOOKS_DEBUG', false),
    'queue_connection' => env('LARA_CRUD_QUEUE_CONNECTION', 'default'),
    'batch_queue'      => env('LARA_CRUD_BATCH_QUEUE', 'batch'),

    'default_service_hooks' => [
        'global'      => true,   // registerGlobalServiceHooks()
        'crud'        => true,   // registerCrudHooks()
        'performance' => false,  // registerPerformanceHooks()
        'caching'     => false,  // registerCachingHooks()
    ],
],

'dirs' => [
    'main-container-dir-name' => 'YourApp',
    // ...
],
```

---

## Artisan commands

| Command | Purpose |
|---|---|
| `lara-crud:go` | Interactive CRUD generator wizard |
| `lara-crud:hook {name}` | Generate a hook class |
| `lara-crud:hooks` | Manage hooks (list/stats/debug/clear/enable/disable/test/export) |
| `lara-crud:api-controller` | Generate API controller only |
| `lara-crud:model` | Generate model only |
| `lara-crud:test` | Generate unit test |
| `lara-crud:export-table` | Export table schema to JSON |

---

## Contributing / License

Contributions are welcome. Please open an issue or pull request on [GitHub](https://github.com/ahmedebead/lara-crud).

This package is open-sourced software licensed under the [MIT license](LICENSE).