<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ProfileImageProcessor;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ProfileImageController extends Controller
{
    public function show(Request $request, User $user, string $type): StreamedResponse
    {
        $path = $user->getAttribute(ProfileImageProcessor::column($type));
        abort_unless(is_string($path) && $path !== '', 404);

        $disk = Storage::disk('local');
        $isCurrentVersion = $request->query('v') === ProfileImageProcessor::version($path);
        $variant = $request->query('size');
        if (is_string($variant) && array_key_exists($variant, ProfileImageProcessor::TYPES[$type]['variants'])
            && $disk->exists(ProfileImageProcessor::variantPath($path, $variant))) {
            $path = ProfileImageProcessor::variantPath($path, $variant);
        }

        abort_unless($disk->exists($path), 404);

        $response = $disk->response($path, null, ['X-Content-Type-Options' => 'nosniff']);
        $lastModified = $disk->lastModified($path);
        $response->setPublic();
        $response->setMaxAge($isCurrentVersion ? 31536000 : 300);
        if ($isCurrentVersion) {
            $response->setImmutable();
        }
        $response->setLastModified(new DateTimeImmutable('@'.$lastModified));
        $response->setEtag(sha1($path.'|'.$lastModified));
        $response->isNotModified($request);

        return $response;
    }

    public function update(Request $request, string $type, ProfileImageProcessor $images): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->canParticipate(), 403);
        $request->validate(['image' => ProfileImageProcessor::rules()]);

        $column = ProfileImageProcessor::column($type);
        $previousPath = $user->getAttribute($column);
        $path = $images->store($request->file('image'), $type, 'users/'.$user->id);

        try {
            $user->forceFill([$column => $path])->save();
        } catch (Throwable $exception) {
            $images->delete($type, $path);
            throw $exception;
        }

        $images->delete($type, $previousPath);

        return response()->json(['url' => $user->getAttribute($type.'_url')]);
    }

    public function destroy(Request $request, string $type, ProfileImageProcessor $images): JsonResponse
    {
        $user = $request->user();
        $column = ProfileImageProcessor::column($type);
        $previousPath = $user->getAttribute($column);

        $user->forceFill([$column => null])->save();
        $images->delete($type, $previousPath);

        return response()->json(['url' => null]);
    }
}
