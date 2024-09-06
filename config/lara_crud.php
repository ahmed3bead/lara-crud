<?php

return [
    // API version used in the generated code
    'api_version' => 'V1',

    // Enable or disable Data Transfer Objects (DTO)
    'dto_enabled' => true,

    // Enable or disable policies
    'policies_enabled' => false,

    // Enable or disable API resources
    'api_resource_enabled' => true,

    // The default model primary key type (int, uuid, ulid) --> default is uuid
    'primary_key_fields_type' => 'ulid',

    // Endpoint to create the main model
    'create-main-model-on-endpoint' => '',

    // Template names for various components
    'template-names' => [
        // Controllers
        'api-controllers' => 'api-controller.ae',
        'web-controllers' => 'web-controller.ae',

        // Services and Repositories
        'services' => 'services.ae',
        'repositories' => 'repositories.ae',

        // DTO templates
        'dto' => 'dto.ae',
        'list-dto' => 'list-dto.ae',
        'show-dto' => 'show-dto.ae',
        'index-dto' => 'index-dto.ae',
        'card-dto' => 'card-dto.ae',
        'dto-mapper' => 'dto-mapper.ae',

        // Filters and Requests
        'filters' => 'filters.ae',
        'request' => 'request.ae',

        // API Resource templates
        'list-resource' => 'list-resource.ae',
        'show-resource' => 'show-resource.ae',
        'index-resource' => 'index-resource.ae',

        // Model templates
        'models' => 'model.ae',
        'selectors' => 'selectors.ae',
        'sup-selectors' => 'sup-selectors.ae',
        'sup-model' => 'sup-model.ae',
        'policies' => 'policies.ae',
        'model-relations' => 'model-relations.ae',
        'model-filters' => 'model-filters.ae',
        'routes' => 'routes.ae',
    ],

    // Base classes for various components
    'base_controller_class' => '',
    'base_selector_class' => '',
    'base_repository_class' => '',
    'base_Service_class' => '',

    // Directory structure configuration
    'dirs' => [
        // Main container directory name
        'main-container-dir-name' => 'MyApp',

        // Supplementary container directory name
        'sup-container-dir-name' => '',

        // Separated endpoints configuration
        'separated_endpoints' => [],

        // Directory names for various components
        'dir_names' => [
            'Controllers',
            'DTOs',
            'Resources',
            'Policies',
            'Selectors',
            'Notifications',
            'Events',
            'Listeners',
            'Mappers',
            'Models',
            'Repositories',
            'Requests',
            'Services',
            'Scopes',
            'Traits',
            'Filters',
        ],
    ],

    // Postman configuration for API documentation
    'post_man' => [
        // Enable or disable Postman documentation
        // API key for Postman
        'api_key' => '',

        // Main collection ID in Postman
        'main_collection_id' => '',

        // Separate endpoints configuration
        'separate_endpoints' => true,
        'separated_endpoints_list' => [],
    ]
];
