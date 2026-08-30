# 07 — Actualizaciones

## Actualizar Runtime Integrity

Ejemplo:

```text
1.0.x → 1.1.0
```

Composer reemplaza el código dentro de:

```text
vendor/ymlmau/runtime-integrity
```

pero conserva:

```text
.runtime-integrity
```

Por tanto se conservan:

```text
installation_id UUID
configuración runtime
estado mínimo
```

### Migración desde v1.0.x

v1.0.x podía guardar un par criptográfico por instalación. v1.1.0 ya no lo utiliza.

Al leer un estado schema 1, v1.1.0:

```text
preserva installation_id
elimina identity.auth
elimina un pending_event firmado antiguo si existía
actualiza schema → 2
```

No genera un UUID nuevo.

## Actualizar el aplicativo

1. termina cambios autorizados;
2. actualiza dependencias necesarias;
3. genera nuevo baseline firmado;
4. verifica `CLEAN`;
5. despliega código + baseline;
6. conserva `.runtime-integrity` de la instalación.

El baseline anterior no se conserva como historial dentro de Runtime Integrity.
