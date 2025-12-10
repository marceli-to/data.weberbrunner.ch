<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Jury;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JuryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Jury::query();

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
        $juries = $query->orderBy('year', 'desc')->orderBy('position')->paginate($perPage);

        return response()->json($juries);
    }

    public function show(Jury $jury): JsonResponse
    {
        return response()->json($jury);
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
            $validated['position'] = Jury::where('year', $validated['year'])->max('position') + 1;
        }

        $jury = Jury::create($validated);

        return response()->json($jury, 201);
    }

    public function update(Request $request, Jury $jury): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'sometimes|integer|min:1900|max:2100',
            'title' => 'sometimes|string',
            'publish' => 'sometimes|boolean',
            'position' => 'sometimes|integer',
        ]);

        $jury->update($validated);

        return response()->json($jury);
    }

    public function destroy(Jury $jury): JsonResponse
    {
        $jury->delete();

        return response()->json(['message' => 'Jurytätigkeit gelöscht']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'juries' => 'required|array',
            'juries.*.id' => 'required|integer|exists:juries,id',
            'juries.*.position' => 'required|integer',
        ]);

        foreach ($validated['juries'] as $item) {
            Jury::where('id', $item['id'])->update(['position' => $item['position']]);
        }

        return response()->json(['message' => 'Reihenfolge aktualisiert']);
    }

    public function filterOptions(): JsonResponse
    {
        $years = Jury::distinct()
            ->pluck('year')
            ->sortDesc()
            ->values();

        return response()->json([
            'years' => $years,
        ]);
    }
}
