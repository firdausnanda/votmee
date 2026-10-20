<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\MasterObject;
use App\Services\ContentImageService;
use App\Services\ObjectImageProcessor;
use App\Services\ObjectMatchingService;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

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

    public function image(Request $request, MasterObject $object): StreamedResponse
    {
        $pending = $request->boolean('pending');

        if ($pending) {
            abort_unless($object->pending_image_path && ($request->user()?->can('moderate')
                || $object->pending_image_by === $request->user()?->id), 404);
            $path = $object->pending_image_path;
        } else {
            abort_unless(in_array($object->status, ['verified', 'merged'], true) && $object->image_path, 404);
            $path = $object->image_path;
        }

        $disk = Storage::disk('local');
        $isCurrentVersion = $request->query('v') === ObjectImageProcessor::version($path);
        $variant = $request->query('size');
        if (is_string($variant) && array_key_exists($variant, ObjectImageProcessor::VARIANTS)
            && $disk->exists(ObjectImageProcessor::variantPath($path, $variant))) {
            $path = ObjectImageProcessor::variantPath($path, $variant);
        }

        abort_unless($disk->exists($path), 404);

        $response = $disk->response($path, null, ['X-Content-Type-Options' => 'nosniff']);
        if ($pending) {
            $response->headers->set('Cache-Control', 'private, no-cache');

            return $response;
        }

        $response->setPublic();
        if ($isCurrentVersion) {
            $response->setMaxAge(31536000);
            $response->setImmutable();
        } else {
            $response->setMaxAge(300);
        }
        $lastModified = $disk->lastModified($path);
        $response->setLastModified(new DateTimeImmutable('@'.$lastModified));
        $response->setEtag(sha1($path.'|'.$lastModified));
        $response->isNotModified($request);

        return $response;
    }

    public function store(Request $request, ObjectMatchingService $matching, ObjectImageProcessor $images, ContentImageService $contentImages): JsonResponse
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
            'image' => ObjectImageProcessor::rules(),
        ]);

        $matches = $matching->search($data['canonical_name'], $data['identity_attributes'] ?? [], $data['brand_id'] ?? null);

        if ($matches->contains(fn (MasterObject $object) => $object->normalized_name === $matching->normalize($data['canonical_name']) && $object->status === 'verified')) {
            throw ValidationException::withMessages(['canonical_name' => __('votmee.object_exists')]);
        }
        $imageIds = $contentImages->referencedIds($data['summary_body'], $request->user(), 'summary_body');

        $object = DB::transaction(function () use ($data, $request, $matching, $images, $contentImages, $imageIds): MasterObject {
            $object = MasterObject::create([
                'canonical_name' => $data['canonical_name'],
                'normalized_name' => $matching->normalize($data['canonical_name']),
                'slug' => Str::slug($data['canonical_name']).'-'.Str::lower((string) Str::ulid()),
                'brand_id' => $data['brand_id'] ?? null,
                'identity_attributes' => $data['identity_attributes'] ?? [],
                'submitted_by' => $request->user()->id,
                'status' => 'pending',
            ]);

            $revision = $object->contentRevisions()->create([
                'author_id' => $request->user()->id,
                'body' => $data['summary_body'],
                'body_format' => $data['summary_body_format'] ?? 'plain',
                'language_code' => $data['language_code'],
                'sources' => $data['sources'],
                'status' => 'pending',
            ]);
            $contentImages->link($revision, $imageIds);

            foreach ($data['sources'] as $source) {
                DB::table('object_references')->insert([
                    'object_id' => $object->id,
                    'submitted_by' => $request->user()->id,
                    'url' => $source,
                    'created_at' => now('UTC'),
                    'updated_at' => now('UTC'),
                ]);
            }

            if ($request->hasFile('image')) {
                $path = $images->store($request->file('image'), 'objects/'.$object->id);
                try {
                    $object->update(['pending_image_path' => $path, 'pending_image_by' => $request->user()->id]);
                } catch (Throwable $exception) {
                    $images->delete($path);
                    throw $exception;
                }
            }

            return $object;
        });

        return response()->json($object, 201);
    }

    public function resubmit(Request $request, MasterObject $object, ObjectMatchingService $matching, ObjectImageProcessor $images, ContentImageService $contentImages): JsonResponse
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
            'image' => ObjectImageProcessor::rules(),
        ]);

        $normalized = $matching->normalize($data['canonical_name']);
        $matches = $matching->search($data['canonical_name'], $object->identity_attributes ?? [], $data['brand_id'] ?? $object->brand_id);
        if ($matches->contains(fn (MasterObject $match) => $match->id !== $object->id && $match->normalized_name === $normalized && $match->status === 'verified')) {
            throw ValidationException::withMessages(['canonical_name' => __('votmee.object_exists')]);
        }

        $imageIds = $contentImages->referencedIds($data['summary_body'], $request->user(), 'summary_body');
        $previousPendingPath = $object->pending_image_path;
        DB::transaction(function () use ($object, $request, $data, $normalized, $images, $contentImages, $imageIds): void {
            $updates = [
                'canonical_name' => $data['canonical_name'],
                'normalized_name' => $normalized,
                'status' => 'pending',
            ];
            if (array_key_exists('brand_id', $data)) {
                $updates['brand_id'] = $data['brand_id'];
            }
            $object->update($updates);
            $revision = $object->contentRevisions()->create([
                'author_id' => $request->user()->id,
                'body' => $data['summary_body'],
                'body_format' => $data['summary_body_format'] ?? 'plain',
                'language_code' => $data['language_code'],
                'sources' => $data['sources'],
                'status' => 'pending',
            ]);
            $contentImages->link($revision, $imageIds);
            if ($request->hasFile('image')) {
                $path = $images->store($request->file('image'), 'objects/'.$object->id);
                try {
                    $object->update(['pending_image_path' => $path, 'pending_image_by' => $request->user()->id]);
                } catch (Throwable $exception) {
                    $images->delete($path);
                    throw $exception;
                }
            }
        });

        if ($request->hasFile('image')) {
            $images->delete($previousPendingPath);
        }

        return response()->json($object->refresh());
    }

    public function proposeImage(Request $request, MasterObject $object, ObjectImageProcessor $images): JsonResponse
    {
        abort_unless($object->status === 'verified' && $request->user()->canParticipate(), 403);
        abort_if($object->pending_image_path, 409);

        $request->validate([
            'image' => ObjectImageProcessor::rules(required: true),
        ]);

        $path = $images->store($request->file('image'), 'objects/'.$object->id);
        $updated = MasterObject::query()->whereKey($object->id)->where('status', 'verified')->whereNull('pending_image_path')
            ->update(['pending_image_path' => $path, 'pending_image_by' => $request->user()->id]);
        if ($updated === 0) {
            $images->delete($path);
            abort(409);
        }

        return response()->json(['status' => 'pending'], 201);
    }
}
