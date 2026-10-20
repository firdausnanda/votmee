<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ContentImage;
use App\Services\ContentImageService;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContentImageController extends Controller
{
    public function store(Request $request, ContentImageService $images): JsonResponse
    {
        abort_unless($request->user()->canParticipate(), 403);
        $request->validate(['image' => ContentImageService::rules()]);

        $image = $images->store($request->file('image'), $request->user());

        return response()->json([
            'id' => $image->id,
            'url' => $image->url(),
            'width' => $image->width,
            'height' => $image->height,
        ], 201);
    }

    /**
     * Published images are public; unpublished ones are only visible to their uploader and moderators.
     */
    public function show(Request $request, ContentImage $image): StreamedResponse
    {
        $isPublic = $image->isPublic();
        $user = $request->user();
        abort_unless($isPublic || ($user !== null && ($user->id === $image->uploader_id || $user->can('moderate'))), 404);

        $disk = Storage::disk('local');
        $path = $image->path;
        $variant = $request->query('size');
        if (is_string($variant) && array_key_exists($variant, ContentImageService::VARIANTS)
            && $disk->exists(ContentImageService::variantPath($path, $variant))) {
            $path = ContentImageService::variantPath($path, $variant);
        }

        abort_unless($disk->exists($path), 404);

        $response = $disk->response($path, null, ['X-Content-Type-Options' => 'nosniff']);
        if (! $isPublic) {
            $response->headers->set('Cache-Control', 'private, no-cache');

            return $response;
        }

        // Articles can be hidden by moderators: browsers may keep the image for a day, but shared caches
        // such as a CDN must drop it within minutes.
        $lastModified = $disk->lastModified($path);
        $response->setPublic();
        $response->setMaxAge(86400);
        $response->setSharedMaxAge(600);
        $response->setLastModified(new DateTimeImmutable('@'.$lastModified));
        $response->setEtag(sha1($path.'|'.$lastModified));
        $response->isNotModified($request);

        return $response;
    }
}
