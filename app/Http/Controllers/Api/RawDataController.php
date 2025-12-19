<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RawData;
use App\Models\RawDataAttribute;
use App\Models\RawDataMeta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RawDataController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = RawData::query();

        // Search by number or title
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('number', 'like', '%' . $search . '%');
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'number');
        $sortDirection = $request->input('sort_direction', 'asc');
        $allowedSortColumns = ['number', 'title'];

        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDirection === 'desc' ? 'desc' : 'asc');
        }

        $perPage = $request->input('per_page', 100);
        $rawData = $query->paginate($perPage);

        return response()->json($rawData);
    }

    public function show(RawData $rawData): JsonResponse
    {
        $rawData->load(['meta', 'attributes']);

        return response()->json($rawData);
    }

    public function update(Request $request, RawData $rawData): JsonResponse
    {
        $validated = $request->validate([
            'number' => 'sometimes|string|max:255',
            'title' => 'sometimes|string|max:255',
        ]);

        $rawData->update($validated);

        return response()->json($rawData);
    }

    public function destroy(RawData $rawData): JsonResponse
    {
        $rawData->delete();

        return response()->json(['message' => 'Rohdaten gelöscht']);
    }

    // Meta
    public function storeMeta(Request $request, RawData $rawData): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'value' => 'nullable|string',
            'position' => 'integer',
        ]);

        if (!isset($validated['position'])) {
            $validated['position'] = $rawData->meta()->max('position') + 1;
        }

        $meta = $rawData->meta()->create($validated);

        return response()->json($meta, 201);
    }

    public function updateMeta(Request $request, RawData $rawData, RawDataMeta $meta): JsonResponse
    {
        if ($meta->raw_data_id !== $rawData->id) {
            return response()->json(['message' => 'Meta gehört nicht zu diesen Rohdaten'], 403);
        }

        $validated = $request->validate([
            'label' => 'sometimes|string|max:255',
            'value' => 'sometimes|nullable|string',
            'position' => 'sometimes|integer',
        ]);

        $meta->update($validated);

        return response()->json($meta);
    }

    public function destroyMeta(RawData $rawData, RawDataMeta $meta): JsonResponse
    {
        if ($meta->raw_data_id !== $rawData->id) {
            return response()->json(['message' => 'Meta gehört nicht zu diesen Rohdaten'], 403);
        }

        $meta->delete();

        return response()->json(['message' => 'Meta gelöscht']);
    }

    public function reorderMeta(Request $request, RawData $rawData): JsonResponse
    {
        $validated = $request->validate([
            'meta' => 'required|array',
            'meta.*.id' => 'required|integer|exists:raw_data_meta,id',
            'meta.*.position' => 'required|integer',
        ]);

        foreach ($validated['meta'] as $item) {
            RawDataMeta::where('id', $item['id'])
                ->where('raw_data_id', $rawData->id)
                ->update(['position' => $item['position']]);
        }

        return response()->json(['message' => 'Reihenfolge aktualisiert']);
    }

    // Attributes
    public function storeAttribute(Request $request, RawData $rawData): JsonResponse
    {
        $validated = $request->validate([
            'group_key' => 'nullable|string|max:255',
            'label' => 'required|string|max:255',
            'value' => 'nullable|string',
            'position' => 'integer',
        ]);

        if (!isset($validated['position'])) {
            $validated['position'] = $rawData->attributes()->max('position') + 1;
        }

        $attribute = $rawData->attributes()->create($validated);

        return response()->json($attribute, 201);
    }

    public function updateAttribute(Request $request, RawData $rawData, RawDataAttribute $attribute): JsonResponse
    {
        if ($attribute->raw_data_id !== $rawData->id) {
            return response()->json(['message' => 'Attribut gehört nicht zu diesen Rohdaten'], 403);
        }

        $validated = $request->validate([
            'group_key' => 'sometimes|nullable|string|max:255',
            'label' => 'sometimes|string|max:255',
            'value' => 'sometimes|nullable|string',
            'position' => 'sometimes|integer',
        ]);

        $attribute->update($validated);

        return response()->json($attribute);
    }

    public function destroyAttribute(RawData $rawData, RawDataAttribute $attribute): JsonResponse
    {
        if ($attribute->raw_data_id !== $rawData->id) {
            return response()->json(['message' => 'Attribut gehört nicht zu diesen Rohdaten'], 403);
        }

        $attribute->delete();

        return response()->json(['message' => 'Attribut gelöscht']);
    }

    public function reorderAttributes(Request $request, RawData $rawData): JsonResponse
    {
        $validated = $request->validate([
            'attributes' => 'required|array',
            'attributes.*.id' => 'required|integer|exists:raw_data_attributes,id',
            'attributes.*.position' => 'required|integer',
        ]);

        foreach ($validated['attributes'] as $item) {
            RawDataAttribute::where('id', $item['id'])
                ->where('raw_data_id', $rawData->id)
                ->update(['position' => $item['position']]);
        }

        return response()->json(['message' => 'Reihenfolge aktualisiert']);
    }
}
