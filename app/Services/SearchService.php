<?php

namespace App\Services;

class SearchService
{
    public function search(string $q, array $types = []): array
    {
        if (env('ALGOLIA_APP_ID') && env('ALGOLIA_API_KEY')) {
            return ['hits' => []];
        }

        return ['hits' => []];
    }
}
