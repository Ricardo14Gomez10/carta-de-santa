<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CartasTest extends TestCase
{
    use RefreshDatabase;

    private array $datos = ['remitente' => 'Valentina Pérez', 'edad' => 9, 'ciudad' => 'Guayaquil', 'regalos' => ['Libro']];

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-12-24 23:59:59', 'America/Guayaquil'));
        config(['polo.jefe' => bin2hex(random_bytes(16)), 'polo.elfo' => bin2hex(random_bytes(16))]);
        Http::preventStrayRequests();
    }

    public function test_cierre_segun_el_calendario_de_guayaquil(): void
    {
        $this->postJson('/api/cartas', $this->datos)->assertCreated()->assertJsonPath('fechaRecepcion', '2026-12-24');
        $this->travel(1)->seconds();
        $this->postJson('/api/cartas', $this->datos)->assertUnprocessable()->assertJsonPath('codigo', 'RECEPCION_CERRADA');
        $this->assertDatabaseCount('cartas', 1);
    }

    public function test_fallo_de_conexion_permite_reintentar(): void
    {
        Http::fake(['*' => Http::failedConnection()]);
        $id = $this->postJson('/api/cartas', $this->datos)->assertCreated()->json('id');
        $this->withToken(config('polo.jefe'))->patchJson("/api/cartas/$id/estado", ['estado' => 'APROBADA'])
            ->assertStatus(503)->assertJsonStructure(['codigo', 'mensaje', 'detalles', 'timestamp', 'ruta']);
        $this->getJson("/api/cartas/$id")->assertOk()->assertJsonPath('estado', 'RECIBIDA')
            ->assertJsonPath('comportamiento', 'PENDIENTE_VERIFICACION');
    }

    public function test_aprueba_si_la_lista_se_recupera_en_el_segundo_intento(): void
    {
        $respuesta = '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" xmlns:pn="http://polonorte.beatlech/lista">'
            .'<s:Body><pn:ConsultarComportamientoResponse><pn:resultado>BUENO</pn:resultado></pn:ConsultarComportamientoResponse></s:Body></s:Envelope>';
        Http::fakeSequence()->pushStatus(503)->push($respuesta, 200);
        $id = $this->postJson('/api/cartas', $this->datos)->assertCreated()->json('id');
        $this->withToken(config('polo.jefe'))->patchJson("/api/cartas/$id/estado", ['estado' => 'APROBADA'])
            ->assertOk()->assertJsonPath('estado', 'APROBADA')->assertJsonPath('comportamiento', 'BUENO');
        Http::assertSentCount(2);
    }

    public function test_distingue_credenciales_y_protege_los_campos_del_servidor(): void
    {
        $this->deleteJson('/api/cartas/1')->assertUnauthorized();
        $this->withToken(config('polo.elfo'))->deleteJson('/api/cartas/1')->assertForbidden();
        foreach (['edad' => 18, 'regalos' => ['Tren', 'tren'], 'estado' => 'ENTREGADA', 'comportamiento' => 'BUENO'] as $campo => $valor) {
            $this->postJson('/api/cartas', array_replace($this->datos, [$campo => $valor]))->assertUnprocessable();
        }
    }
}
