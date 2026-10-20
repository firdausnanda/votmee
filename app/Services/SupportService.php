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
    public function claimDaily(User $user): PointLedger
    {
        $this->ensureEligible($user);
        $day = now('Asia/Jakarta')->toDateString();

        return DB::transaction(function () use ($user, $day): PointLedger {
            $wallet = $this->lockedWallet($user);
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

            return $ledger;
        }, 3);
    }

    public function support(User $user, RankingEntry $entry, int $amount, string $idempotencyKey): SupportEvent
    {
        $this->ensureEligible($user);

        return DB::transaction(function () use ($user, $entry, $amount, $idempotencyKey): SupportEvent {
            $wallet = $this->lockedWallet($user);
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

            $wallet = $this->lockedWallet(User::findOrFail($event->user_id));

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

    private function lockedWallet(User $user): Wallet
    {
        Wallet::query()->insertOrIgnore(['user_id' => $user->id, 'balance' => 0, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);

        return Wallet::query()->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
    }

    private function ensureEligible(User $user): void
    {
        if (! $user->canParticipate()) {
            throw ValidationException::withMessages(['account' => __('votmee.account_ineligible')]);
        }
    }
}
