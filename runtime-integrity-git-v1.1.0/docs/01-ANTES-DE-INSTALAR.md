# 01 — Antes de instalar

## 1. ¿Qué es un paquete Composer?

Es una carpeta de código con su propio `composer.json`. Otro proyecto PHP declara que necesita ese paquete y Composer lo instala dentro de `vendor/`.

Hay dos archivos distintos:

```text
runtime-integrity/composer.json
    describe Runtime Integrity

mi-aplicativo/composer.json
    describe el aplicativo y declara que necesita Runtime Integrity
```

Nunca reemplaces uno por el otro.

## 2. ¿Dónde se guarda Runtime Integrity?

Durante desarrollo/pruebas puedes tenerlo, por ejemplo:

Windows:

```text
C:\packages\runtime-integrity\
```

Linux:

```text
/opt/packages/runtime-integrity/
```

Composer lo instala después dentro del aplicativo:

```text
vendor/ymlmau/runtime-integrity/
```

No copies tú manualmente el paquete dentro de `vendor`.

## 3. Tres identidades que no deben confundirse

### `product_id`

Identifica el producto, no el cliente.

```text
avaluos
portuflow
noni
```

Todas las instalaciones del mismo producto pueden compartir el mismo `product_id`.

### `installation_id`

UUID generado automáticamente en el primer arranque.

```text
6bf1da54-950c-4dd5-84b8-c3ec79ac6785
```

No lo escribes manualmente y no depende de IP, hostname o sistema operativo.

### `environment_fingerprint`

Es una huella del entorno observado. Puede cambiar en una migración legítima.

Un clon completo puede conservar el mismo `installation_id` pero aparecer con otro `environment_fingerprint`. Runtime Integrity no crea un UUID nuevo automáticamente solo porque cambió el entorno.

## 4. `build_id`

Identifica la entrega autorizada actual:

```text
2026.08.30.1
```

No es un historial interno. Un build nuevo reemplaza el baseline anterior.

## 5. ¿De dónde sale `product_id`?

Runtime Integrity puede tomar `Yii::$app->id` como fallback cuando es estable y representa realmente el producto. Para builds y proyectos con varios entry points, es más claro sembrar explícitamente el mismo `product_id` en la configuración inicial.

Ejemplo:

```json
"product_id": "avaluos"
```

## 6. Solo existe una pareja de claves relevante en v1.1

La **clave del desarrollador** firma baselines autorizados:

```text
developer-private.key  ← solo en tu entorno de build
developer-public.key   ← puede distribuirse con el aplicativo
```

Runtime Integrity **no genera claves ni certificados por instalación**.

El `installation_id` es simplemente un UUID persistente.

## 7. Orden correcto de primera instalación

```text
1. Composer instala Runtime Integrity
2. definir product_id + clave pública del desarrollador
3. generar baseline autorizado
4. verificar baseline
5. arrancar Yii
6. se crea installation_id UUID
7. ejecutar doctor
```
