# YMLMau Runtime Integrity 1.1.3

Extensión Composer para Yii2/PHP 7.4+ que mantiene una identidad lógica de instalación mediante UUID, compara hashes del aplicativo contra un baseline autorizado y puede reportar estados mediante relay de correo y/o API HTTP.

## Si es tu primera vez usando Composer

No copies este paquete manualmente dentro de `vendor/`.

`vendor/` es administrado por Composer. Durante desarrollo el paquete se guarda fuera del aplicativo y Composer lo instala. Actualmente puede distribuirse desde su repositorio público etiquetado o desde una fuente Composer/VCS equivalente.

Empieza aquí:

1. [Conceptos antes de instalar](docs/01-ANTES-DE-INSTALAR.md)
2. [Instalación local con Composer `path`](docs/02-INSTALACION-LOCAL-PATH.md)
3. [Clave del desarrollador y baseline](docs/03-CLAVES-Y-BASELINE.md)
4. [Configuración](docs/04-CONFIGURACION.md)
5. [Doctor, verify y pruebas](docs/05-PRUEBAS-Y-DOCTOR.md)
6. [Repositorio / distribución](docs/06-REPOSITORIO-PRIVADO.md)
7. [Actualizaciones](docs/07-ACTUALIZACIONES.md)
8. [Problemas comunes](docs/08-TROUBLESHOOTING.md)
9. [Resumen de arquitectura](docs/09-ARQUITECTURA.md)

## Qué crea en el aplicativo

Composer instala el código en:

```text
vendor/ymlmau/runtime-integrity/
```

El primer arranque real de Yii crea un único archivo persistente en la raíz del proyecto:

```text
.runtime-integrity
```

Ese archivo contiene UUID de instalación, configuración y estado mínimo. **No contiene certificado ni clave privada de la instalación.**

El build autorizado incluye además:

```text
.runtime-integrity.baseline
```

Ese baseline contiene hashes y una firma del desarrollador. Cada nueva entrega autorizada reemplaza el baseline anterior.

## Identidades

```text
product_id
  = qué producto/aplicativo es

installation_id
  = UUID lógico permanente de esa instalación

environment_fingerprint
  = huella del entorno observado; puede cambiar sin cambiar installation_id

build_id
  = identificador de la entrega autorizada actual
```

Una copia completa de una instancia conserva normalmente el mismo `installation_id`. Si corre en otro entorno, el `environment_fingerprint` puede ser distinto. Eso permite observar una posible clonación sin confundir automáticamente una migración legítima con una instalación nueva.

## Criptografía: una sola finalidad

Runtime Integrity v1.1.3 **no genera certificados ni pares de claves por instalación**.

La criptografía se utiliza únicamente para responder:

> ¿Este baseline fue autorizado por el desarrollador?

La clave privada del desarrollador se conserva fuera de los aplicativos. Las instalaciones reciben únicamente la clave pública para verificar el baseline.

## Comandos públicos

```bash
vendor/bin/runtime-integrity build
vendor/bin/runtime-integrity verify
vendor/bin/runtime-integrity doctor
```

El helper `examples/generate-developer-keys.php` se usa únicamente en el entorno donde preparas builds para generar, una sola vez, la clave del desarrollador.

## Principios

- Fail-open: un fallo del monitor no debe tumbar el aplicativo.
- No DRM: no bloquea el uso del software.
- No VCS: guarda/compara hashes; no guarda versiones, diffs ni snapshots.
- Sin cron obligatorio.
- Sin base de datos obligatoria.
- API y correo son opcionales e independientes.
- No envía código fuente ni contenido de archivos.
- El runtime es independiente de Windows/Linux; las rutas de documentación muestran ambos ejemplos cuando aplica.


### Diagnóstico detallado de diferencias

Cuando `verify` devuelve `MODIFIED`, use `vendor/bin/runtime-integrity verify --root=<ruta> --details` para listar hasta 100 rutas por categoría sin cambiar el baseline.


### Manifest policy updates

`manifest.include` and `manifest.exclude` are package-owned policy and are refreshed on monitor upgrades. Installation identity and runtime transport/privacy configuration remain preserved.
