<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Procedimiento;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProcedimientoTest extends TestCase
{
    use RefreshDatabase;

    public function test_busqueda_procedimiento_retorna_json(): void
    {
        $user = User::factory()->create([
            'role' => 'comprador',
            'activo' => true,
        ]);

        Procedimiento::create([
            'id_persona' => null,
            'user_id' => $user->id,

            'num_procedimiento' => 'LA-08-VST-008VST973-N-30-2026',
            'nombre_procedimiento' => 'Servicio de prueba',

            'fecha_publicacion' => '2026-04-23',

            'fecha_vm' => '2026-04-29',
            'hora_vm' => '11:00:00',

            'fecha_ac' => '2026-04-30',
            'hora_ac' => '10:00:00',

            'fecha_apertura' => '2026-05-08',
            'hora_apertura' => '10:00:00',

            'fecha_fallo' => '2026-05-12',
            'hora_fallo' => '11:00:00',

            'fecha_inicio_contrato' => '2026-06-01',
            'fecha_fin_contrato' => '2026-12-31',

            'monto_maximo' => 100000,
        ]);

        $response = $this->actingAs($user)
            ->get('/buscar-procedimiento-acta/LA-08-VST-008VST973-N-30-2026');

        $response->assertStatus(200);

        $response->assertJson([
            'num_procedimiento' => 'LA-08-VST-008VST973-N-30-2026',
            'nombre_procedimiento' => 'Servicio de prueba',
            'fecha_ac' => '2026-04-30',
            'hora_ac' => '10:00:00',
            'fecha_apertura' => '2026-05-08',
            'hora_apertura' => '10:00:00',
        ]);
    }

    public function test_convocatoria_falla_sin_campos_requeridos(): void
    {
        $user = User::factory()->create([
            'role' => 'comprador',
            'activo' => true,
        ]);

        $response = $this->actingAs($user)
            ->post('/procedimientos', []);

        $response->assertSessionHasErrors([
            'nombre_procedimiento',
            'num_procedimiento',
            'monto_maximo',
        ]);
    }
}