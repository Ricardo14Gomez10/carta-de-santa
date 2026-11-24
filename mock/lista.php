<?php
// Simulador SOAP 1.1 local para la práctica.
$soap = 'http://schemas.xmlsoap.org/soap/envelope/';
$lista = 'http://polonorte.beatlech/lista';
header('Content-Type: text/xml; charset=utf-8');
$xml = file_get_contents('php://input');
$doc = new DOMDocument();
$nombre = $ciudad = '';
if ($xml !== '' && @$doc->loadXML($xml, LIBXML_NONET) && !$doc->doctype) {
    $xpath = new DOMXPath($doc);
    $xpath->registerNamespace('s', $soap);
    $xpath->registerNamespace('pn', $lista);
    $base = '/s:Envelope/s:Body/pn:ConsultarComportamientoRequest';
    $nombre = $xpath->evaluate('string('.$base.'/pn:nombre)');
    $ciudad = $xpath->evaluate('string('.$base.'/pn:ciudad)');
}
if ($nombre === '' || $ciudad === '' || $nombre === 'Grinch') {
    http_response_code(500);
    $texto = $nombre === 'Grinch' ? 'Lista en mantenimiento' : 'Solicitud inválida';
    $cuerpo = "<soap:Fault><faultcode>soap:Server</faultcode><faultstring>$texto</faultstring></soap:Fault>";
} else {
    $resultado = $nombre === 'Travieso' ? 'TRAVIESO' : 'BUENO';
    $cuerpo = "<pn:ConsultarComportamientoResponse><pn:resultado>$resultado</pn:resultado></pn:ConsultarComportamientoResponse>";
}
echo "<soap:Envelope xmlns:soap=\"$soap\" xmlns:pn=\"$lista\"><soap:Body>$cuerpo</soap:Body></soap:Envelope>";
