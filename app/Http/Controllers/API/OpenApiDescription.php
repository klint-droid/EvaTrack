<?php

namespace App\Http\Controllers\API;

use OpenApi\Attributes as OA;

#[
    OA\Info(
        version: "1.0.0",
        title: "Evatrack API",
        description: "API Documentation for Evatrack Evacuation Management System",
    )
]
#[
    OA\Server(
        url: "http://localhost:9000/api",
        description: "Local Development Server"
    )
]
#[
    OA\SecurityScheme(
        securityScheme: "bearerAuth",
        type: "http",
        name: "Authorization",
        in: "header",
        bearerFormat: "JWT",
        scheme: "bearer"
    )
]
class OpenApiDescription
{
    // This class is only used to hold the base OpenAPI definitions
}
