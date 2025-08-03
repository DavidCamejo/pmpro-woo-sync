# PMPro-Woo-Sync

## 🎯 Finalidad

Este plugin permite sincronizar automáticamente las membresías de **Paid Memberships Pro** con los pedidos y pagos recurrentes de **WooCommerce**. Está optimizado para trabajar con **PagBank Connect** para pagos recurrentes en Brasil, pero funciona con cualquier gateway de WooCommerce que maneje pagos recurrentes mediante los hooks estándar.

### El Problema

Cuando un usuario adquiere una membresía de PMPro a través de WooCommerce:

- El pago inicial se procesa correctamente
- La membresía se activa en PMPro
- Los pagos recurrentes se procesan en WooCommerce/PagBank (creando nuevos pedidos)
- **PERO** PMPro no se actualiza automáticamente con las renovaciones

Esto resulta en:

- ❌ Membresías que expiran aunque el pago recurrente sea exitoso
- ❌ Usuarios perdiendo acceso a contenido pagado
- ❌ Falta de sincronización entre sistemas
- ❌ Registros de pagos desactualizados en PMPro

### La Solución

Este plugin mantiene **automáticamente sincronizadas** las membresías de PMPro con el estado real de los pedidos y pagos recurrentes en WooCommerce y PagBank.

---

## 🔧 Funcionalidades

### ✅ Sincronización Automática con WooCommerce

- **Pedidos Completados**: Activa o extiende automáticamente la membresía en PMPro
- **Pedidos Fallidos**: Cancela membresías cuando está configurado para hacerlo
- **Cancelaciones**: Sincroniza cancelaciones bidireccionales entre PMPro y WooCommerce
- **Fechas de Expiración**: Calcula y actualiza automáticamente las fechas de renovación

### ✅ Integración Optimizada con PagBank Connect

- **Pagos Recurrentes**: Maneja automáticamente los hooks específicos de PagBank
- **Renovaciones Automáticas**: Extiende membresías cuando PagBank procesa pagos recurrentes exitosos
- **Gestión de Fallos**: Maneja pagos fallidos según la configuración
- **Registro en PMPro**: Opcional registro de todos los pagos en el historial de PMPro

### ✅ Panel de Administración Completo

Interfaz moderna y profesional con **4 secciones principales**:

#### **Configuraciones**

- Ajustes centralizados para todas las opciones del plugin
- Mapeo directo entre niveles de PMPro y productos de WooCommerce
- Validación en tiempo real de configuraciones
- Indicadores visuales de estado (Sincronización, Debug, Dependencias)
- Configuración de logs y retención de datos

#### **Logs del Sistema**

- Visualización paginada de todos los eventos del plugin
- **Dashboard de estadísticas** con métricas en tiempo real
- **Filtros avanzados** por nivel (Info, Success, Warning, Error, Debug)
- **Búsqueda en tiempo real** en mensajes de logs
- **Auto-refresh cada 30 segundos** (se pausa cuando la página no está visible)
- **Exportación de logs** en formato JSON
- **Modal de detalles** para cada entrada de log
- Limpieza automática de logs antiguos configurables

#### **Herramientas**

- **Sincronización manual** de usuarios específicos
- **Sincronización masiva** de todas las membresías
- **Reparación de enlaces** entre productos y niveles de membresía
- **Verificación de estados** de membresías
- **Limpieza de metadatos huérfanos**
- **Herramienta de debug específica** para usuarios individuales
- **Reinicio completo** de configuraciones

#### **Estado del Sistema**

- **Monitoreo de dependencias** (PMPro, WooCommerce, PagBank)
- **Información del sistema** (versiones, límites, configuración)
- **Estado de gateways** activos
- **Estadísticas de sincronización** integradas
- **Configuraciones críticas** del plugin

### ✅ Sistema de Logging Avanzado

- **5 niveles de logs** (info, success, warning, error, debug)
- **Base de datos dedicada** para logs con optimización de consultas
- **Rotación automática** configurable por días
- **Contexto detallado** con datos JSON estructurados
- **Estadísticas en tiempo real** de eventos
- **Integración con logs de WooCommerce**

### ✅ Interfaz de Usuario Moderna

- **Diseño responsive** optimizado para móviles y tablets
- **Indicadores visuales** de estado con animaciones
- **Loading states** y feedback inmediato
- **Notificaciones AJAX** no intrusivas
- **Tooltips y ayuda contextual**
- **Validación client-side y server-side**
- **Animaciones suaves** y transiciones profesionales

### ✅ Compatibilidad Amplia

- **WooCommerce nativo**: Funciona con pedidos regulares y recurrentes
- **PagBank Connect**: Integración optimizada para pagos recurrentes
- **Cualquier Gateway**: Compatible con gateways que usen hooks estándar de WooCommerce
- **Soporte multisitio** completo
- **WordPress 5.0+** y **PHP 7.4+**

---

## 🏗️ Arquitectura

### Estructura Modular y Profesional

```
pmpro-woo-sync/
├── pmpro-woo-sync.php # Archivo principal con autoloader optimizado
├── uninstall.php # Script de desinstalación completa
├── /assets/ # Archivos CSS y JS optimizados
│ ├── /css/
│ │ └── admin.css # Estilos responsive y modernos
│ └── /js/
│ └── admin.js # JavaScript con AJAX completo
├── /includes/ # Clases principales y lógica del negocio
│ ├── class-pmpro-woo-sync.php # Orquestador principal
│ ├── class-pmpro-woo-sync-integrations.php # Lógica de integración WooCommerce
│ ├── class-pmpro-woo-sync-logger.php # Sistema de logs avanzado
│ └── class-pmpro-woo-sync-settings.php # Gestión de configuraciones
├── /admin/ # Sistema de administración completo
│ ├── class-pmpro-woo-sync-admin.php # Controlador principal admin
│ └── /partials/ # Plantillas HTML modulares
│ ├── admin-display-settings.php # Página de configuraciones
│ ├── admin-display-logs.php # Visualizador de logs
│ ├── admin-display-tools.php # Herramientas del plugin
│ ├── admin-display-status.php # Estado del sistema
│ └── log-row.php # Componente fila de log
└── /languages/ # Internacionalización
```

### Flujo de Funcionamiento

```
Usuario Compra → Pedido WooCommerce → Plugin Detecta Pedido
↓
Estado del Pedido → Completado → Activa/Extiende Membresía PMPro
→ Fallido → Cancela Membresía (opcional)
→ Cancelado → Cancela Membresía PMPro
↓
Registra Pago en PMPro (opcional)
```

### Integración con PagBank Connect

```
PagBank Pago Recurrente → Hook 'woocommerce_pagbank_recurring_payment_complete'
↓
Plugin Extiende Membresía PMPro
↓
Registra Pago en PMPro (opcional)
```

### Estados Manejados

| Estado WooCommerce | Acción en PMPro                             | Log Level |
| ------------------ | ------------------------------------------- | --------- |
| `completed`        | ✅ Activa/Extiende membresía + registra pago | `success` |
| `processing`       | ✅ Activa/Extiende membresía + registra pago | `info`    |
| `failed`           | ❌ Cancela membresía (si está configurado)   | `warning` |
| `cancelled`        | ❌ Cancela membresía                         | `info`    |
| `refunded`         | ❌ Cancela membresía                         | `info`    |

---

## 📥 Instalación

### Requisitos del Sistema

| Componente           | Versión Mínima | Recomendada |
| -------------------- | -------------- | ----------- |
| WordPress            | 5.0+           | 6.6+        |
| WooCommerce          | 4.0+           | 8.0+        |
| Paid Memberships Pro | 2.0+           | Última      |
| PHP                  | 7.4+           | 8.1+        |
| MySQL                | 5.6+           | 8.0+        |

### Dependencias Adicionales

| Plugin                                    | Propósito                                      | Beneficio                                                 |
| ----------------------------------------- | ---------------------------------------------- | --------------------------------------------------------- |
| Paid Memberships Pro - WooCommerce Add On | Integración Paid Memberships Pro - WooCommerce | Vincula membresías de PMPro con productos de WooCommerce  |
| PagBank Connect                           | Pagos recurrentes en Brasil                    | Integración optimizada para pagos recurrentes automáticos |

### Pasos de Instalación

1. **Descarga** el repositorio completo del plugin
2. **Sube** la carpeta `pmpro-woo-sync` a `/wp-content/plugins/`
3. **Activa** el plugin desde el panel de WordPress
4. **Configura** navegando a **`PMPro-Woo-Sync`** en el menú lateral

---

## ⚙️ Configuración

### Panel de Administración (Recomendado)

Accede a: **WordPress Admin → PMPro-Woo-Sync → Configuraciones**

#### Configuraciones Principales

* ✅ **Habilitar/Deshabilitar Sincronización**
* ✅ **Dirección de Sincronización** (Bidireccional, PMPro→WooCommerce, WooCommerce→PMPro)
* ✅ **Mapeo de Niveles**: Vincula niveles de PMPro con productos de WooCommerce
* ✅ **Registrar Pagos en PMPro** (recomendado para trazabilidad completa)
* ✅ **Sincronizar Pedidos Fallidos** (cancelar membresías cuando fallan pagos)
* ✅ **Auto-vincular Productos** (basado en nombres similares)

#### Configuración de Logs

* ✅ **Habilitar Logging**
* ✅ **Nivel de Log** (Solo errores, Advertencias, Información, Debug completo)
* ✅ **Retención de Logs** (días de conservación)
* ✅ **Modo Debug** para troubleshooting detallado

#### Indicadores de Estado Visual

El panel muestra en tiempo real:

* 🟢 **Sincronización Activa**
* 🟢 **Dependencias OK** (PMPro, WooCommerce, etc.)
* 🟡 **Modo Debug** (si está habilitado)

### Mapeo de Productos

El paso más importante es mapear correctamente los niveles de membresía de PMPro con los productos de WooCommerce:

1. Ve a **PMPro-Woo-Sync → Configuraciones**
2. En la sección **"Mapeo de Niveles de Membresía"**
3. Para cada nivel de PMPro, selecciona el producto de WooCommerce correspondiente
4. Guarda la configuración

### Configuración Avanzada por Hooks

```php
// Personalizar duración de membresía por producto
add_filter('pmpro_woo_sync_membership_duration', function($duration, $product_id) {
    // Ejemplo: Producto ID 123 dura 6 meses
    if ($product_id == 123) {
        return '6 months';
    }
    return $duration; // Por defecto 1 año
}, 10, 2);

// Personalizar nivel de log mínimo
add_filter('pmpro_woo_sync_min_log_level', function($level) {
    return 'warning'; // Solo warnings y errores
});

// Hook después de activar membresía
add_action('pmpro_woo_sync_membership_activated', function($user_id, $level_id, $order) {
    // Tu código personalizado aquí
    // Ejemplo: enviar email de bienvenida, actualizar otros sistemas, etc.
}, 10, 3);
```

---

🔍 Monitoreo y Debug
--------------------

### Dashboard de Logs Avanzado

**WordPress Admin → PMPro-Woo-Sync → Logs**

#### Características del Dashboard:

* 📊 **Estadísticas en tiempo real**: Total, últimas 24h, errores, advertencias
* 🔍 **Filtros avanzados**: Por nivel, búsqueda en tiempo real
* 📱 **Interfaz responsive**: Optimizada para móviles
* ⬇️ **Exportación**: Logs en formato JSON
* 🔄 **Auto-refresh**: Actualización automática cada 30s
* 📋 **Detalles completos**: Modal with contexto JSON

#### Niveles de Log:

| Nivel     | Color       | Uso                                                                |
| --------- | ----------- | ------------------------------------------------------------------ |
| `success` | 🟢 Verde    | Operaciones exitosas (membresías activadas, pagos procesados)      |
| `info`    | 🔵 Azul     | Información general (pedidos procesados, cambios de estado)        |
| `warning` | 🟡 Amarillo | Advertencias no críticas (pagos fallidos, configuración subóptima) |
| `error`   | 🔴 Rojo     | Errores que requieren atención (fallos de API, datos corruptos)    |
| `debug`   | 🟣 Morado   | Información detallada de debug (solo en modo debug)                |

### Herramientas de Diagnóstico

**WordPress Admin → PMPro-Woo-Sync → Herramientas**

#### Herramientas Disponibles:

1. **Sincronización Manual de Usuario**: Sincroniza un usuario específico
2. **Sincronización Masiva**: Procesa todas las membresías activas
3. **Reparar Enlaces de Productos**: Corrige vinculaciones rotas entre productos y niveles
4. **Verificar Estados de Membresías**: Revisa consistencia entre PMPro y WooCommerce
5. **Limpiar Metadatos Huérfanos**: Elimina datos huérfanos del plugin
6. **Debug de Usuario Específico**: Información completa de debug de un usuario
7. **Reinicio de Configuraciones**: Restaura valores por defecto

### Debug de Usuario Programático

```php
// Obtener información completa de debug de un usuario
if (function_exists('pmpro_woo_sync_get_user_debug_info')) {
    $debug_info = pmpro_woo_sync_get_user_debug_info($user_id);

    /* Ejemplo de salida:
    Array(
        [user_id] => 123
        [current_membership] => Array(
            [ID] => 1
            [name] => Premium
            [enddate] => 2024-12-31 23:59:59
            [startdate] => 2024-01-01 00:00:00
        )
        [recent_orders] => Array(
            [0] => Array(
                [id] => 456
                [status] => completed
                [total] => 29.99
                [date] => 2024-07-01 10:30:00
                [payment_method] => pagbank
            )
        )
        [sync_metadata] => Array(
            [_pmpro_woo_sync_order_id] => 456
            [_pmpro_woo_sync_last_sync] => 2024-07-01 10:35:00
            [_pmpro_woo_sync_sync_status] => completed
        )
    )
    */
}*/
```

---

📊 Estado del Sistema
---------------------

**WordPress Admin → PMPro-Woo-Sync → Estado**

### Información Monitoreada:

#### Estado de Dependencias

* 🟢 **Paid Memberships Pro** (versión y estado)
* 🟢 **WooCommerce** (versión y estado)
* 🟢 **Paid Memberships Pro - WooCommerce Add On** (integración)
* 🟢 **PagBank Connect** (para pagos recurrentes)

#### Información del Sistema

* Versiones (Plugin, WordPress, PHP, MySQL)
* Límites del servidor (memoria, ejecución)
* Gateways de pago activos
* Configuración crítica del plugin

#### Estadísticas de Sincronización

* Usuarios sincronizados totales
* Pedidos activos monitoreados
* Última sincronización realizada
* Errores en las últimas 24 horas

#### Configuración Actual

* Estado de sincronización (habilitada/deshabilitada)
* Modo debug (activado/desactivado)
* Logging habilitado/deshabilitado
* Número de niveles mapeados

* * *

🚨 Troubleshooting
------------------

### Problemas Comunes y Soluciones

#### 1. Membresías no se activan con pedidos completados

**Diagnóstico:**

1. Verificar logs en: PMPro-Woo-Sync → Logs
2. Comprobar mapeo: ¿El producto está vinculado a un nivel de PMPro?
3. Usar herramienta de debug específica para el usuario afectado

**Posibles causas:**

* Producto WooCommerce no mapeado a nivel de PMPro
* Sincronización deshabilitada
* Error en la configuración del plugin

#### 2. Pagos recurrentes no extienden membresías

**Diagnóstico:**

1. Verificar que PagBank Connect esté activo y configurado
2. Revisar logs para hooks de PagBank (`woocommerce_pagbank_recurring_payment_complete`)
3. Comprobar que el producto original esté correctamente mapeado

**Posibles causas:**

* PagBank Connect no está enviando los hooks correctos
* Usuario no tiene membresía activa para extender
* Configuración incorrecta del producto recurrente

#### 3. Logs no aparecen o se llenan muy rápido

**Soluciones:**

* Ajustar nivel de logging (desactivar debug en producción)
* Configurar retención de logs apropiada (7-30 días recomendado)
* Usar limpieza automática de logs antiguos

#### 4. Plugin no detecta PagBank Connect

**Diagnóstico:**

1. Verificar que PagBank Connect esté activo
2. Comprobar versión compatible del plugin PagBank
3. Revisar logs de WordPress para errores de hooks

### Activar Debug Completo

#### Desde el Panel de Administración:

**PMPro-Woo-Sync → Configuraciones → Modo Debug ✅**

#### Desde wp-config.php:

```php
// En wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Para logs específicos del plugin
define('PMPRO_WOO_SYNC_DEBUG', true);
```

### Herramientas de Diagnóstico Automatizado

El plugin incluye **verificaciones automáticas** que alertan sobre:

* ❌ Dependencias faltantes (PMPro, WooCommerce)
* ⚠️ Configuraciones subóptimas (productos sin mapear)
* 🔧 Problemas de conectividad
* 📊 Estadísticas anómalas en logs

* * *

📊 Casos de Uso
---------------

### Caso 1: Membresía Mensual con PagBank

```plaintext
Usuario compra producto "Membresía Premium Mensual" (R$ 29,90)
↓
WooCommerce procesa el pago inicial
↓
Plugin activa membresía "Premium" en PMPro
↓
PagBank programa pagos recurrentes mensuales
↓
Cada mes: PagBank cobra → Hook → Plugin extiende membresía
```

### Caso 2: Diferentes Tipos de Membresías

| Tipo de Membresía  | Producto WooCommerce | Mapeo PMPro | Duración |
| ------------------ | -------------------- | ----------- | -------- |
| Básica Mensual     | ID: 100              | Nivel ID: 1 | 30 días  |
| Premium Trimestral | ID: 101              | Nivel ID: 2 | 90 días  |
| VIP Anual          | ID: 102              | Nivel ID: 3 | 365 días |

### Caso 3: Integración con Hooks Personalizados

```php
// Acciones después de activar membresía
add_action('pmpro_woo_sync_membership_activated', function($user_id, $level_id, $order) {
    // Enviar email de bienvenida personalizado
    wp_mail(
        get_userdata($user_id)->user_email,
        'Bienvenido a tu nueva membresía',
        'Tu membresía ha sido activada exitosamente.'
    );

    // Actualizar sistema de email marketing
    // Activar acceso a contenido premium
    // Log personalizado
}, 10, 3);

// Acciones cuando pago recurrente es exitoso
add_action('pmpro_woo_sync_recurring_payment_complete', function($user_id, $order) {
    // Notificar renovación exitosa
    // Actualizar estadísticas
    // Activar contenido del siguiente período
}, 10, 2);
```

---

🔧 Integración con PagBank Connect
--------------------------------------

### Configuración Recomendada

#### En PagBank Connect:

1. **Activar pagos recurrentes** en la configuración del gateway
2. **Configurar webhooks** (se configuran automáticamente)
3. **Establecer método de pago** como PagBank en productos recurrentes

#### En PMPro-Woo-Sync:

1. **Mapear productos** de WooCommerce con niveles de PMPro
2. **Habilitar "Registrar Pagos en PMPro"** para trazabilidad completa
3. **Configurar logs** en nivel "info" o superior

### Hooks Específicos de PagBank Manejados

```php
// Automáticamente manejados por el plugin:
'woocommerce_pagbank_recurring_payment_complete' // Pago recurrente exitoso
'woocommerce_pagbank_recurring_payment_failed'   // Pago recurrente falló
```

### Compatibilidad con Otros Gateways

El plugin también funciona con cualquier gateway que use los hooks estándar de WooCommerce:

```php
// Hooks estándar de WooCommerce soportados:
'woocommerce_order_status_completed'  // Pedido completado
'woocommerce_order_status_processing' // Pedido en procesamiento
'woocommerce_order_status_cancelled'  // Pedido cancelado
'woocommerce_order_status_failed'     // Pedido fallido
'woocommerce_order_status_refunded'   // Pedido reembolsado
```

---

## 🤝 Contribución y Desarrollo

### Reportar Issues

1. **Activar Debug Mode** en configuraciones del plugin
2. **Reproducir el problema** paso a paso
3. **Exportar logs relevantes** desde el panel de administración
4. **Crear issue en GitHub** con información completa:
   * Versiones de dependencias (WordPress, WooCommerce, PMPro)
   * Versión de Paid Memberships Pro - WooCommerce Add On
   * Versión de PagBank Connect
   * Configuración del mapeo de productos
   * Logs exportados
   * Pasos para reproducir

### Estructura de Desarrollo

```
# Clonar repositorio
git clone https://github.com/DavidCamejo/pmpro-woo-sync.git

# Instalar dependencias de desarrollo (próximamente)
npm install

# Construir assets
npm run build
```

### Coding Standards

* **PSR-4** para autoloading de clases
* **WordPress Coding Standards** para PHP
* **ESLint** para JavaScript
* **Documentación PHPDoc** completa
* **Hooks y filtros** bien documentados
* **Escape de output** completo para prevenir XSS
* **Sanitización de input** en todas las entradas

* * *

## 📝 Changelog

### v2.0.0 - 2025-08-01

#### 🔄 BREAKING CHANGES

* **Eliminada dependencia** de WooCommerce Subscriptions
* **Refactorización completa** para trabajar directamente con WooCommerce
* **Optimización especófica** para PagBank Connect

#### ✅ Added

* **Integración nativa con WooCommerce**: Funciona con pedidos regulares y recurrentes
* **Soporte optimizado para PagBank Connect**: Manejo especófico de hooks de pagos recurrentes
* **Mapeo directo de productos**: Vinculación simplificada entre productos WooCommerce y niveles PMPro
* **Panel de configuraciones mejorado**: Interfaz más intuitiva y completa
* **Sistema de logging mejorado**: Mejor rendimiento y opciones de filtrado
* **Herramientas de diagnóstico**: Nuevas utilidades para debug y mantenimiento

#### 🔄Changed

* **Arquitectura simplificada**: Menos dependencias, mejor rendimiento
* **Configuración centralizada**: Todo desde el panel de administración
* **Hooks actualizados**: Enfoque en hooks estándar de WooCommerce y PagBank

#### 🗑️Removed

* **Dependencia de WooCommerce Subscriptions**: Ya no es necesario
* **Complejidad innecesaria**: Código especófico para múltiples sistemas de suscripción
* **Configuraciones obsoletas**: Opciones que ya no son relevantes

### v1.0.0 - 2025-07-30

* **Lanzamiento inicial** con soporte para WooCommerce Subscriptions
* **Sistema de administración completo**
* **Logging avanzado** y herramientas de diagnóstico

* * *

## 📜 Licencia

Este proyecto está bajo la **Licencia MIT**. Consulta el archivo `LICENSE` para más detalles.

### Términos de Uso

* ✅ Uso comercial permitido
* ✅ Modificación permitida
* ✅ Distribución permitida
* ✅ Uso privado permitido
* ❌ Sin garantía
* ❌ Sin responsabilidad del autor

* * *

## 🙏 Agradecimientos

* **Desarrollado por**: David Camejo
* **Inspirado en**: Necesidades reales de sincronización PMPro-WooCommerce
* **Optimizado para**: [PagBank Connect](https://github.com/r-martins/PagBank-WooCommerce) y pagos recurrentes en Brasil
* **Testeado por**: Comunidad de desarrolladores WordPress

* * *

## 📞 Soporte y Contacto

### Canales de Soporte

* 📧 **Email**: jdavidcamejo@gmail.com
* 🐛 **Issues**: [GitHub Issues](https://github.com/DavidCamejo/pmpro-woo-sync/issues)
* 📚 **Documentación**: Este archivo
* 💬 **Discusiones**: GitHub Discussions

### Antes de Contactar

1. ✅ Revisar documentación completa
2. ✅ Buscar en issues existentes
3. ✅ Probar con debug mode activado
4. ✅ Exportar logs relevantes
5. ✅ Preparar información del sistema

### Información para Soporte

Incluir siempre:

* Versión del plugin PMPro-Woo-Sync
* Versión de WordPress
* Versión de WooCommerce y Paid Memberships Pro
* Versión de Paid Memberships Pro - WooCommerce Add On
* Versión de PagBank Connect
* Configuración de mapeo de productos
* Logs exportados del problema
* Pasos detallados para reproducir

* * *

**⚡ ¡Mantén tus membresías siempre sincronizadas con la solución más completa!**

* * *

*Última actualización: 02 de Agosto, 2025*
