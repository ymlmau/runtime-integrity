# 11 — QA de release

Checklist recomendado antes de declarar una versión lista:

## Paquete

```text
[ ] lint PHP
[ ] Composer package/install
[ ] Yii bootstrap registrado
[ ] doctor OK
```

## Identidad

```text
[ ] UUID persiste entre requests
[ ] UUID persiste al reinstalar vendor
[ ] migración legítima conserva UUID
[ ] cambio de entorno no regenera UUID
```

## Baseline

```text
[ ] firma válida
[ ] CLEAN
[ ] MODIFIED
[ ] ADDED
[ ] DELETED
[ ] restauración vuelve a CLEAN
[ ] exclusiones operativas no generan incidencia
```

## Pulse

Ejecutar `tests/pulse.php` en los sistemas objetivo.

Debe cubrir:

```text
[ ] transportes deshabilitados
[ ] jitter normal 6.5–7.5 días
[ ] flock/concurrencia
[ ] fallo de transporte fail-open
[ ] retry 12–24 h
[ ] pending_event
[ ] incidente y recuperación
```

## Despliegue cruzado

```text
[ ] Windows doctor/verify
[ ] Linux doctor/verify
[ ] composer.lock idéntico al autorizado
[ ] misma identidad lógica
[ ] baseline aceptado en ambos
```

## Release 1.1.3 validada

La validación de referencia realizada el 2026-08-30 confirmó el comportamiento anterior sobre PHP 7.4.33 en Windows y Linux, incluyendo pulse real vía bootstrap Yii.
