<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ModerationService;
use App\Services\ObjectMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Category::query()->orderBy('name')->get());
    }

    public function brands(): JsonResponse
    {
        return response()->json(DB::table('brands')->orderBy('name')->get());
    }

    public function store(Request $request, ModerationService $moderation): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'min:2', 'max:120']]);
        $slug = Str::slug($data['name']);

        if ($slug === '' || Category::query()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['name' => __('votmee.category_exists')]);
        }

        $category = DB::transaction(function () use ($request, $moderation, $data, $slug): Category {
            $category = Category::create(['name' => $data['name'], 'slug' => $slug]);
            $moderation->audit($request->user(), 'category.create', 'category', (string) $category->id, ['name' => $category->name]);

            return $category;
        });

        return response()->json($category, 201);
    }

    public function update(Request $request, Category $category, ModerationService $moderation): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'min:2', 'max:120']]);
        DB::transaction(function () use ($request, $category, $moderation, $data): void {
            $category->update(['name' => $data['name']]);
            $moderation->audit($request->user(), 'category.update', 'category', (string) $category->id, ['name' => $category->name]);
        });

        return response()->json($category->refresh());
    }

    public function storeBrand(Request $request, ModerationService $moderation, ObjectMatchingService $matching): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'min:2', 'max:120']]);
        $normalized = $matching->normalize($data['name']);

        if (DB::table('brands')->where('normalized_name', $normalized)->exists()) {
            throw ValidationException::withMessages(['name' => __('votmee.brand_exists')]);
        }

        $id = DB::transaction(function () use ($request, $moderation, $data, $normalized): int {
            $id = DB::table('brands')->insertGetId(['name' => $data['name'], 'normalized_name' => $normalized, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
            $moderation->audit($request->user(), 'brand.create', 'brand', (string) $id, ['name' => $data['name']]);

            return $id;
        });

        return response()->json(DB::table('brands')->find($id), 201);
    }
}
