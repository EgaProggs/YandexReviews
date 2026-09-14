<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Services\OrganizationImportService;
use Illuminate\Http\JsonResponse;
use App\Jobs\ParseOrganizationReviewsJob;
use App\Http\Resources\ReviewResource;

class OrganizationController extends Controller
{



    public function index(Request $request)
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return OrganizationResource::collection(
            Organization::query()
                ->latest()
                ->paginate(
                    perPage: $validated['page_size'] ?? 50,
                    page: $validated['page'] ?? 1,
                )
        );
    }

    public function store(
        StoreOrganizationRequest $request,
        OrganizationImportService $service
    ): JsonResponse {
        $organization = $service->import(
            $request->validated('yandex_url')
        );
    
        return (new OrganizationResource($organization))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Organization $organization): OrganizationResource
    {
        return new OrganizationResource($organization);
    }


    public function parse(Organization $organization): JsonResponse
    {
        if ($organization->parsing_status === 'in_progress') {
            return response()->json([
                'message' => 'Парсинг этой организации уже выполняется.',
                'organization' => new OrganizationResource($organization),
            ], 409);
        }

        ParseOrganizationReviewsJob::dispatch($organization->id);

        $organization->refresh();

        return response()->json([
            'message' => 'Парсинг поставлен в очередь.',
            'organization' => new OrganizationResource($organization),
        ], 202);
    }
}