<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FreightUnavailableException extends RuntimeException
{
    public function report(): void
    {
        Log::warning('Cotação de frete indisponível', [
            'reason' => $this->getPrevious()?->getMessage() ?? $this->getMessage(),
        ]);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'Não foi possível calcular o frete agora. Tente novamente em instantes.',
        ], 503);
    }
}
