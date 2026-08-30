# 04 — Configuración

## 1. Configuración inicial vs runtime

En el primer setup Runtime Integrity toma valores desde:

```text
composer.json → extra → ymlmau-runtime-integrity
```

Después crea:

```text
.runtime-integrity
```

Desde ese momento ese archivo es la fuente canónica de la configuración runtime de esa instalación.

Excepción deliberada: `config.manifest` es política del monitor. Al actualizar Runtime Integrity se refresca desde los defaults de la versión instalada más cualquier override explícito en `composer.json`. Esto permite corregir inclusiones/exclusiones sin regenerar la identidad ni reemplazar transportes u otras opciones runtime.

## 2. Estructura conceptual

```json
{
  "schema": 2,
  "identity": {
    "installation_id": "UUID",
    "created_at": "..."
  },
  "config": {},
  "baseline": {},
  "state": {}
}
```

No existe `identity.auth`, certificado ni private key de instalación.

## 3. API

Deshabilitada:

```json
"api": {"enabled": false, "url": null}
```

Habilitada:

```json
"api": {
  "enabled": true,
  "url": "https://monitor.example.com/runtime/event"
}
```

Regla atómica:

```text
false + null = válido
true + URL   = válido
true + null  = inválido
false + URL  = inválido
```

Una configuración inválida deshabilita solo ese transporte.

## 4. Correo

Las instalaciones no guardan credenciales Gmail. El canal de correo apunta a un relay HTTPS:

```json
"email": {
  "enabled": true,
  "relay_url": "https://relay.example.com/runtime/email"
}
```

Deshabilitado:

```json
"email": {"enabled": false, "relay_url": null}
```

## 5. Hostname

Por defecto:

```json
"privacy": {"include_hostname": false}
```

Puede habilitarse sin convertir el hostname en identidad.

## 6. Qué puede modificarse después

Puedes modificar conscientemente el bloque `config` de `.runtime-integrity` para activar/desactivar transportes u otras opciones runtime.

No uses `.runtime-integrity` para personalizar `config.manifest`: si necesitas un override de manifiesto, decláralo en `composer.json → extra → ymlmau-runtime-integrity → manifest`, porque esa política se refresca al actualizar el paquete.

No modifiques manualmente:

```text
identity.installation_id
state
baseline
schema
```

## 7. Clonación o migración

No cambies `installation_id` por un cambio de IP, hostname, dominio, ruta o sistema operativo.

Una migración legítima conserva identidad. Un clon completo también puede conservarla; el `environment_fingerprint` ayuda a distinguir entornos observados.
