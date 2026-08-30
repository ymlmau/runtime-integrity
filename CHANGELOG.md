# Changelog

## 1.1.1 - 2026-08-30

- Fix Yii generated-path exclusions across Windows/Linux deployments.
- Exclude `web/assets`, Yii Advanced runtime directories, and their frontend/backend published asset directories explicitly.
- Protect root `assets/` source code such as Yii AssetBundle classes instead of treating it as generated output.
- Apply filename-only exclusion patterns such as `*.log`, `.DS_Store`, and `Thumbs.db` at any protected depth.
- No change to installation identity, baseline authorization model, or transport behavior.

## 1.1.1 - 2026-08-30

- Corrige exclusiones de manifiesto para directorios generados anidados. Patrones simples como `assets`, `runtime`, `cache`, `logs`, `uploads` y `node_modules` ahora excluyen ese nombre como segmento en cualquier profundidad, por ejemplo `web/assets/...`.
- Patrones de archivo sin ruta como `*.log`, `*.tmp` y `*.bak` ahora se aplican también al basename de archivos anidados.
- Evita falsos `ADDED/DELETED` al migrar el mismo build entre Windows y Linux por recursos generados que nunca debieron formar parte del baseline.

## 1.1.0 — 2026-08-30

- Simplifica la identidad de instalación a un UUID persistente.
- Elimina generación/almacenamiento de certificados o pares de claves por instalación.
- Los eventos dejan de usar firma criptográfica por instalación; conservan `event_id` para correlación/reintentos.
- Mantiene criptografía únicamente para firmar/verificar el baseline autorizado por el desarrollador.
- `doctor` verifica capacidad de validación del baseline y ya no intenta generar claves del cliente.
- Schema runtime sube a 2; migración automática desde schema 1 preserva `installation_id` y elimina `identity.auth` obsoleto.
- Mejora documentación para dejar claro el mismo flujo conceptual en Windows y Linux.
- Añade rutas comunes Unix al fallback OpenSSL usado únicamente para generar claves del desarrollador.

## 1.0.1 — 2026-08-30

- Mejora diagnóstico/fallback OpenSSL para generación de claves.

## 1.0.0

- Primera implementación del paquete.
