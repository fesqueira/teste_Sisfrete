<?php

namespace App\Services\Freight;

readonly class FreightQuote
{
    public function __construct(
        public float $price,
        public int $deadlineDays,
        public string $service,
    ) {}
}
