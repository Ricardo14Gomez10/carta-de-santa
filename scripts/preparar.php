<?php

chdir(dirname(__DIR__));
if (!is_file('.env')) {
    copy('.env.example', '.env');
}
$config = file_get_contents('.env');
foreach (['APP_KEY', 'TOKEN_ELFO_JEFE', 'TOKEN_ELFO'] as $nombre) {
    $patron = '/^'.preg_quote($nombre, '/').'=[ \t]*\r?$/m';
    if (!preg_match('/^'.$nombre.'=/m', $config)) {
        $config .= "\n".$nombre."=\n";
    }
    if (preg_match($patron, $config)) {
        $valor = $nombre === 'APP_KEY' ? 'base64:'.base64_encode(random_bytes(32)) : bin2hex(random_bytes(32));
        $config = preg_replace($patron, $nombre.'='.$valor, $config);
    }
}
file_put_contents('.env', $config, LOCK_EX);
if (!is_file('database/database.sqlite')) {
    touch('database/database.sqlite');
}
echo "Entorno local preparado. Los tokens de prueba están en .env.\n";
