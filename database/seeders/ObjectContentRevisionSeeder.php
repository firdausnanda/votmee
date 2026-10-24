<?php

namespace Database\Seeders;

use App\Models\MasterObject;
use App\Models\ObjectArticle;
use App\Models\ObjectContentRevision;
use App\Models\User;
use Carbon\CarbonInterface;
use Database\Seeders\Content\ObjectContent;
use Illuminate\Database\Seeder;

class ObjectContentRevisionSeeder extends Seeder
{
    /**
     * Write the community summaries of the demo objects with their revision history: improvements
     * that were published, drafts a moderator sent back or rejected, and one still in the queue.
     */
    public function run(): void
    {
        $users = User::pluck('id', 'username');
        $reviewerId = self::reviewerId();

        foreach (ObjectContent::summaries() as $summary) {
            $object = MasterObject::where('canonical_name', $summary['object'])->first();

            if ($object === null || $object->contentRevisions()->whereNull('article_id')->exists()) {
                continue;
            }

            foreach ($summary['revisions'] as $data) {
                if (isset($users[$data['by']])) {
                    self::record($object, null, $users[$data['by']], $reviewerId, $data);
                }
            }
        }
    }

    public static function reviewerId(): ?int
    {
        return User::where('role', 'moderator')->value('id') ?? User::where('role', 'admin')->value('id');
    }

    /**
     * Save one revision written `days_ago`, never before the object was verified, and reviewed
     * a few hours later unless it is still pending.
     *
     * @param  array<string, mixed>  $data
     */
    public static function record(MasterObject $object, ?ObjectArticle $article, int $authorId, ?int $reviewerId, array $data): ObjectContentRevision
    {
        $notBefore = ($object->verified_at ?? $object->created_at)->copy()->addHour();
        $writtenAt = self::past(now('UTC')->subDays($data['days_ago'])->subMinutes(crc32($data['body']) % 480));
        $writtenAt = $writtenAt->lessThan($notBefore) ? $notBefore : $writtenAt;
        $reviewedAt = $data['status'] === 'pending' ? null : self::past($writtenAt->copy()->addMinutes(mt_rand(60, 600)));

        $revision = new ObjectContentRevision;
        $revision->forceFill([
            'object_id' => $object->id,
            'article_id' => $article?->id,
            'author_id' => $authorId,
            'reviewed_by' => $reviewedAt ? $reviewerId : null,
            'title' => $data['title'] ?? null,
            'body' => $data['body'],
            'body_format' => $data['format'] ?? ($article ? 'markdown' : 'plain'),
            'sources' => $data['sources'],
            'language_code' => $data['language'],
            'status' => $data['status'],
            'review_reason' => $reviewedAt ? ($data['reason'] ?? 'Isi sesuai dengan sumber, ditulis netral, dan tidak mengandung promosi.') : null,
            'published_at' => $data['status'] === 'published' ? $reviewedAt : null,
            'created_at' => $writtenAt,
            'updated_at' => $reviewedAt ?? $writtenAt,
        ])->save();

        return $revision;
    }

    private static function past(CarbonInterface $at): CarbonInterface
    {
        return $at->isFuture() ? now('UTC')->subMinutes(mt_rand(5, 90)) : $at;
    }
}
