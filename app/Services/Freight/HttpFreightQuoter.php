<?php

namespace App\Services\Freight;

use App\Exceptions\FreightUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Throwable;

class HttpFreightQuoter implements FreightQuoter
{
    public function quote(string $destinationZipCode, array $packages): FreightQuote
    {
        try {
            $response = Http::baseUrl(config('services.freight.url'))
                ->withToken(config('services.freight.token'))
                ->acceptJson()
                ->connectTimeout(config('services.freight.connect_timeout'))
                ->timeout(config('services.freight.timeout'))
                ->retry(
                    config('services.freight.retries'),
                    fn (int $attempt) => $attempt * 200,
                    fn (Throwable $exception) => $exception instanceof ConnectionException
                        || ($exception instanceof RequestException && $exception->response->serverError()),
                )
                ->post('/quotes', [
                    'origin_zip_code' => config('services.freight.origin_zip_code'),
                    'destination_zip_code' => $destinationZipCode,
                    'packages' => $packages,
                ]);
        } catch (RequestException $exception) {
            if ($exception->response->clientError()) {
                throw ValidationException::withMessages([
                    'address_id' => 'A transportadora não atende o CEP de destino.',
                ]);
            }

            throw new FreightUnavailableException(previous: $exception);
        } catch (ConnectionException $exception) {
            throw new FreightUnavailableException(previous: $exception);
        }

        if (! is_numeric($response->json('price')) || ! is_int($response->json('deadline_days'))) {
            throw new FreightUnavailableException('Resposta inválida da transportadora.');
        }

        return new FreightQuote(
            price: round((float) $response->json('price'), 2),
            deadlineDays: $response->json('deadline_days'),
            service: (string) $response->json('service'),
        );
    }
}
