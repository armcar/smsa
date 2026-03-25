<?php

namespace Tests\Feature;

use App\Services\WordPressUserProvisioner;
use Database\Factories\SocioFactory;
use Database\Factories\SocioTypeFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WordPressUserProvisionerTest extends TestCase
{
    use RefreshDatabase;

    private string $wpSqlitePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wpSqlitePath = storage_path('framework/testing/wp-provisioner-test.sqlite');
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
        Config::set('wordpress.expose_plain_password', true);
        Config::set('wordpress.local_fallback_email_domain', 'smsa.local.test');

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

    public function test_nao_recria_utilizador_se_ja_existir_wp_user_id(): void
    {
        $type = SocioTypeFactory::new()->create(['code' => 'B']);

        $wpUserId = DB::connection('wordpress')->table('wp_users')->insertGetId([
            'user_login' => 'socio-B001',
            'user_pass' => password_hash('secret', PASSWORD_BCRYPT),
            'user_nicename' => 'socio-b001',
            'user_email' => 'existente@smsa.test',
            'user_registered' => now()->format('Y-m-d H:i:s'),
            'display_name' => 'Existente',
        ]);

        $socio = SocioFactory::new()
            ->forType($type)
            ->create([
                'num_socio' => 1,
                'email' => 'existente@smsa.test',
                'wp_user_id' => $wpUserId,
            ]);

        $result = app(WordPressUserProvisioner::class)->createMemberUser($socio);

        $this->assertFalse($result->created);
        $this->assertSame((int) $wpUserId, $result->wpUserId);
        $this->assertSame('socio-B001', $result->username);
        $this->assertSame(1, DB::connection('wordpress')->table('wp_users')->count());
    }

    public function test_gera_username_com_formato_socio_tipo_numero(): void
    {
        $type = SocioTypeFactory::new()->create(['code' => 'B']);
        $socio = SocioFactory::new()->forType($type)->make(['num_socio' => 1]);

        $username = app(WordPressUserProvisioner::class)->buildUsername($socio);

        $this->assertSame('socio-B001', $username);
    }

    public function test_em_ambiente_teste_devolve_password_temporaria_no_resultado(): void
    {
        $type = SocioTypeFactory::new()->create(['code' => 'B']);
        $socio = SocioFactory::new()
            ->forType($type)
            ->create([
                'num_socio' => 7,
                'email' => 'novo-socio@smsa.test',
                'wp_user_id' => null,
            ]);

        $result = app(WordPressUserProvisioner::class)->createMemberUser($socio);

        $this->assertTrue($result->created);
        $this->assertSame('socio-B007', $result->username);
        $this->assertNotNull($result->plainPassword);
        $this->assertNotSame('', (string) $result->plainPassword);
        $this->assertNotNull($socio->fresh()->wp_user_id);
    }

    public function test_password_nao_e_persistida_na_bd_do_laravel(): void
    {
        $type = SocioTypeFactory::new()->create(['code' => 'B']);
        $socio = SocioFactory::new()
            ->forType($type)
            ->create([
                'num_socio' => 22,
                'email' => 'persist-check@smsa.test',
                'wp_user_id' => null,
            ]);

        $result = app(WordPressUserProvisioner::class)->createMemberUser($socio);
        $this->assertTrue($result->created);
        $this->assertNotNull($result->plainPassword);

        $row = (array) DB::table('socios')->where('id', $socio->id)->first();
        $this->assertArrayHasKey('wp_user_id', $row);
        $this->assertArrayNotHasKey('password', $row);

        foreach ($row as $value) {
            if (is_string($value)) {
                $this->assertNotSame($result->plainPassword, $value);
            }
        }
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
}
