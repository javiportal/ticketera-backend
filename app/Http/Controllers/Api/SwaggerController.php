<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use OpenApi\Generator;

class SwaggerController extends Controller
{
    public function json(): JsonResponse
    {
        $generator = new Generator();
        $openapi = $generator->generate([
            app_path(),
        ]);

        return response()->json(json_decode($openapi->toJson()));
    }

    public function ui(): Response
    {
        $specUrl = url('/api/docs/json');

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Ticketera API - Swagger UI</title>
            <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
            <style>
                html { box-sizing: border-box; overflow-y: scroll; }
                *, *:before, *:after { box-sizing: inherit; }
                body { margin: 0; background: #fafafa; }
            </style>
        </head>
        <body>
            <div id="swagger-ui"></div>
            <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
            <script>
                SwaggerUIBundle({
                    url: "{$specUrl}",
                    dom_id: '#swagger-ui',
                    deepLinking: true,
                    presets: [
                        SwaggerUIBundle.presets.apis,
                        SwaggerUIBundle.SwaggerUIStandalonePreset
                    ],
                    layout: "BaseLayout",
                    persistAuthorization: true,
                });
            </script>
        </body>
        </html>
        HTML;

        return response($html)->header('Content-Type', 'text/html');
    }
}
