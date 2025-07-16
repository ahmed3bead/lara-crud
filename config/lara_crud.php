<?php

return [
    // API version used in the generated code
    'api_version' => 'V1',

    // Enable or disable Data Transfer Objects (DTO)
    'dto_enabled' => false,
    'ui_mode' => 'bootstrap',// adminlte - bootstrap

    // Enable or disable policies
    'policies_enabled' => false,

    // Enable or disable API resources
    'api_resource_enabled' => true,

    // The default model primary key type (int, uuid, ulid) --> default is uuid
    'primary_key_fields_type' => 'id',

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
        'test' => 'test.ae',

        // API Resource templates
        'list-resource' => 'list-resource.ae',
        'show-resource' => 'show-resource.ae',
        'index-resource' => 'index-resource.ae',

        // Model templates
        'model' => 'model.ae',
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
    'base_service_class' => '',
    'base_dto_class' => '',
    'base_model_class' => '',
    'base_policy_class' => '',
    'base_resource_class' => '',
    'base_filter_class' => '',
    'base_request_class' => '',
    'base_mapper_class' => '',
    'base_event_class' => '',
    'base_listener_class' => '',
    'base_notification_class' => '',
    'base_scope_class' => '',
    'base_trait_class' => '',
    'base_route_dir' => base_path('routes' . DIRECTORY_SEPARATOR),
    'base_route_file_name' => 'routes',

    // Directory structure configuration
    'dirs' => [
        // Main container directory name
        'main-container-dir-name' => 'MunjzNow',

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
    'default_service_hooks' => [
        'global' => false,
        'crud' => false,
        'performance' => false,
        'caching' => false
    ],
    'hook_context_fields' => [
        'method' => true,
        'phase' => true,
        'data' => true,
        'parameters' => true,
        'request_data' => true,
        'result_type' => true,
        'has_wrapped_response' => true,
        'status_code' => true,
        'message' => true,
        'service' => true,
        'model' => true,
        'extracted_model' => true,
        'user' => true,
        'metadata' => true
    ],
    'debug_mode' => true,
];
