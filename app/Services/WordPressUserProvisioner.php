<?php

namespace App\Services;

use App\Models\Socio;
use App\Services\DTO\ProvisionedWordPressUserResult;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class WordPressUserProvisioner
{
    public function createMemberUser(Socio $socio): ProvisionedWordPressUserResult
    {
        if ($socio->wp_user_id) {
            $username = $this->usernameFromLinkedWpUser((int) $socio->wp_user_id) ?? $this->buildUsername($socio);

            return new ProvisionedWordPressUserResult(
                created: false,
                username: $username,
                plainPassword: null,
                wpUserId: (int) $socio->wp_user_id,
                message: 'Sócio já tem utilizador WordPress associado.',
            );
        }

        $username = $this->buildUsername($socio);
        $email = $this->resolveProvisioningEmail($socio);
        $displayName = trim((string) $socio->nome) !== '' ? trim((string) $socio->nome) : $username;
        $now = now()->format('Y-m-d H:i:s');

        $wp = $this->wordpressConnection();
        $usersTable = $this->table('users');
        $userMetaTable = $this->table('usermeta');

        return $wp->transaction(function () use ($socio, $username, $email, $displayName, $now, $wp, $usersTable, $userMetaTable): ProvisionedWordPressUserResult {
            $existingByUsername = $wp->table($usersTable)->where('user_login', $username)->first();
            $existingByEmail = $wp->table($usersTable)->where('user_email', $email)->first();

            if ($existingByUsername && $existingByEmail && (int) $existingByUsername->ID !== (int) $existingByEmail->ID) {
                throw new RuntimeException('Conflito no WordPress: username e email já existem em utilizadores diferentes.');
            }

            if ($existingByUsername) {
                $wpUserId = (int) $existingByUsername->ID;
                $this->assignRoleMeta($wp, $userMetaTable, $wpUserId);
                $socio->forceFill(['wp_user_id' => $wpUserId])->save();

                return new ProvisionedWordPressUserResult(
                    created: false,
                    username: $username,
                    plainPassword: null,
                    wpUserId: $wpUserId,
                    message: 'Utilizador WordPress já existia com o mesmo username e foi associado ao sócio.',
                );
            }

            if ($existingByEmail) {
                throw new RuntimeException('Já existe um utilizador WordPress com este email. Resolva o conflito antes de continuar.');
            }

            $plainPassword = Str::password(20, true, true, true, false);
            $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
            if (! is_string($hashedPassword) || $hashedPassword === '') {
                throw new RuntimeException('Não foi possível gerar hash de password compatível com WordPress.');
            }

            $wpUserId = (int) $wp->table($usersTable)->insertGetId([
                'user_login' => $username,
                'user_pass' => $hashedPassword,
                'user_nicename' => Str::slug($username),
                'user_email' => $email,
                'user_registered' => $now,
                'display_name' => $displayName,
            ]);

            $this->assignRoleMeta($wp, $userMetaTable, $wpUserId);
            $socio->forceFill(['wp_user_id' => $wpUserId])->save();

            return new ProvisionedWordPressUserResult(
                created: true,
                username: $username,
                plainPassword: $this->shouldExposePlainPassword() ? $plainPassword : null,
                wpUserId: $wpUserId,
                message: 'Utilizador criado com sucesso no site.',
            );
        });
    }

    public function buildUsername(Socio $socio): string
    {
        $memberCode = $this->formatMemberCode($socio);
        return 'socio-' . $memberCode;
    }

    private function formatMemberCode(Socio $socio): string
    {
        $number = (int) $socio->num_socio;
        if ($number <= 0) {
            throw new RuntimeException('Número de sócio inválido para criar utilizador WordPress.');
        }

        $roleCode = strtoupper((string) optional($socio->socioType)->code ?: 'B');
        $roleCode = preg_replace('/[^A-Z0-9]/', '', $roleCode) ?: 'B';

        return $roleCode . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }

    private function resolveProvisioningEmail(Socio $socio): string
    {
        $email = trim((string) $socio->email);
        if ($email !== '') {
            return $email;
        }

        if (! app()->environment(['local', 'development', 'testing'])) {
            throw new RuntimeException('Sócio sem email. Em produção não é permitido criar utilizador WordPress sem email.');
        }

        $domain = (string) config('wordpress.local_fallback_email_domain', 'smsa.local.test');
        $domain = trim($domain) !== '' ? trim($domain) : 'smsa.local.test';

        return $this->buildUsername($socio) . '@' . $domain;
    }

    private function usernameFromLinkedWpUser(int $wpUserId): ?string
    {
        $user = $this->wordpressConnection()
            ->table($this->table('users'))
            ->where('ID', $wpUserId)
            ->first();

        if (! $user) {
            return null;
        }

        return (string) $user->user_login;
    }

    private function assignRoleMeta(ConnectionInterface $wp, string $userMetaTable, int $wpUserId): void
    {
        $role = (string) config('wordpress.member_role', 'smsa_socio');
        $role = trim($role) !== '' ? trim($role) : 'smsa_socio';

        $capsKey = $this->tablePrefix() . 'capabilities';
        $levelKey = $this->tablePrefix() . 'user_level';

        $currentCaps = $wp->table($userMetaTable)
            ->where('user_id', $wpUserId)
            ->where('meta_key', $capsKey)
            ->value('meta_value');

        $caps = [];
        if (is_string($currentCaps) && $currentCaps !== '') {
            $decoded = @unserialize($currentCaps);
            if (is_array($decoded)) {
                $caps = $decoded;
            }
        }
        $caps[$role] = true;

        $this->upsertUserMeta($wp, $userMetaTable, $wpUserId, $capsKey, serialize($caps));
        $this->upsertUserMeta($wp, $userMetaTable, $wpUserId, $levelKey, '0');
    }

    private function upsertUserMeta(
        ConnectionInterface $wp,
        string $userMetaTable,
        int $wpUserId,
        string $metaKey,
        string $metaValue
    ): void {
        $existing = $wp->table($userMetaTable)
            ->where('user_id', $wpUserId)
            ->where('meta_key', $metaKey)
            ->first();

        if ($existing) {
            $wp->table($userMetaTable)
                ->where('umeta_id', (int) $existing->umeta_id)
                ->update(['meta_value' => $metaValue]);
            return;
        }

        $wp->table($userMetaTable)->insert([
            'user_id' => $wpUserId,
            'meta_key' => $metaKey,
            'meta_value' => $metaValue,
        ]);
    }

    private function shouldExposePlainPassword(): bool
    {
        if (! app()->environment(['local', 'development', 'testing'])) {
            return false;
        }

        return (bool) config('wordpress.expose_plain_password', false);
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
}
