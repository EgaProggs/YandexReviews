<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReviewResource;
use App\Models\Organization;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $reviews = $organization->reviews()
            ->with('media')
            ->paginate(
                perPage: $validated['page_size'] ?? 50,
                page: $validated['page'] ?? 1,
            );

        return ReviewResource::collection($reviews);
    }
}