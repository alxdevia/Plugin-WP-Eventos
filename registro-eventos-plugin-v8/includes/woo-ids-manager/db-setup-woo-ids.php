<?php
// includes/woo-ids-manager/db-setup-woo-ids.php

if (!function_exists('woo_ids_manager_crear_tabla')) {
    function woo_ids_manager_crear_tabla() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'woo_ids_manager';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $tabla (
            id INT(11) NOT NULL AUTO_INCREMENT,
            button_type VARCHAR(64) NOT NULL,
            button_label VARCHAR(255) NOT NULL,
            button_selector VARCHAR(255) NOT NULL,
            button_id VARCHAR(255) DEFAULT '',
            date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
