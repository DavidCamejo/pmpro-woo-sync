=== PMPro-WooCommerce Sync ===
Contributors: DavidCamejo
Tags: woocommerce, paid memberships pro, pmpro, subscriptions, sync, pagbank, membership, payments, recurring, billing
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Resuelve problemas críticos de sincronización entre Paid Memberships Pro y WooCommerce Subscriptions para pagos recurrentes, especialmente con gateways externos como PagBank.

== Description ==

**PMPro-WooCommerce Sync** es la solución definitiva para mantener automáticamente sincronizadas las membresías de **Paid Memberships Pro (PMPro)** con el estado real de las suscripciones de **WooCommerce**, especialmente cuando se utilizan gateways de pago externos como **PagBank**.

= El Problema que Resuelve =

Cuando los usuarios adquieren membresías de PMPro a través de WooCommerce:
* El pago inicial se procesa correctamente
* La membresía se activa en PMPro  
* Los pagos recurrentes se procesan en WooCommerce
* **PERO** PMPro no se actualiza automáticamente con las renovaciones

Esto resulta en membresías que expiran aunque el pago recurrente sea exitoso, usuarios perdiendo acceso a contenido pagado y falta de sincronización entre sistemas.

= La Solución Completa =

Este plugin mantiene **automáticamente sincronizadas** las membresías de PMPro con WooCommerce, incluyendo **cancelación bidireccional** que propaga las cancelaciones desde PMPro directamente a los gateways de pago externos.

= Características Principales =

**🔄 Sincronización Automática**
* Extiende membresías automáticamente en renovaciones exitosas
* Sistema de reintentos inteligente para pagos fallidos
* Cancelaciones bidireccionales (WooCommerce ↔ PMPro ↔ Gateway)
* Actualización automática de fechas de expiración

**⚙️ Panel de Administración Profesional**
* **Configuraciones:** Interfaz centralizada con validación en tiempo real
* **Logs:** Visualizador avanzado con filtros, búsqueda y estadísticas
* **Herramientas:** Utilidades de diagnóstico, sincronización masiva y reparación
* **Estado:** Dashboard de monitoreo del sistema en tiempo real

**📊 Sistema de Logging Avanzado**
* 5 niveles de logs (success, info, warning, error, debug)
* Base de datos dedicada con consultas optimizadas
* Exportación de logs en formato JSON
* Auto-refresh cada 30 segundos con estadísticas en tiempo real
* Modal de detalles con contexto completo

**🛠️ Herramientas de Diagnóstico**
* Debug específico por usuario con información completa
* Sincronización masiva de todas las membresías
* Reparación automática de enlaces rotos
* Verificación de conectividad con gateways externos
* Limpieza de metadatos huérfanos

**🎨 Interfaz Moderna y Responsive**
* Diseño optimizado para móviles, tablets y escritorio
* Indicadores visuales de estado con animaciones
* Sistema de notificaciones AJAX no intrusivas
* Loading states y feedback inmediato

**🔐 Seguridad Reforzada**
* Nonces en todas las operaciones AJAX
* Sanitización completa de inputs y escape de outputs
* Validación de permisos en cada endpoint
* Prevención de XSS e inyección SQL

= Gateways Soportados =

**PagBank (Soporte Completo)**
* Cancelación automática de suscripciones
* Manejo de webhooks nativos
* Configuración de API con modo Sandbox/Live
* Logging detallado de todas las operaciones

**Otros Gateways**
* Compatible con cualquier gateway que use WooCommerce Subscriptions
* Arquitectura extensible para agregar nuevos gateways
* Próximamente: Stripe, PayPal, Mercado Pago

= Casos de Uso =

1. **E-commerce con Membresías Recurrentes:** Tiendas que venden acceso a contenido premium
2. **Sitios de Suscripción:** Plataformas con múltiples niveles de membresía
3. **Cursos Online:** Academias con acceso por suscripción mensual/anual
4. **Comunidades Privadas:** Foros o grupos con acceso de pago
5. **SaaS con WordPress:** Servicios con facturación recurrente

= Requisitos del Sistema =

* WordPress 5.0 o superior
* WooCommerce 4.0 o superior  
* Paid Memberships Pro 2.0 o superior
* PHP 7.4 o superior
* MySQL 5.6 o superior

== Installation ==

= Instalación Automática =

1. Desde el panel de WordPress, ve a `Plugins → Añadir nuevo`
2. Busca "PMPro-WooCommerce Sync"
3. Instala y activa el plugin
4. Ve a `PMPRO-Woo Sync` en el menú lateral para configurar

= Instalación Manual =

1. Descarga el archivo ZIP del plugin
2. Ve a `Plugins → Añadir nuevo → Subir plugin`
3. Selecciona el archivo ZIP y haz clic en "Instalar ahora"
4. Activa el plugin
5. Configura desde `PMPRO-Woo Sync` en el menú de administración

= Configuración Inicial =

1. **Activar Sincronización:** Ve a `PMPRO-Woo Sync → Configuraciones` y habilita la sincronización
2. **Configurar Gateway:** Ingresa las credenciales de API de tu gateway (ej. PagBank)
3. **Revisar Logs:** Verifica el funcionamiento en `PMPRO-Woo Sync → Logs`
4. **Probar Conexión:** Usa las herramientas de verificación en `PMPRO-Woo Sync → Herramientas`

== Frequently Asked Questions ==

= ¿Es compatible con mi gateway de pago? =

El plugin tiene soporte completo para PagBank y es compatible con cualquier gateway que use WooCommerce Subscriptions. Para gateways específicos, consulta la documentación completa.

= ¿Afecta el rendimiento de mi sitio? =

No. El plugin está optimizado para cargar recursos solo cuando es necesario y utiliza técnicas avanzadas de performance como consultas optimizadas y carga condicional de assets.

= ¿Puedo desactivar el plugin sin perder datos? =

Sí. Toda la información de membresías se mantiene en PMPro y WooCommerce. El plugin solo gestiona la sincronización entre ambos.

= ¿Cómo funciona la cancelación bidireccional? =

Cuando un usuario cancela su membresía en PMPro, el plugin automáticamente:
1. Encuentra la suscripción asociada en WooCommerce
2. Cancela la suscripción en WooCommerce  
3. Notifica al gateway externo (ej. PagBank) para detener los cobros
4. Registra toda la operación en los logs

= ¿Qué información contienen los logs? =

Los logs incluyen:
* Timestamp exacto de cada evento
* Nivel de importancia (success, info, warning, error, debug)
* Mensaje descriptivo de la acción
* Contexto adicional en formato JSON (IDs, datos relevantes)
* Información de debugging para troubleshooting

= ¿Es seguro para uso en producción? =

Absolutamente. El plugin incluye:
* Validación robusta de datos
* Escape completo de salidas
* Protección CSRF con nonces
* Verificación de permisos de usuario
* Manejo seguro de errores

= ¿Puedo personalizar el comportamiento del plugin? =

Sí. El plugin incluye múltiples hooks y filtros para personalización:

`// Cambiar máximo de reintentos
add_filter('pmpro_woo_sync_max_retries', function($max) {
    return 5; // 5 reintentos
});`

= ¿Hay soporte para multisitio? =

Sí. El plugin es completamente compatible con WordPress Network/Multisitio.

== Screenshots ==

1. **Panel de Configuraciones** - Interfaz centralizada para todos los ajustes del plugin
2. **Visualizador de Logs** - Dashboard con estadísticas en tiempo real y filtros avanzados  
3. **Página de Herramientas** - Utilidades de diagnóstico y reparación
4. **Estado del Sistema** - Monitoreo de dependencias y información del servidor
5. **Modal de Detalles** - Vista expandida de logs con contexto completo
6. **Indicadores de Estado** - Información visual del estado de sincronización
7. **Debug de Usuario** - Herramienta específica para troubleshooting por usuario

== Changelog ==

= 1.0.0 - 2024-07-30 =

**Added**
* Sistema de administración completo con 4 páginas principales
* Interfaz de usuario moderna y responsive
* Sistema de logging avanzado con 5 niveles
* Herramientas de diagnóstico profesionales  
* Cancelación bidireccional PMPro → Gateway
* Características técnicas avanzadas (autoloader, seguridad, performance)
* Sistema AJAX completo con 5 endpoints
* Estilos CSS profesionales con animaciones
* Soporte completo para PagBank API

**Changed**
* Migración completa a arquitectura orientada a objetos
* Panel centralizado reemplaza configuración manual
* Logs en base de datos en lugar de archivos
* API de configuraciones con WordPress Settings

**Fixed**
* Compatibilidad con PHP 8.1+ (propiedades dinámicas)
* Integración correcta con WordPress Settings API
* Seguridad reforzada (XSS, CSRF, SQL injection)
* Optimizaciones de performance y memory leaks

**Security**
* Input sanitization completa
* Output escaping en todas las salidas
* Protección CSRF con nonces
* Validación de permisos robusta

== Upgrade Notice ==

= 1.0.0 =
Versión inicial estable con funcionalidades completas. Migración segura desde versiones beta. Se recomienda hacer backup antes de actualizar.

== Additional Info ==

= Links Útiles =
* [Documentación Completa](https://github.com/DavidCamejo/pmpro-woo-sync)
* [Reportar Issues](https://github.com/DavidCamejo/pmpro-woo-sync/issues)
* [Código Fuente](https://github.com/DavidCamejo/pmpro-woo-sync)

= Soporte =
* Email: jdavidcamejo@gmail.com
* GitHub Issues para bugs y feature requests
* Documentación detallada en el repositorio

= Próximas Características =
* Soporte para Stripe Payment Gateway
* Herramientas de migración masiva
* Dashboard de reportes avanzados
* Sistema de notificaciones por email
* API REST para integraciones externas

= Requisitos Recomendados =
* WordPress 6.0+
* WooCommerce 7.0+
* PHP 8.0+
* MySQL 8.0+
* Memoria: 256MB+
* Disco: 50MB para logs

---

**¡Mantén tus membresías siempre sincronizadas!**
