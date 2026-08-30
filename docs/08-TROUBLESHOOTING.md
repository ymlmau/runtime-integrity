# 08 — Problemas comunes

## Composer no encuentra `ymlmau/runtime-integrity`

Si usas desarrollo `path`, la ruta debe apuntar a la carpeta que contiene el `composer.json` del paquete. Para distribución pública, confirma conectividad HTTPS y la versión requerida.

## Composer instaló el paquete pero Yii no lo ejecuta

Ejecuta `doctor`. No edites `vendor/yiisoft/extensions.php` manualmente.

## `BASELINE_PRODUCT_MISMATCH`

El `product_id` configurado y el firmado en el baseline son distintos. Genera/despliega el baseline correcto; no cambies hashes manualmente.

## `BASELINE_INVALID`

Posibles causas:

- clave pública incorrecta;
- baseline firmado con otra privada;
- baseline corrupto/modificado;
- algoritmo distinto;
- runtime sin capacidad para verificar ese algoritmo.

## `MODIFIED` inmediatamente después del build

Ejecuta:

```bash
vendor/bin/runtime-integrity verify --root=RUTA --details
```

Un archivo protegido pudo cambiar o desaparecer entre `build` y `verify`, o la política de manifiesto pudo haber cambiado con una actualización del monitor.

## Windows CLEAN / Linux MODIFIED en `composer.lock`

Compare los hashes SHA-256 y tamaños del archivo en ambos sistemas. Si son distintos, copie exactamente el `composer.lock` autorizado desde el entorno de build y ejecute `composer install` en Linux.

No use `composer update` en destino para intentar “arreglarlo”. Runtime Integrity debe detectar un lock distinto aunque la aplicación aparentemente funcione.

## `web/assets`, `web/debug` o `runtime` producen cambios

En 1.1.3 estas rutas generadas están excluidas por política. `assets/` de raíz, en cambio, puede contener código fuente y está protegido.

## `.runtime-integrity` desapareció

No regenere intencionalmente una identidad de producción sin investigar. Si Yii arranca sin ese archivo, el auto-setup puede crear un UUID nuevo y romper continuidad histórica.

## Migración legítima a otro servidor

Si es la misma instalación lógica, copie `.runtime-integrity` y `.runtime-integrity.baseline`. El UUID debe mantenerse; el fingerprint del entorno puede cambiar.

## API/correo caído

No debe detener Yii. El fallo de transporte programa retry 12–24 h y permanece fail-open.

## OpenSSL al generar clave del desarrollador

Esto solo aplica al entorno de build. Runtime Integrity prefiere Ed25519/Sodium cuando está disponible y soporta RSA-SHA256/OpenSSL. El cliente solo necesita verificar el algoritmo elegido.
