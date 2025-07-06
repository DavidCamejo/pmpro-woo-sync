# Informe Detallado: Aceitando Pagamentos Recorrentes com WooCommerce - Visão Geral

## Sección "Funcionamento e funcionalidades"

### Punto: "Uma assinatura é gerada a partir de um pedido inicial, e as cobranças subsequentes gerarão novos pedidos"

Este mecanismo de suscripciones recurrentes se implementa mediante una integración entre WooCommerce y PagBank que sigue un flujo específico, como se puede observar en el código fuente del plugin.

#### Lógica de Implementación:

1. **Creación de la Suscripción desde el Pedido Inicial**:
   
   - Cuando un cliente realiza el primer pago de un producto/servicio recurrente, se crea un pedido estándar en WooCommerce.
   - El plugin detecta que es un producto recurrente y crea una suscripción asociada a este pedido inicial.
   - Esta suscripción se registra tanto en WooCommerce como en los sistemas de PagBank.

2. **Proceso de Cobro Recurrente**:
   
   - Para cada ciclo de facturación (mensual, anual, etc.), el sistema de PagBank inicia el proceso de cobro.
   - El plugin recibe una notificación (webhook) desde PagBank sobre el cobro recurrente exitoso o fallido.
   - Al recibir la confirmación de pago, el plugin crea automáticamente un nuevo pedido en WooCommerce asociado a la suscripción original.

3. **Flujo del Código** (basado en el repositorio):
   
   - **Clase Principal**: `class-wc-pagbank.php` maneja los hooks y registra los gatillos para suscripciones.
   - **Handler de Webhooks**: `class-wc-pagbank-webhook-handler.php` procesa las notificaciones de PagBank y gestiona la creación de nuevos pedidos.
     - Método `handle_subscription_charge` se encarga específicamente de procesar los cargos recurrentes.
   - **API Client**: `class-wc-pagbank-api.php` maneja la comunicación con los endpoints de PagBank para crear y gestionar suscripciones.

4. **Creación de Nuevos Pedidos**:
   
   ```php
   // Ejemplo simplificado del proceso (basado en el código)
   public function create_renewal_order($subscription) {
       $renewal_order = wc_create_order(array(
           'customer_id' => $subscription->get_customer_id(),
           'status' => 'pending'
       ));
   
       // Copiar items y datos de la suscripción al nuevo pedido
       foreach ($subscription->get_items() as $item) {
           $renewal_order->add_product($item->get_product(), $item->get_quantity());
       }
   
       // Actualizar metadatos y relación con la suscripción
       $renewal_order->update_meta_data('_pagbank_subscription_id', $subscription->get_id());
       $renewal_order->save();
   
       return $renewal_order;
   }
   ```

5. **Sincronización de Estados**:
   
   - El plugin mantiene sincronizados los estados entre WooCommerce y PagBank:
     - Suscripción activa → cobros automáticos continuan
     - Suscripción cancelada → se detienen los cobros recurrentes
     - Fallo en el pago → reintentos según configuración

6. **Gestión de Errores**:
   
   - El código incluye manejo robusto de errores para:
     - Fallos en la comunicación con PagBank
     - Rechazos de tarjetas
     - Problemas al crear nuevos pedidos
   - En caso de error, el sistema registra logs detallados y puede notificar al administrador.

#### Consideraciones Técnicas Importantes:

1. **Seguridad**: Todos los procesos recurrentes se validan mediante firmas digitales y verificaciones de webhooks para garantizar que solo PagBank pueda iniciar estos procesos.

2. **Idempotencia**: El código implementa checks de idempotencia para evitar duplicación de pedidos si recibe la misma notificación múltiples veces.

3. **Compatibilidad**: La implementación sigue los estándares de WooCommerce Subscriptions para garantizar compatibilidad con otros plugins y extensiones.

Esta arquitectura permite una integración fluida entre WooCommerce y PagBank para gestionar pagos recurrentes de manera automatizada y confiable, manteniendo un registro completo de todos los pedidos asociados a cada suscripción en el sistema WooCommerce.
