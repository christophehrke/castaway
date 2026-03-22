<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExtensionController extends Controller
{
    public function ping(Request $request): JsonResponse
    {
        $organization = $request->get('organization');

        return response()->json([
            'status' => 'ok',
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
        ]);
    }
}
