<?php

namespace Tests\Feature;

use App\Models\WpApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WpIngestEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.wp_bridge.token', 'test-token');
        config()->set('services.wp_bridge.allowed_callback_hosts', []);
    }

    public function test_pedido_sem_token_valido_e_rejeitado(): void
    {
        $payload = $this->validPayload('wp-1');

        $response = $this->postJson('/integrations/wp/applications', $payload, [
            'X-WP-Bridge-Token' => 'token-invalido',
        ]);

        $response->assertStatus(401);
        $this->assertSame(0, WpApplication::query()->count());
    }

    public function test_pedido_valido_cria_registo(): void
    {
        $payload = $this->validPayload('wp-2');

        $response = $this->postJson('/integrations/wp/applications', $payload, [
            'X-WP-Bridge-Token' => 'test-token',
        ]);

        $response->assertStatus(201)
            ->assertJson(['ok' => true]);

        $this->assertSame(1, WpApplication::query()->count());
    }

    public function test_pedido_duplicado_nao_cria_duplicado(): void
    {
        Log::spy();

        $payload = $this->validPayload('wp-3');

        $first = $this->postJson('/integrations/wp/applications', $payload, [
            'X-WP-Bridge-Token' => 'test-token',
        ]);

        $second = $this->postJson('/integrations/wp/applications', $payload, [
            'X-WP-Bridge-Token' => 'test-token',
        ]);

        $first->assertStatus(201);
        $second->assertStatus(200)
            ->assertJson(['ok' => true]);

        $this->assertSame(1, WpApplication::query()->count());
        $this->assertSame(
            WpApplication::query()->first()->id,
            $second->json('id')
        );
    }

    private function validPayload(string $externalId): array
    {
        return [
            'kind' => 'socio',
            'external_id' => $externalId,
            'submitted_at' => now()->toAtomString(),
            'payload' => [
                'nome' => 'Teste WP',
                'email' => 'teste.wp.' . $externalId . '@example.org',
                'numero_fiscal' => '123456789',
                'data_nascimento' => '1990-01-01',
            ],
        ];
    }
}
