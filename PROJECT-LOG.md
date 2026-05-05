# PROJECT LOG — estamospescando.com

## Información del Proyecto

| Campo | Valor |
|-------|-------|
| **Sitio** | [estamospescando.com](https://estamospescando.com) |
| **Plataforma** | Magento 2.4.8 Open Source |
| **Frontend** | Hyvä Theme + child theme `Panamerik/estamospescando` |
| **Servidor** | Cloudways (DigitalOcean) — IP 134.199.224.170 |
| **PHP** | 8.3 |
| **DB** | MariaDB |
| **Cache** | Redis (db0: default, db1: FPC) |
| **Search** | OpenSearch 2.19 |
| **CDN** | Cloudflare |
| **Repo** | [github.com/donturis/EPES](https://github.com/donturis/EPES) |

---

## Módulos Relevantes

| Módulo | Función |
|--------|---------|
| `Hyva_ThemeFallback` | Plugin que cambia el tema de Hyvä a Luma para URLs específicas (checkout, PayPal, etc.) |
| `Hyva_LumaCheckout` | Pre-configura ThemeFallback con las URLs de checkout |
| `Panamerik_CheckoutCustom` | Header/footer custom para el layout de checkout |

---

## Temas Registrados (DB `theme`)

| ID | Theme | Parent |
|----|-------|--------|
| 1 | Magento/blank | — |
| 3 | Magento/luma | blank (1) |
| 6 | Hyva/default | — |
| 7 | Panamerik/estamospescando | Hyva/default (6) |

---

## Historial de Cambios

### 2025-05-05 — Fix: Checkout roto ("No Checkout module installed")

#### Síntoma

La página de checkout (`/checkout/`) mostraba el mensaje de error de Hyvä:
"No Checkout module installed. Either install Hyvä Checkout, the Luma Fallback Checkout, or another alternative checkout."

El módulo `Hyva_ThemeFallback` estaba correctamente instalado y configurado, pero el checkout no cambiaba al tema Luma.

#### Diagnóstico — 3 problemas encadenados

**Problema 1: OPcache `validate_timestamps=0` en PHP-FPM**

PHP-FPM tenía OPcache configurado con `validate_timestamps=0` (heredado del pool config de Cloudways), lo que significa que cualquier cambio a archivos PHP requiere reiniciar PHP-FPM para ser detectado. CLI PHP reportaba `validate_timestamps=1`, lo cual era engañoso — el valor real en FPM era diferente.

Esto causó que TODOS los intentos previos de debug (agregar logging a ThemeFallbackPlugin.php, ThemeSwitch.php, etc.) aparentaran no funcionar, ya que OPcache seguía sirviendo las versiones anteriores de los archivos.

Se confirmó inyectando código de diagnóstico en `pub/index.php` y reiniciando FPM para forzar la recarga.

**Problema 2: Full Page Cache (FPC) cacheando la página de error**

El layout de Hyvä para checkout (`vendor/hyva-themes/magento2-default-theme/Magento_Checkout/layout/checkout_index_index.xml`) usa un bloque `Magento\Framework\View\Element\Text` **sin** el atributo `cacheable="false"`. Esto permite que el FPC (Redis db1) cachee la respuesta.

Una vez cacheada la página de error, el controller de checkout nunca se ejecuta en requests subsecuentes, el plugin ThemeFallback nunca dispara, y el error persiste indefinidamente.

El flush normal con `bin/magento cache:flush` no limpiaba completamente el FPC de Redis. Se requirió `redis-cli -n 1 FLUSHDB` directo.

**Problema 3: Falta de static content para Luma/es_MX**

Después de resolver los problemas 1 y 2, el checkout cargaba pero se quedaba en un spinner infinito. La consola del navegador mostraba un 404 para `js-translation.json` del tema Luma en locale `es_MX`.

Se resolvió con:
```bash
php bin/magento setup:static-content:deploy es_MX --theme=Magento/luma --jobs=1 -f
```

#### Secuencia de Fix

1. Reiniciar PHP-FPM (vía panel Cloudways) — para que OPcache recargue archivos
2. `redis-cli -n 0 FLUSHDB` + `redis-cli -n 1 FLUSHDB` — limpiar caches Redis
3. `php bin/magento cache:flush` — limpiar caches Magento
4. `php bin/magento setup:static-content:deploy es_MX --theme=Magento/luma --jobs=1 -f` — generar assets Luma

#### Resultado

Checkout renderiza correctamente el formulario Luma con Shipping Address, Order Summary, y los pasos Shipping / Review & Payments.

---

### 2025-05-05 — Fix preventivo: `cacheable="false"` en layout de checkout Hyvä

#### Problema

Si en algún deploy futuro el FPC llega a cachear la página de checkout mientras el tema Hyvä está activo (antes de que ThemeFallback cambie a Luma), el error "No Checkout module installed" reaparecerá y se quedará cacheado indefinidamente.

#### Solución

Se creó un layout override en el child theme que agrega `cacheable="false"` al bloque de fallback:

**Archivo:** `app/design/frontend/Panamerik/estamospescando/Magento_Checkout/layout/checkout_index_index.xml`

```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      layout="checkout"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <referenceContainer name="content">
            <block class="Magento\Framework\View\Element\Text"
                   name="fallback.module.missing"
                   cacheable="false">
                <!-- arguments with error message -->
            </block>
        </referenceContainer>
    </body>
</page>
```

#### Verificación

- Checkout responde con `cache-control: max-age=0, must-revalidate, no-cache, no-store`
- Homepage (comparación) responde con `cache-control: max-age=86400, public`
- Redis FPC: 0 keys de checkout almacenadas
- No requiere DI compile ni static-content deploy (es XML de layout, no PHP)

---

## Configuración Importante

### OPcache en FPM

- `validate_timestamps=0` en el pool de PHP-FPM (configuración de Cloudways)
- **Cualquier cambio a archivos PHP requiere reiniciar PHP-FPM** vía panel Cloudways
- CLI PHP tiene `validate_timestamps=1` (diferente al FPM — no confiar en `php -i`)

### ThemeFallback Config (DB `core_config_data`)

- `hyva_theme_fallback/general/enable` = **1**
- `hyva_theme_fallback/general/theme_full_path` = **frontend/Magento/luma**
- URLs configuradas: checkout/index, paypal, customer/ajax/login, blog

### Cache Redis

- Database 0: Default cache
- Database 1: Full Page Cache (FPC)
- Para flush manual: `redis-cli -n 0 FLUSHDB && redis-cli -n 1 FLUSHDB`

---

## Archivos Clave del Proyecto

| Archivo | Propósito |
|---------|-----------|
| `vendor/hyva-themes/magento2-theme-fallback/src/Plugin/ThemeFallbackPlugin.php` | Plugin que intercepta `ActionInterface::beforeExecute()` para cambiar tema |
| `vendor/hyva-themes/magento2-theme-fallback/src/Model/ThemeSwitch.php` | Ejecuta el cambio de tema vía `DesignInterface::setDesignTheme()` |
| `vendor/hyva-themes/magento2-default-theme/Magento_Checkout/layout/checkout_index_index.xml` | Layout Hyvä que muestra error (SIN cacheable=false) |
| `app/design/frontend/Panamerik/estamospescando/Magento_Checkout/layout/checkout_index_index.xml` | **Nuestro override** con `cacheable="false"` |
| `vendor/magento/module-checkout/view/frontend/layout/checkout_index_index.xml` | Layout real del checkout Luma (con cacheable=false en Onepage) |
| `vendor/magento/module-theme/Plugin/LoadDesignPlugin.php` | Carga el tema configurado (sortOrder=0, antes de ThemeFallback) |
| `app/etc/env.php` | Config de Redis, DB, session |

---

## Notas para Deploys Futuros

1. **Siempre reiniciar PHP-FPM** después de modificar archivos PHP en vendor/ o generated/
2. **Flush Redis directo** si el checkout se rompe: `redis-cli -n 0 FLUSHDB && redis-cli -n 1 FLUSHDB`
3. **Deploy static content para Luma** si se cambia de locale o versión: `php bin/magento setup:static-content:deploy es_MX --theme=Magento/luma --jobs=1 -f`
4. El layout override de `cacheable="false"` previene que FPC cachee el error, pero si Hyvä cambia el nombre del bloque en una actualización, hay que actualizar el override
