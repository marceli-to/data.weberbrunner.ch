<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lecture;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LectureController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Lecture::query();

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
        $lectures = $query->orderBy('year', 'desc')->orderBy('position')->paginate($perPage);

        return response()->json($lectures);
    }

    public function show(Lecture $lecture): JsonResponse
    {
        return response()->json($lecture);
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
            $validated['position'] = Lecture::where('year', $validated['year'])->max('position') + 1;
        }

        $lecture = Lecture::create($validated);

        return response()->json($lecture, 201);
    }

    public function update(Request $request, Lecture $lecture): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'sometimes|integer|min:1900|max:2100',
            'title' => 'sometimes|string',
            'publish' => 'sometimes|boolean',
            'position' => 'sometimes|integer',
        ]);

        $lecture->update($validated);

        return response()->json($lecture);
    }

    public function destroy(Lecture $lecture): JsonResponse
    {
        $lecture->delete();

        return response()->json(['message' => 'Vortrag gelöscht']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lectures' => 'required|array',
            'lectures.*.id' => 'required|integer|exists:lectures,id',
            'lectures.*.position' => 'required|integer',
        ]);

        foreach ($validated['lectures'] as $item) {
            Lecture::where('id', $item['id'])->update(['position' => $item['position']]);
        }

        return response()->json(['message' => 'Reihenfolge aktualisiert']);
    }

    public function filterOptions(): JsonResponse
    {
        $years = Lecture::distinct()
            ->pluck('year')
            ->sortDesc()
            ->values();

        return response()->json([
            'years' => $years,
        ]);
    }
}
