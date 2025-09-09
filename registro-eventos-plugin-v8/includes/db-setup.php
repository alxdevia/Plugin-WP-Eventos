<?php
// includes/db-setup.php
/**
 * Crea o actualiza la tabla de logs del plugin, sin borrar datos existentes.
 */
if (!function_exists('reu_crear_tabla_logs')) {
    function reu_crear_tabla_logs() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'registro_eventos_log';
        $charset_collate = $wpdb->get_charset_collate();

        // Estructura completa de la tabla (añade campos nuevos aquí si los necesitas en el futuro)
        $sql = "CREATE TABLE $tabla (
            id INT(11) NOT NULL AUTO_INCREMENT,
            fecha DATETIME NOT NULL,
            tipo VARCHAR(32) NOT NULL,
            selector VARCHAR(255) NOT NULL,
            nombre_etiqueta VARCHAR(255) NOT NULL,
            id_ads VARCHAR(255) DEFAULT '',
            telefono VARCHAR(64) DEFAULT '',
            fuente VARCHAR(32) DEFAULT '',
            dispositivo VARCHAR(32) DEFAULT '',
            pagina TEXT NOT NULL,
            utm_source VARCHAR(255) DEFAULT '',
            utm_medium VARCHAR(255) DEFAULT '',
            utm_campaign VARCHAR(255) DEFAULT '',
            gclid VARCHAR(255) DEFAULT '',
            gbraid VARCHAR(255) DEFAULT '',
            gad_campaignid VARCHAR(255) DEFAULT '',
            gad_source VARCHAR(255) DEFAULT '',
            ip VARCHAR(64) DEFAULT '',
            user_agent TEXT,
            PRIMARY KEY (id),
            KEY idx_fecha (fecha),
            KEY idx_tipo (tipo),
            KEY idx_fuente (fuente),
            KEY idx_nombre (nombre_etiqueta(191))
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
