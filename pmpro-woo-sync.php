<?php
/**
 * Plugin Name: PMPro-WooCommerce Sync Enhanced
 * Description: Sincronización mejorada entre PMPro y WooCommerce para pagos recurrentes
 * Version: 1.0
 * Author: David Camejo
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class PMPro_WooCommerce_Sync {
    
    private $log_enabled = true;
    private $max_retry_attempts = 3;
    private $retry_delay_days = 2;
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    public function init() {
        // Verificar dependencias
        if (!$this->check_dependencies()) {
            return;
        }
        
        // Registrar hooks principales
        $this->register_hooks();
    }
    
    private function check_dependencies() {
        if (!class_exists('WooCommerce')) {
            $this->log('WooCommerce no está activo');
            return false;
        }
        
        if (!function_exists('pmpro_getMembershipLevels')) {
            $this->log('PMPro no está activo');
            return false;
        }
        
        return true;
    }
    
    private function register_hooks() {
        // Hook principal para cambios de estado de pedidos
        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 20, 3);
        
        // Hook para webhooks de PagBank
        add_filter('pagbank_webhook_subscription_payment', array($this, 'handle_pagbank_webhook'), 10, 2);
        
        // Hook para reintentos programados
        add_action('pmpro_woo_retry_payment', array($this, 'process_retry_payment'), 10, 2);
        
        // Hook para limpieza de datos
        add_action('wp_scheduled_delete', array($this, 'cleanup_old_logs'));
    }
    
    /**
     * Maneja cambios de estado en pedidos de WooCommerce
     */
    public function handle_order_status_change($order_id, $old_status, $new_status) {
        // Verificar si es pedido de renovación
        if (!$this->is_subscription_renewal_order($order_id)) {
            return;
        }
        
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();
        
        // Validar datos básicos
        if (!$user_id || !$order) {
            $this->log("Datos inválidos para orden $order_id");
            return;
        }
        
        $this->log("Procesando cambio de estado: $old_status -> $new_status para orden $order_id");
        
        // Procesar según el nuevo estado
        switch ($new_status) {
            case 'completed':
            case 'processing':
                $this->handle_successful_renewal($user_id, $order);
                break;
                
            case 'failed':
                $this->handle_failed_renewal($user_id, $order);
                break;
                
            case 'cancelled':
                $this->handle_cancelled_renewal($user_id, $order);
                break;
        }
    }
    
    /**
     * Verifica si es un pedido de renovación de suscripción
     */
    private function is_subscription_renewal_order($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return false;
        }
        
        // Verificar múltiples indicadores de renovación
        $renewal_indicators = array(
            '_subscription_renewal',
            '_pmpro_subscription_id',
            '_subscription_id',
            '_pagbank_subscription_id'
        );
        
        foreach ($renewal_indicators as $meta_key) {
            if ($order->get_meta($meta_key)) {
                return true;
            }
        }
        
        // Verificar si tiene productos de suscripción
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && $product->get_meta('_pmpro_membership_level')) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Maneja renovaciones exitosas
     */
    private function handle_successful_renewal($user_id, $order) {
        try {
            // Obtener nivel de membresía
            $membership_level_id = $this->get_membership_level_from_order($order);
            
            if (!$membership_level_id) {
                $this->log("No se encontró nivel de membresía para orden " . $order->get_id());
                return;
            }
            
            // Actualizar membresía en PMPro
            $this->update_pmpro_membership($user_id, $membership_level_id, $order);
            
            // Registrar pago en PMPro
            $this->register_pmpro_payment($user_id, $membership_level_id, $order);
            
            // Actualizar fechas de ciclo
            $this->update_membership_dates($user_id, $membership_level_id, $order);
            
            // Limpiar intentos de reintento previos
            $this->clear_retry_attempts($order);
            
            $this->log("Renovación exitosa procesada para usuario $user_id, orden " . $order->get_id());
            
        } catch (Exception $e) {
            $this->log("Error procesando renovación exitosa: " . $e->getMessage());
        }
    }
    
    /**
     * Maneja fallos en renovaciones
     */
    private function handle_failed_renewal($user_id, $order) {
        try {
            $order_id = $order->get_id();
            $current_attempts = (int) $order->get_meta('_pmpro_retry_attempts', 0);
            
            if ($current_attempts < $this->max_retry_attempts) {
                // Incrementar contador de intentos
                $order->update_meta_data('_pmpro_retry_attempts', $current_attempts + 1);
                $order->save();
                
                // Programar reintento
                $retry_time = time() + ($this->retry_delay_days * DAY_IN_SECONDS);
                wp_schedule_single_event($retry_time, 'pmpro_woo_retry_payment', array($order_id, $user_id));
                
                $this->log("Reintento #" . ($current_attempts + 1) . " programado para orden $order_id");
                
            } else {
                // Máximo de intentos alcanzado
                $this->handle_max_retries_reached($user_id, $order);
            }
            
        } catch (Exception $e) {
            $this->log("Error procesando fallo de renovación: " . $e->getMessage());
        }
    }
    
    /**
     * Maneja cancelaciones
     */
    private function handle_cancelled_renewal($user_id, $order) {
        try {
            $membership_level_id = $this->get_membership_level_from_order($order);
            
            if ($membership_level_id) {
                // Cancelar membresía en PMPro
                pmpro_changeMembershipLevel(0, $user_id, 'cancelled');
                
                // Registrar orden cancelada
                $this->register_cancelled_order($user_id, $order);
                
                $this->log("Membresía cancelada para usuario $user_id, orden " . $order->get_id());
            }
            
        } catch (Exception $e) {
            $this->log("Error procesando cancelación: " . $e->getMessage());
        }
    }
    
    /**
     * Obtiene el nivel de membresía del pedido
     */
    private function get_membership_level_from_order($order) {
        // Verificar meta del pedido
        $level_id = $order->get_meta('_pmpro_membership_level_id');
        
        if ($level_id) {
            return $level_id;
        }
        
        // Buscar en productos del pedido
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                $level_id = $product->get_meta('_pmpro_membership_level');
                if ($level_id) {
                    return $level_id;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Actualiza membresía en PMPro
     */
    private function update_pmpro_membership($user_id, $membership_level_id, $order) {
        $current_level = pmpro_getMembershipLevelForUser($user_id);
        
        if (!$current_level || $current_level->id != $membership_level_id) {
            // Cambiar nivel de membresía
            pmpro_changeMembershipLevel($membership_level_id, $user_id, 'changed');
        }
    }
    
    /**
     * Registra pago en PMPro
     */
    private function register_pmpro_payment($user_id, $membership_level_id, $order) {
        $pmpro_order = new MemberOrder();
        $pmpro_order->user_id = $user_id;
        $pmpro_order->membership_id = $membership_level_id;
        $pmpro_order->InitialPayment = $order->get_total();
        $pmpro_order->PaymentAmount = $order->get_total();
        $pmpro_order->payment_transaction_id = $order->get_transaction_id();
        $pmpro_order->subscription_transaction_id = $order->get_meta('_subscription_id');
        $pmpro_order->gateway = 'woocommerce';
        $pmpro_order->status = 'success';
        $pmpro_order->notes = 'Renovación automática desde WooCommerce - Orden #' . $order->get_id();
        
        $pmpro_order->saveOrder();
    }
    
    /**
     * Actualiza fechas de membresía
     */
    private function update_membership_dates($user_id, $membership_level_id, $order) {
        global $wpdb;
        
        // Obtener información del ciclo
        $cycle_period = $order->get_meta('_pmpro_cycle_period');
        $cycle_number = $order->get_meta('_pmpro_cycle_number');
        
        if (!$cycle_period) {
            $cycle_period = 'month';
        }
        
        if (!$cycle_number) {
            $cycle_number = 1;
        }
        
        // Calcular nueva fecha de expiración
        $current_membership = pmpro_getMembershipLevelForUser($user_id);
        $start_date = $current_membership->enddate ?? current_time('mysql');
        
        // Si la membresía ya expiró, usar fecha actual
        if (strtotime($start_date) < time()) {
            $start_date = current_time('mysql');
        }
        
        $end_date = date('Y-m-d H:i:s', strtotime("+{$cycle_number} {$cycle_period}", strtotime($start_date)));
        
        // Actualizar en base de datos
        $wpdb->update(
            $wpdb->pmpro_memberships_users,
            array(
                'enddate' => $end_date,
                'modified' => current_time('mysql')
            ),
            array(
                'user_id' => $user_id,
                'membership_id' => $membership_level_id,
                'status' => 'active'
            ),
            array('%s', '%s'),
            array('%d', '%d', '%s')
        );
    }
    
    /**
     * Maneja webhooks de PagBank
     */
    public function handle_pagbank_webhook($processed, $webhook_data) {
        if ($processed) {
            return $processed;
        }
        
        $order_id = $webhook_data['order_id'] ?? null;
        
        if (!$order_id) {
            return false;
        }
        
        $order = wc_get_order($order_id);
        
        if (!$order || !$order->get_meta('_pmpro_subscription_id')) {
            return false;
        }
        
        // Disparar procesamiento de estado
        $status = $webhook_data['status'] ?? 'failed';
        do_action('woocommerce_order_status_' . $status, $order_id);
        
        $this->log("Webhook procesado para orden $order_id con estado $status");
        
        return true;
    }
    
    /**
     * Procesa reintentos programados
     */
    public function process_retry_payment($order_id, $user_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        $this->log("Procesando reintento para orden $order_id");
        
        // Aquí se podría implementar lógica específica para reintentar el pago
        // Por ahora, simplemente marcar como fallido para que se procese normalmente
        $this->handle_failed_renewal($user_id, $order);
    }
    
    /**
     * Maneja cuando se alcanzan máximos reintentos
     */
    private function handle_max_retries_reached($user_id, $order) {
        $membership_level_id = $this->get_membership_level_from_order($order);
        
        if ($membership_level_id) {
            // Suspender membresía
            pmpro_changeMembershipLevel(0, $user_id, 'expired');
            
            // Registrar orden como fallida
            $this->register_failed_order($user_id, $order);
            
            $this->log("Máximo de reintentos alcanzado para orden " . $order->get_id() . " - Membresía suspendida");
        }
    }
    
    /**
     * Registra orden cancelada en PMPro
     */
    private function register_cancelled_order($user_id, $order) {
        $pmpro_order = new MemberOrder();
        $pmpro_order->user_id = $user_id;
        $pmpro_order->membership_id = 0;
        $pmpro_order->InitialPayment = 0;
        $pmpro_order->PaymentAmount = 0;
        $pmpro_order->payment_transaction_id = $order->get_transaction_id();
        $pmpro_order->gateway = 'woocommerce';
        $pmpro_order->status = 'cancelled';
        $pmpro_order->notes = 'Suscripción cancelada - Orden WooCommerce #' . $order->get_id();
        
        $pmpro_order->saveOrder();
    }
    
    /**
     * Registra orden fallida en PMPro
     */
    private function register_failed_order($user_id, $order) {
        $membership_level_id = $this->get_membership_level_from_order($order);
        
        $pmpro_order = new MemberOrder();
        $pmpro_order->user_id = $user_id;
        $pmpro_order->membership_id = $membership_level_id;
        $pmpro_order->InitialPayment = $order->get_total();
        $pmpro_order->PaymentAmount = $order->get_total();
        $pmpro_order->payment_transaction_id = $order->get_transaction_id();
        $pmpro_order->gateway = 'woocommerce';
        $pmpro_order->status = 'error';
        $pmpro_order->notes = 'Pago fallido después de múltiples intentos - Orden WooCommerce #' . $order->get_id();
        
        $pmpro_order->saveOrder();
    }
    
    /**
     * Limpia intentos de reintento
     */
    private function clear_retry_attempts($order) {
        $order->delete_meta_data('_pmpro_retry_attempts');
        $order->save();
    }
    
    /**
     * Función de logging
     */
    private function log($message, $data = null) {
        if (!$this->log_enabled) {
            return;
        }
        
        $log_entry = '[' . date('Y-m-d H:i:s') . '] ' . $message;
        
        if ($data) {
            $log_entry .= "\n" . print_r($data, true);
        }
        
        // Escribir al log de WordPress
        error_log($log_entry);
        
        // También escribir a archivo específico si WP_DEBUG está activo
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_file = WP_CONTENT_DIR . '/pmpro-woo-sync.log';
            file_put_contents($log_file, $log_entry . "\n", FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * Limpia logs antiguos
     */
    public function cleanup_old_logs() {
        $log_file = WP_CONTENT_DIR . '/pmpro-woo-sync.log';
        
        if (file_exists($log_file) && filesize($log_file) > 10 * 1024 * 1024) { // 10MB
            // Mantener solo las últimas 1000 líneas
            $lines = file($log_file);
            $lines = array_slice($lines, -1000);
            file_put_contents($log_file, implode('', $lines));
        }
    }
}

// Inicializar el plugin
new PMPro_WooCommerce_Sync();

/**
 * Función de utilidad para obtener información de debug
 */
function pmpro_woo_sync_debug_info($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $debug_info = array(
        'user_id' => $user_id,
        'current_membership' => pmpro_getMembershipLevelForUser($user_id),
        'recent_orders' => array(),
        'subscriptions' => array()
    );
    
    // Obtener órdenes recientes de WooCommerce
    $orders = wc_get_orders(array(
        'customer' => $user_id,
        'limit' => 10,
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    foreach ($orders as $order) {
        $debug_info['recent_orders'][] = array(
            'id' => $order->get_id(),
            'status' => $order->get_status(),
            'total' => $order->get_total(),
            'date' => $order->get_date_created()->format('Y-m-d H:i:s'),
            'is_subscription' => $order->get_meta('_subscription_id') ? true : false
        );
    }
    
    return $debug_info;
}