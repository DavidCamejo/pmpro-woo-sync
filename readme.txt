=== PMPro-WooCommerce Sync ===
Contributors: DavidCamejo
Tags: woocommerce, paid memberships pro, pmpro, subscriptions, sync, pagbank, membership, payments
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

== Description ==
Este plugin resuelve un problema crítico en la sincronización entre **Paid Memberships Pro (PMPro)** y **WooCommerce Subscriptions** para pagos recurrentes, especialmente con gateways externos como **PagBank**. Asegura que las membresías de PMPro se mantengan automáticamente sincronizadas con el estado real de las suscripciones de WooCommerce, incluyendo renovaciones, pagos fallidos, y ahora, **cancelaciones bidireccales**.

**Características Clave:**

* **Sincronización Automática:** Extiende membresías en renovaciones exitosas, gestiona pagos fallidos y cancela membresías en PMPro cuando las suscripciones de WooCommerce cambian de estado.
* **Cancelación Bidireccional (¡NUEVO!):** Propaga las cancelaciones de membresías iniciadas en PMPro directamente a los gateways de pago externos (como PagBank) para asegurar que los cobros recurrentes se detengan.
* **Sistema de Reintentos Inteligente:** Gestiona automáticamente los pagos fallidos con reintentos configurables.
* **Panel de Administración Intuitivo:** Centraliza la configuración del plugin y ofrece una interfaz para visualizar logs detallados.
* **Logging y Monitoreo Mejorado:** Registra eventos con niveles de detalle (info, warning, error, debug) y permite la visualización directa en el backend de WordPress.
* **Arquitectura Modular:** Diseñado con principios de programación orientada a objetos (OOP) para una mayor mantenibilidad y escalabilidad.

== Installation ==

1.  **Descarga** el archivo ZIP del plugin completo.
2.  **Sube** la carpeta `pmpro-woo-sync` al directorio `/wp-content/plugins/` de tu instalación de WordPress.
3.  **Activa** el plugin "PMPRO-WooCommerce Sync" desde el panel de administración de WordPress.
4.  **Configura** los ajustes del plugin navegando a `PMPRO-Woo Sync` en tu menú de administración para ingresar las credenciales de API del gateway y revisar los logs.

== Screenshots ==

(Aquí irían los enlaces a tus capturas de pantalla, por ejemplo:)
1.  Captura de pantalla de la página de ajustes generales.
2.  Captura de pantalla del visualizador de logs.

== Changelog ==

= 1.0.0 =
* **Added**
    * **Estructura de Plugin Modular y Profesional:** Refactorización completa a OOP.
    * **Panel de Administración Intuitivo:** Nuevo menú de ajustes y visualizador de logs.
    * **Sistema de Logging Mejorado:** Logs detallados por nivel, accesibles desde el admin.
    * **Cancelación Bidireccional de Suscripciones (PMPro -> Gateway):** Permite detener cobros remotos desde PMPro.
    * **Manejo de Valores Predeterminados:** Inicialización correcta de ajustes del plugin.
* **Changed**
    * La configuración del plugin ahora se gestiona desde el panel de administración.
    * Los logs son accesibles desde el panel de administración y se guardan en BD.
* **Fixed**
    * Advertencia de Propiedad Dinámica (PHP Deprecated).
    * Error de Grupo de Opciones en el panel de administración.
* **Removed**
    * Configuraciones directas en el archivo principal del plugin.
