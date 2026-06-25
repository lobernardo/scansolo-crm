<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateBearerToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();

        if (! $bearerToken) {
            return response()->json(['message' => 'Token de autenticação não fornecido.'], Response::HTTP_UNAUTHORIZED);
        }

        $apiToken = ApiToken::findByPlaintext($bearerToken);

        if (! $apiToken) {
            return response()->json(['message' => 'Token inválido ou expirado.'], Response::HTTP_UNAUTHORIZED);
        }

        $owner = User::where('tenant_id', $apiToken->tenant_id)->oldest()->first();

        if (! $owner) {
            return response()->json(['message' => 'Tenant inativo.'], Response::HTTP_UNAUTHORIZED);
        }

        auth()->setUser($owner);
        $apiToken->update(['last_used_at' => now()]);
        $request->attributes->set('api_token', $apiToken);

        return $next($request);
    }
}
