# Changelog

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
