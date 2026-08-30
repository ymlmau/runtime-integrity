# 05 — Doctor, verify y QA

## 1. `doctor`

Después del primer arranque real de Yii:

Windows:

```bat
vendor\bin\runtime-integrity doctor --root=C:\proyectos\mi-aplicativo
```

Linux:

```bash
vendor/bin/runtime-integrity doctor --root=/var/www/mi-aplicativo
```

Resultado sano aproximado:

```text
PHP_OK 7.4.33
YII_BOOTSTRAP_REGISTERED
IDENTITY_OK 6bf1da54-...
PRODUCT_ID_OK avaluos
CRYPTO_VERIFY_OK rsa-sha256
BASELINE_OK 2026.08.30.1
DOCTOR_OK
```

## 2. `verify`

```bash
vendor/bin/runtime-integrity verify --root=RUTA_DEL_APLICATIVO
```

Sano:

```text
CLEAN
```

Con incidencia:

```text
MODIFIED
MODIFIED_COUNT 1
ADDED_COUNT 0
DELETED_COUNT 0
```

Para rutas concretas:

```bash
vendor/bin/runtime-integrity verify --root=RUTA --details
```

## 3. QA mínimo de integridad

En una copia/control de pruebas:

1. confirmar `CLEAN`;
2. modificar un archivo protegido → `MODIFIED_FILE`;
3. restaurarlo exactamente → `CLEAN`;
4. agregar un archivo dentro de una ruta protegida → `ADDED_FILE`;
5. eliminarlo → `CLEAN`;
6. mover temporalmente un archivo protegido → `DELETED_FILE`;
7. restaurarlo → `CLEAN`;
8. crear archivos dentro de exclusiones como `runtime/`, `web/assets/`, `web/debug/`, `*.log` y `config/*-local.php` → debe seguir `CLEAN`.

## 4. QA del pulse incluido en el paquete

Windows:

```bat
php vendor\ymlmau\runtime-integrity\tests\pulse.php
```

Linux:

```bash
php vendor/ymlmau/runtime-integrity/tests/pulse.php
```

Debe terminar:

```text
PULSE PASS
```

Esta prueba cubre transportes deshabilitados, jitter 6.5–7.5 días, `flock()`, retry 12–24 h, fail-open e incidente→recuperación.

## 5. Reinstalar vendor no cambia identidad

Guarda el `installation_id`, reinstala dependencias con Composer, arranca Yii y confirma el mismo UUID. La identidad vive fuera de `vendor`.

## 6. Códigos importantes

```text
STATE_NOT_INITIALIZED
PRODUCT_ID_MISSING
DEVELOPER_AUTH_MISSING
CRYPTO_VERIFY_UNAVAILABLE
BASELINE_MISSING
BASELINE_INVALID
BASELINE_PRODUCT_MISMATCH
YII_EXTENSION_NOT_REGISTERED
EMAIL_CONFIG_INVALID
API_CONFIG_INVALID
HTTP_CLIENT_UNAVAILABLE
```
