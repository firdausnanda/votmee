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
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
        ]);

        return response()->json($matching->search($data['q'], [], $data['brand_id'] ?? null));
    }

    public function show(MasterObject $object): JsonResponse|RedirectResponse
    {
        if ($object->status === 'merged') {
            $target = MasterObject::findOrFail($object->merged_into_id);

            return redirect()->route('backend.objects.show', $target->slug);
        }

        abort_if($object->status === 'rejected', 404);

        $object->load('aliases');
        $object->setAttribute('translations', DB::table('object_translations')->where('object_id', $object->id)->get(['locale', 'name']));

        return response()->json($object);
    }

    public function store(Request $request, ObjectMatchingService $matching): JsonResponse
    {
        abort_unless($request->user()->canParticipate(), 403);
        $data = $request->validate([
            'canonical_name' => ['required', 'string', 'min:2', 'max:255'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'identity_attributes' => ['nullable', 'array'],
            'identity_attributes.*' => ['string', 'max:120'],
            'summary_body' => ['required', 'string', 'min:30', 'max:15000'],
            'summary_body_format' => ['sometimes', 'in:markdown'],
            'language_code' => ['required', 'in:id,en'],
            'sources' => ['required', 'array', 'min:1', 'max:10'],
            'sources.*' => ['required', 'url', 'starts_with:https://', 'max:2048'],
        ]);

        $matches = $matching->search($data['canonical_name'], $data['identity_attributes'] ?? [], $data['brand_id'] ?? null);

        if ($matches->contains(fn (MasterObject $object) => $object->normalized_name === $matching->normalize($data['canonical_name']) && $object->status === 'verified')) {
            throw ValidationException::withMessages(['canonical_name' => __('votmee.object_exists')]);
        }

        $object = DB::transaction(function () use ($data, $request, $matching): MasterObject {
            $object = MasterObject::create([
                'canonical_name' => $data['canonical_name'],
                'normalized_name' => $matching->normalize($data['canonical_name']),
                'slug' => Str::slug($data['canonical_name']).'-'.Str::lower((string) Str::ulid()),
                'brand_id' => $data['brand_id'] ?? null,
                'identity_attributes' => $data['identity_attributes'] ?? [],
                'submitted_by' => $request->user()->id,
                'status' => 'pending',
            ]);

            $object->contentRevisions()->create([
                'author_id' => $request->user()->id,
                'body' => $data['summary_body'],
                'body_format' => $data['summary_body_format'] ?? 'plain',
                'language_code' => $data['language_code'],
                'sources' => $data['sources'],
                'status' => 'pending',
            ]);

            foreach ($data['sources'] as $source) {
                DB::table('object_references')->insert([
                    'object_id' => $object->id,
                    'submitted_by' => $request->user()->id,
                    'url' => $source,
                    'created_at' => now('UTC'),
                    'updated_at' => now('UTC'),
                ]);
            }

            return $object;
        });

        return response()->json($object, 201);
    }

    public function resubmit(Request $request, MasterObject $object, ObjectMatchingService $matching): JsonResponse
    {
        abort_unless($object->submitted_by === $request->user()->id && in_array($object->status, ['needs_revision', 'rejected'], true) && $request->user()->canParticipate(), 403);
        $data = $request->validate([
            'canonical_name' => ['required', 'string', 'min:2', 'max:255'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'summary_body' => ['required', 'string', 'min:30', 'max:15000'],
            'summary_body_format' => ['sometimes', 'in:markdown'],
            'language_code' => ['required', 'in:id,en'],
            'sources' => ['required', 'array', 'min:1', 'max:10'],
            'sources.*' => ['required', 'url', 'starts_with:https://', 'max:2048'],
        ]);

        $normalized = $matching->normalize($data['canonical_name']);
        $matches = $matching->search($data['canonical_name'], $object->identity_attributes ?? [], $data['brand_id'] ?? $object->brand_id);
        if ($matches->contains(fn (MasterObject $match) => $match->id !== $object->id && $match->normalized_name === $normalized && $match->status === 'verified')) {
            throw ValidationException::withMessages(['canonical_name' => __('votmee.object_exists')]);
        }

        DB::transaction(function () use ($object, $request, $data, $normalized): void {
            $updates = [
                'canonical_name' => $data['canonical_name'],
                'normalized_name' => $normalized,
                'status' => 'pending',
            ];
            if (array_key_exists('brand_id', $data)) {
                $updates['brand_id'] = $data['brand_id'];
            }
            $object->update($updates);
            $object->contentRevisions()->create([
                'author_id' => $request->user()->id,
                'body' => $data['summary_body'],
                'body_format' => $data['summary_body_format'] ?? 'plain',
                'language_code' => $data['language_code'],
                'sources' => $data['sources'],
                'status' => 'pending',
            ]);
        });

        return response()->json($object->refresh());
    }
}
