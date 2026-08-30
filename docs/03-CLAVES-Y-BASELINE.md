# 03 — Clave del desarrollador y baseline autorizado

## 1. Esta es la única pareja de claves del diseño v1.1

No existe una pareja de claves por cliente o instalación. Genera una pareja de firma controlada por ti para autorizar builds. El `product_id` está incluido en el baseline y Runtime Integrity verifica que coincida con el producto configurado.

## 2. Generar la clave del desarrollador una sola vez

Después de instalar Runtime Integrity mediante Composer, desde la raíz del aplicativo puedes usar el helper documental incluido:

### Windows

```bat
php vendor\ymlmau\runtime-integrity\examples\generate-developer-keys.php C:\runtime-keys
```

### Linux

```bash
php vendor/ymlmau/runtime-integrity/examples/generate-developer-keys.php /secure/runtime-keys
```

Se crearán:

```text
developer-private.key
developer-public.key
developer-algorithm.txt
```

El script se niega a sobrescribir claves existentes.

### Regla crítica

```text
developer-private.key
```

NO debe estar dentro del aplicativo, `vendor`, repositorio del cliente, ZIP de entrega ni servidor del cliente.

Guárdala fuera del paquete de distribución y con respaldo seguro.

## 3. Leer el algoritmo y la clave pública

Ejemplo:

```text
developer-algorithm.txt
ed25519
```

`developer-public.key` contiene una cadena de una sola línea cuando se usa Ed25519.

## 4. Sembrar la configuración inicial en el composer.json DEL APLICATIVO

Agrega dentro de `extra`:

```json
"extra": {
    "ymlmau-runtime-integrity": {
        "product_id": "avaluos",
        "developer_auth": {
            "algorithm": "ed25519",
            "public_key": "PEGA_AQUI_EL_CONTENIDO_DE_DEVELOPER_PUBLIC.KEY"
        },
        "email": {
            "enabled": false,
            "relay_url": null
        },
        "api": {
            "enabled": false,
            "url": null
        },
        "privacy": {
            "include_hostname": false
        }
    }
}
```

Si `extra` ya contiene otras configuraciones, no las borres. Agrega la clave `ymlmau-runtime-integrity` dentro del objeto `extra` existente.

## 5. `product_id` debe ser estable

Ejemplo:

```json
"product_id": "avaluos"
```

No uses:

```text
portuflow-cliente-a
portuflow-servidor-2
192.168.1.50
```

La instalación y el entorno tienen identificadores diferentes.

## 6. Generar el baseline

El baseline se genera cuando la distribución está EXACTAMENTE como será autorizada.

Antes de ejecutarlo termina tus cambios de código y Composer.

Ejemplo Windows:

```bat
vendor\bin\runtime-integrity build ^
  --root=C:\proyectos\avaluos ^
  --product=avaluos ^
  --build=2026.08.30.1 ^
  --algorithm=ed25519 ^
  --private-key=C:\runtime-keys\developer-private.key ^
  --public-key=C:\runtime-keys\developer-public.key
```

En Linux:

```bash
vendor/bin/runtime-integrity build \
  --root=/var/build/avaluos \
  --product=avaluos \
  --build=2026.08.30.1 \
  --algorithm=ed25519 \
  --private-key=/secure/runtime-keys/developer-private.key \
  --public-key=/secure/runtime-keys/developer-public.key
```

El comando comprueba opcionalmente que la clave privada coincida con la pública antes de escribir el baseline.

Resultado esperado:

```text
BASELINE_WRITTEN .../.runtime-integrity.baseline
PRODUCT avaluos
BUILD 2026.08.30.1
FILES ...
ROOT_HASH sha256:...
```

## 7. Qué entra por defecto en el baseline

Incluye cuando existen:

```text
composer.json
composer.lock
yii
yii.bat
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

Excluye contenido operativo como:

```text
vendor/
runtime/
assets/
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

Estas listas pueden ajustarse por producto, pero no agregues exclusiones únicamente para ocultar una incidencia.

## 8. El baseline no es Git

Contiene hashes necesarios para comparar la distribución autorizada. No guarda versiones anteriores, diffs, commits ni copias del código fuente.

Una actualización autorizada reemplaza `.runtime-integrity.baseline`.
