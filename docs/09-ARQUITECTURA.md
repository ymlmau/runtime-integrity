# 09 — Resumen de arquitectura v1.1

## Empaquetado

- Composer `type: yii2-extension`.
- Bootstrap automático por Yii2.
- Sin tocar controllers/models/views del aplicativo.

## Pulse

- Sin cron obligatorio.
- Aproximadamente cada 7 días de actividad con jitter.
- `flock()` para evitar trabajo simultáneo.
- Reintentos acotados.
- Fail-open.

## Identidad

```text
product_id
→ producto

installation_id
→ UUID lógico persistente

environment_fingerprint
→ observación del entorno
```

No existe certificado, private key ni public key por instalación.

Un clon completo puede conservar el UUID; dos fingerprints de entorno diferentes para la misma identidad son una señal útil para análisis posterior.

## Persistencia

Un único archivo runtime:

```text
.runtime-integrity
```

con identity/config/baseline summary/state.

El baseline autorizado vigente es:

```text
.runtime-integrity.baseline
```

## Integridad

- SHA-256.
- CLEAN / MODIFIED / BASELINE_MISSING / BASELINE_INVALID / CHECK_ERROR.
- no guarda versiones ni diffs.
- no reconstruye Git.

## Firma del baseline

Esta es la única identidad criptográfica necesaria en v1.1:

```text
clave privada del desarrollador
→ firma build autorizado

clave pública distribuida
→ verifica baseline
```

La clave privada nunca se distribuye.

## Reportes

Los eventos incluyen `event_id`, timestamp, `product_id`, `installation_id`, `environment_fingerprint`, build y estado de integridad.

No están firmados por una clave de instalación en v1.1. La autenticación de una futura API se diseñará cuando exista esa API y sus requisitos concretos.

Correo relay y API usan el mismo evento JSON. No se envía contenido de archivos.

## Portabilidad

El diseño apunta a PHP 7.4+/Yii2, no a un sistema operativo concreto. Debe funcionar de la misma manera en Windows y Linux; solo cambian rutas/permisos propios del entorno.

## Límites

Un administrador con control total del servidor puede copiar o modificar código. Runtime Integrity busca trazabilidad y detección razonable, no DRM ni persistencia encubierta.
