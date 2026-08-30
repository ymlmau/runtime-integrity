# 05 — Doctor, verify y prueba inicial

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
CRYPTO_VERIFY_OK ed25519
BASELINE_OK 2026.08.30.1
DOCTOR_OK
```

`CRYPTO_VERIFY_OK` significa que este runtime puede **verificar** el baseline. No intenta generar claves de instalación.

## 2. `verify`

```bash
vendor/bin/runtime-integrity verify --root=RUTA_DEL_APLICATIVO
```

Correcto:

```text
CLEAN
```

Con cambio protegido:

```text
MODIFIED
MODIFIED_COUNT 1
ADDED_COUNT 0
DELETED_COUNT 0
```

## 3. Prueba controlada

1. confirma `CLEAN`;
2. modifica temporalmente un archivo protegido en una copia de prueba;
3. confirma `MODIFIED`;
4. restaura exactamente el archivo;
5. confirma `CLEAN`.

## 4. Reinstalar vendor no cambia identidad

En QA:

1. guarda el `installation_id`;
2. reinstala `vendor` por Composer;
3. arranca Yii;
4. confirma el mismo UUID.

## 5. Códigos importantes

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
