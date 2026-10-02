<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Interaccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role = 'admin'): User
    {
        $user = User::factory()->create();
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function client(array $data = []): Cliente
    {
        return Cliente::create([...['nombre' => 'Henry Test', 'correo' => 'henry@example.com', 'estado' => 'activo', 'etapa_crm' => 'Prospecto'], ...$data]);
    }

    private function payload(): array
    {
        return ['nombre' => 'Cliente nuevo', 'correo' => 'nuevo@example.com', 'telefono' => '+52 449 123 4567', 'empresa' => 'HF', 'estado' => 'activo', 'etapa_crm' => 'Prospecto'];
    }

    public function test_guest_and_customer_cannot_access_crm(): void
    {
        $this->getJson('/clientes')->assertUnauthorized();
        $this->actingAs(User::factory()->create())->getJson('/clientes')->assertForbidden();
        $this->getJson('/crm')->assertForbidden();
    }

    public function test_client_crud_filters_stage_and_delete_permissions(): void
    {
        $user = $this->staff('usuario');
        $this->actingAs($user);
        $id = $this->postJson('/clientes', $this->payload())->assertCreated()->json('id');
        $this->getJson('/clientes/'.$id)->assertOk()->assertJsonPath('nombre', 'Cliente nuevo');
        $this->putJson('/clientes/'.$id, [...$this->payload(), 'nombre' => 'Nombre actualizado'])->assertOk();
        $this->putJson('/clientes/'.$id.'/etapa', ['etapa_crm' => 'Frecuente'])->assertOk();
        $this->getJson('/clientes?q=actualizado&estado=activo&etapa=Frecuente')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/clientes?estado=inactivo')->assertJsonPath('total', 0);
        $this->deleteJson('/clientes/'.$id)->assertForbidden();
        $this->actingAs($this->staff())->deleteJson('/clientes/'.$id)->assertNoContent();
        $this->assertDatabaseMissing('clientes', ['id' => $id]);
    }

    public function test_validation_duplicate_email_and_missing_client(): void
    {
        $this->actingAs($this->staff());
        $this->client();
        $this->postJson('/clientes', [...$this->payload(), 'correo' => 'henry@example.com'])->assertUnprocessable()->assertJsonValidationErrors('correo');
        $this->postJson('/clientes', [...$this->payload(), 'estado' => 'otro', 'etapa_crm' => 'VIP'])->assertUnprocessable()->assertJsonValidationErrors(['estado', 'etapa_crm']);
        $this->getJson('/clientes/9999')->assertNotFound();
        $this->postJson('/interacciones', ['cliente_id' => 9999, 'tipo' => 'llamada', 'descripcion' => 'Llamada inicial', 'fecha' => now()->toISOString()])->assertUnprocessable();
    }

    public function test_interaction_responsible_is_session_user_and_activity_is_private(): void
    {
        $user = $this->staff('usuario');
        $other = $this->staff();
        $client = $this->client();
        $this->actingAs($user);
        $id = $this->postJson('/interacciones', ['cliente_id' => $client->id, 'tipo' => 'reunión', 'descripcion' => 'Reunión inicial', 'fecha' => now()->subMinute()->toISOString(), 'usuario_id' => $other->id])->assertCreated()->assertJsonPath('usuario_id', $user->id)->json('id');
        $this->getJson('/clientes/'.$client->id.'/interacciones')->assertJsonPath('0.id', $id)->assertJsonPath('0.usuario.name', $user->name);
        $this->getJson('/mi-actividad')->assertJsonPath('total', 1);
        $this->actingAs($other)->getJson('/mi-actividad')->assertJsonPath('total', 0);
        $this->postJson('/interacciones', ['cliente_id' => $client->id, 'tipo' => 'correo', 'descripcion' => 'Correo futuro', 'fecha' => now()->addDay()->toISOString()])->assertUnprocessable();
    }

    public function test_metrics_risk_and_evaluations_use_real_data(): void
    {
        $this->travelTo(now()->startOfSecond());
        $user = $this->staff();
        $this->actingAs($user);
        $risk = $this->client();
        $recent = $this->client(['correo' => 'recent@example.com']);
        $inactive = $this->client(['correo' => 'inactive@example.com', 'estado' => 'inactivo']);
        Interaccion::create(['cliente_id' => $recent->id, 'usuario_id' => $user->id, 'tipo' => 'correo', 'descripcion' => 'Contacto reciente', 'fecha' => now()->subDays(2)]);
        $this->getJson('/metricas')->assertOk()->assertJsonPath('total', 3)->assertJsonPath('activos', 2)->assertJsonPath('inactivos', 1)->assertJsonPath('interacciones', 1)->assertJsonCount(1, 'clientes_en_riesgo')->assertJsonPath('clientes_en_riesgo.0.id', $risk->id);
        $this->postJson('/clientes/'.$risk->id.'/evaluaciones', ['puntuacion' => 5, 'observaciones' => 'Buena relación'])->assertCreated();
        $this->getJson('/clientes/'.$risk->id)->assertJsonPath('evaluaciones.0.puntuacion', 5);
        $this->postJson('/clientes/'.$risk->id.'/evaluaciones', ['puntuacion' => 6])->assertUnprocessable();
        Interaccion::create(['cliente_id' => $risk->id, 'usuario_id' => $user->id, 'tipo' => 'llamada', 'descripcion' => 'Contacto antiguo', 'fecha' => now()->subDays(31)]);
        $this->getJson('/metricas')->assertJsonCount(1, 'clientes_en_riesgo');
        $this->deleteJson('/clientes/'.$risk->id)->assertNoContent();
        $this->assertDatabaseMissing('interacciones', ['cliente_id' => $risk->id]);
        $this->assertDatabaseMissing('evaluaciones', ['cliente_id' => $risk->id]);
    }

    public function test_public_registration_cannot_escalate_role_and_real_login_works(): void
    {
        $this->postJson('/registro', ['name' => 'Cliente registrado', 'email' => 'TEST@example.com', 'password' => 'ValidPassword123!', 'role' => 'admin'])->assertCreated()->assertJsonPath('user.role', 'cliente');
        $this->assertDatabaseHas('clientes', ['correo' => 'test@example.com']);
        $this->postJson('/logout')->assertOk();
        $this->postJson('/login', ['email' => 'test@example.com', 'password' => 'incorrecta', 'role' => 'admin'])->assertUnprocessable();
        $this->postJson('/login', ['email' => 'test@example.com', 'password' => 'ValidPassword123!', 'role' => 'admin'])->assertOk()->assertJsonPath('user.role', 'cliente');
        $this->getJson('/usuarios')->assertForbidden();
    }

    public function test_only_admin_can_create_staff(): void
    {
        $this->actingAs($this->staff('usuario'))->postJson('/usuarios', ['name' => 'Operador', 'email' => 'op@example.com', 'password' => 'ValidPassword123!', 'role' => 'usuario'])->assertForbidden();
        $this->actingAs($this->staff())->postJson('/usuarios', ['name' => 'Operador', 'email' => 'op@example.com', 'password' => 'ValidPassword123!', 'role' => 'usuario'])->assertCreated()->assertJsonPath('role', 'usuario');
    }

    public function test_contact_is_persisted_and_convertible_once(): void
    {
        $this->postJson('/tienda/contacto', ['name' => 'Consulta HF', 'email' => 'contact@example.com', 'message' => 'Información sobre envíos'])->assertOk();
        $this->actingAs($this->staff('usuario'));
        $id = $this->getJson('/contactos')->assertOk()->json('data.0.id');
        $this->postJson('/contactos/'.$id.'/registrar')->assertOk();
        $this->assertDatabaseHas('clientes', ['correo' => 'contact@example.com']);
        $this->assertDatabaseCount('interacciones',1);
        $this->postJson('/contactos/'.$id.'/registrar')->assertConflict();
        $this->assertDatabaseCount('interacciones',1);
    }
}
