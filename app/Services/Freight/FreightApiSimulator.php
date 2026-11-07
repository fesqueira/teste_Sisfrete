<?php

namespace App\Services\Freight;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class FreightApiSimulator
{
    public function __invoke(Request $request): PromiseInterface
    {
        $destination = (string) $request['destination_zip_code'];

        if ($destination === '00000000' || random_int(1, 100) <= config('services.freight.simulated_failure_rate')) {
            return Http::response(['message' => 'Serviço temporariamente indisponível.'], 503);
        }

        if ($destination === '99999999') {
            return Http::response(['message' => 'CEP não atendido.'], 422);
        }

        $billableWeight = collect($request['packages'])->sum(function (array $package) {
            $cubicWeight = $package['height_cm'] * $package['width_cm'] * $package['length_cm'] / 6000;

            return max($package['weight_kg'], $cubicWeight) * $package['quantity'];
        });

        $distance = abs((int) substr($destination, 0, 2) - (int) substr((string) $request['origin_zip_code'], 0, 2));

        return Http::response([
            'service' => 'Padrão',
            'price' => round(15 + $billableWeight * 0.5 + $distance * 0.9, 2),
            'deadline_days' => 2 + intdiv($distance, 10),
        ]);
    }
}
