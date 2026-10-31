<?php

namespace App\Services;

use App\Models\PointLedger;
use App\Models\RankingEntry;
use App\Models\SupportEvent;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupportService
{
    public function __construct(private GamificationService $gamification, private AchievementService $achievements, private MissionService $missions) {}

    public function claimDaily(User $user): PointLedger
    {
        $this->ensureEligible($user);
        $day = now('Asia/Jakarta')->toDateString();

        $ledger = DB::transaction(function () use ($user, $day): PointLedger {
            $wallet = Wallet::lockedFor($user);
            $existing = PointLedger::query()->where('user_id', $user->id)->where('type', 'daily_bonus')->whereDate('claimed_on', $day)->first();

            if ($existing !== null) {
                return $existing;
            }

            $ledger = PointLedger::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'amount' => 5,
                'type' => 'daily_bonus',
                'idempotency_key' => "daily:{$user->id}:{$day}",
                'claimed_on' => $day,
            ]);
            $wallet->increment('balance', 5);
            $this->gamification->advanceStreak($wallet, $day);

            return $ledger;
        }, 3);

        if ($ledger->wasRecentlyCreated) {
            $this->achievements->evaluate($user, 'streak');

            if (now('Asia/Jakarta')->hour === 0) {
                $this->achievements->unlockHidden($user, 'night_owl');
            }
        }

        return $ledger;
    }

    public function support(User $user, RankingEntry $entry, int $amount, string $idempotencyKey): SupportEvent
    {
        $this->ensureEligible($user);

        $event = DB::transaction(function () use ($user, $entry, $amount, $idempotencyKey): SupportEvent {
            $wallet = Wallet::lockedFor($user);
            $existing = PointLedger::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                $event = SupportEvent::query()->where('ledger_id', $existing->id)->first();

                if ($existing->user_id === $user->id && $event?->ranking_entry_id === $entry->id && $event->amount === $amount) {
                    return $event;
                }

                throw ValidationException::withMessages(['idempotency_key' => __('votmee.key_reused')]);
            }

            $entry->loadMissing(['post', 'object']);
            $object = $entry->object;

            if ($entry->post->status !== 'published' || ! in_array($object->status, ['verified', 'merged'], true)) {
                throw ValidationException::withMessages(['ranking_entry_id' => __('votmee.candidate_unavailable')]);
            }

            $resolvedObjectId = $object->status === 'merged' ? $object->merged_into_id : $object->id;

            if ($object->status === 'merged' && ! $object->newQuery()->whereKey($resolvedObjectId)->where('status', 'verified')->exists()) {
                throw ValidationException::withMessages(['ranking_entry_id' => __('votmee.candidate_unavailable')]);
            }

            if ($amount < 1 || $wallet->balance < $amount) {
                throw ValidationException::withMessages(['amount' => __('votmee.insufficient_points')]);
            }

            $ledger = PointLedger::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'amount' => -$amount,
                'type' => 'support',
                'idempotency_key' => $idempotencyKey,
            ]);
            $event = SupportEvent::create([
                'user_id' => $user->id,
                'ranking_entry_id' => $entry->id,
                'object_id' => $resolvedObjectId,
                'ledger_id' => $ledger->id,
                'amount' => $amount,
                'status' => 'valid',
                'occurred_at' => now('UTC'),
            ]);
            $wallet->decrement('balance', $amount);

            return $event;
        }, 3);

        $this->missions->record($user, 'support');
        $this->achievements->evaluate($user, 'voter');

        if ($entry->post->creator !== null && ! $entry->post->creator->is_official) {
            $this->missions->record($entry->post->creator, 'popular_ranking', $entry->post->created_at);
            $this->achievements->evaluate($entry->post->creator, 'influencer');
            $this->achievements->evaluate($entry->post->creator, 'ranker');
        }

        return $event;
    }

    public function reverse(SupportEvent $event): void
    {
        DB::transaction(function () use ($event): void {
            $lockedEvent = SupportEvent::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            if ($lockedEvent->status === 'reversed') {
                return;
            }

            if ($lockedEvent->ledger_id === null) {
                $lockedEvent->update(['status' => 'reversed']);

                return;
            }

            $wallet = Wallet::lockedFor(User::findOrFail($event->user_id));

            PointLedger::create([
                'wallet_id' => $wallet->id,
                'user_id' => $lockedEvent->user_id,
                'amount' => $lockedEvent->amount,
                'type' => 'reversal',
                'idempotency_key' => 'reversal:'.$lockedEvent->id,
                'reverses_ledger_id' => $lockedEvent->ledger_id,
            ]);
            $wallet->increment('balance', $lockedEvent->amount);
            $lockedEvent->update(['status' => 'reversed']);
        }, 3);
    }

    private function ensureEligible(User $user): void
    {
        if (! $user->canParticipate()) {
            throw ValidationException::withMessages(['account' => __('votmee.account_ineligible')]);
        }
    }
}
