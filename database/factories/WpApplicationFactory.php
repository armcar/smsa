<?php

namespace Database\Factories;

use App\Models\WpApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WpApplication>
 */
class WpApplicationFactory extends Factory
{
    protected $model = WpApplication::class;

    public function definition(): array
    {
        $payload = [
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'numero_fiscal' => fake()->numerify('#########'),
            'data_nascimento' => '1990-01-01',
        ];

        return [
            'source' => 'wordpress',
            'kind' => 'socio',
            'external_id' => (string) fake()->unique()->numberBetween(10000, 99999),
            'payload_hash' => hash('sha256', 'socio|' . json_encode($payload)),
            'imported_socio_id' => null,
            'target_socio_type_code' => 'B',
            'target_num_socio' => null,
            'status' => 'pendente',
            'display_name' => $payload['nome'],
            'display_email' => $payload['email'],
            'submitted_at' => now(),
            'payload' => $payload,
            'resolution_notes' => null,
            'resolved_at' => null,
            'wp_status_callback_url' => null,
            'last_callback_at' => null,
            'last_callback_response' => null,
        ];
    }
}
