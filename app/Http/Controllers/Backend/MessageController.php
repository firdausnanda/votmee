<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\User;
use App\Services\AvatarFrameService;
use App\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(private MessagingService $messaging) {}

    public function index(Request $request, AvatarFrameService $frames): JsonResponse
    {
        $data = $request->validate(['box' => ['nullable', 'in:inbox,requests']]);
        $user = $request->user();
        $conversations = Conversation::query()
            ->whereIn('id', $this->messaging->participations($user, $data['box'] ?? 'inbox')->select('conversation_id'))
            ->with(['participants.user', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(20);
        $flair = $frames->flairFor($conversations->getCollection()->map(fn (Conversation $conversation) => $conversation->otherParticipant($user)?->user_id));

        return response()->json([
            'conversations' => $conversations->through(fn (Conversation $conversation): array => $this->messaging->summary($conversation, $user, $flair)),
            ...$this->counters($user),
        ]);
    }

    /** Messages newest first; opening the thread marks it read. */
    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        $this->messaging->participantOrFail($conversation, $user);
        $messages = $conversation->messages()->where('status', 'visible')->orderByDesc('id')->cursorPaginate(30);

        if ($request->query('cursor') === null) {
            $this->messaging->markRead($user, $conversation);
        }

        return response()->json([
            'conversation' => $this->messaging->summary($conversation->refresh(), $user),
            'messages' => $messages->through(fn ($message): array => $this->messaging->listing($message)),
            ...$this->counters($user),
        ]);
    }

    public function start(StoreMessageRequest $request): JsonResponse
    {
        $conversation = $this->messaging->start($request->user(), User::query()->findOrFail($request->validated('recipient_id')), $request->validated('body'));

        return response()->json(['conversation' => $this->messaging->summary($conversation->refresh(), $request->user())], 201);
    }

    public function store(StoreMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $message = $this->messaging->send($request->user(), $conversation, $request->validated('body'));

        return response()->json(['message' => $this->messaging->listing($message)], 201);
    }

    public function accept(Request $request, Conversation $conversation): JsonResponse
    {
        $this->messaging->accept($request->user(), $conversation);

        return response()->json(['conversation' => $this->messaging->summary($conversation->refresh(), $request->user()), ...$this->counters($request->user())]);
    }

    public function decline(Request $request, Conversation $conversation): JsonResponse
    {
        $this->messaging->decline($request->user(), $conversation);

        return response()->json($this->counters($request->user()));
    }

    public function read(Request $request, Conversation $conversation): JsonResponse
    {
        $this->messaging->markRead($request->user(), $conversation);

        return response()->json($this->counters($request->user()));
    }

    /**
     * @return array{unread_messages: int, pending_requests: int}
     */
    private function counters(User $user): array
    {
        return [
            'unread_messages' => $this->messaging->unreadCount($user),
            'pending_requests' => $this->messaging->pendingRequestCount($user),
        ];
    }
}
