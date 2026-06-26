<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccesoRutasTest extends TestCase
{
    use RefreshDatabase;

    public function test_ruta_dashboard_requiere_autenticacion(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_comprador_no_accede_a_ruta_admin(): void
    {
        $user = User::factory()->create([
            'role'   => 'comprador',
            'activo' => true,
        ]);

        $response = $this->actingAs($user)->get('/admin/dashboard');
        $response->assertStatus(403);
    }
}