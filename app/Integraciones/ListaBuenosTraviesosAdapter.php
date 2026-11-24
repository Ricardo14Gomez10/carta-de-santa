<?php

namespace App\Integraciones;

use DOMDocument;
use DOMXPath;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ListaBuenosTraviesosAdapter
{
    private const SOAP = 'http://schemas.xmlsoap.org/soap/envelope/';
    private const LISTA = 'http://polonorte.beatlech/lista';

    public function consultar(string $nombre, string $ciudad): ?string
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $sobre = $xml->appendChild($xml->createElementNS(self::SOAP, 'soapenv:Envelope'));
        $cuerpo = $sobre->appendChild($xml->createElementNS(self::SOAP, 'soapenv:Body'));
        $consulta = $cuerpo->appendChild($xml->createElementNS(self::LISTA, 'pn:ConsultarComportamientoRequest'));
        foreach (['nombre' => $nombre, 'ciudad' => $ciudad] as $campo => $valor) {
            $consulta->appendChild($xml->createElementNS(self::LISTA, 'pn:'.$campo))
                ->appendChild($xml->createTextNode($valor));
        }

        // Ambos intentos y la pausa comparten el presupuesto de tres segundos.
        $limite = microtime(true) + 3;
        for ($intento = 1; $intento <= 2; $intento++) {
            $tiempo = $limite - microtime(true);
            if ($tiempo <= 0) {
                break;
            }
            try {
                $respuesta = Http::withOptions(['timeout' => $tiempo, 'connect_timeout' => $tiempo, 'allow_redirects' => false])
                    ->withHeaders(['SOAPAction' => '"ConsultarComportamiento"'])
                    ->withBody($xml->saveXML(), 'text/xml; charset=utf-8')
                    ->post(config('polo.lista'));
                if ($respuesta->successful()) {
                    $resultado = $this->leerResultado($respuesta->body());
                    if ($resultado !== null) {
                        return $resultado;
                    }
                }
            } catch (ConnectionException) {
                // El servicio de cartas decide qué hacer con una consulta sin resultado.
            }
            if ($intento === 1 && $limite - microtime(true) > 0.1) {
                usleep(100000);
            }
        }
        return null;
    }

    private function leerResultado(string $contenido): ?string
    {
        $doc = new DOMDocument();
        if ($contenido === '' || !@$doc->loadXML($contenido, LIBXML_NONET) || $doc->doctype) {
            return null;
        }
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('s', self::SOAP);
        $xpath->registerNamespace('pn', self::LISTA);
        $body = '/s:Envelope/s:Body';
        if ($xpath->query($body.'/s:Fault')->length) {
            return null;
        }
        $resultados = $xpath->query($body.'//pn:resultado');
        $valor = $resultados->length === 1 ? trim($resultados->item(0)->textContent) : '';
        return in_array($valor, ['BUENO', 'TRAVIESO'], true) ? $valor : null;
    }
}
