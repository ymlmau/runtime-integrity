# 03 — Clave del desarrollador y baseline autorizado

## 1. Una sola pareja de claves

No existe una pareja de claves por cliente o instalación. El desarrollador conserva una pareja para autorizar builds. El `product_id` forma parte del baseline y debe coincidir con el producto configurado.

## 2. Generar la clave del desarrollador una sola vez

Después de instalar Runtime Integrity:

### Windows

```bat
php vendor\ymlmau\runtime-integrity\examples\generate-developer-keys.php C:\runtime-keys
```

### Linux

```bash
php vendor/ymlmau/runtime-integrity/examples/generate-developer-keys.php /secure/runtime-keys
```

Se crean:

```text
developer-private.key
developer-public.key
developer-algorithm.txt
```

El helper se niega a sobrescribir claves existentes.

### Regla crítica

`developer-private.key` NO debe estar dentro del aplicativo, `vendor`, repositorio público/cliente, ZIP de entrega ni servidor cliente.

## 3. Configuración inicial

Ejemplo dentro de `composer.json → extra` del aplicativo:

```json
"ymlmau-runtime-integrity": {
  "product_id": "avaluos",
  "developer_auth": {
    "algorithm": "rsa-sha256",
    "public_key": "CLAVE_PUBLICA"
  },
  "email": {"enabled": false, "relay_url": null},
  "api": {"enabled": false, "url": null},
  "privacy": {"include_hostname": false}
}
```

Puede usarse `ed25519` cuando Sodium está disponible o `rsa-sha256` mediante OpenSSL.

## 4. `product_id` debe ser estable

Correcto:

```json
"product_id": "portuflow-app"
```

No uses IP, hostname, cliente o número de servidor como identidad del producto.

## 5. Generar baseline

Hazlo cuando la distribución esté exactamente como será autorizada: código terminado, `composer.json` y `composer.lock` definitivos.

Windows:

```bat
vendor\bin\runtime-integrity build ^
  --root=C:\proyectos\avaluos ^
  --product=avaluos ^
  --build=2026.08.30.1 ^
  --algorithm=rsa-sha256 ^
  --private-key=C:\runtime-keys\developer-private.key ^
  --public-key=C:\runtime-keys\developer-public.key
```

Linux:

```bash
vendor/bin/runtime-integrity build \
  --root=/var/build/avaluos \
  --product=avaluos \
  --build=2026.08.30.1 \
  --algorithm=rsa-sha256 \
  --private-key=/secure/runtime-keys/developer-private.key \
  --public-key=/secure/runtime-keys/developer-public.key
```

Resultado aproximado:

```text
BASELINE_WRITTEN .../.runtime-integrity.baseline
PRODUCT avaluos
BUILD 2026.08.30.1
FILES ...
ROOT_HASH sha256:...
```

## 6. Qué entra por defecto

Cuando existen, se protegen archivos raíz relevantes y directorios de código habituales, por ejemplo:

```text
composer.json
composer.lock
yii
yii.bat
assets/
common/
frontend/
backend/
console/
controllers/
models/
components/
helpers/
services/
modules/
views/
widgets/
commands/
config/
web/
```

La política excluye contenido generado u operativo, entre otros:

```text
vendor/
runtime/
web/assets/
web/debug/
frontend/runtime/
backend/runtime/
frontend/web/assets/
backend/web/assets/
frontend/web/debug/
backend/web/debug/
uploads/
cache/
logs/
sessions/
.git/
node_modules/
.env
*.log
*.tmp
.runtime-integrity*
```

`assets/` de raíz NO se excluye por defecto porque en Yii2 Basic suele contener código fuente como `AppAsset.php`.

`migrations/` no forma parte del conjunto protegido por defecto; un producto puede omitirlo deliberadamente del despliegue de producción. Si un producto decide protegerlo, debe declararlo explícitamente como override de manifiesto.

## 7. Regla de despliegue

El baseline representa bytes autorizados. Si se genera en Windows y se despliega en Linux, los archivos protegidos deben conservar sus bytes. No regenere `composer.lock` en el servidor destino y no utilice `composer update` allí para reconstruir una entrega autorizada.

## 8. El baseline no es Git

No guarda versiones anteriores, diffs, commits ni copias del código. Una actualización autorizada reemplaza `.runtime-integrity.baseline`.
