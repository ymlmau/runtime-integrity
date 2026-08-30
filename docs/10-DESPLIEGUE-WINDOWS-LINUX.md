# 10 — Despliegue Windows ↔ Linux

## Objetivo

Una migración legítima de la misma instancia debe conservar:

```text
installation_id
```

y puede producir un `environment_fingerprint` diferente.

## Archivos que deben viajar para la misma instancia

Además del código del aplicativo y sus artefactos normales:

```text
.runtime-integrity
.runtime-integrity.baseline
composer.json
composer.lock
```

No copie la clave privada del desarrollador al servidor destino.

## Preservar bytes autorizados

El baseline usa hashes SHA-256 de los archivos protegidos. Evite procesos de transferencia que reescriban archivos o normalicen contenido inesperadamente.

Para `composer.lock`, compare antes/después:

Windows:

```bat
php -r "echo hash_file('sha256','composer.lock'),PHP_EOL;"
```

Linux:

```bash
sha256sum composer.lock
```

Los hashes deben coincidir con el baseline.

## Composer en destino

Use:

```bash
composer install
```

Esto instala las versiones fijadas por `composer.lock`. No use `composer update` para un despliegue ya autorizado.

Si la política del aplicativo exige conservar dependencias de desarrollo, no use `--no-dev`.

## Validación final

```bash
vendor/bin/runtime-integrity doctor --root="$(pwd)"
vendor/bin/runtime-integrity verify --root="$(pwd)" --details
```

Esperado:

```text
DOCTOR_OK
CLEAN
```

El mismo `installation_id` debe aparecer en Windows y Linux si representa la misma instancia lógica.
