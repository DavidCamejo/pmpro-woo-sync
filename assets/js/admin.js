/**
 * Scripts para el área de administración del plugin PMPRO-WooCommerce Sync.
 */

(function($) {
    'use strict';

    // Variables globales
    var pmproWooSync = {
        ajaxRunning: false,
        refreshInterval: null,
        searchTimeout: null
    };

    $(document).ready(function() {
        pmproWooSync.init();
    });

    pmproWooSync.init = function() {
        this.bindEvents();
        this.initTooltips();
        this.initAutoRefresh();
        this.initModals();
        console.log('PMPRO-Woo Sync Admin scripts loaded and initialized.');
    };

    pmproWooSync.bindEvents = function() {
        // Test de conexión PagBank
        $('#test-pagbank-connection').on('click', this.testPagBankConnection);
        
        // Limpiar logs
        $('#clear-logs').on('click', this.clearLogs);
        
        // Exportar logs
        $('#export-logs').on('click', this.exportLogs);
        
        // Refrescar logs
        $('#refresh-logs').on('click', this.refreshLogs);
        
        // Toggle de mensajes largos
        $(document).on('click', '.toggle-full-message', this.toggleFullMessage);
        
        // Auto-submit en filtros de logs
        $('#log-level-filter').on('change', this.autoSubmitFilters);
        $('#log-search').on('input', this.debounceSearch);
        
        // Validación de formulario de configuraciones
        $('#pmpro-woo-sync-settings-form').on('submit', this.validateSettingsForm);
        
        // Modal de detalles de logs
        $(document).on('click', '.view-log-details', this.showLogDetails);
        
        // Botones de herramientas
        $('.pmpro-woo-sync-tool-button').on('click', this.handleToolAction);
        
        // Auto-guardar borrador de configuraciones
        $('#pmpro-woo-sync-settings-form input, #pmpro-woo-sync-settings-form select').on('change', this.autoSaveDraft);
    };

    pmproWooSync.testPagBankConnection = function(e) {
        e.preventDefault();
        
        if (pmproWooSync.ajaxRunning) return;
        
        var $button = $(this);
        var originalText = $button.html();
        
        $button.html('<span class="dashicons dashicons-update spin"></span> ' + pmproWooSyncAdmin.strings.testing_connection);
        $button.prop('disabled', true);
        pmproWooSync.ajaxRunning = true;
        
        $.ajax({
            url: pmproWooSyncAdmin.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'pmpro_woo_sync_test_connection',
                gateway: 'pagbank',
                nonce: pmproWooSyncAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    pmproWooSync.showNotice(response.data.message || pmproWooSyncAdmin.strings.connection_success, 'success');
                    pmproWooSync.updateConnectionStatus('pagbank', true);
                } else {
                    pmproWooSync.showNotice(response.data.message || pmproWooSyncAdmin.strings.connection_failed, 'error');
                    pmproWooSync.updateConnectionStatus('pagbank', false);
                }
            },
            error: function(xhr, status, error) {
                pmproWooSync.showNotice(pmproWooSyncAdmin.strings.connection_failed + ': ' + error, 'error');
                pmproWooSync.updateConnectionStatus('pagbank', false);
            },
            complete: function() {
                $button.html(originalText);
                $button.prop('disabled', false);
                pmproWooSync.ajaxRunning = false;
            }
        });
    };

    pmproWooSync.clearLogs = function(e) {
        e.preventDefault();
        
        if (!confirm(pmproWooSyncAdmin.strings.confirm_clear_logs)) {
            return;
        }
        
        if (pmproWooSync.ajaxRunning) return;
        
        var $button = $(this);
        var originalText = $button.html();
        
        $button.html('<span class="dashicons dashicons-update spin"></span> ' + pmproWooSyncAdmin.strings.processing);
        $button.prop('disabled', true);
        pmproWooSync.ajaxRunning = true;
        
        $.ajax({
            url: pmproWooSyncAdmin.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'pmpro_woo_sync_clear_logs',
                nonce: pmproWooSyncAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    pmproWooSync.showNotice(response.data.message, 'success');
                    // Recargar la página después de un breve delay
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    pmproWooSync.showNotice('Error al limpiar logs', 'error');
                }
            },
            error: function(xhr, status, error) {
                pmproWooSync.showNotice('Error AJAX: ' + error, 'error');
            },
            complete: function() {
                $button.html(originalText);
                $button.prop('disabled', false);
                pmproWooSync.ajaxRunning = false;
            }
        });
    };

    pmproWooSync.exportLogs = function(e) {
        e.preventDefault();
        
        if (pmproWooSync.ajaxRunning) return;
        
        var $button = $(this);
        var originalText = $button.html();
        var filterLevel = $('#log-level-filter').val();
        
        $button.html('<span class="dashicons dashicons-update spin"></span> ' + pmproWooSyncAdmin.strings.processing);
        $button.prop('disabled', true);
        pmproWooSync.ajaxRunning = true;
        
        $.ajax({
            url: pmproWooSyncAdmin.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'pmpro_woo_sync_export_logs',
                level: filterLevel,
                nonce: pmproWooSyncAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Crear y descargar archivo
                    pmproWooSync.downloadFile(response.data.data, response.data.filename, 'application/json');
                    pmproWooSync.showNotice('Logs exportados exitosamente', 'success');
                } else {
                    pmproWooSync.showNotice('Error al exportar logs', 'error');
                }
            },
            error: function(xhr, status, error) {
                pmproWooSync.showNotice('Error AJAX: ' + error, 'error');
            },
            complete: function() {
                $button.html(originalText);
                $button.prop('disabled', false);
                pmproWooSync.ajaxRunning = false;
            }
        });
    };

    pmproWooSync.refreshLogs = function(e) {
        e.preventDefault();
        
        if (pmproWooSync.ajaxRunning) return;
        
        var $button = $(this);
        var originalText = $button.html();
        var currentPage = new URLSearchParams(window.location.search).get('paged') || 1;
        var filterLevel = $('#log-level-filter').val();
        var searchQuery = $('#log-search').val();
        
        $button.html('<span class="dashicons dashicons-update spin"></span>');
        $button.prop('disabled', true);
        pmproWooSync.ajaxRunning = true;
        
        $.ajax({
            url: pmproWooSyncAdmin.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'pmpro_woo_sync_refresh_logs',
                page: currentPage,
                level: filterLevel,
                search: searchQuery,
                nonce: pmproWooSyncAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#logs-table-body').html(response.data.html).addClass('fade-in');
                    pmproWooSync.showNotice('Logs actualizados', 'success', 2000);
                    pmproWooSync.updateLogStats(response.data.stats);
                } else {
                    pmproWooSync.showNotice('Error al actualizar logs', 'error');
                }
            },
            error: function(xhr, status, error) {
                pmproWooSync.showNotice('Error AJAX: ' + error, 'error');
            },
            complete: function() {
                $button.html(originalText);
                $button.prop('disabled', false);
                pmproWooSync.ajaxRunning = false;
            }
        });
    };

    pmproWooSync.toggleFullMessage = function(e) {
        e.preventDefault();
        
        var $link = $(this);
        var $messageDiv = $link.closest('.log-message');
        var $fullMessage = $messageDiv.find('.log-message-full');
        
        if ($fullMessage.is(':visible')) {
            $fullMessage.hide();
            $link.text('ver más');
        } else {
            $fullMessage.show();
            $link.text('ver menos');
        }
    };

    pmproWooSync.showLogDetails = function(e) {
        e.preventDefault();
        
        var $link = $(this);
        var context = $link.data('context');
        var message = $link.data('message');
        var timestamp = $link.data('timestamp');
        
        var modalContent = '<div class="log-detail-item"><strong>Mensaje:</strong><br>' + pmproWooSync.escapeHtml(message) + '</div>';
        modalContent += '<div class="log-detail-item"><strong>Fecha:</strong><br>' + pmproWooSync.escapeHtml(timestamp) + '</div>';
        
        if (context && context !== 'null' && context !== '[]') {
            var formattedContext = typeof context === 'string' ? context : JSON.stringify(context, null, 2);
            modalContent += '<div class="log-detail-item"><strong>Contexto:</strong><br><pre>' + pmproWooSync.escapeHtml(formattedContext) + '</pre></div>';
        }
        
        $('#log-details-body').html(modalContent);
        $('#log-details-modal').dialog({
            modal: true,
            width: 700,
            height: 500,
            resizable: true,
            title: 'Detalles del Log',
            close: function() {
                $(this).dialog('destroy');
            }
        });
    };

    pmproWooSync.autoSubmitFilters = function() {
        $('#logs-filter-form').submit();
    };

    pmproWooSync.debounceSearch = function() {
        clearTimeout(pmproWooSync.searchTimeout);
        pmproWooSync.searchTimeout = setTimeout(function() {
            $('#logs-filter-form').submit();
        }, 500);
    };

    pmproWooSync.validateSettingsForm = function(e) {
        var $form = $(this);
        var hasErrors = false;
        
        // Limpiar errores previos
        $form.find('.form-error').removeClass('form-error');
        
        // Validar API Key de PagBank
        var $apiKeyField = $form.find('input[name*="[api_key]"]');
        var apiKey = $apiKeyField.val().trim();
        
        if (apiKey && apiKey.length < 20) {
            if (!confirm('La API Key de PagBank parece ser muy corta (menos de 20 caracteres). ¿Deseas continuar?')) {
                e.preventDefault();
                $apiKeyField.addClass('form-error').focus();
                return false;
            }
        }
        
        // Validar configuraciones numéricas
        var numericFields = [
            {name: 'log_retention_days', min: 0, max: 365},
            {name: 'retry_attempts', min: 0, max: 10},
            {name: 'batch_size', min: 1, max: 200},
            {name: 'api_timeout', min: 5, max: 120}
        ];
        
        numericFields.forEach(function(field) {
            var $field = $form.find('input[name*="[' + field.name + ']"]');
            if ($field.length) {
                var value = parseInt($field.val());
                
                if (isNaN(value) || value < field.min || value > field.max) {
                    pmproWooSync.showNotice(
                        'El campo ' + field.name + ' debe ser un número entre ' + field.min + ' y ' + field.max, 
                        'error'
                    );
                    $field.addClass('form-error').focus();
                    e.preventDefault();
                    hasErrors = true;
                    return false;
                }
            }
        });
        
        if (!hasErrors) {
            var $submitButton = $form.find('input[type="submit"]');
            $submitButton.prop('disabled', true).val(pmproWooSyncAdmin.strings.processing + '...');
            
            // Re-habilitar después de 10 segundos por seguridad
            setTimeout(function() {
                $submitButton.prop('disabled', false).val('Guardar Configuraciones');
            }, 10000);
        }
        
        return !hasErrors;
    };

    pmproWooSync.handleToolAction = function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var action = $button.data('action');
        var confirmMessage = $button.data('confirm');
        
        if (confirmMessage && !confirm(confirmMessage)) {
            return;
        }
        
        if (pmproWooSync.ajaxRunning) return;
        
        var originalText = $button.html();
        $button.html('<span class="dashicons dashicons-update spin"></span> ' + pmproWooSyncAdmin.strings.processing);
        $button.prop('disabled', true);
        pmproWooSync.ajaxRunning = true;
        
        $.ajax({
            url: pmproWooSyncAdmin.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'pmpro_woo_sync_tool_action',
                tool_action: action,
                nonce: pmproWooSyncAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    pmproWooSync.showNotice(response.data.message, 'success');
                } else {
                    pmproWooSync.showNotice(response.data.message || 'Error en la herramienta', 'error');
                }
            },
            error: function(xhr, status, error) {
                pmproWooSync.showNotice('Error AJAX: ' + error, 'error');
            },
            complete: function() {
                $button.html(originalText);
                $button.prop('disabled', false);
                pmproWooSync.ajaxRunning = false;
            }
        });
    };

    pmproWooSync.autoSaveDraft = function() {
        // Auto-guardar configuraciones como borrador cada 30 segundos
        clearTimeout(pmproWooSync.draftTimeout);
        pmproWooSync.draftTimeout = setTimeout(function() {
            var formData = $('#pmpro-woo-sync-settings-form').serialize();
            
            $.ajax({
                url: pmproWooSyncAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'pmpro_woo_sync_save_draft',
                    form_data: formData,
                    nonce: pmproWooSyncAdmin.nonce
                },
                success: function() {
                    $('.draft-saved-indicator').remove();
                    $('#pmpro-woo-sync-settings-form').append('<span class="draft-saved-indicator" style="color: #46b450; font-size: 12px; margin-left: 10px;">Borrador guardado</span>');
                    setTimeout(function() {
                        $('.draft-saved-indicator').fadeOut();
                    }, 3000);
                }
            });
        }, 30000);
    };

    pmproWooSync.initTooltips = function() {
        // Inicializar tooltips si existen
        if ($.fn.tooltip) {
            $('[data-tooltip]').tooltip({
                position: { my: "center bottom-20", at: "center top", using: function( position, feedback ) {
                    $( this ).css( position );
                    $( "<div>" )
                        .addClass( "arrow" )
                        .addClass( feedback.vertical )
                        .addClass( feedback.horizontal )
                        .appendTo( this );
                }}
            });
        }
    };

    pmproWooSync.initAutoRefresh = function() {
        // Auto-refresh de logs cada 30 segundos si estamos en la página de logs
        if (window.location.href.indexOf('pmpro-woo-sync-logs') > -1) {
            pmproWooSync.refreshInterval = setInterval(function() {
                if (!pmproWooSync.ajaxRunning && !document.hidden) {
                    $('#refresh-logs').trigger('click');
                }
            }, 30000);
            
            // Pausar auto-refresh cuando la página no está visible
            document.addEventListener('visibilitychange', function() {
                if (document.hidden && pmproWooSync.refreshInterval) {
                    clearInterval(pmproWooSync.refreshInterval);
                } else if (!document.hidden) {
                    pmproWooSync.initAutoRefresh();
                }
            });
        }
    };

    pmproWooSync.initModals = function() {
        // Crear modal de detalles de logs si no existe
        if ($('#log-details-modal').length === 0) {
            $('body').append('<div id="log-details-modal" style="display: none;"><div class="log-details-content"><h3>Detalles del Log</h3><div id="log-details-body"></div></div></div>');
        }
    };

    pmproWooSync.showNotice = function(message, type, timeout) {
        type = type || 'info';
        timeout = timeout || 5000;
        
        var noticeClass = 'notice notice-' + (type === 'success' ? 'success' : type === 'error' ? 'error' : 'info');
        var $notice = $('<div class="' + noticeClass + ' is-dismissible"><p>' + pmproWooSync.escapeHtml(message) + '</p></div>');
        
        $('#pmpro-woo-sync-admin-notices').empty().append($notice);
        
        // Agregar botón de cerrar
        $notice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Cerrar este aviso.</span></button>');
        $notice.find('.notice-dismiss').on('click', function() {
            $notice.fadeOut(function() { $notice.remove(); });
        });
        
        // Auto-hide después del timeout
        if (timeout > 0) {
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            }, timeout);
        }
        
        // Scroll hacia arriba para ver el notice
        $('html, body').animate({
            scrollTop: Math.max(0, $('#pmpro-woo-sync-admin-notices').offset().top - 50)
        }, 300);
    };

    pmproWooSync.updateConnectionStatus = function(gateway, status) {
        var $indicator = $('.indicator').filter(function() {
            return $(this).text().toLowerCase().indexOf(gateway) > -1;
        });
        
        if ($indicator.length) {
            $indicator.removeClass('active inactive warning')
                     .addClass(status ? 'active' : 'inactive')
                     .find('.dashicons')
                     .removeClass('dashicons-yes-alt dashicons-dismiss')
                     .addClass(status ? 'dashicons-yes-alt' : 'dashicons-dismiss');
        }
    };

    pmproWooSync.updateLogStats = function(stats) {
        if (stats) {
            $('.pmpro-woo-sync-stat-box').each(function() {
                var $box = $(this);
                var text = $box.find('p').text().toLowerCase();
                
                if (text.indexOf('total') > -1) {
                    $box.find('h3').text(pmproWooSync.numberFormat(stats.total || 0));
                } else if (text.indexOf('24h') > -1) {
                    $box.find('h3').text(pmproWooSync.numberFormat(stats.last_24h || 0));
                } else if (text.indexOf('error') > -1) {
                    $box.find('h3').text(pmproWooSync.numberFormat(stats.by_level.error || 0));
                } else if (text.indexOf('advertencia') > -1) {
                    $box.find('h3').text(pmproWooSync.numberFormat(stats.by_level.warning || 0));
                }
            });
        }
    };

    pmproWooSync.downloadFile = function(data, filename, mimeType) {
        var dataStr = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
        var dataBlob = new Blob([dataStr], {type: mimeType});
        
        if (window.navigator && window.navigator.msSaveOrOpenBlob) {
            // IE
            window.navigator.msSaveOrOpenBlob(dataBlob, filename);
        } else {
            // Otros navegadores
            var url = URL.createObjectURL(dataBlob);
            var link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }
    };

    pmproWooSync.escapeHtml = function(unsafe) {
        return unsafe
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    };

    pmproWooSync.numberFormat = function(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    };

    // Cleanup al salir de la página
    $(window).on('beforeunload', function() {
        if (pmproWooSync.refreshInterval) {
            clearInterval(pmproWooSync.refreshInterval);
        }
        if (pmproWooSync.draftTimeout) {
            clearTimeout(pmproWooSync.draftTimeout);
        }
    });

    // Agregar estilos CSS dinámicos
    $('<style>' +
        '.spin { animation: spin 1s linear infinite; } ' +
        '@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } } ' +
        '.form-error { border-color: #d63638 !important; box-shadow: 0 0 2px rgba(214, 54, 56, 0.8); } ' +
        '.fade-in { animation: fadeIn 0.3s ease-in; } ' +
        '@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }' +
    '</style>').appendTo('head');

    // Exponer objeto global para uso externo
    window.pmproWooSync = pmproWooSync;

})(jQuery);
