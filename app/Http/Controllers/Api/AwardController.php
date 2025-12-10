<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Award;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AwardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Award::query();

        // Filter by year
        if ($request->has('year') && $request->year !== '') {
            $query->where('year', $request->year);
        }

        // Filter by publish status
        if ($request->has('publish')) {
            $query->where('publish', $request->publish === 'true' || $request->publish === '1');
        }

        // Search by title
        if ($request->has('search') && $request->search !== '') {
            $query->where('title', 'like', "%{$request->search}%");
        }

        $perPage = $request->input('per_page', 50);
        $awards = $query->orderBy('year', 'desc')->orderBy('position')->paginate($perPage);

        return response()->json($awards);
    }

    public function show(Award $award): JsonResponse
    {
        return response()->json($award);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:1900|max:2100',
            'title' => 'required|string',
            'publish' => 'boolean',
            'position' => 'integer',
        ]);

        // Set position to end if not provided
        if (!isset($validated['position'])) {
            $validated['position'] = Award::where('year', $validated['year'])->max('position') + 1;
        }

        $award = Award::create($validated);

        return response()->json($award, 201);
    }

    public function update(Request $request, Award $award): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'sometimes|integer|min:1900|max:2100',
            'title' => 'sometimes|string',
            'publish' => 'sometimes|boolean',
            'position' => 'sometimes|integer',
        ]);

        $award->update($validated);

        return response()->json($award);
    }

    public function destroy(Award $award): JsonResponse
    {
        $award->delete();

        return response()->json(['message' => 'Auszeichnung gelöscht']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'awards' => 'required|array',
            'awards.*.id' => 'required|integer|exists:awards,id',
            'awards.*.position' => 'required|integer',
        ]);

        foreach ($validated['awards'] as $item) {
            Award::where('id', $item['id'])->update(['position' => $item['position']]);
        }

        return response()->json(['message' => 'Reihenfolge aktualisiert']);
    }

    public function filterOptions(): JsonResponse
    {
        $years = Award::distinct()
            ->pluck('year')
            ->sortDesc()
            ->values();

        return response()->json([
            'years' => $years,
        ]);
    }
}
