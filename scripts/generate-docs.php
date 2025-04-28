// scripts/generate-docs.php
<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;

// Load the router
$router = require __DIR__ . '/../routes/web.php';

// Get all routes
$routes = $router->getRoutes();

// Generate documentation
$documentation = [
    'title' => 'ArtSpace API Documentation',
    'version' => '1.0.0',
    'description' => 'API documentation for ArtSpace social media platform',
    'endpoints' => []
];

// Process routes
foreach ($routes as $method => $methodRoutes) {
    foreach ($methodRoutes as $path => $handler) {
        // Only include API routes
        if (strpos($path, '/api/') === 0) {
            // Extract controller and method
            list($controller, $action) = explode('@', $handler);
            
            // Add namespace
            $controller = "App\\Controllers\\{$controller}";
            
            // Create reflection
            $reflection = new ReflectionMethod($controller, $action);
            
            // Get method docblock
            $docComment = $reflection->getDocComment();
            
            // Parse docblock
            $description = '';
            $parameters = [];
            $responses = [];
            
            if ($docComment) {
                // Extract description
                preg_match('/@description\s+(.+)/i', $docComment, $descMatches);
                if (isset($descMatches[1])) {
                    $description = trim($descMatches[1]);
                }
                
                // Extract parameters
                preg_match_all('/@param\s+(\S+)\s+\$(\S+)\s+(.+)/i', $docComment, $paramMatches, PREG_SET_ORDER);
                
                foreach ($paramMatches as $match) {
                    $parameters[] = [
                        'name' => $match[2],
                        'type' => $match[1],
                        'description' => trim($match[3])
                    ];
                }
                
                // Extract responses
                preg_match_all('/@response\s+(\d+)\s+(.+)/i', $docComment, $respMatches, PREG_SET_ORDER);
                
                foreach ($respMatches as $match) {
                    $responses[] = [
                        'code' => $match[1],
                        'description' => trim($match[2])
                    ];
                }
            }
            
            // Add endpoint to documentation
            $documentation['endpoints'][] = [
                'path' => $path,
                'method' => $method,
                'controller' => $controller,
                'action' => $action,
                'description' => $description,
                'parameters' => $parameters,
                'responses' => $responses
            ];
        }
    }
}

// Generate HTML documentation
$html = generateHtml($documentation);

// Save to file
file_put_contents(__DIR__ . '/../public/docs/api.html', $html);

echo "Documentation generated successfully.\n";

/**
 * Generate HTML documentation
 * 
 * @param array $documentation Documentation data
 * @return string HTML
 */
function generateHtml($documentation) {
    $endpoints = '';
    
    foreach ($documentation['endpoints'] as $endpoint) {
        $parameters = '';
        
        foreach ($endpoint['parameters'] as $param) {
            $parameters .= <<<HTML
            <tr>
                <td>${param['name']}</td>
                <td>${param['type']}</td>
                <td>${param['description']}</td>
            </tr>
            HTML;
        }
        
        $responses = '';
        
        foreach ($endpoint['responses'] as $response) {
            $responses .= <<<HTML
            <tr>
                <td>${response['code']}</td>
                <td>${response['description']}</td>
            </tr>
            HTML;
        }
        
        $endpoints .= <<<HTML
        <div class="endpoint">
            <h3><span class="method">${endpoint['method']}</span> ${endpoint['path']}</h3>
            <p>${endpoint['description']}</p>
            
            <h4>Parameters</h4>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    ${parameters}
                </tbody>
            </table>
            
            <h4>Responses</h4>
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    ${responses}
                </tbody>
            </table>
        </div>
        HTML;
    }
    
    return <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>${documentation['title']}</title>
        <style>
            body {
                font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                line-height: 1.5;
                padding: 2rem;
                color: #333;
                max-width: 1200px;
                margin: 0 auto;
            }
            h1, h2, h3, h4 {
                margin-top: 2rem;
                margin-bottom: 1rem;
            }
            .method {
                display: inline-block;
                padding: 0.25rem 0.5rem;
                border-radius: 0.25rem;
                font-weight: bold;
                margin-right: 0.5rem;
            }
            .method:contains('GET') {
                background-color: #e3f2fd;
                color: #0d47a1;
            }
            .method:contains('POST') {
                background-color: #e8f5e9;
                color: #1b5e20;
            }
            .method:contains('PUT') {
                background-color: #fff8e1;
                color: #ff6f00;
            }
            .method:contains('DELETE') {
                background-color: #ffebee;
                color: #b71c1c;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 1rem;
            }
            th, td {
                padding: 0.5rem;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }
            th {
                background-color: #f5f5f5;
            }
            .endpoint {
                margin-bottom: 2rem;
                padding-bottom: 2rem;
                border-bottom: 1px solid #ddd;
            }
        </style>
    </head>
    <body>
        <h1>${documentation['title']}</h1>
        <p>${documentation['description']}</p>
        <p><strong>Version:</strong> ${documentation['version']}</p>
        
        <h2>Endpoints</h2>
        ${endpoints}
    </body>
    </html>
    HTML;
}