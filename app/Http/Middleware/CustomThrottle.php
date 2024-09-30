<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests as Throttle;

class CustomThrottle extends Throttle
{
    protected function buildResponse($key, $maxAttempts)
    {
        return response()->json([
            'success' => false,
            'message' => 'Bạn đã vượt quá giới hạn yêu cầu. Vui lòng thử lại sau.',
            'data' => [
                'error' => 'Too Many Requests'
            ]
        ], 429);
    }
}