# 02 — Primera instalación local usando Composer `path`

Este método permite probar Runtime Integrity antes de publicarlo en un repositorio privado. Composer administra `vendor`; nosotros no copiamos archivos manualmente allí.

## Windows

Una estructura posible:

```text
C:\packages\runtime-integrity\composer.json
C:\proyectos\mi-aplicativo\composer.json
```

## Linux

Una estructura posible:

```text
/opt/packages/runtime-integrity/composer.json
/var/www/mi-aplicativo/composer.json
```

## Paso 1 — Descomprimir el paquete fuera del aplicativo

La carpeta indicada por Composer debe contener directamente:

```text
composer.json
src/
bin/
```

## Paso 2 — Modificar el `composer.json` DEL APLICATIVO

Agrega el repositorio local. Windows:

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
"ymlmau/runtime-integrity": "1.1.0"
```

## Paso 4 — Plugin de Yii2

Composer debe permitir el plugin oficial de Yii2:

```json
"config": {
  "allow-plugins": {
    "yiisoft/yii2-composer": true
  }
}
```

Si ya existe, no lo dupliques.

## Paso 5 — Instalar solo Runtime Integrity

Windows o Linux, desde la raíz del aplicativo:

```bash
composer update ymlmau/runtime-integrity --with-dependencies
```

Composer debe crear:

```text
vendor/ymlmau/runtime-integrity/
vendor/bin/runtime-integrity
```

En Windows también puede existir un wrapper `.bat` en `vendor/bin`.

## Paso 6 — Confirmar bootstrap

Composer/Yii2 actualiza automáticamente:

```text
vendor/yiisoft/extensions.php
```

Debe aparecer:

```text
YmlMau\RuntimeIntegrity\Bootstrap
```

No edites ese archivo manualmente.

## Paso 7 — Todavía no necesitas certificados de instalación

No hay ninguna clave que generar por cliente. Solo necesitas tener una clave de desarrollador para firmar el baseline. Continúa con [03 — Clave del desarrollador y baseline](03-CLAVES-Y-BASELINE.md).
