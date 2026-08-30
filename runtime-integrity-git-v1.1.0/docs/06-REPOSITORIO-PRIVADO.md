# 06 — De prueba local a repositorio privado

## Etapa 1 — Desarrollo local

Usamos:

```json
{
  "type": "path",
  "url": "C:/packages/runtime-integrity",
  "options": {"symlink": false}
}
```

Esto es ideal para validar el paquete sin publicarlo.

## Etapa 2 — Producción

No recomendamos dejar una ruta local como:

```text
C:/packages/runtime-integrity
```

en el `composer.json` de un aplicativo que luego irá a otro servidor.

Para producción tienes dos opciones razonables.

### Opción A — Repositorio Git/VCS privado

El paquete vive en un repositorio privado y se etiqueta, por ejemplo:

```text
1.1.0
1.1.1
```

El aplicativo declara el repositorio privado y requiere:

```json
"ymlmau/runtime-integrity": "^1.1"
```

Composer descarga e instala el paquete.

### Opción B — Repositorio Composer privado

Puedes usar un servidor/repository manager Composer privado. El aplicativo lo declara como repositorio Composer y las credenciales se configuran fuera del código cuando sea posible.

## ¿Por qué no copiar directamente en vendor?

Porque Composer puede reconstruir `vendor` en cualquier momento.

Una carpeta copiada manualmente puede desaparecer al ejecutar:

```bash
composer install
composer update
```

Además no quedaría correctamente fijada en `composer.lock` ni registrada como extensión Yii2.

## Disponibilidad a largo plazo

La aplicación ya desplegada funciona con el `vendor` entregado y no consulta el repositorio para cada request.

Para recuperación/actualización a largo plazo debes conservar:

```text
- ZIP/tag de cada release del paquete que realmente distribuyas
- composer.lock de la aplicación entregada
- backup de los artefactos de despliegue
- clave privada del desarrollador fuera de los clientes
```

El repositorio remoto facilita mantenimiento, pero no debe ser tu único respaldo histórico.

## Recomendación para nuestro proceso

```text
AHORA
path local + QA

DESPUÉS DE QA
repositorio privado + tag 1.1.0

PRODUCCIÓN
composer.lock fijado + vendor desplegado + baseline firmado
```
