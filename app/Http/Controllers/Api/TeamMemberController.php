<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TeamMemberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TeamMember::query();

        // Filter by role
        if ($request->has('role') && $request->role !== '') {
            $query->where('role', $request->role);
        }

        // Filter by publish status
        if ($request->has('publish')) {
            $query->where('publish', $request->publish === 'true' || $request->publish === '1');
        }

        // Search by name or email
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 50);
        $members = $query->orderBy('position')->orderBy('name')->paginate($perPage);

        return response()->json($members);
    }

    public function show(TeamMember $teamMember): JsonResponse
    {
        return response()->json($teamMember);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:team_members,slug',
            'title' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'since' => 'nullable|integer|min:1900|max:2100',
            'role' => 'nullable|string|max:255',
            'profile_url' => 'nullable|url|max:255',
            'image' => 'nullable|string|max:255',
            'publish' => 'boolean',
            'position' => 'integer',
        ]);

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Set position to end if not provided
        if (!isset($validated['position'])) {
            $validated['position'] = TeamMember::max('position') + 1;
        }

        $member = TeamMember::create($validated);

        return response()->json($member, 201);
    }

    public function update(Request $request, TeamMember $teamMember): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:team_members,slug,' . $teamMember->id,
            'title' => 'sometimes|nullable|string|max:255',
            'email' => 'sometimes|nullable|email|max:255',
            'since' => 'sometimes|nullable|integer|min:1900|max:2100',
            'role' => 'sometimes|nullable|string|max:255',
            'profile_url' => 'sometimes|nullable|url|max:255',
            'image' => 'sometimes|nullable|string|max:255',
            'publish' => 'sometimes|boolean',
            'position' => 'sometimes|integer',
        ]);

        $teamMember->update($validated);

        return response()->json($teamMember);
    }

    public function destroy(TeamMember $teamMember): JsonResponse
    {
        $teamMember->delete();

        return response()->json(['message' => 'Teammitglied gelöscht']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'members' => 'required|array',
            'members.*.id' => 'required|integer|exists:team_members,id',
            'members.*.position' => 'required|integer',
        ]);

        foreach ($validated['members'] as $item) {
            TeamMember::where('id', $item['id'])->update(['position' => $item['position']]);
        }

        return response()->json(['message' => 'Reihenfolge aktualisiert']);
    }

    public function filterOptions(): JsonResponse
    {
        $roles = TeamMember::whereNotNull('role')
            ->where('role', '!=', '')
            ->distinct()
            ->pluck('role')
            ->sort()
            ->values();

        return response()->json([
            'roles' => $roles,
        ]);
    }
}
