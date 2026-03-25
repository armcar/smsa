<?php

namespace Tests\Feature;

use App\Services\WordPressMemberAccessSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WordPressMemberAccessSynchronizerTest extends TestCase
{
    use RefreshDatabase;

    private string $wpSqlitePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wpSqlitePath = storage_path('framework/testing/wp-access-sync-test.sqlite');
        if (file_exists($this->wpSqlitePath)) {
            @unlink($this->wpSqlitePath);
        }

        $dir = dirname($this->wpSqlitePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (! file_exists($this->wpSqlitePath)) {
            touch($this->wpSqlitePath);
        }

        Config::set('database.connections.wordpress', [
            'driver' => 'sqlite',
            'database' => $this->wpSqlitePath,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        Config::set('wordpress.connection', 'wordpress');
        Config::set('wordpress.table_prefix', 'wp_');
        Config::set('wordpress.member_role', 'smsa_socio');

        DB::purge('wordpress');
        $this->createWordPressTables();
    }

    protected function tearDown(): void
    {
        DB::disconnect('wordpress');

        if (file_exists($this->wpSqlitePath)) {
            @unlink($this->wpSqlitePath);
        }

        parent::tearDown();
    }

    public function test_remove_role_quando_socio_fica_inativo(): void
    {
        $wpUserId = $this->createWpUserWithRole('smsa_socio');

        app(WordPressMemberAccessSynchronizer::class)->syncSocioMembershipState($wpUserId, false);

        $caps = $this->getWpCapabilities($wpUserId);
        $this->assertArrayNotHasKey('smsa_socio', $caps);
    }

    public function test_adiciona_role_quando_socio_fica_ativo(): void
    {
        $wpUserId = $this->createWpUserWithRole('subscriber');

        app(WordPressMemberAccessSynchronizer::class)->syncSocioMembershipState($wpUserId, true);

        $caps = $this->getWpCapabilities($wpUserId);
        $this->assertArrayHasKey('smsa_socio', $caps);
        $this->assertTrue((bool) $caps['smsa_socio']);
    }

    public function test_eliminar_socio_remove_user_no_wordpress(): void
    {
        $wpUserId = $this->createWpUserWithRole('smsa_socio');

        app(WordPressMemberAccessSynchronizer::class)->deleteWordPressUser($wpUserId);

        $usersCount = DB::connection('wordpress')->table('wp_users')->where('ID', $wpUserId)->count();
        $metaCount = DB::connection('wordpress')->table('wp_usermeta')->where('user_id', $wpUserId)->count();

        $this->assertSame(0, $usersCount);
        $this->assertSame(0, $metaCount);
    }

    private function createWordPressTables(): void
    {
        Schema::connection('wordpress')->create('wp_users', function ($table): void {
            $table->increments('ID');
            $table->string('user_login', 60)->unique();
            $table->string('user_pass', 255);
            $table->string('user_nicename', 50)->default('');
            $table->string('user_email', 100)->unique();
            $table->dateTime('user_registered');
            $table->string('display_name', 250)->default('');
        });

        Schema::connection('wordpress')->create('wp_usermeta', function ($table): void {
            $table->increments('umeta_id');
            $table->unsignedInteger('user_id');
            $table->string('meta_key', 255)->nullable();
            $table->text('meta_value')->nullable();
            $table->index(['user_id', 'meta_key']);
        });
    }

    private function createWpUserWithRole(string $role): int
    {
        $wp = DB::connection('wordpress');
        $wpUserId = (int) $wp->table('wp_users')->insertGetId([
            'user_login' => 'user-' . $role . '-' . uniqid(),
            'user_pass' => password_hash('secret', PASSWORD_BCRYPT),
            'user_nicename' => 'user',
            'user_email' => uniqid('mail-', true) . '@smsa.test',
            'user_registered' => now()->format('Y-m-d H:i:s'),
            'display_name' => 'Test User',
        ]);

        $wp->table('wp_usermeta')->insert([
            'user_id' => $wpUserId,
            'meta_key' => 'wp_capabilities',
            'meta_value' => serialize([$role => true]),
        ]);

        return $wpUserId;
    }

    private function getWpCapabilities(int $wpUserId): array
    {
        $raw = DB::connection('wordpress')
            ->table('wp_usermeta')
            ->where('user_id', $wpUserId)
            ->where('meta_key', 'wp_capabilities')
            ->value('meta_value');

        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = @unserialize($raw);
        return is_array($decoded) ? $decoded : [];
    }
}

