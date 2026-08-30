# 06 — Distribución con Composer

## Desarrollo local

Puede usarse un repositorio `path` para trabajar sin publicar cada cambio:

```json
{
  "type": "path",
  "url": "C:/packages/runtime-integrity",
  "options": {"symlink": false}
}
```

## Distribución remota

Runtime Integrity se distribuye desde su repositorio público etiquetado. El aplicativo fija una versión concreta, por ejemplo:

```json
"ymlmau/runtime-integrity": "1.1.3"
```

Composer puede descargar la distribución `dist` por HTTPS sin Git, SSH ni credenciales del repositorio en el servidor destino.

## No copiar manualmente en vendor

Composer puede reconstruir `vendor` en cualquier momento. Una carpeta copiada manualmente puede desaparecer al ejecutar `composer install` o `composer update` y no quedaría correctamente fijada en `composer.lock`.

## `composer.lock` es parte de la entrega autorizada

En despliegue:

```text
composer.json
composer.lock
.runtime-integrity.baseline
```

deben corresponder al mismo build autorizado.

En el servidor destino usa:

```bash
composer install
```

No uses `composer update` para reconstruir una entrega ya autorizada, porque puede resolver versiones diferentes y producir otro `composer.lock`.

La decisión de instalar o no dependencias `require-dev` pertenece a la política de despliegue del aplicativo; si el build autorizado exige conservarlas, use `composer install` sin `--no-dev`.

## Disponibilidad a largo plazo

Conserva fuera del servidor cliente:

```text
- tags/releases distribuidos
- composer.lock de cada entrega
- artefactos ZIP/TAR de despliegue
- clave privada del desarrollador
```

El repositorio remoto no debe ser el único respaldo histórico.
