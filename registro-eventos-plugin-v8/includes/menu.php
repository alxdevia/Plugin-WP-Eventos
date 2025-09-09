<?php
// includes/menu.php

// Menú y submenús para el panel de administración del plugin

// Asegúrate de incluir el generador UTM antes de registrar el menú.
if (file_exists(plugin_dir_path(__FILE__) . 'generador-utm.php')) {
    require_once plugin_dir_path(__FILE__) . 'generador-utm.php';
}

function reu_menu_admin() {
    add_menu_page(
        'Registro Eventos',                // Título de la página principal (guía)
        'Registro Eventos',                // Texto principal menú lateral
        'manage_options',                  // Capacidad
        'reu_dashboard',                   // Slug principal (explicación/guía)
        'reu_dashboard_page',              // Callback para la explicación principal
        'dashicons-welcome-learn-more'
    );
    add_submenu_page(
        'reu_dashboard',
        'Gestión de Etiquetas',
        'Gestión de Etiquetas',
        'manage_options',
        'registro_eventos',                // Aquí la gestión real de etiquetas
        'reu_configurar_etiqueta'
    );
    add_submenu_page(
        'reu_dashboard',
        'Eventos Registrados',
        'Eventos Registrados',
        'manage_options',
        'registro_eventos_log',
        'reu_eventos_log_panel'
    );
    // --- Nuevo submenú: Generador de URLs UTM ---
    add_submenu_page(
        'reu_dashboard',
        'Generador de URLs UTM',
        'Generador UTM',
        'manage_options',
        'generador_utm',
        'reu_generador_utm_html'
    );    
}
add_action('admin_menu', 'reu_menu_admin');
