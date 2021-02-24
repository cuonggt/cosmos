<?php

namespace Sinclair\Cosmos\Resources;

class KeyGenerator
{
    public function make($verb, $resourceType, $resourceLink, $date)
    {
        $string = strtolower($verb) . "\n" . strtolower($resourceType) . "\n" . $resourceLink . "\n" . strtolower($date) . "\n" . "" . "\n";

        $key = base64_decode(config('cosmos.access_token'));

        $signature = hash_hmac('sha256', $string, $key, true);

        return urlencode('type=master&ver=1.0&sig=' . base64_encode($signature));
    }
}
