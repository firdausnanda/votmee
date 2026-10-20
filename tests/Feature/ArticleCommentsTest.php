<?php

use App\Models\Comment;
use App\Models\MasterObject;
use App\Models\ObjectArticle;
use App\Models\ObjectContentRevision;
use App\Models\User;

function publishedArticleForComments(): ObjectArticle
{
    $object = MasterObject::factory()->create();
    $article = ObjectArticle::factory()->create(['object_id' => $object->id]);
    $revision = ObjectContentRevision::factory()->create([
        'object_id' => $object->id,
        'article_id' => $article->id,
        'author_id' => $article->author_id,
        'title' => 'A community story',
        'status' => 'published',
        'published_at' => now(),
    ]);
    $article->update(['published_revision_id' => $revision->id]);

    return $article;
}

test('readers can see published article comments and signed in members can reply and like', function () {
    $article = publishedArticleForComments();
    $member = User::factory()->create();
    $commentsUrl = route('backend.articles.comments.index', $article);

    $this->get(route('community.article', ['locale' => 'id', 'article' => $article]))->assertOk();
    $this->getJson($commentsUrl)->assertOk()->assertJsonPath('total', 0);
    $this->postJson(route('backend.articles.comments.store', $article), ['body' => 'First comment'])->assertUnauthorized();

    $rootId = $this->actingAs($member)->postJson(route('backend.articles.comments.store', $article), [
        'body' => 'First comment',
    ])->assertCreated()->json('id');
    $replyId = $this->postJson(route('backend.articles.comments.store', $article), [
        'body' => 'A helpful reply', 'parent_id' => $rootId,
    ])->assertCreated()->assertJsonPath('parent_id', $rootId)->json('id');
    $this->postJson(route('backend.articles.comments.store', $article), [
        'body' => 'Reply to a reply', 'parent_id' => $replyId,
    ])->assertCreated()->assertJsonPath('parent_id', $rootId);
    $this->postJson(route('backend.comments.like', $rootId))->assertOk()->assertJsonPath('likes_count', 1);
    $this->getJson($commentsUrl)->assertOk()->assertJsonPath('total', 3)
        ->assertJsonPath('comments.2.id', $rootId)
        ->assertJsonPath('comments.2.liked', true)
        ->assertJsonPath('comments.2.likes_count', 1);
});

test('unpublished articles and comments from other discussions cannot be used', function () {
    $article = publishedArticleForComments();
    $pendingArticle = ObjectArticle::factory()->create(['object_id' => $article->object_id]);
    $member = User::factory()->create();
    $rootId = $this->actingAs($member)->postJson(route('backend.articles.comments.store', $article), [
        'body' => 'Only for this article',
    ])->assertCreated()->json('id');

    $this->getJson(route('backend.articles.comments.index', $pendingArticle))->assertNotFound();
    $this->postJson(route('backend.articles.comments.store', $pendingArticle), [
        'body' => 'Private article comment',
    ])->assertNotFound();
    $this->postJson(route('backend.articles.comments.store', publishedArticleForComments()), [
        'body' => 'Cross article reply', 'parent_id' => $rootId,
    ])->assertUnprocessable()->assertJsonValidationErrors('parent_id');

    Comment::findOrFail($rootId)->update(['status' => 'hidden']);
    $this->postJson(route('backend.articles.comments.store', $article), [
        'body' => 'Reply to hidden comment', 'parent_id' => $rootId,
    ])->assertUnprocessable()->assertJsonValidationErrors('parent_id');
    $this->getJson(route('backend.articles.comments.index', $article))->assertJsonPath('total', 0);
});

test('article comments paginate and popular order includes likes', function () {
    $article = publishedArticleForComments();
    $member = User::factory()->create();
    $comments = Comment::factory()->count(16)->create([
        'ranking_post_id' => null,
        'article_id' => $article->id,
        'user_id' => $member->id,
    ]);
    $this->actingAs($member)->postJson(route('backend.comments.like', $comments->first()))->assertOk();
    $commentsUrl = route('backend.articles.comments.index', $article);

    $this->getJson($commentsUrl)->assertOk()->assertJsonPath('total', 16)
        ->assertJsonCount(15, 'comments')->assertJsonPath('has_more', true);
    $this->getJson($commentsUrl.'?offset=15')->assertOk()
        ->assertJsonCount(1, 'comments')->assertJsonPath('has_more', false);
    $this->getJson($commentsUrl.'?sort=popular')->assertOk()
        ->assertJsonPath('comments.0.id', $comments->first()->id)
        ->assertJsonPath('comments.0.likes_count', 1);
});
