<?php

namespace App\Services\Freight;

interface FreightQuoter
{
    public function quote(string $destinationZipCode, array $packages): FreightQuote;
}
