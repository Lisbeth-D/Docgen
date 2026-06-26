<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_crear_usuario(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'activo' => true,
        ]);

        $response = $this->actingAs($admin)->post('/usuarios', [
            'name' => 'Usuario Prueba',
            'username' => 'uprueba',
            'email' => 'uprueba@lpb.mx',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'comprador',
            'activo' => true,
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('users', [
            'name' => 'Usuario Prueba',
            'username' => 'uprueba',
            'email' => 'uprueba@lpb.mx',
            'role' => 'comprador',
        ]);
    }

    public function test_admin_puede_eliminar_usuario(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'activo' => true,
        ]);

        $usuario = User::factory()->create([
            'name' => 'Usuario a Eliminar',
            'username' => 'ueliminar',
            'email' => 'ueliminar@lpb.mx',
            'role' => 'comprador',
            'activo' => true,
        ]);

        $response = $this->actingAs($admin)->delete('/usuarios/' . $usuario->id);

        $response->assertStatus(302);

        $this->assertDatabaseMissing('users', [
            'id' => $usuario->id,
        ]);
    }
}