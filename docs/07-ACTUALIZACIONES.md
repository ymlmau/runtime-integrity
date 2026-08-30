# 07 — Actualizaciones

## Actualizar Runtime Integrity

Composer reemplaza:

```text
vendor/ymlmau/runtime-integrity
```

pero conserva:

```text
.runtime-integrity
```

Por tanto permanecen el `installation_id`, configuración runtime y estado mínimo.

La política `manifest.include/exclude` sí se refresca desde la versión instalada del monitor y sus overrides explícitos en `composer.json`.

## Migración desde schema 1

Runtime Integrity 1.1.x preserva `installation_id`, elimina `identity.auth` obsoleto, descarta un `pending_event` firmado antiguo si existía y actualiza el schema a 2. No genera un UUID nuevo.

## Actualizar el aplicativo

1. terminar cambios autorizados;
2. resolver Composer en el entorno de build;
3. fijar `composer.lock`;
4. generar un nuevo `build_id` y baseline firmado;
5. verificar `CLEAN`;
6. desplegar código + `composer.json` + `composer.lock` + baseline;
7. conservar `.runtime-integrity` si es la misma instalación lógica;
8. ejecutar `composer install` en destino;
9. ejecutar `doctor` y `verify --details`.

No regenere el baseline en el servidor cliente para “aceptar” diferencias encontradas allí.
