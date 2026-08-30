# YMLMau Runtime Integrity 1.1.3

Extensión Composer para Yii2/PHP 7.4+ que conserva una identidad lógica de instalación mediante UUID, compara el aplicativo contra un baseline autorizado y puede reportar estados mediante relay de correo y/o API HTTP.

## Qué hace

- Mantiene un `installation_id` UUID persistente por instalación lógica.
- Calcula una huella observable del entorno sin convertirla en identidad.
- Verifica archivos protegidos mediante SHA-256 contra un baseline autorizado.
- Verifica la firma del baseline con la clave pública del desarrollador.
- Detecta archivos modificados, agregados y eliminados.
- Ejecuta un pulse aproximadamente cada 7 días de actividad, con jitter.
- Opera fail-open: un fallo interno o de transporte no debe tumbar Yii.
- No es DRM, Git, respaldo ni mecanismo de restauración.

## Si es tu primera vez usando Composer

No copies este paquete manualmente dentro de `vendor/`. Composer administra esa carpeta.

Empieza aquí:

1. [Conceptos antes de instalar](docs/01-ANTES-DE-INSTALAR.md)
2. [Instalación local con Composer `path`](docs/02-INSTALACION-LOCAL-PATH.md)
3. [Clave del desarrollador y baseline](docs/03-CLAVES-Y-BASELINE.md)
4. [Configuración](docs/04-CONFIGURACION.md)
5. [Doctor, verify y pruebas](docs/05-PRUEBAS-Y-DOCTOR.md)
6. [Distribución con Composer](docs/06-DISTRIBUCION-COMPOSER.md)
7. [Actualizaciones](docs/07-ACTUALIZACIONES.md)
8. [Problemas comunes](docs/08-TROUBLESHOOTING.md)
9. [Resumen de arquitectura](docs/09-ARQUITECTURA.md)
10. [Despliegue Windows ↔ Linux](docs/10-DESPLIEGUE-WINDOWS-LINUX.md)
11. [QA de release](docs/11-QA-RELEASE.md)

## Qué crea en el aplicativo

Composer instala el código en:

```text
vendor/ymlmau/runtime-integrity/
```

El primer arranque real de Yii crea en la raíz:

```text
.runtime-integrity
```

Ese archivo conserva UUID, configuración runtime y estado mínimo. No contiene una clave privada de la instalación.

El build autorizado utiliza además:

```text
.runtime-integrity.baseline
```

El baseline contiene hashes y la firma del desarrollador. Cada nueva entrega autorizada reemplaza al baseline anterior.

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

Una migración legítima conserva normalmente `installation_id`. Un clon completo también puede conservarlo; un `environment_fingerprint` diferente ayuda a observar que la misma identidad apareció en otro entorno.

## Criptografía

Runtime Integrity 1.1.3 no genera certificados ni pares de claves por instalación.

La criptografía responde únicamente:

> ¿Este baseline fue autorizado por el desarrollador?

La clave privada del desarrollador permanece fuera del aplicativo y de los servidores cliente. La instalación recibe solo la clave pública necesaria para verificar el baseline.

## Comandos públicos

```bash
vendor/bin/runtime-integrity build
vendor/bin/runtime-integrity verify
vendor/bin/runtime-integrity verify --details
vendor/bin/runtime-integrity doctor
```

El helper `examples/generate-developer-keys.php` se usa únicamente en el entorno donde se preparan builds.

## Política de manifiesto

La política `manifest.include` / `manifest.exclude` pertenece al monitor y se refresca al actualizar Runtime Integrity. La identidad y la configuración runtime de la instalación se preservan.

Por defecto se protege código fuente del aplicativo, incluyendo `assets/` de Yii cuando contiene AssetBundle/código. Se excluyen contenidos generados u operativos como `vendor/`, `runtime/`, `web/assets/`, `web/debug/`, logs, temporales y estado propio de Runtime Integrity.

## Principios

- Fail-open.
- No DRM.
- No VCS: guarda/compara hashes, no diffs ni snapshots.
- Sin cron obligatorio.
- Sin base de datos obligatoria.
- API y correo opcionales e independientes.
- No envía código fuente ni contenido de archivos.
- Windows y Linux usan el mismo modelo; solo cambian rutas/permisos del entorno.
