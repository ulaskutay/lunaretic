<?php

namespace App\Etic\Storefront\Http\Controllers;

use App\Etic\Search\CatalogProductSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSuggestionsController
{
    public function __invoke(Request $request, CatalogProductSearch $search): JsonResponse
    {
        $term = trim($request->string('q')->toString());

        return response()->json([
            'data' => $search->suggestions($term),
        ]);
    }
}
