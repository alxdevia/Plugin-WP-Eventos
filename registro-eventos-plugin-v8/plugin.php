<?php
// plugin.php

/**
 * Plugin Name: Registro de Eventos de Usuario
 * Description: Plugin avanzado para WordPress que registra eventos de usuario (envíos de formularios, clics en teléfono, WhatsApp, descargas, banners, etc.), permite gestionar etiquetas personalizadas y mide conversiones de Google Ads, Facebook, Instagram, LinkedIn, TikTok y Twitter/X, diferenciando tráfico orgánico y de pago. Compatible con Elementor.
 * Version: 1.0
 * Author: Alejandro Aragón
 */

// 1. Incluir archivo de setup de la base de datos (eventos)
require_once plugin_dir_path(__FILE__) . 'includes/db-setup.php';

// 1b. Incluir archivo de setup para la tabla de IDs WooCommerce
require_once plugin_dir_path(__FILE__) . 'includes/woo-ids-manager/db-setup-woo-ids.php';

// 1c. Incluir archivo Generador de UTM
require_once plugin_dir_path(__FILE__) . 'includes/generador-utm.php';

// 2. Hook de activación para CREAR/ACTUALIZAR las tablas
register_activation_hook(__FILE__, 'reu_crear_tabla_logs');
register_activation_hook(__FILE__, 'woo_ids_manager_crear_tabla');

// 3. Incluir el resto de archivos del plugin
$inc_path = plugin_dir_path(__FILE__) . 'includes/';
foreach (['dashboard.php', 'functions.php', 'menu.php', 'event_log.php'] as $file) {
    if (file_exists($inc_path . $file)) {
        require_once $inc_path . $file;
    }
}

// 3b. Incluir SOLO el archivo principal del Gestor de IDs WooCommerce
$woo_ids_path = $inc_path . 'woo-ids-manager/';
if (file_exists($woo_ids_path . 'woo-ids-manager.php')) {
    require_once $woo_ids_path . 'woo-ids-manager.php';
}

// 4. Encolar CSS y JS solo donde corresponde en admin
add_action('admin_enqueue_scripts', function($hook) {
    if (
        isset($_GET['page']) &&
        in_array($_GET['page'], ['reu_dashboard', 'registro_eventos', 'registro_eventos_log', 'woo_ids_manager'])
    ) {
        wp_enqueue_style(
            'reu-admin-guide',
            plugins_url('assets/css/reu-admin-guide.css', __FILE__),
            [],
            null
        );
    }

    if (isset($_GET['page']) && in_array($_GET['page'], ['registro_eventos_log', 'registro_eventos'])) {
        wp_enqueue_style(
            'reu-eventos-table',
            plugins_url('assets/css/reu-eventos-table.css', __FILE__),
            [],
            null
        );
        wp_enqueue_script(
            'reu-export-csv',
            plugins_url('assets/js/reu-export-csv.js', __FILE__),
            ['jquery'],
            false,
            true
        );
        wp_localize_script('reu-export-csv', 'reuExport', [
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);
    }

    if (isset($_GET['page']) && $_GET['page'] === 'registro_eventos') {
        wp_enqueue_script(
            'reu-detect-ads-ajax',
            plugins_url('assets/js/reu-detect-ads-ajax.js', __FILE__),
            ['jquery'],
            false,
            true
        );
        wp_localize_script('reu-detect-ads-ajax', 'reuDetectAds', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('reu_detect_ads_nonce')
        ]);
    }

    // Encolar JS/CSS específicos para el Gestor de IDs WooCommerce
    if (isset($_GET['page']) && $_GET['page'] === 'woo_ids_manager') {
        wp_enqueue_style(
            'woo-ids-manager',
            plugins_url('assets/css/woo-ids-manager.css', __FILE__),
            [],
            null
        );
        wp_enqueue_script(
            'woo-ids-manager',
            plugins_url('assets/js/woo-ids-manager.js', __FILE__),
            ['jquery'],
            false,
            true
        );
        wp_localize_script('woo-ids-manager', 'wooIdsManagerAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('woo_ids_manager_nonce')
        ]);
    }
});

// 5. Encolar JS para el frontend (sitio público)
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script(
        'registro-eventos',
        plugins_url('assets/js/registro-eventos.js', __FILE__),
        ['jquery'],
        false,
        true
    );
    wp_localize_script('registro-eventos', 'registroEventosAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('registro_eventos_nonce')
    ]);
});

// 5b. Encolar JS para asignar IDs automáticos a posts (blog)
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script(
        'post-id-assigner',
        plugins_url('assets/js/post-id-assigner.js', __FILE__),  // Pon el archivo en assets/js/
        ['jquery'],
        false,
        true
    );
});

// 6. Oculta avisos de Elementor/WordPress solo en páginas del plugin
add_action('admin_head', function() {
    $slugs = [
        'reu_dashboard',
        'registro_eventos',
        'registro_eventos_log',
        'woo_ids_manager',
        'generador_utm',
        // Puedes añadir más slugs aquí si creas nuevas páginas
    ];
    if (isset($_GET['page']) && in_array($_GET['page'], $slugs)) {
        echo '<style>
            .elementor-message,
            .elementor-admin-message,
            .e-notice,
            .e-notice--update,
            .e-admin-notice,
            .elementor-pro-data-updater__container,
            .update-nag,
            .notice,
            .notice-warning,
            .notice-info,
            .notice-success,
            .notice-error {
                display: none !important;
            }
        </style>';
    }
});

// --- BORRAR TODOS LOS REGISTROS DE EVENTOS DESDE EL ADMIN (SUPER SEGURO) ---
add_action('admin_menu', function() {
    add_submenu_page(
        null, // No aparece en el menú, solo URL directa
        'Borrar todos los eventos',
        'Borrar eventos',
        'manage_options',
        'borrar_todos_eventos',
        function() {
            if (!current_user_can('manage_options')) return;
            if (isset($_POST['confirmar_borrado']) && check_admin_referer('borrar_eventos_confirm')) {
                global $wpdb;
                $tabla = $wpdb->prefix . 'registro_eventos_log';
                $wpdb->query("DELETE FROM $tabla");
                echo '<div class="notice notice-success" style="margin-top:20px;"><b>¡Todos los registros de eventos han sido borrados!</b></div>';
            }
            ?>
            <div class="wrap" style="max-width:560px">
                <h1 style="color:red;">⚠️ Borrar TODOS los registros de eventos</h1>
                <form method="post">
                    <?php wp_nonce_field('borrar_eventos_confirm'); ?>
                    <p style="font-size:16px;">
                        Esta acción <b>eliminará todos los registros de la tabla de eventos</b> (<code>wp_registro_eventos_log</code>).
                        <br>Esta acción <span style="color:red;"><b>NO se puede deshacer</b></span>.
                    </p>
                    <button type="submit" name="confirmar_borrado" class="button button-danger" 
                        onclick="return confirm('¿Seguro que deseas borrar TODOS los registros de eventos? ¡NO se puede deshacer!')">
                        🗑️ Borrar TODOS los registros
                    </button>
                </form>
            </div>
            <?php
        }
    );
});
