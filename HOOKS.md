# Lara-CRUD

The **Lara-CRUD** package simplifies the process of creating, reading, updating, and deleting data in a Laravel application. This package provides a set of helpful traits and methods to handle common CRUD operations efficiently, ensuring a streamlined development workflow with **enterprise-level service hooks** for event-driven architecture.

## 🚀 Key Features

- **Complete CRUD Generation**: Generates controllers, models, services, repositories, and more
- **Service Hook System**: Enterprise-level hook system for event-driven architecture
- **Multiple UI Frameworks**: Support for Bootstrap and AdminLTE
- **Standardized API Responses**: Consistent response formatting
- **Repository Pattern**: Clean data access layer
- **Service Layer**: Business logic encapsulation
- **Multiple Execution Modes**: Sync, queue, delay, and batch hook execution

## Installation

To install the package via Composer, run the following command:

```bash
composer require ahmedebead/lara-crud
```

## How to Use

### Step 1: Generate CRUD Operations

To generate CRUD operations, use the following Artisan command. The command will prompt you to enter the database table name associated with the model:

```bash
php artisan lara-crud:go
```

Upon running this command, you will be prompted to enter the database table name associated with the model.

## Step 2: Publishing Stubs and Configs

To customize the generated files, you can publish the stubs and configuration files provided by the package using the following Artisan command:

```bash
php artisan vendor:publish --provider="Ahmed3bead\LaraCrud\LaraCrudServiceProvider"
```

This will publish the configuration to `config/lara-crud.php` and stubs to `resources/stubs/vendor/lara-crud`.

## 🎯 Service Hook System

The **Service Hook System** is a powerful enterprise-level feature that transforms your Laravel application into an event-driven architecture. This system allows you to execute custom code at specific lifecycle points in your service methods, providing unprecedented flexibility and maintainability.

### Why Use the Hook System?

**🔧 Modular Architecture**: Break down complex business logic into smaller, manageable hook components that can be developed, tested, and maintained independently.

**⚡ Multiple Execution Strategies**: Choose from synchronous, queued, delayed, or batched execution modes based on your performance requirements and business needs.

**🎯 Precise Control**: Hook into exact moments of your application lifecycle - before validation, after creation, during errors, or any custom trigger point you define.

**📈 Scalable Performance**: Handle heavy operations asynchronously while keeping your main application flow responsive and fast.

**🔍 Enterprise Monitoring**: Built-in audit trails, performance tracking, and detailed execution logs help you monitor and optimize your application behavior.

**🧪 Testing Made Easy**: Isolated hook components are easier to unit test, debug, and modify without affecting core business logic.

### Real-World Hook Examples

- **User Registration**: Validate data → Create user → Send welcome email → Update analytics → Clear cache
- **Order Processing**: Check inventory → Process payment → Update stock → Notify warehouse → Generate invoice
- **Content Publishing**: Validate content → Save to database → Update search index → Notify subscribers → Generate sitemap

### Hook Execution Flow

```
Service Method Called
        ↓
   Before Hooks (Sync)     ← Validation, Authorization
        ↓
   Core Business Logic     ← Your main service method
        ↓
   After Hooks (Async)     ← Notifications, Analytics, Cache Updates
        ↓
   Response Returned
```

> **📖 Ready to implement hooks in your application? Our [Comprehensive Hook Guide](./HOOKS.md) provides:**
> - Complete step-by-step workflow examples
> - Real-world hook implementations with code
> - Advanced patterns and optimization techniques
> - Hook generation commands and management tools
> - Performance monitoring and debugging strategies
```

###  Understanding the Generated Files

After running the generate command, a new folder for the specified table (model) will be created inside the `app` directory containing all the necessary files and folders.

### Generated Folders and Files

The package will create a folder inside the `app` directory for the specified table. This folder will include all necessary subfolders and classes, organized as follows:

- **Controllers**: Handles the HTTP requests for your model.
- **DTOs**: Data Transfer Objects for data encapsulation.
- **Resources**: Formatting API responses.
- **Policies**: Authorization policies.
- **Selectors**: Methods to select specific data.
- **Notifications**: Notifications related to the model.
- **Events**: Events for the model.
- **Listeners**: Event listeners.
- **Mappers**: Data mappers.
- **Models**: Eloquent model class.
- **Repositories**: Data access layer.
- **Services**: Business logic services with hook support.
- **Scopes**: Query scopes.
- **Traits**: Reusable traits.
- **Filters**: Query filters.

## What the Package Will Do for You

The **Lara-CRUD** package automates the following tasks:

- **Controller Generation**: HTTP controllers for managing CRUD operations.
- **Data Transfer Objects (DTOs)**: Classes for data encapsulation and transfer.
- **API Resources**: Resource classes for JSON serialization.
- **Policies**: Authorization logic for the model.
- **Selectors**: Helper methods to fetch specific data.
- **Notifications**: Notification classes related to the model.
- **Events and Listeners**: Event-driven architecture support.
- **Data Mappers**: Classes for mapping data to different formats.
- **Eloquent Models**: Model class for database operations.
- **Repositories**: Repository pattern for data access logic.
- **Request Classes**: Validation logic for input data.
- **Service Classes**: Business logic encapsulation with hook system.
- **Query Scopes**: Reusable query logic.
- **Traits**: Shared functionality using traits.
- **Query Filters**: Classes for filtering query results.

## Features of the Package

### Standardized API Responses

The package provides a trait with methods to handle standardized API responses, making error handling and success responses consistent.

#### Example: Success Response

```php
$response = $this->setSuccessResponse("Operation successful.", HttpStatus::HTTP_OK);
```

## View Generation with Multiple UI Frameworks

The lara-crud package now supports generating views for your CRUD operations with different UI frameworks:

### Available UI Frameworks

- **Bootstrap**: A clean, responsive Bootstrap-based UI
- **AdminLTE**: A full-featured admin dashboard based on Bootstrap

### Generating Views

To generate views for your model, use the `--with-views` option with your preferred UI framework:

```bash
# Generate with AdminLTE
php artisan lara-crud:go --with-views=adminlte

# Generate with Bootstrap
php artisan lara-crud:go --with-views=bootstrap

# Or let the CLI prompt you to choose
php artisan lara-crud:go --with-views
```

#### Prerequisites for AdminLTE
If you choose the AdminLTE option, you'll need to have the AdminLTE package installed:
```bash
composer require jeroennoten/laravel-adminlte
php artisan adminlte:install
```
If the package is not installed, the command will offer to install it for you.

#### Customizing View Stubs
You can publish the view stubs to customize them:

```bash
# Publish AdminLTE stubs
php artisan vendor:publish --tag=lara-crud-adminlte-stubs

# Publish Bootstrap stubs
php artisan vendor:publish --tag=lara-crud-bootstrap-stubs

# Or publish all view stubs
php artisan vendor:publish --tag=lara-crud-views-stubs
```

This will publish the stubs to resources/stubs/views/ where you can modify them according to your needs.

### Error Handling

The package includes methods to handle and format error responses uniformly.

#### Example: Error Response

```php
$response = $this->setErrorResponse("An error occurred.", HttpStatus::HTTP_ERROR);
```

### Transaction Management

The package supports executing code within a database transaction and handling the response.

#### Example: Transactional Code

```php
$response = $this->tryAndResponse(function () {
    // Your transactional code here
});
```

### Paginated Responses

Automatically sets paginated responses with relevant metadata.

#### Example: Paginated Response

```php
$response = $this->setPaginateResponse($paginator);
```

### HTTP Status Codes

Provides constants for common HTTP status codes, ensuring consistency in API responses.

```php
HttpStatus::HTTP_OK // 200
HttpStatus::HTTP_ERROR // 400
// Other status codes...
```

## Complete Example

### Creating a Product with Hooks

```php
<?php

namespace App\Services;

use Ahmed3bead\LaraCrud\BaseClasses\BaseService;

class ProductService extends BaseService
{
    protected function registerServiceHooks(): void
    {
        parent::registerServiceHooks();

        // Validate product data before creation
        $this->addServiceSyncHook('before', 'create', ProductValidationHook::class);
        
        // Send inventory notification after creation
        $this->addServiceQueuedHook('after', 'create', InventoryNotificationHook::class);
        
        // Update search index after product changes
        $this->addServiceDelayedHook('after', 'update', SearchIndexUpdateHook::class, 60);
    }

    public function getResourceByType(string $type = 'index', $data = null)
    {
        return match($type) {
            'show' => new ProductResource($data),
            'list' => ProductResource::collection($data),
            default => $data
        };
    }
}
```

### API Usage Examples

#### Creating a Product

```http
POST /api/products
Content-Type: application/json

{
    "name": "Sample Product",
    "description": "This is a sample product.",
    "price": 19.99
}
```

#### Retrieving All Products

```http
GET /api/products
```

#### Retrieving a Single Product

```http
GET /api/products/{id}
```

#### Updating a Product

```http
PUT /api/products/{id}
Content-Type: application/json

{
    "name": "Updated Product",
    "description": "This is an updated product.",
    "price": 29.99
}
```

#### Deleting a Product

```http
DELETE /api/products/{id}
```

## 🎯 Hook System Benefits

- **Event-Driven Architecture**: Decouple business logic with hooks
- **Scalability**: Handle operations asynchronously with queues
- **Maintainability**: Clean separation of concerns
- **Testability**: Easy to test individual hooks
- **Flexibility**: Multiple execution strategies
- **Performance**: Batch operations and delayed execution
- **Monitoring**: Built-in audit logging and performance tracking

## 🚀 Advanced Features

### Custom Hook Strategies

Create custom execution strategies:

```php
class CustomHookStrategy implements HookExecutionStrategy
{
    public function execute(HookJobInterface $hook, HookContext $context): void
    {
        // Custom execution logic
    }
}

// Register the strategy
$hookManager->registerStrategy('custom', new CustomHookStrategy());
```

### Middleware Support

Add middleware to transform hook context:

```php
$hookManager->addMiddleware(function(HookContext $context) {
    // Transform context
    return $context;
});
```

### Hook Debugging

Enable debug mode for detailed hook execution logs:

```php
// In config/lara-crud.php
'hooks' => [
    'debug' => true,
    'log_channel' => 'hooks'
]
```

## Contributing

Contributions are welcome! Feel free to submit issues and pull requests for improvements.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

---

## 📚 Documentation

For detailed documentation and advanced usage examples, visit our [GitHub repository](https://github.com/Ahmed3bead/lara-crud).

## 🤝 Support

If you encounter any issues or have questions, please:

1. Check the documentation
2. Search existing issues
3. Create a new issue with detailed information

## 🌟 Features Coming Soon

- **Hook Templates**: Pre-built hook templates for common use cases
- **Visual Hook Designer**: GUI for designing hook workflows
- **Hook Metrics Dashboard**: Monitor hook performance and execution
- **Advanced Batching**: Smart batching with dynamic sizing
- **Hook Marketplace**: Community-driven hook sharing