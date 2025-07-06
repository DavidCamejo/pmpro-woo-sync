# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-07-06

### Added

- **Estructura de Plugin Modular y Profesional:** Refactorización completa del plugin a una arquitectura orientada a objetos (OOP) para mejorar la mantenibilidad, escalabilidad y legibilidad del código.
  - Introducción de clases dedicadas para el core, logger, settings, integraciones y gestores de gateways.
  - Creación de subdirectorios para una mejor organización (includes, admin, assets, etc.).
- **Panel de Administración Intuitivo:** Nuevo menú en el dashboard de WordPress (`PMPRO-Woo Sync`) con dos subpáginas:
  - **Ajustes:** Interfaz centralizada para configurar opciones del plugin como habilitar/deshabilitar sincronización, modo depuración y credenciales de API para gateways externos (ej. PagBank).
  - **Logs:** Visualizador de logs detallado directamente en el panel de administración, permitiendo filtrar por nivel y revisar el contexto de los eventos.
- **Sistema de Logging Mejorado:** Implementación de un sistema de logs robusto con diferentes niveles (info, warning, error, debug) almacenados en la base de datos para facilitar el monitoreo y troubleshooting.
- **Cancelación Bidireccional de Suscripciones (PMPro -> Gateway):** Nueva funcionalidad crítica que permite que las cancelaciones de membresías iniciadas desde PMPro se propaguen automáticamente al gateway de pago (ej. PagBank) para detener los cobros recurrentes.
  - Inclusión de un `Gateway Manager` y clases específicas para la interacción con las APIs de los gateways.
- **Manejo de Valores Predeterminados:** Implementación del método `get_default_settings()` para asegurar que los ajustes del plugin se inicialicen correctamente si no existen en la base de datos.

### Changed

- **Método de Configuración:** La configuración del plugin ahora se gestiona principalmente a través del panel de administración en lugar de variables directas en el código.
- **Acceso a Logs:** Los logs ahora son accesibles desde el panel de administración y se almacenan en la base de datos, en lugar de solo en un archivo plano.

### Fixed

- **Advertencia de Propiedad Dinámica (PHP Deprecated):** Corregido el error `Creation of dynamic property... is deprecated` al declarar explícitamente las propiedades de las clases.
- **Error de Grupo de Opciones:** Solucionado el error `a página de opções pmpro_woo_sync_option_group não foi encontrada` al asegurar la correcta y consistente definición del grupo de opciones en la API de Settings de WordPress.

### Removed

- Eliminadas las configuraciones directas en el archivo principal del plugin en favor del nuevo panel de administración.
