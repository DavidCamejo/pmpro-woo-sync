# Implementación de Hook para Sincronización PMPro-WooCommerce

Basado en el análisis del plugin PagBank-WooCommerce y la integración con Paid Memberships Pro (PMPro), voy a diseñar un sistema de hooks que mantenga sincronizadas las suscripciones de PMPro cuando se procesen pagos recurrentes a través de WooCommerce.

## Solución Propuesta

### 1. Estructura Básica del Hook

```php
/**
 * Sincroniza suscripciones de PMPro cuando WooCommerce procesa un pago recurrente
 */
add_action('woocommerce_order_status_changed', 'pmpro_woo_sync_subscription_on_renewal', 20, 3);

function pmpro_woo_sync_subscription_on_renewal($order_id, $old_status, $new_status) {
    // Verificar si es un pedido de renovación de suscripción
    if (!is_subscription_renewal_order($order_id)) {
        return;
    }

    // Obtener información del pedido y suscripción
    $order = wc_get_order($order_id);
    $subscription_id = $order->get_meta('_subscription_id');
    $user_id = $order->get_user_id();

    // Procesar según el estado del pedido
    switch ($new_status) {
        case 'completed':
            handle_successful_renewal($user_id, $subscription_id, $order);
            break;

        case 'failed':
            handle_failed_renewal($user_id, $subscription_id, $order);
            break;

        case 'cancelled':
            handle_cancelled_renewal($user_id, $subscription_id, $order);
            break;
    }
}
```

### 2. Funciones Auxiliares

#### Detección de Pedidos de Renovación

```php
function is_subscription_renewal_order($order_id) {
    $order = wc_get_order($order_id);

    // Verificar si es un pedido de renovación
    if ($order->get_meta('_subscription_renewal')) {
        return true;
    }

    // Verificar si está asociado a una suscripción PMPro
    $pmpro_subscription_id = $order->get_meta('_pmpro_subscription_id');
    return !empty($pmpro_subscription_id);
}
```

#### Manejo de Renovación Exitosa

```php
function handle_successful_renewal($user_id, $subscription_id, $order) {
    // Obtener el nivel de membresía asociado
    $membership_level_id = $order->get_meta('_pmpro_membership_level_id');

    // Extender la membresía en PMPro
    pmpro_changeMembershipLevel(
        $membership_level_id,
        $user_id,
        'changed',
        $order->get_id()
    );

    // Registrar el pago en PMPro
    if (!pmpro_getMembershipLevelForUser($user_id)) {
        // Si el usuario no tenía nivel, es una nueva suscripción
        pmpro_updated_order($order->get_id(), 'success');
    } else {
        // Si ya tenía nivel, es una renovación
        $last_order = new MemberOrder();
        $last_order->getLastMemberOrder($user_id);

        $new_order = new MemberOrder();
        $new_order->user_id = $user_id;
        $new_order->membership_id = $membership_level_id;
        $new_order->InitialPayment = $order->get_total();
        $new_order->PaymentAmount = $order->get_total();
        $new_order->payment_transaction_id = $order->get_transaction_id();
        $new_order->gateway = 'woocommerce';
        $new_order->subscription_transaction_id = $subscription_id;
        $new_order->status = 'success';
        $new_order->saveOrder();
    }

    // Actualizar fechas de ciclo
    update_pmpro_dates($user_id, $order);
}
```

#### Manejo de Fallos en el Pago

```php
function handle_failed_renewal($user_id, $subscription_id, $order) {
    // Obtener información de la suscripción PMPro
    $pmpro_subscription = pmpro_getSubscription($subscription_id);

    if (!empty($pmpro_subscription)) {
        // Intentar cobro nuevamente según configuración
        $retry_attempts = get_option('pmpro_woo_retry_attempts', 3);

        if ($pmpro_subscription->retry_attempts < $retry_attempts) {
            // Incrementar contador de intentos
            $pmpro_subscription->retry_attempts++;
            $pmpro_subscription->save();

            // Programar próximo intento
            wp_schedule_single_event(
                time() + (2 * DAY_IN_SECONDS), // 2 días después
                'pmpro_woo_retry_payment',
                array($subscription_id, $order->get_id())
            );
        } else {
            // Cancelar suscripción después de máximos intentos
            pmpro_cancelSubscription($subscription_id);
        }
    }
}
```

#### Actualización de Fechas de Ciclo

```php
function update_pmpro_dates($user_id, $order) {
    $membership_level_id = $order->get_meta('_pmpro_membership_level_id');
    $cycle_period = $order->get_meta('_pmpro_cycle_period'); // month, year, etc.
    $cycle_number = $order->get_meta('_pmpro_cycle_number', 1);

    // Calcular nueva fecha de expiración
    $startdate = current_time('mysql');
    $enddate = date('Y-m-d H:i:s', strtotime("+{$cycle_number} {$cycle_period}", strtotime($startdate)));

    // Actualizar en la base de datos de PMPro
    global $wpdb;
    $wpdb->update(
        $wpdb->pmpro_memberships_users,
        array(
            'startdate' => $startdate,
            'enddate' => $enddate
        ),
        array(
            'user_id' => $user_id,
            'membership_id' => $membership_level_id,
            'status' => 'active'
        )
    );
}
```

### 3. Hook Adicional para Webhooks de PagBank

Para completar la integración con el análisis inicial del plugin PagBank-WooCommerce:

```php
/**
 * Extender el handler de webhooks para incluir PMPro
 */
add_filter('pagbank_webhook_subscription_payment', 'pmpro_handle_pagbank_webhook', 10, 2);

function pmpro_handle_pagbank_webhook($processed, $webhook_data) {
    if ($processed) {
        return $processed;
    }

    // Verificar si es un pedido asociado a PMPro
    $order_id = $webhook_data['order_id'];
    $order = wc_get_order($order_id);

    if ($order->get_meta('_pmpro_subscription_id')) {
        // Disparar el procesamiento de estado del pedido
        do_action('woocommerce_order_status_' . $webhook_data['status'], $order_id);
        return true;
    }

    return false;
}
```

## Implementación Recomendada

1. **Archivo de Integración**: Crear un nuevo archivo `pmpro-woocommerce-subscriptions.php` en tu plugin personalizado o en `wp-content/mu-plugins/`

2. **Dependencias**: Asegurarse de que tanto WooCommerce como PMPro estén activos antes de ejecutar los hooks:
   
   ```php
   add_action('plugins_loaded', 'init_pmpro_woo_integration');
   ```

function init_pmpro_woo_integration() {
    if (class_exists('WooCommerce') && function_exists('pmpro_getMembershipLevels')) {
        // Inicializar todos los hooks aquí
        require_once 'pmpro-woocommerce-subscriptions.php';
    }
}

```
3. **Manejo de Errores**: Implementar logging para diagnóstico:
```php
function pmpro_woo_log($message, $data = null) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $log_entry = date('[Y-m-d H:i:s]') . ' ' . $message;

        if ($data) {
            $log_entry .= "\n" . print_r($data, true);
        }

        file_put_contents(
            WP_CONTENT_DIR . '/pmpro-woo-integration.log',
            $log_entry . "\n",
            FILE_APPEND
        );
    }
}
```

## Consideraciones Finales

1. **Pruebas**: Verificar todos los escenarios:
   
   - Primer pago exitoso
   - Renovaciones exitosas
   - Fallos en pagos
   - Cancelaciones
   - Reintentos automáticos

2. **Compatibilidad**: Este sistema está diseñado para trabajar con:
   
   - WooCommerce Subscriptions
   - Paid Memberships Pro
   - La integración PMPro-WooCommerce

3. **Personalización**: Los hooks pueden ajustarse según necesidades específicas:
   
   - Períodos de gracia para renovaciones fallidas
   - Notificaciones personalizadas
   - Lógica especial para diferentes niveles de membresía

Esta implementación mantendrá perfectamente sincronizadas las suscripciones de PMPro con los pagos recurrentes procesados a través de WooCommerce, extendiendo la funcionalidad del add-on oficial de PMPro para WooCommerce.
