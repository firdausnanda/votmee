<?php

use App\Models\Comment;
use App\Models\CommentMention;
use App\Models\MasterObject;
use App\Models\ObjectArticle;
use App\Models\ObjectContentRevision;
use App\Models\RankingPost;
use App\Models\User;
use App\Models\UserBlock;

function mentionNotifications(User $user): array
{
    return $user->notifications()->get()->filter(fn ($notification): bool => $notification->data['type'] === 'comment_mention')->values()->all();
}

function publishedArticleForMentions(): ObjectArticle
{
    $object = MasterObject::factory()->create();
    $article = ObjectArticle::factory()->create(['object_id' => $object->id]);
    $revision = ObjectContentRevision::factory()->create([
        'object_id' => $object->id, 'article_id' => $article->id, 'author_id' => $article->author_id,
        'title' => 'Mention story', 'status' => 'published', 'published_at' => now(),
    ]);
    $article->update(['published_revision_id' => $revision->id]);

    return $article;
}

test('mentioning a member links them and notifies them on every kind of comment', function (string $target) {
    $friend = User::factory()->create(['username' => 'teman-baik']);
    [$storeUrl, $listUrl, $url] = match ($target) {
        'ranking' => (function () {
            $post = RankingPost::factory()->create();

            return [route('backend.comments.store', $post), route('backend.comments.index', $post), "/ranking/{$post->slug}"];
        })(),
        'object' => (function () {
            $object = MasterObject::factory()->create();

            return [route('backend.objects.comments.store', $object->slug), route('backend.objects.comments.index', $object->slug), "/object/{$object->slug}"];
        })(),
        'article' => (function () {
            $article = publishedArticleForMentions();

            return [route('backend.articles.comments.store', $article), route('backend.articles.comments.index', $article), "/article/{$article->id}"];
        })(),
    };

    $this->actingAs(User::factory()->create())->postJson($storeUrl, ['body' => 'Coba lihat ini @teman-baik!'])
        ->assertCreated()
        ->assertJsonPath('mentions.0.username', 'teman-baik');

    $this->getJson($listUrl)->assertJsonPath('comments.0.mentions.0.name', $friend->name);
    expect(mentionNotifications($friend))->toHaveCount(1)
        ->and(mentionNotifications($friend)[0]->data)->toMatchArray(['url' => $url, 'excerpt' => 'Coba lihat ini @teman-baik!']);
})->with(['ranking', 'object', 'article']);

test('only the first five distinct known members are mentioned and never the author', function () {
    $post = RankingPost::factory()->create();
    $author = User::factory()->create(['username' => 'penulis']);
    $members = collect(range(1, 6))->map(fn (int $index): User => User::factory()->create(['username' => "anggota{$index}"]));
    $body = '@penulis @anggota1 @Anggota1 @tidak-ada @anggota2 @anggota3 @anggota4 @anggota5 @anggota6 email@anggota1.com';

    $this->actingAs($author)->postJson(route('backend.comments.store', $post), ['body' => $body])->assertCreated();

    expect(CommentMention::query()->pluck('user_id')->sort()->values()->all())->toBe($members->take(3)->pluck('id')->all())
        ->and(mentionNotifications($members[5]))->toBe([]);
});

test('blocked, restricted, and unverified members are linked but not notified', function () {
    $post = RankingPost::factory()->create();
    $author = User::factory()->create();
    $blocker = User::factory()->create(['username' => 'pemblokir']);
    $restricted = User::factory()->restricted()->create(['username' => 'dibatasi']);
    $unverified = User::factory()->unverified()->create(['username' => 'belum-verif']);
    UserBlock::factory()->create(['blocker_id' => $blocker->id, 'blocked_id' => $author->id]);

    $this->actingAs($author)->postJson(route('backend.comments.store', $post), ['body' => 'Halo @pemblokir @dibatasi @belum-verif'])
        ->assertCreated()
        ->assertJsonCount(3, 'mentions');

    expect(mentionNotifications($blocker))->toBe([])
        ->and(mentionNotifications($restricted))->toBe([])
        ->and(mentionNotifications($unverified))->toBe([]);
});

test('mentioning the member being replied to sends only the reply notification', function () {
    $post = RankingPost::factory()->create();
    $parentAuthor = User::factory()->create(['username' => 'yang-dibalas']);
    $parent = Comment::factory()->create(['ranking_post_id' => $post->id, 'user_id' => $parentAuthor->id]);

    $this->actingAs(User::factory()->create())->postJson(route('backend.comments.store', $post), [
        'body' => 'Setuju @yang-dibalas', 'parent_id' => $parent->id,
    ])->assertCreated();

    expect($parentAuthor->notifications()->get()->pluck('data.type')->all())->toBe(['comment_reply']);
});

test('switching off the mentions group stops mention notifications', function () {
    $post = RankingPost::factory()->create();
    $quiet = User::factory()->create(['username' => 'pendiam', 'notification_preferences' => ['mentions' => false]]);

    $this->actingAs(User::factory()->create())->postJson(route('backend.comments.store', $post), ['body' => 'Halo @pendiam'])->assertCreated();

    expect($quiet->notifications()->count())->toBe(0)
        ->and(CommentMention::query()->where('user_id', $quiet->id)->exists())->toBeTrue();
});
