

---

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

- **Cancelaciones Bidireccionales**: **Cancela la membresía en PMPro cuando se cancela en WooCommerce y, ¡NUEVO!, propaga las cancelaciones desde PMPro a los gateways de pago externos para detener los cobros recurrentes.**

- **Fechas de Expiración**: Calcula y actualiza automáticamente las fechas de renovación

### ✅ Sistema de Reintentos Inteligente

- Reintentos automáticos para pagos fallidos

- Límite configurable de intentos

- Suspensión automática tras máximos reintentos

- Limpieza automática de intentos exitosos

### ✅ **Panel de Administración Intuitivo (¡NUEVO!)**

- **Ajustes Centralizados**: Una sección dedicada en el panel de WordPress para configurar fácilmente las opciones del plugin, **incluyendo credenciales de API para los gateways de pago**.

- **Visualización de Logs**: Interfaz para revisar detalladamente los logs de eventos del plugin, facilitando el monitoreo y la depuración.

### ✅ **Logging y Monitoreo Mejorado (¡NUEVO!)**

- **Logs Detallados por Nivel**: Registra eventos con niveles específicos (`info`, `warning`, `error`, `debug`) para un análisis más preciso.

- **Acceso Directo desde Admin**: Consulta los logs directamente en el panel de administración, sin necesidad de acceder a archivos.

- **Función de Debug para Troubleshooting**: Ayuda a diagnosticar problemas con información contextual.

- **Rotación Automática de Logs**: Gestión eficiente del espacio.

- **Compatible con WP_DEBUG**.

### ✅ Compatibilidad Amplia

- Funciona con WooCommerce Subscriptions

- Compatible con PagBank y otros gateways

- Detecta múltiples tipos de pedidos de renovación

- Integración con webhooks existentes

---

## 🏗️ Arquitectura

### **Estructura Modular y Profesional (¡NUEVO!)**

El plugin ha sido refactorizado para seguir los estándares de desarrollo de WordPress y principios de programación orientada a objetos (OOP), mejorando la legibilidad, mantenibilidad y escalabilidad.

```
pmpro-woo-sync/
├── pmpro-woo-sync.php       <-- Archivo principal del plugin (bootstrap)
├── readme.txt               <-- Información estándar del plugin
├── uninstall.php            <-- Script de desinstalación limpia
├── /assets/                 <-- Archivos CSS y JS (front y admin)
│   ├── /css/
│   └── /js/
├── /includes/               <-- Clases principales y lógica del negocio
│   ├── class-pmpro-woo-sync.php          <-- Orquestador principal del plugin
│   ├── class-pmpro-woo-sync-integrations.php  <-- Lógica específica de integración (WooCommerce, PMPro)
│   ├── class-pmpro-woo-sync-logger.php   <-- Clase para el sistema de logs
│   ├── class-pmpro-woo-sync-settings.php <-- Clase para gestionar las opciones de configuración
│   └── class-pmpro-woo-sync-gateway-manager.php <-- ¡NUEVO! Gestiona las interacciones con APIs de gateways.
│       └── /gateways/                   <-- ¡NUEVO! Clases específicas para cada gateway (ej. PagBank).
│           ├── class-pmpro-woo-sync-pagbank-api.php
├── /admin/                  <-- Funcionalidades y vistas del panel de administración
│   ├── class-pmpro-woo-sync-admin.php    <-- Clase para la interfaz de administración
│   └── /partials/                        <-- Plantillas HTML para el panel de administración
│       ├── admin-display-settings.php
│       └── admin-display-logs.php
└── /languages/              <-- Archivos de internacionalización (.po, .mo)
```

### Flujo de Funcionamiento

1. **Usuario realiza compra inicial** → PMPro + WooCommerce se sincronizan normalmente

2. **Gateway procesa pago recurrente** → WooCommerce crea nuevo pedido

3. **Plugin detecta renovación** → Verifica si es pedido de suscripción

4. **Sincronización automática** → Actualiza PMPro según estado del pedido

Fragmento do código

```
graph LR
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

### Flujo de Cancelación Bidireccional (¡NUEVO!)

Fragmento do código

```
sequenceDiagram
    participant U as Usuario
    participant P as PMPro
    participant S as PMPro-Woo Sync Plugin
    participant W as WooCommerce
    participant G as Gateway de Pago (ej. PagBank)

    U->>P: Cancela membresía en PMPro
    P->>S: Hook pmpro_after_change_membership_level
    S->>S: Determina si necesita cancelación remota
    S->>W: Busca suscripción WooCommerce relacionada
    W->>S: Retorna datos de suscripción
    S->>G: API Call para cancelar suscripción
    G-->>S: Confirmación de cancelación (o error)
    S->>W: Actualiza estado en WooCommerce (si el gateway no usa webhooks para esto)
    S->>S: Registra acción en logs del plugin
```

### Estados Manejados

| Estado WooCommerce | Acción en PMPro                                               |
| ------------------ | ------------------------------------------------------------- |
| `completed`        | ✅ Extiende membresía + registra pago                          |
| `processing`       | ✅ Extiende membresía + registra pago                          |
| `failed`           | ⚠️ Programa reintento automático                              |
| `cancelled`        | ❌ Cancela membresía + **intenta cancelar en gateway externo** |

---

## 📥 Instalación

### Requisitos Previos

- WordPress 5.0+

- WooCommerce 4.0+

- Paid Memberships Pro 2.0+

- PHP 7.4+

### Pasos de Instalación

1. **Descarga el repositorio completo del plugin.**

2. **Sube el contenido:** Descomprime el archivo y sube la carpeta `pmpro-woo-sync` completa al directorio `/wp-content/plugins/` de tu instalación de WordPress.

3. **Activa el Plugin:** Ve al panel de administración de WordPress, navega a `Plugins` y activa "PMPRO-WooCommerce Sync".

4. **Configura el Plugin:** Una vez activado, navega a **`PMPRO-Woo Sync`** en el menú lateral de WordPress para configurar los ajustes y revisar los logs.

---

## ⚙️ Configuración

Ahora, las configuraciones principales se gestionan a través del **Panel de Administración**.

### Panel de Administración (Recomendado)

Accede a los ajustes del plugin en:

**WordPress Admin → PMPRO-Woo Sync → Ajustes**

Aquí podrás:

- **Habilitar/Deshabilitar Sincronización:** Controlar si el plugin está activo.

- **Activar/Desactivar Modo Depuración:** Para obtener logs más detallados que ayudan en el diagnóstico.

- **(Nuevo) Configuración de Gateways:** Ingresa tus credenciales de API (ej. API Key de PagBank) y selecciona el modo (Sandbox/Live) para los gateways de pago externos que requieran sincronización de cancelaciones.

- **(Próximamente) Mapeo de Niveles/Productos:** Configurar las relaciones entre los niveles de membresía de PMPRO y los productos de suscripción de WooCommerce.

### Configuración por Hooks (Opcional, para ajustes específicos)

Puedes seguir utilizando hooks para ajustar ciertos comportamientos:

PHP

```
// Cambiar máximo de reintentos
add_filter('pmpro_woo_sync_max_retries', function($max) {
    return 5; // 5 reintentos en lugar de 3
});

// Cambiar días entre reintentos
add_filter('pmpro_woo_sync_retry_delay', function($days) {
    return 3; // 3 días en lugar de 2
});
```

---

## 🔍 Monitoreo y Debug

### Logs del Sistema (Mejorado)

Los logs se guardan en una **tabla de base de datos dedicada**, accesibles directamente desde el panel de administración.

Accede a los Logs en:

**WordPress Admin → PMPRO-Woo Sync → Logs**

Aquí podrás:

- Ver una lista paginada de todos los eventos del plugin.

- Filtrar logs por nivel (Info, Advertencia, Error, Depuración).

- Inspeccionar el contexto detallado de cada evento.

Además, los **Errores Críticos** también se registran en el **WordPress Error Log** estándar para compatibilidad con herramientas de monitoreo externas.

### Función de Debug

Para troubleshooting, puedes usar esta función:

PHP

```
// Obtener información de debug de un usuario (desde un hook o script personalizado)
$debug_info = pmpro_woo_sync_debug_info($user_id);
print_r($debug_info);
```

**Ejemplo de salida:**

PHP

```
Array(
    [user_id] => 123
    [current_membership] => Array(
        [ID] => 1
        [name] => Premium
        [enddate] => 2024-12-31 23:59:59
    )
    [recent_orders] => Array(
        [0] => Array(
            [id] => 456
            [status] => completed
            [total] => 29.99
            [date] => 2024-07-01 10:30:00
            [is_subscription] => true
        )
    )
)
```

---

## 📊 Casos de Uso

### Caso 1: Renovación Exitosa

```
Usuario con membresía → Pago recurrente exitoso → Membresía extendida automáticamente
```

### Caso 2: Pago Fallido

```
Pago recurrente falla → Reintento automático en 2 días → Máximo 3 intentos → Membresía suspendida
```

### Caso 3: Cancelación Unidireccional (Desde WooCommerce)

```
Usuario cancela suscripción en WooCommerce (o PagBank) → WooCommerce marca como cancelado → PMPro cancela membresía
```

### Caso 4: Cancelación Bidireccional (Desde PMPro)

```
Usuario cancela membresía en PMPro → Plugin detecta cancelación → Plugin notifica a WooCommerce y al Gateway (ej. PagBank) para detener cobros.
```

---

## 🔧 Integración con Gateways

### PagBank Connect

El plugin está específicamente optimizado para trabajar con PagBank:

PHP

```
// Maneja webhooks de PagBank automáticamente
add_filter('pagbank_webhook_subscription_payment', 'handle_pagbank_webhook');
```

### Otros Gateways

Compatible con cualquier gateway que:

- Use WooCommerce Subscriptions

- Cree nuevos pedidos para renovaciones

- Dispare hooks estándar de WooCommerce

---

## 🚨 Troubleshooting

### Problemas Comunes

**1. Membresías no se renuevan**

Bash

```
# Verificar logs en el panel de administración
# Ir a PMPRO-Woo Sync -> Logs
# También puedes verificar los logs de errores de PHP en tu servidor
```

**2. Múltiples renovaciones**

- Verifica que no tengas múltiples integraciones activas

- Revisa hooks duplicados en functions.php

**3. Fechas incorrectas**

- Verifica configuración de timezone en WordPress

- Revisa metadatos de ciclo en productos

**4. La cancelación de PMPro no detiene el cobro recurrente en el Gateway**

Bash

```
# Asegúrate de que las credenciales de API del Gateway (ej. PagBank) estén configuradas correctamente en los ajustes del plugin.
# Revisa los logs del plugin en el panel de administración para ver si hay errores al intentar comunicarse con el Gateway.
# Verifica que el nivel de membresía y la suscripción de WooCommerce estén correctamente vinculados.
```

### Activar Debug Mode

Activa el modo depuración desde el panel de administración del plugin:

**WordPress Admin → PMPRO-Woo Sync → Ajustes → Habilitar Modo Depuración**

También puedes activar el `WP_DEBUG_LOG` en tu `wp-config.php` para registros adicionales del sistema:

PHP

```
// En wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true); // Asegúrate de que esto esté en 'true' para escribir en el archivo de log.
```

---

## 🤝 Contribución

### Reportar Problemas

1. Activa el Modo Depuración en los ajustes del plugin.

2. Reproduce el problema.

3. Incluye logs relevantes (copiados desde el panel de logs del plugin).

4. Describe los pasos para reproducir detalladamente.

### Desarrollo

Bash

```
# Clonar repositorio
git clone https://github.com/DavidCamejo/pmpro-woo-sync.git

# La instalación se realiza subiendo la carpeta completa a /wp-content/plugins/
```

---

## 📝 Changelog

### v1.0.0

- ✅ Sincronización automática PMPro-WooCommerce

- ✅ Sistema de reintentos para pagos fallidos

- ✅ **Panel de administración para ajustes y logs.**

- ✅ **Estructura modular y profesional del plugin.**

- ✅ Logging detallado y función de debug

- ✅ Compatibilidad con PagBank Connect

- ✅ Manejo de múltiples tipos de renovación

- ✅ **NUEVO: Implementación de cancelación bidireccional (PMPro al Gateway).**

---

## 📜 Licencia

Este proyecto está bajo la licencia MIT. Consulta el archivo `LICENSE` para más detalles.

---

## 🙏 Agradecimientos

- Desarrollado por [David Camejo](https://github.com/DavidCamejo)

- Basado en el análisis del plugin [PagBank Connect](https://github.com/r-martins/PagBank-WooCommerce)

- Inspirado en la necesidad de sincronización automática PMPro-WooCommerce

---

## 📞 Soporte

Para soporte técnico:

- 📧 Email: [jdavidcamejo@gmail.com]

- 🐛 Issues: [GitHub Issues](https://github.com/DavidCamejo/pmpro-woo-sync/issues)

- 📚 Documentación: [Wiki del Proyecto](https://github.com/DavidCamejo/pmpro-woo-sync/wiki)

---

**⚡ ¡Mantén tus membresías siempre sincronizadas!**
