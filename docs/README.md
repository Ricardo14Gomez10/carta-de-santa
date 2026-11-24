# Cartas a Santa y gestión de regalos

API REST en Laravel y SQLite para registrar cartas y gestionar su aprobación, producción y entrega.

## Ejecutar

Requisitos del proyecto: PHP 8.5, Composer y las extensiones PDO SQLite, DOM, cURL y mbstring.

```powershell
composer install
php scripts/preparar.php
php artisan migrate
php -S 127.0.0.1:8081 mock/lista.php
```

Deja el simulador abierto y ejecuta Laravel en otra terminal.

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

## Configuración y pruebas

El preparador crea el archivo `.env` y genera la clave de Laravel y los tokens locales. La base usa `DB_CONNECTION=sqlite`. Si usas el servicio SOAP del docente, configura `LISTA_SOAP_URL`, ejecuta `php artisan config:clear` y reinicia Laravel.

Importa la [colección de Postman](../postman/Cartas-a-Santa.postman_collection.json). En sus variables locales, copia `TOKEN_ELFO_JEFE` a `tokenJefe` y `TOKEN_ELFO` a `tokenElfo`. Ejecuta la colección en orden con el simulador activo. Para correr las pruebas de Laravel, usa `php artisan test`.

## Arquitectura

Las rutas reciben peticiones de Postman. El middleware comprueba el rol en PATCH y DELETE. El controlador llama a CartaService, que aplica las reglas, guarda en SQLite mediante Eloquent y consulta la lista mediante el adaptador SOAP. Si falla la consulta, la carta queda recibida y pendiente. El manejador global devuelve el error 503 en JSON.

[Ver el diagrama de arquitectura](arquitectura.png)

## Microservicios

Cartas sería dueño del remitente, la ciudad, los regalos solicitados y el historial de recepción y aprobación. Comportamiento administraría la lista y sus verificaciones. Taller gestionaría las órdenes de producción y Entregas custodiaría los despachos y sus confirmaciones. Cada servicio tendría su propia base y comunicaría sus cambios mediante eventos identificados para evitar duplicados. Si Comportamiento cae el 24 a las 23:00, Cartas seguiría recibiendo y mostrando solicitudes. Una aprobación devolvería 503 y conservaría la carta recibida con verificación pendiente. Al restablecerse la lista, se podría reintentar sin crear otra carta ni enviar dos órdenes de producción.
