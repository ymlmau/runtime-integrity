# 08 — Problemas comunes

## Composer no encuentra `ymlmau/runtime-integrity`

La ruta `path` debe apuntar a la carpeta que contiene el `composer.json` del paquete.

## Composer instaló el paquete pero Yii no lo ejecuta

Ejecuta `doctor` y revisa `YII_EXTENSION_NOT_REGISTERED`. No edites `vendor/yiisoft/extensions.php` manualmente.

## `BASELINE_PRODUCT_MISMATCH`

El `product_id` configurado y el del baseline son distintos. Genera el baseline correcto; no cambies hashes manualmente.

## `BASELINE_INVALID`

Posibles causas:

- clave pública incorrecta;
- baseline firmado con otra privada;
- baseline corrupto/modificado;
- algoritmo distinto;
- runtime sin capacidad para verificar ese algoritmo.

## `MODIFIED` inmediatamente después del build

Probablemente cambió un archivo protegido después de generar el baseline. El baseline debe corresponder exactamente a la distribución autorizada.

## API/correo caído

No debe detener Yii. Runtime Integrity reintenta según su política.

## `.runtime-integrity` desapareció

No regeneres intencionalmente una identidad de producción sin investigar. Si el archivo falta, el siguiente auto-setup puede generar un UUID nuevo y romper continuidad histórica.

## Problemas al generar la CLAVE DEL DESARROLLADOR con OpenSSL

Esto solo ocurre en el entorno donde preparas builds; las instalaciones de clientes no generan claves.

Runtime Integrity prefiere Ed25519/Sodium. Si no está disponible, puede usar RSA-SHA256/OpenSSL.

Para el fallback OpenSSL busca configuración en variables del entorno, ubicaciones habituales junto a PHP, rutas comunes Unix (`/etc/ssl/openssl.cnf`, `/usr/lib/ssl/openssl.cnf`) y finalmente el `resources/openssl.cnf` incluido con el paquete.

El runtime del cliente solo necesita poder verificar el algoritmo que elegiste para el baseline.
