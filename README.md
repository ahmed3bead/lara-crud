# Lara-CRUD

The **Lara-CRUD** package simplifies the process of creating, reading, updating, and deleting data in a Laravel application. This package provides a set of helpful traits and methods to handle common CRUD operations efficiently, ensuring a streamlined development workflow.

## Installation

To install the package via Composer, run the following command:

```bash
composer require ahmedebead/lara-crud
```

## How to Use

### Step 1: Generate CRUD Operations

To generate CRUD operations, use the following Artisan command. The command will prompt you to enter the database table name associated with the model:

```bash
php artisan crud:go
```

Upon running this command, you will be prompted to enter the database table name associated with the model.

## Step 2: Publishing Stubs and Configs

To customize the generated files, you can publish the stubs and configuration files provided by the package using the following Artisan command:

```bash
php artisan vendor:publish --provider="Ahmed3bead\LaraCrud\LaraCrudServiceProvider"
```

This will publish the configuration to `config/lara-crud.php` and stubs to `resources/stubs/vendor/lara-crud`.

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
- **Requests**: Request validation classes.
- **Services**: Business logic services.
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
- **Service Classes**: Business logic encapsulation.
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

### Creating a Product

```http
POST /api/products
Content-Type: application/json

{
    "name": "Sample Product",
    "description": "This is a sample product.",
    "price": 19.99
}
```

### Retrieving All Products

```http
GET /api/products
```

### Retrieving a Single Product

```http
GET /api/products/{id}
```

### Updating a Product

```http
PUT /api/products/{id}
Content-Type: application/json

{
    "name": "Updated Product",
    "description": "This is an updated product.",
    "price": 29.99
}
```

### Deleting a Product

```http
DELETE /api/products/{id}
```

## Contributing

Contributions are welcome! Feel free to submit issues and pull requests for improvements.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
