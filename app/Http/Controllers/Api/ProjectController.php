<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectAttribute;
use App\Models\ProjectImage;
use App\Models\ProjectText;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function filterOptions(): JsonResponse
    {
        $statuses = Project::whereNotNull('status')
            ->where('status', '!=', '')
            ->distinct()
            ->pluck('status')
            ->sort()
            ->values();

        $years = Project::whereNotNull('year')
            ->where('year', '!=', '')
            ->distinct()
            ->pluck('year')
            ->sortDesc()
            ->values();

        return response()->json([
            'statuses' => $statuses,
            'years' => $years,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Project::with(['featuredImage', 'categories'])
            ->withCount(['texts', 'images']);

        // Filter by category
        if ($request->has('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by year
        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        // Filter by publish status
        if ($request->has('publish_status')) {
            $query->where('publish_status', $request->publish_status);
        }

        // Search by title or number
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('number', 'like', '%' . $search . '%');
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by');
        $sortDirection = $request->input('sort_direction', 'asc');
        $allowedSortColumns = ['number', 'title', 'year'];

        if ($sortBy && in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDirection === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('year', 'desc');
        }

        $perPage = $request->input('per_page', 25);
        $projects = $query->paginate($perPage);

        return response()->json($projects);
    }

    public function show(Project $project): JsonResponse
    {
        $project->load([
            'texts',
            'images',
            'featuredImage',
            'contentBlockImages',
            'data',
            'categories',
            'attributes',
        ]);

        return response()->json($project);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'number' => 'sometimes|nullable|string|max:255',
            'title' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:projects,slug,' . $project->id,
            'year' => 'sometimes|nullable|string|max:4',
            'status' => 'sometimes|nullable|string|max:255',
            'steckbrief' => 'sometimes|nullable|string',
            'publish_status' => 'sometimes|string|in:publish,draft',
            'menu_order' => 'sometimes|integer',
            'color_text_dark' => 'sometimes|nullable|string|max:7',
            'color_text_light' => 'sometimes|nullable|string|max:7',
            'categories' => 'sometimes|array',
            'categories.*' => 'integer|exists:categories,id',
        ]);

        // Handle category sync separately
        if (isset($validated['categories'])) {
            $project->categories()->sync($validated['categories']);
            unset($validated['categories']);
        }

        $project->update($validated);
        $project->load(['featuredImage', 'categories']);

        return response()->json($project);
    }

    public function destroy(Project $project): JsonResponse
    {
        $project->delete();

        return response()->json(['message' => 'Projekt gelöscht']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'projects' => 'required|array',
            'projects.*.id' => 'required|integer|exists:projects,id',
            'projects.*.menu_order' => 'required|integer',
        ]);

        foreach ($validated['projects'] as $item) {
            Project::where('id', $item['id'])->update(['menu_order' => $item['menu_order']]);
        }

        return response()->json(['message' => 'Reihenfolge aktualisiert']);
    }

    // Nested: Texts
    public function texts(Project $project): JsonResponse
    {
        return response()->json($project->texts);
    }

    public function storeText(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:text,text_large',
            'text' => 'nullable|string',
            'custom_css' => 'nullable|string',
            'position' => 'integer',
        ]);

        // Set position to end if not provided
        if (!isset($validated['position'])) {
            $validated['position'] = $project->texts()->max('position') + 1;
        }

        $text = $project->texts()->create($validated);

        return response()->json($text, 201);
    }

    public function updateText(Request $request, Project $project, ProjectText $text): JsonResponse
    {
        // Ensure text belongs to project
        if ($text->project_id !== $project->id) {
            return response()->json(['message' => 'Text gehört nicht zu diesem Projekt'], 403);
        }

        $validated = $request->validate([
            'type' => 'sometimes|string|in:text,text_large',
            'text' => 'sometimes|nullable|string',
            'custom_css' => 'sometimes|nullable|string',
            'position' => 'sometimes|integer',
        ]);

        $text->update($validated);

        return response()->json($text);
    }

    public function destroyText(Project $project, ProjectText $text): JsonResponse
    {
        // Ensure text belongs to project
        if ($text->project_id !== $project->id) {
            return response()->json(['message' => 'Text gehört nicht zu diesem Projekt'], 403);
        }

        $text->delete();

        return response()->json(['message' => 'Textblock gelöscht']);
    }

    public function reorderTexts(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'texts' => 'required|array',
            'texts.*.id' => 'required|integer|exists:project_texts,id',
            'texts.*.position' => 'required|integer',
        ]);

        foreach ($validated['texts'] as $item) {
            ProjectText::where('id', $item['id'])
                ->where('project_id', $project->id)
                ->update(['position' => $item['position']]);
        }

        return response()->json(['message' => 'Reihenfolge aktualisiert']);
    }

    // Nested: Images
    public function images(Project $project): JsonResponse
    {
        return response()->json($project->images);
    }

    public function updateImage(Request $request, Project $project, ProjectImage $image): JsonResponse
    {
        // Ensure image belongs to project
        if ($image->project_id !== $project->id) {
            return response()->json(['message' => 'Bild gehört nicht zu diesem Projekt'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|nullable|string|max:255',
            'alt' => 'sometimes|nullable|string|max:255',
            'caption' => 'sometimes|nullable|string',
            'is_featured' => 'sometimes|boolean',
            'position' => 'sometimes|integer',
        ]);

        // If setting as featured, unset other featured images
        if (isset($validated['is_featured']) && $validated['is_featured']) {
            $project->images()->where('id', '!=', $image->id)->update(['is_featured' => false]);
        }

        $image->update($validated);

        return response()->json($image);
    }

    public function destroyImage(Project $project, ProjectImage $image): JsonResponse
    {
        // Ensure image belongs to project
        if ($image->project_id !== $project->id) {
            return response()->json(['message' => 'Bild gehört nicht zu diesem Projekt'], 403);
        }

        $image->delete();

        return response()->json(['message' => 'Bild gelöscht']);
    }

    public function reorderImages(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'images' => 'required|array',
            'images.*.id' => 'required|integer|exists:project_images,id',
            'images.*.position' => 'required|integer',
        ]);

        foreach ($validated['images'] as $item) {
            ProjectImage::where('id', $item['id'])
                ->where('project_id', $project->id)
                ->update(['position' => $item['position']]);
        }

        return response()->json(['message' => 'Reihenfolge aktualisiert']);
    }

    // Nested: Attributes
    public function attributes(Project $project): JsonResponse
    {
        return response()->json($project->attributes);
    }

    public function storeAttribute(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'position' => 'integer',
        ]);

        if (!isset($validated['position'])) {
            $validated['position'] = $project->attributes()->max('position') + 1;
        }

        $attribute = $project->attributes()->create($validated);

        return response()->json($attribute, 201);
    }

    public function updateAttribute(Request $request, Project $project, ProjectAttribute $attribute): JsonResponse
    {
        if ($attribute->project_id !== $project->id) {
            return response()->json(['message' => 'Attribut gehört nicht zu diesem Projekt'], 403);
        }

        $validated = $request->validate([
            'label' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'position' => 'sometimes|integer',
        ]);

        $attribute->update($validated);

        return response()->json($attribute);
    }

    public function destroyAttribute(Project $project, ProjectAttribute $attribute): JsonResponse
    {
        if ($attribute->project_id !== $project->id) {
            return response()->json(['message' => 'Attribut gehört nicht zu diesem Projekt'], 403);
        }

        $attribute->delete();

        return response()->json(['message' => 'Attribut gelöscht']);
    }

    public function reorderAttributes(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'attributes' => 'required|array',
            'attributes.*.id' => 'required|integer|exists:project_attributes,id',
            'attributes.*.position' => 'required|integer',
        ]);

        foreach ($validated['attributes'] as $item) {
            ProjectAttribute::where('id', $item['id'])
                ->where('project_id', $project->id)
                ->update(['position' => $item['position']]);
        }

        return response()->json(['message' => 'Reihenfolge aktualisiert']);
    }
}
