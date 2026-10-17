<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\MasterObject;
use App\Services\ObjectMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ObjectController extends Controller
{
    public function index(Request $request, ObjectMatchingService $matching): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
        ]);

        return response()->json($matching->search($data['q'], $data['category_id'] ?? null, [], $data['brand_id'] ?? null));
    }

    public function show(MasterObject $object): JsonResponse|RedirectResponse
    {
        if ($object->status === 'merged') {
            $target = MasterObject::findOrFail($object->merged_into_id);

            return redirect()->route('backend.objects.show', $target->slug);
        }

        abort_if($object->status === 'rejected', 404);

        $object->load('category', 'aliases');
        $object->setAttribute('translations', DB::table('object_translations')->where('object_id', $object->id)->get(['locale', 'name']));

        return response()->json($object);
    }

    public function store(Request $request, ObjectMatchingService $matching): JsonResponse
    {
        $data = $request->validate([
            'canonical_name' => ['required', 'string', 'min:2', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'identity_attributes' => ['nullable', 'array'],
            'identity_attributes.*' => ['string', 'max:120'],
            'reference_url' => ['nullable', 'url', 'starts_with:https://', 'max:2048'],
        ]);

        $matches = $matching->search($data['canonical_name'], $data['category_id'], $data['identity_attributes'] ?? [], $data['brand_id'] ?? null);

        if ($matches->contains(fn (MasterObject $object) => $object->normalized_name === $matching->normalize($data['canonical_name']) && $object->status === 'verified')) {
            throw ValidationException::withMessages(['canonical_name' => __('votmee.object_exists')]);
        }

        $object = DB::transaction(function () use ($data, $request, $matching): MasterObject {
            $object = MasterObject::create([
                'canonical_name' => $data['canonical_name'],
                'normalized_name' => $matching->normalize($data['canonical_name']),
                'slug' => Str::slug($data['canonical_name']).'-'.Str::lower((string) Str::ulid()),
                'category_id' => $data['category_id'],
                'brand_id' => $data['brand_id'] ?? null,
                'identity_attributes' => $data['identity_attributes'] ?? [],
                'submitted_by' => $request->user()->id,
                'status' => 'pending',
            ]);

            if (isset($data['reference_url'])) {
                DB::table('object_references')->insert([
                    'object_id' => $object->id,
                    'submitted_by' => $request->user()->id,
                    'url' => $data['reference_url'],
                    'created_at' => now('UTC'),
                    'updated_at' => now('UTC'),
                ]);
            }

            return $object;
        });

        return response()->json($object, 201);
    }
}
