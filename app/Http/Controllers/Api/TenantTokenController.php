<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use Illuminate\Http\JsonResponse;

class TenantTokenController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $result = ApiToken::generateFor(auth()->user()->tenant_id);

        return response()->json([
            'token' => $result['plaintext'],
            'message' => 'Guarde este token em lugar seguro. Ele não será exibido novamente.',
        ]);
    }
}
