# PMPro-WooCommerce Sync

## 🎯 Finalidad

Este plugin resuelve un problema específico en la integración entre **Paid Memberships Pro (PMPro)** y **WooCommerce** cuando se procesan pagos recurrentes a través de gateways como **PagBank**.

### El Problema

Cuando un usuario adquiere una membresía de PMPro a través de WooCommerce:

- El pago inicial se procesa correctamente
- La membresía se activa en PMPro
- Los pagos recurrentes se procesan en WooCommerce (creando nuevos pedidos)
- **PERO** PMPro no se actualiza automáticamente con las renovaciones

Esto resulta en:

- ❌ Membresías que expiran aunque el pago recurrente sea exitoso
- ❌ Usuarios perdiendo acceso a contenido pagado
- ❌ Falta de sincronización entre sistemas
- ❌ Registros de pagos desactualizados en PMPro

### La Solución

Este plugin mantiene **automáticamente sincronizadas** las membresías de PMPro con el estado real de las suscripciones en WooCommerce.

---

## 🔧 Funcionalidades

### ✅ Sincronización Automática

- **Renovaciones Exitosas**: Extiende automáticamente la membresía en PMPro
- **Pagos Fallidos**: Implementa sistema de reintentos configurables
- **Cancelaciones Bidireccionales**: Cancela la membresía en PMPro cuando se cancela en WooCommerce y propaga las cancelaciones desde PMPro a los gateways de pago externos para detener los cobros recurrentes
- **Fechas de Expiración**: Calcula y actualiza automáticamente las fechas de renovación

### ✅ Sistema de Reintentos Inteligente

- Reintentos automáticos para pagos fallidos
- Límite configurable de intentos
- Suspensión automática tras máximos reintentos
- Limpieza automática de intentos exitosos

### ✅ Panel de Administración Completo

Interfaz moderna y profesional con **4 secciones principales**:

#### **Configuraciones**

- Ajustes centralizados para todas las opciones del plugin
- Configuración de credenciales de API para gateways de pago

- Validación en tiempo real de configuraciones
- Indicadores visuales de estado (Sincronización, Debug, API)
- Auto-guardado de borradores cada 30 segundos

#### **Logs del Sistema**

- Visualización paginada de todos los eventos del plugin
- **Dashboard de estadísticas** con métricas en tiempo real
- **Filtros avanzados** por nivel (Info, Success, Warning, Error, Debug)
- **Búsqueda en tiempo real** en mensajes de logs
- **Auto-refresh cada 30 segundos** (se pausa cuando la página no está visible)
- **Exportación de logs** en formato JSON
- **Modal de detalles** para cada entrada de log
- Limpieza automática de logs antiguos configurables

#### **Herramientas** (¡NUEVO!)

- **Sincronización masiva** de todas las membresías
- **Reparación de enlaces** entre suscripciones y membresías
- **Verificación de estados** en gateways externos
- **Limpieza de metadatos huérfanos**
- **Herramienta de debug específica** para usuarios individuales
- **Reinicio completo** de configuraciones

#### **Estado del Sistema** (¡NUEVO!)

- **Monitoreo de dependencias** en tiempo real
- **Información del sistema** (versiones, límites, configuración)
- **Estado de gateways** con prueba de conexión
- **Estadísticas de logs** integradas
- **Configuraciones críticas** del plugin

### ✅ Logging y Monitoreo Avanzado

- **5 niveles de logs** (info, success, warning, error, debug)
- **Base de datos dedicada** para logs con optimización de consultas
- **Rotación automática** configurable por días
- **Contexto detallado** con datos JSON estructurados
- **Estadísticas en tiempo real** de eventos
- **Integración con WordPress Debug** y logs del sistema
- **API AJAX** para operaciones en tiempo real

### ✅ Interfaz de Usuario Moderna

- **Diseño responsive** optimizado para móviles y tablets
- **Indicadores visuales** de estado con animaciones
- **Loading states** y feedback inmediato
- **Notificaciones AJAX** no intrusivas
- **Tooltips y ayuda contextual**
- **Validación client-side y server-side**
- **Animaciones suaves** y transiciones profesionales

### ✅ Compatibilidad Amplia

- Funciona con WooCommerce Subscriptions
- Compatible con PagBank y otros gateways
- Detecta múltiples tipos de pedidos de renovación
- Integración con webhooks existentes
- **Soporte multisitio** completo
- **WordPress 5.0+** y **PHP 7.4+**

---

## 🏗️ Arquitectura

### Estructura Modular y Profesional

```
pmpro-woo-sync/
├── pmpro-woo-sync.php # Archivo principal con autoloader optimizado
├── readme.txt # Información estándar del plugin
├── uninstall.php # Script de desinstalación completa
├── /assets/ # Archivos CSS y JS optimizados
│ ├── /css/
│ │ └── admin.css # Estilos responsive y modernos
│ └── /js/
│ └── admin.js # JavaScript con AJAX completo
├── /includes/ # Clases principales y lógica del negocio
│ ├── class-pmpro-woo-sync.php # Orquestador principal
│ ├── class-pmpro-woo-sync-integrations.php # Lógica de integración
│ ├── class-pmpro-woo-sync-logger.php # Sistema de logs avanzado
│ ├── class-pmpro-woo-sync-settings.php # Gestión de configuraciones
│ ├── class-pmpro-woo-sync-gateway-manager.php # Gestión de gateways
│ └── /gateways/ # Clases específicas para cada gateway
│ └── class-pmpro-woo-sync-pagbank-api.php # Integración PagBank
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
    A[Pago Recurrente] --> B[Nuevo Pedido WooCommerce]
    B --> C[Plugin Detecta Renovación]
    C --> D{Estado del Pedido}
    D -->|Exitoso| E[Extiende Membresía PMPro]
    D -->|Fallido| F[Sistema de Reintentos]
    D -->|Cancelado| G[Cancela Membresía PMPro]
    E --> H[Registra Pago en PMPro]
    F --> I[Programa Reintento]
    G --> J[Actualiza Estado Usuario]
```

### Flujo de Cancelación Bidireccional

### Estados Manejados

| Estado WooCommerce | Acción en PMPro                          | Log Level |
| ------------------ | ---------------------------------------- | --------- |
| `completed`        | ✅ Extiende membresía + registra pago     | `success` |
| `processing`       | ✅ Extiende membresía + registra pago     | `info`    |
| `failed`           | ⚠️ Programa reintento automático         | `warning` |
| `cancelled`        | ❌ Cancela membresía + cancela en gateway | `info`    |

* * *

📥 Instalación
--------------

### Requisitos del Sistema

| Componente           | Versión Mínima | Recomendada |
| -------------------- | -------------- | ----------- |
| WordPress            | 5.0+           | 6.6+        |
| WooCommerce          | 4.0+           | 8.0+        |
| Paid Memberships Pro | 2.0+           | Última      |
| PHP                  | 7.4+           | 8.1+        |
| MySQL                | 5.6+           | 8.0+        |

### Pasos de Instalación

1. **Descarga** el repositorio completo del plugin

2. **Sube** la carpeta `pmpro-woo-sync` a `/wp-content/plugins/`

3. **Activa** el plugin desde el panel de WordPress

4. **Configura** navegando a **`PMPRO-Woo Sync`** en el menú lateral

* * *

⚙️ Configuración
----------------

### Panel de Administración (Recomendado)

Accede a: **WordPress Admin → PMPRO-Woo Sync**

#### Configuraciones Principales

* ✅ **Habilitar/Deshabilitar Sincronización**
* ✅ **Modo Debug** para troubleshooting detallado
* ✅ **Configuración de Gateways** (API Keys, modos Sandbox/Live)
* ✅ **Configuración de Reintentos** (máximo, intervalos)
* ✅ **Retención de Logs** (días de conservación)
* ✅ **Tamaño de Lote** para procesamientos masivos

#### Indicadores de Estado Visual

El panel muestra en tiempo real:

* 🟢 **Sincronización Activa**
* 🟡 **Modo Debug** (si está habilitado)
* 🟢 **PagBank API** (si está configurada)

### Configuración Avanzada por Hooks

```php
// Cambiar máximo de reintentos
add_filter('pmpro_woo_sync_max_retries', function($max) {
    return 5; // 5 reintentos en lugar de 3
});

// Cambiar días entre reintentos
add_filter('pmpro_woo_sync_retry_delay', function($days) {
    return 3;

 // 3 días en lugar de 2
});

// Personalizar timeout de API
add_filter('pmpro_woo_sync_api_timeout', function($timeout) {
    return 60; // 60 segundos en lugar de 30
});
```

---

🔍 Monitoreo y Debug
--------------------

### Dashboard de Logs Avanzado

**WordPress Admin → PMPRO-Woo Sync → Logs**

#### Características del Dashboard:

* 📊 **Estadísticas en tiempo real**: Total, últimas 24h, errores, advertencias
* 🔍 **Filtros avanzados**: Por nivel, búsqueda en tiempo real
* 📱 **Interfaz responsive**: Optimizada para móviles
* ⬇️ **Exportación**: Logs en formato JSON
* 🔄 **Auto-refresh**: Actualización automática cada 30s
* 📋 **Detalles completos**: Modal con contexto JSON

#### Niveles de Log:

| Nivel     | Color       | Uso                            |
| --------- | ----------- | ------------------------------ |
| `success` | 🟢 Verde    | Operaciones exitosas           |
| `info`    | 🔵 Azul     | Información general            |
| `warning` | 🟡 Amarillo | Advertencias no críticas       |
| `error`   | 🔴 Rojo     | Errores que requieren atención |
| `debug`   | 🟣 Morado   | Información detallada de debug |

### Herramientas de Diagnóstico

**WordPress Admin → PMPRO-Woo Sync → Herramientas**

#### Herramientas Disponibles:

1. **Sincronización Masiva**: Procesa todas las membresías activas
2. **Reparación de Enlaces**: Corrige vinculaciones rotas
3. **Verificación de Gateways**: Prueba conexiones con APIs externas
4. **Debug de Usuario**: Información detallada de un usuario específico
5. **Limpieza de Metadatos**: Elimina datos huérfanos
6. **Reinicio de Configuraciones**: Restaura valores por defecto

### Función de Debug Programática

```php
// Obtener información completa de debug de un usuario
$debug_info = pmpro_woo_sync_debug_info($user_id);

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
            [date] =>

 2024-07-01 10:30:00
            [is_subscription] => true
        )
    )
    [wc_subscriptions] => Array(
        [0] => Array(
            [id] => 789
            [status] => active
            [next_payment] => 2024-08-01 10:30:00
            [pmpro_linked_level] => 1
        )
    )
    [plugin_metadata] => Array(
        [_pmpro_woo_sync_subscription_id] => 789
        [_pmpro_woo_sync_last_sync] => 2024-07-01 10:35:00
    )
)
*/
```

---

📊 Estado del Sistema
---------------------

**WordPress Admin → PMPRO-Woo Sync → Estado**

### Información Monitoreada:

#### Estado General

* ✅ Plugin activo y funcional
* ✅ Sincronización habilitada/deshabilitada
* ⚠️ Modo debug (si está activo)

#### Información del Sistema

* Versiones (Plugin, WordPress, PHP, MySQL)
* Límites del servidor (memoria, ejecución, uploads)
* Configuración crítica del plugin

#### Dependencias

* 🟢 Paid Memberships Pro (con versión)
* 🟢 WooCommerce (con versión)
* 🟡 WooCommerce Subscriptions (recomendado)

#### Estado de Gateways

* API de PagBank (configurada/no configurada)
* Modo del gateway (Live/Sandbox)
* Prueba de conexión en tiempo real

#### Estadísticas de Logs

* Total de logs almacenados
* Logs de las últimas 24 horas
* Contadores por nivel de error
* Enlaces directos para revisar errores

* * *

🚨 Troubleshooting
------------------

### Problemas Comunes y Soluciones

#### 1. Membresías no se renuevan

**Diagnóstico:**

1. Verificar logs en: PMPRO-Woo Sync → Logs

2. Filtrar por nivel "error" o "warning"

3. Usar herramienta de debug específica para el usuario afectado

**Posibles causas:**

* Producto WooCommerce no vinculado correctamente
* Error en la API del gateway
* Configuración incorrecta de ciclos de facturación

#### 2. Múltiples renovaciones duplicadas

**Diagnóstico:**

* Revisar hooks duplicados en `functions.php`
* Verificar múltiples integraciones activas
* Comprobar logs para identificar fuente del problema

#### 3. Cancelaciones no se propagan al gateway

**Diagnóstico:**

1. Verificar configuración de API del gateway
2. Revisar logs para errores de comunicación
3. Comprobar vinculación membresía-suscripción
4. Usar herramienta "Verificar Estados de Gateway"

**Posibles causas:**

* Producto WooCommerce no vinculado correctamente
* Error en la API del gateway
* Configuración incorrecta de ciclos de facturación

#### 2. Múltiples renovaciones duplicadas

**Diagnóstico:**

* Revisar hooks duplicados en `functions.php`
* Verificar múltiples integraciones activas
* Comprobar logs para identificar fuente del problema

#### 3. Cancelaciones no se propagan al gateway

**Diagnóstico:**

1. Verificar configuración de API del gateway
. Revisar logs para errores de comunicación
3. Comprobar vinculación membresía-suscripción
4. Usar herramienta "Verificar Estados de Gateway"
4. Logs no aparecen o se llenan muy rápido

**Soluciones:**

* Ajustar nivel de logging (desactivar debug en producción)
* Configurar retención de logs apropiada
* Usar limpieza automática de logs antiguos

### Activar Debug Completo

#### Desde el Panel de Administración:

**PMPRO-Woo Sync → Configuraciones → Habilitar Modo Debug**

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

* ❌ Dependencias faltantes
* ⚠️ Configuraciones subóptimas
* 🔧 Problemas de conectividad con gateways
* 📊 Estadísticas anómalas en logs

* * *

📊 Casos de Uso Avanzados
-------------------------

### Caso 1: E-commerce con Múltiples Gateways

```graph
    A[Usuario Compra] --> B{Gateway Elegido}
    B -->|PagBank| C[Procesa con PagBank API]
    B -->|Stripe| D[Procesa con Stripe API]
    B -->|PayPal| E[Procesa con PayPal API]
    C --> F[Sincronización PMPro]
    D --> F
    E --> F
    F --> G[Usuario Accede a Contenido]
```

### Caso 2: Membresías con Diferentes Ciclos

| Tipo de Membresía | Ciclo WooCommerce | Acción PMPro          |
| ----------------- | ----------------- | --------------------- |
| Mensual Básica    | 30 días           | Renueva cada 30 días  |
| Trimestral Pro    | 90 días           | Renueva cada 90 días  |
| Anual Premium     | 365 días          | Renueva cada 365 días |

### Caso 3: Integración con Marketing Automation

```php
// Hook personalizado tras renovación exitosa
add_action('pmpro_woo_sync_membership_renewed', function($user_id, $level_id) {
    // Notificar a sistema de email marketing
    // Actualizar segmentación de usuarios
    // Activar secuencias de contenido
});
```

---

🔧 Integración con Gateways
--------

### PagBank Connect - Configuración Completa

#### Configuración en el Plugin:

1. **API Key**: Tu clave de API de PagBank
2. **Modo**: Sandbox (pruebas) o Live (producción)
3. **Timeout**: Tiempo límite para llamadas API (30s recomendado)
4. **Webhook URL**: Configurado automáticamente

#### Eventos Manejados:

```php
// Webhooks automáticos de PagBank
'subscription.payment_succeeded' => 'renovar_membresia'
'subscription.payment_failed'    => 'programar_reintento'  
'subscription.canceled'          => 'cancelar_membresia'
'subscription.payment_retrying'  => 'log_reintento'
```

### Stripe - Próximamente

El plugin está preparado para soportar Stripe con:

* Manejo de webhooks estándar
* API de cancelaciones
* Gestión de métodos de pago

### Desarrollo de Nuevos Gateways

```php
// Estructura base para nuevo gateway
class PMPro_Woo_Sync_New_Gateway_API {
    public function cancel_subscription($subscription_id) {
        // Implementar lógica especí

fica del gateway
    }

    public function test_connection() {
        // Verificar conectividad con API
    }
}
```

---

🤝 Contribución y Desarrollo
----------------------------

### Reportar Issues

1. **Activar Debug Mode** en configuraciones del plugin
2. **Reproducir el problema** paso a paso
3. **Exportar logs relevantes** desde el panel de administración
4. **Crear issue en GitHub** con información completa:
   * Versiones de dependencias
   * Configuración de gateway
   * Logs exportados
   * Pasos para reproducir

### Estructura de Desarrollo

```
# Clonar repositorio
git clone https://github.com/DavidCamejo/pmpro-woo-sync.git

# Estructura para desarrollo
pmpro-woo-sync/
├── /tests/                    # Tests unitarios (próximamente)
├── /docs/                     # Documentación adicional
├── package.json              # Dependencias de desarrollo
└── webpack.config.js         # Build de assets
```

### Coding Standards

* **PSR-4** para autoloading de clases
* **WordPress Coding Standards** para PHP
* **ESLint** para JavaScript
* **Documentación PHPDoc** completa
* **Hooks y filtros** bien documentados

* * *

📝 Changelog Completo
---------------------

### v1.0.0 - 2024-07-30

#### ✅ Added

* **Sistema de Administración Completo**
  
  * Panel de configuraciones con validación en tiempo real
  * Visualizador de logs con filtros avanzados y búsqueda
  * Página de herramientas con utilidades de diagnóstico
  * Dashboard de estado del sistema con monitoreo en vivo

* **Interfaz de Usuario Moderna**
  
  * Diseño responsive optimizado para todos los dispositivos
  * Indicadores visuales de estado con animaciones
  * Sistema de notificaciones AJAX no intrusivas
  * Loading states y feedback inmediato en todas las acciones

* **Sistema de Logging Avanzado**
  
  * 5 niveles de logs (success, info, warning, error, debug)
  * Base de datos dedicada con consultas optimizadas
  * Exportación de logs en formato JSON
  * Auto-refresh cada 30 segundos con pausa automática
  * Estadísticas en tiempo real de eventos
  * Modal de detalles con contex

to JSON completo

* **Herramientas de Diagnóstico**
  
  * Función de debug específica por usuario
  * Sincronización masiva de membresías
  * Reparación automática de enlaces rotos
  * Verificación de estados en gateways externos
  * Limpieza de metadatos huérfanos

* **Cancelación Bidireccional**
  
  * Propagación de cancelaciones desde PMPro a gateways
  * Soporte completo para PagBank API
  * Logging detallado de todas las operaciones de cancelación

* **Características Técnicas**
  
  * Autoloader optimizado con mapeo directo de clases
  * Verificación robusta de dependencias al activar
  * Soporte completo para multisitio
  * Scripts y estilos cargados solo en páginas relevantes
  * Validación client-side y server-side
  * Escape completo de salidas para prevenir XSS
  * Nonces en todas las operaciones AJAX

#### 🔄 Changed

* Migración completa a arquitectura orientada a objetos
* Panel de administración centralizado reemplaza configuración manual
* Logs almacenados en base de datos

en lugar de archivos

* Sistema de configuraciones con valores predeterminados

#### 🐛 Fixed

* Advertencia de propiedades dinámicas en PHP 8+
* Error de grupo de opciones en WordPress Settings API
* Problemas de sanitización y escape de datos
* Conflictos con otros plugins de membresía

#### 🗑️ Removed

* Configuraciones hardcodeadas en archivo principal
* Dependencia de archivos de log externos
* Funciones deprecated de versiones anteriores

* * *

📜 Licencia
-----------

Este proyecto está bajo la **Licencia MIT**. Consulta el archivo `LICENSE` para más detalles.

### Términos de Uso

* ✅ Uso comercial permitido
* ✅ Modificación permitida
* ✅ Distribución permitida
* ✅ Uso privado permitido
* ❌ Sin garantía
* ❌ Sin responsabilidad del autor

* * *

🙏 Agradecimientos
------------------

* **Desarrollado por**: David Camejo
* **Inspirado en**: Necesidades reales de sincronización PMPro-WooCommerce
* **Basado en análisis de**: [PagBank Connect Plugin](https://github.com/r-martins/PagBank-W

ooCommerce)

* **Testeado por**: Comunidad de desarrolladores WordPress

* * *

📞 Soporte y Contacto
---------------------

### Canales de Soporte

* 📧 **Email**: jdavidcamejo@gmail.com
* 🐛 **Issues**: GitHub Issues
* 📚 **Documentación**: Wiki del Proyecto
* 💬 **Discusiones**: GitHub Discussions

### Antes de Contactar

1. ✅ Revisar documentación completa
2. ✅ Buscar en issues existentes
3. ✅ Probar con debug mode activado
4. ✅ Exportar logs relevantes
5. ✅ Preparar información del sistema

### Información para Soporte

Incluir siempre:

* Versión del plugin
* Versión de WordPress
* Versión de WooCommerce y PMPro
* Configuración del gateway
* Logs exportados del problema
* Pasos detallados para reproducir

* * *

**⚡ ¡Mantén tus membresías siempre sincronizadas con la solución más completa!**

* * *

*Última actualización: 30 de Julio, 2025*
