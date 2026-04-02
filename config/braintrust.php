<?php

return [
    'api_key' => env('BRAINTRUST_API_KEY'),
    
    'base_url' => env('BRAINTRUST_BASE_URL', 'https://api.braintrust.dev'),
    
    // Project ID (not name) for creating experiments
    // Leave empty or null to use the default project
    // You can find your project ID in the Braintrust UI URL: 
    // https://www.braintrust.dev/app/{org}/project/{project-id}
    'project' => env('BRAINTRUST_DEFAULT_PROJECT_ID'),
    
    'timeout' => env('BRAINTRUST_TIMEOUT', 30),
    
    'thresholds' => [
        'exact_match' => 0.8,
    ],
];
