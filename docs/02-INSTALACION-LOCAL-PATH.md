# 02 — Primera instalación local usando Composer `path`

Este método sirve para desarrollar o probar Runtime Integrity antes de consumirlo desde su repositorio remoto. Composer administra `vendor`; no copies archivos manualmente allí.

## Windows

Ejemplo:

```text
C:\packages\runtime-integrity\composer.json
C:\proyectos\mi-aplicativo\composer.json
```

## Linux

Ejemplo:

```text
/opt/packages/runtime-integrity/composer.json
/var/www/mi-aplicativo/composer.json
```

## Paso 1 — Descomprimir fuera del aplicativo

La carpeta del paquete debe contener directamente:

```text
composer.json
src/
bin/
```

## Paso 2 — Agregar el repositorio `path` al composer.json DEL APLICATIVO

Windows:

```json
{
  "type": "path",
  "url": "C:/packages/runtime-integrity",
  "options": {"symlink": false}
}
```

Linux:

```json
{
  "type": "path",
  "url": "/opt/packages/runtime-integrity",
  "options": {"symlink": false}
}
```

Si ya existen otros repositorios, consérvalos.

## Paso 3 — Agregar la dependencia

Dentro del `require` existente:

```json
"ymlmau/runtime-integrity": "1.1.3"
```

## Paso 4 — Permitir el plugin oficial Yii2

```json
"config": {
  "allow-plugins": {
    "yiisoft/yii2-composer": true
  }
}
```

Si ya existe, no lo dupliques.

## Paso 5 — Instalar/actualizar Runtime Integrity

Desde la raíz del aplicativo:

```bash
composer update ymlmau/runtime-integrity --with-dependencies
```

Composer debe crear:

```text
vendor/ymlmau/runtime-integrity/
vendor/bin/runtime-integrity
```

## Paso 6 — Confirmar bootstrap

Composer/Yii2 genera:

```text
vendor/yiisoft/extensions.php
```

Runtime Integrity debe aparecer registrado allí mediante su bootstrap. No edites ese archivo manualmente.

## Paso 7 — Migrar a distribución remota

Cuando terminen las pruebas locales, elimina el repositorio `path` del aplicativo y consume la release pública/remota. Después ejecuta una actualización dirigida a Runtime Integrity y confirma con:

```bash
composer show ymlmau/runtime-integrity
```

La fuente ya no debe apuntar a la carpeta local.
