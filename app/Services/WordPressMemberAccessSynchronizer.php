<?php

namespace App\Services;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

class WordPressMemberAccessSynchronizer
{
    public function syncSocioMembershipState(int $wpUserId, bool $shouldHaveMemberRole): void
    {
        $wp = $this->wordpressConnection();
        $capsKey = $this->tablePrefix() . 'capabilities';
        $levelKey = $this->tablePrefix() . 'user_level';
        $memberRole = $this->memberRole();

        $user = $wp->table($this->table('users'))->where('ID', $wpUserId)->first();
        if (! $user) {
            return;
        }

        $currentCapsRaw = $wp->table($this->table('usermeta'))
            ->where('user_id', $wpUserId)
            ->where('meta_key', $capsKey)
            ->value('meta_value');

        $caps = [];
        if (is_string($currentCapsRaw) && $currentCapsRaw !== '') {
            $decoded = @unserialize($currentCapsRaw);
            if (is_array($decoded)) {
                $caps = $decoded;
            }
        }

        if ($shouldHaveMemberRole) {
            $caps[$memberRole] = true;
        } else {
            unset($caps[$memberRole]);
        }

        $this->upsertUserMeta($wp, $wpUserId, $capsKey, serialize($caps));
        $this->upsertUserMeta($wp, $wpUserId, $levelKey, '0');
    }

    public function deleteWordPressUser(int $wpUserId): void
    {
        $wp = $this->wordpressConnection();

        $wp->transaction(function () use ($wp, $wpUserId): void {
            $wp->table($this->table('usermeta'))->where('user_id', $wpUserId)->delete();
            $wp->table($this->table('users'))->where('ID', $wpUserId)->delete();
        });
    }

    private function upsertUserMeta(ConnectionInterface $wp, int $wpUserId, string $metaKey, string $metaValue): void
    {
        $existing = $wp->table($this->table('usermeta'))
            ->where('user_id', $wpUserId)
            ->where('meta_key', $metaKey)
            ->first();

        if ($existing) {
            $wp->table($this->table('usermeta'))
                ->where('umeta_id', (int) $existing->umeta_id)
                ->update(['meta_value' => $metaValue]);
            return;
        }

        $wp->table($this->table('usermeta'))->insert([
            'user_id' => $wpUserId,
            'meta_key' => $metaKey,
            'meta_value' => $metaValue,
        ]);
    }

    private function wordpressConnection(): ConnectionInterface
    {
        return DB::connection((string) config('wordpress.connection', 'wordpress'));
    }

    private function table(string $suffix): string
    {
        return $this->tablePrefix() . $suffix;
    }

    private function tablePrefix(): string
    {
        return (string) config('wordpress.table_prefix', 'wp_');
    }

    private function memberRole(): string
    {
        return (string) config('wordpress.member_role', 'smsa_socio');
    }
}

