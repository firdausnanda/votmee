<?php

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Support\Facades\Event;

function startConversation(User $sender, User $recipient, string $body = 'Halo, salam kenal!'): Conversation
{
    test()->actingAs($sender)
        ->postJson(route('backend.messages.start'), ['recipient_id' => $recipient->id, 'body' => $body])
        ->assertCreated();

    return Conversation::query()->where('direct_key', Conversation::directKey($sender, $recipient))->firstOrFail();
}

test('a first message opens a request that the recipient sees under requests', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = startConversation($sender, $recipient);

    $this->actingAs($recipient)->getJson(route('backend.messages.index', ['box' => 'requests']))
        ->assertOk()
        ->assertJsonPath('conversations.data.0.id', $conversation->id)
        ->assertJsonPath('conversations.data.0.state', 'request')
        ->assertJsonPath('conversations.data.0.other.id', $sender->id)
        ->assertJsonPath('pending_requests', 1)
        ->assertJsonPath('unread_messages', 0);
    $this->actingAs($recipient)->getJson(route('backend.messages.index'))->assertJsonCount(0, 'conversations.data');
    $this->actingAs($sender)->getJson(route('backend.messages.index'))->assertJsonPath('conversations.data.0.state', 'pending');

    expect($recipient->notifications()->first()->data)->toMatchArray([
        'type' => 'message_request',
        'conversation_id' => $conversation->id,
        'url' => "/messages/{$conversation->id}",
    ]);
});

test('the initiator cannot send more messages until the request is accepted', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $conversation = startConversation($sender, $recipient);

    $this->actingAs($sender)->postJson(route('backend.messages.store', $conversation), ['body' => 'Halo lagi?'])
        ->assertUnprocessable()->assertJsonValidationErrors('body');

    $this->actingAs($recipient)->patchJson(route('backend.messages.accept', $conversation))
        ->assertOk()->assertJsonPath('conversation.state', 'active');
    $this->actingAs($sender)->postJson(route('backend.messages.store', $conversation), ['body' => 'Terima kasih!'])->assertCreated();
});

test('replying to a request accepts it and starting again reuses the thread', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $conversation = startConversation($sender, $recipient);

    $this->actingAs($recipient)->postJson(route('backend.messages.store', $conversation), ['body' => 'Halo juga'])->assertCreated();
    startConversation($recipient, $sender, 'Pesan lewat profil');

    expect(Conversation::query()->count())->toBe(1)
        ->and($conversation->messages()->count())->toBe(3)
        ->and($conversation->participantFor($recipient)->hasAccepted())->toBeTrue();
});

test('a declined request stops the initiator without telling them why', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $conversation = startConversation($sender, $recipient);

    $this->actingAs($recipient)->patchJson(route('backend.messages.decline', $conversation))->assertOk()->assertJsonPath('pending_requests', 0);

    $this->actingAs($sender)->postJson(route('backend.messages.start'), ['recipient_id' => $recipient->id, 'body' => 'Kenapa?'])->assertForbidden();
});

test('unverified or restricted members cannot message', function (string $state) {
    $sender = User::factory()->{$state}()->create();

    $this->actingAs($sender)->postJson(route('backend.messages.start'), ['recipient_id' => User::factory()->create()->id, 'body' => 'Halo'])
        ->assertForbidden();
    expect(Message::query()->count())->toBe(0);
})->with(['unverified', 'restricted']);

test('a block in either direction stops new threads and replies', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $conversation = startConversation($sender, $recipient);
    $this->actingAs($recipient)->patchJson(route('backend.messages.accept', $conversation))->assertOk();

    $this->actingAs($recipient)->postJson(route('backend.users.block', $sender))->assertOk()->assertJsonPath('blocked', true);
    $this->actingAs($sender)->getJson(route('backend.messages.show', $conversation))->assertJsonPath('conversation.state', 'closed');

    $this->actingAs($sender)->postJson(route('backend.messages.store', $conversation), ['body' => 'Halo?'])->assertForbidden();
    $this->actingAs($recipient)->postJson(route('backend.messages.store', $conversation), ['body' => 'Halo?'])->assertForbidden();

    $this->actingAs($recipient)->deleteJson(route('backend.users.unblock', $sender))->assertOk();
    expect(UserBlock::query()->count())->toBe(0);
    $this->actingAs($sender)->postJson(route('backend.messages.store', $conversation), ['body' => 'Halo lagi'])->assertCreated();
});

test('members outside the thread cannot read or write it', function () {
    $conversation = startConversation(User::factory()->create(), User::factory()->create());
    $outsider = User::factory()->create();

    $this->actingAs($outsider)->getJson(route('backend.messages.show', $conversation))->assertNotFound();
    $this->actingAs($outsider)->postJson(route('backend.messages.store', $conversation), ['body' => 'Ikut dong'])->assertNotFound();
    $this->actingAs($outsider)->get(route('community.messages', ['locale' => 'id', 'conversation' => $conversation]))->assertNotFound();
});

test('members cannot message themselves', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('backend.messages.start'), ['recipient_id' => $user->id, 'body' => 'Halo aku'])
        ->assertUnprocessable()->assertJsonValidationErrors('recipient_id');
});

test('opening a thread marks it read', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $conversation = startConversation($sender, $recipient);
    $this->actingAs($recipient)->postJson(route('backend.messages.store', $conversation), ['body' => 'Halo juga'])->assertCreated();
    $this->actingAs($sender)->postJson(route('backend.messages.store', $conversation), ['body' => 'Apa kabar?'])->assertCreated();

    $this->actingAs($recipient)->getJson(route('backend.messages.index'))->assertJsonPath('unread_messages', 1)->assertJsonPath('conversations.data.0.unread', true);
    $this->actingAs($recipient)->getJson(route('backend.messages.show', $conversation))
        ->assertOk()
        ->assertJsonCount(3, 'messages.data')
        ->assertJsonPath('messages.data.0.body', 'Apa kabar?')
        ->assertJsonPath('unread_messages', 0);
});

test('each participant gets the new message on their own channel', function () {
    Event::fake([MessageSent::class]);
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    startConversation($sender, $recipient);

    Event::assertDispatchedTimes(MessageSent::class, 2);
    Event::assertDispatched(MessageSent::class, fn (MessageSent $event): bool => $event->userId === $recipient->id
        && $event->broadcastOn()[0]->name === "private-App.Models.User.{$recipient->id}"
        && $event->broadcastWith()['pending_requests'] === 1
        && $event->broadcastWith()['message']['body'] === 'Halo, salam kenal!');
});

test('the message request notification respects its preference group', function () {
    $recipient = User::factory()->create(['notification_preferences' => ['messages' => false]]);

    startConversation(User::factory()->create(), $recipient);

    expect($recipient->notifications()->count())->toBe(0);
});

test('only participants can report a message and a moderator can hide it', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $conversation = startConversation($sender, $recipient, 'Pesan yang mengganggu');
    $message = $conversation->messages()->first();
    $payload = ['target_type' => 'message', 'target_id' => (string) $message->id, 'reason' => 'Isinya spam dan mengganggu.'];

    $this->actingAs(User::factory()->create())->postJson(route('backend.reports.store'), $payload)
        ->assertUnprocessable()->assertJsonValidationErrors('target_id');
    $this->actingAs($recipient)->postJson(route('backend.reports.store'), $payload)->assertCreated();

    $moderator = User::factory()->moderator()->create();
    $this->actingAs($moderator)->getJson(route('admin.backend.queue'))->assertJsonPath('reports.data.0.message.body', 'Pesan yang mengganggu');
    $this->actingAs($moderator)->patchJson(route('admin.backend.messages.hide', $message), ['reason' => 'Spam pesan pribadi'])->assertOk();

    $this->actingAs($recipient)->getJson(route('backend.messages.show', $conversation))->assertJsonCount(0, 'messages.data');
});

test('new threads are limited per day', function () {
    config(['messaging.new_conversations_per_day' => 2]);
    $sender = User::factory()->create();

    startConversation($sender, User::factory()->create());
    startConversation($sender, User::factory()->create());

    $this->actingAs($sender)->postJson(route('backend.messages.start'), ['recipient_id' => User::factory()->create()->id, 'body' => 'Halo'])
        ->assertTooManyRequests();
});
