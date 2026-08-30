# Changelog

## 1.1.2 - 2026-08-30

- Exclude generated `web/debug` paths (and Yii Advanced frontend/backend equivalents) from protected manifests.
- Refresh package-owned `manifest.include` / `manifest.exclude` policy when Runtime Integrity is upgraded, while preserving installation UUID and runtime configuration such as product, transports, and privacy settings.
- CLI `verify` now uses the refreshed package manifest immediately, even before the application is opened after an upgrade.
- Added `verify --details` to print up to 100 modified, added, and deleted paths for precise diagnostics.
- No change to developer baseline signing, installation identity, transport semantics, or fail-open behavior.

## 1.1.1 - 2026-08-30

- Fix Yii generated-path exclusions across Windows/Linux deployments.
- Exclude `web/assets`, Yii Advanced runtime directories, and their frontend/backend published asset directories explicitly.
- Protect root `assets/` source code such as Yii AssetBundle classes instead of treating it as generated output.
- Apply filename-only exclusion patterns such as `*.log`, `.DS_Store`, and `Thumbs.db` at any protected depth.
- No change to installation identity, baseline authorization model, or transport behavior.

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
