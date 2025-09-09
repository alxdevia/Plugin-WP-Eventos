<?php
// includes/ajax-endpoints.php

/**
 * Endpoint AJAX para obtener etiquetas desde JS (frontend).
 */
add_action('wp_ajax_nopriv_reu_obtener_etiquetas', 'reu_obtener_etiquetas');
add_action('wp_ajax_reu_obtener_etiquetas', 'reu_obtener_etiquetas');
function reu_obtener_etiquetas() {
    $etiquetas = get_option('reu_etiquetas', []);
    wp_send_json_success($etiquetas);
}

/**
 * AJAX: Detección de etiquetas (Ads, Analytics, Facebook, LinkedIn, etc.) en la home.
 */
add_action('wp_ajax_reu_detectar_etiqueta_ads', 'reu_ajax_detectar_etiquetas');
add_action('wp_ajax_nopriv_reu_detectar_etiqueta_ads', 'reu_ajax_detectar_etiquetas');

function reu_ajax_detectar_etiquetas() {
    // En admin, tu JS ya envía nonce; en público, no lo exigimos.
    if (is_admin() && isset($_POST['nonce'])) {
        check_ajax_referer('reu_detect_ads_nonce', 'nonce');
    }

    $url = home_url('/');
    $response = wp_remote_get($url);

    $data = [
        'ads'      => ['found' => false, 'codes' => []],
        'analytics'=> ['found' => false, 'codes' => []],
        'facebook' => ['found' => false, 'codes' => [], 'sources' => []],
        'linkedin' => ['found' => false, 'codes' => []],
        'tiktok'   => ['found' => false, 'codes' => []],
        'twitter'  => ['found' => false, 'codes' => []],
    ];

    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);

        // Google Ads
        if (preg_match_all('/AW-\d{6,}/', $body, $m_ads)) {
            $data['ads']['found'] = true;
            $data['ads']['codes'] = array_unique($m_ads[0]);
        }

        // Google Analytics (GA4, UA, GTM)
        if (preg_match_all('/(G-[A-Z0-9]{8,}|UA-\d{4,}-\d+|GTM-[A-Z0-9]+)/i', $body, $m_analytics)) {
            $data['analytics']['found'] = true;
            $data['analytics']['codes'] = array_unique($m_analytics[0]);
        }

        // Facebook Pixel
        $codes = [];
        $sources = [];

        if (preg_match_all("/fbq\\(['\"]init['\"],\\s*['\"](\\d+)['\"]\\)/", $body, $m_fb)) {
            foreach($m_fb[1] as $id) { $codes[] = $id; $sources[$id][] = 'Clásico'; }
        }
        if (preg_match_all('/"facebook"\s*:\s*\{"pixelIds":\["?(\d+)/', $body, $m_fbjs)) {
            foreach($m_fbjs[1] as $id) { $codes[] = $id; $sources[$id][] = 'PixelYourSite-JS'; }
        }
        if (preg_match_all('/facebook\.com\/tr\?id=(\d+)/', $body, $m_fbimg)) {
            foreach($m_fbimg[1] as $id) { $codes[] = $id; $sources[$id][] = 'PixelYourSite-noscript'; }
        }
        $pys_fb = get_option('pys_facebook');
        if (!empty($pys_fb['pixel_id']) && is_array($pys_fb['pixel_id'])) {
            foreach ($pys_fb['pixel_id'] as $id) {
                if ($id && !in_array($id, $codes)) { $codes[] = $id; $sources[$id][] = 'PixelYourSite-config (pys_facebook)'; }
            }
        }
        if (count($codes) > 0) {
            $data['facebook']['found']   = true;
            $data['facebook']['codes']   = array_unique($codes);
            $data['facebook']['sources'] = $sources;
        } else {
            $data['facebook']['found']   = false;
            $data['facebook']['codes']   = [];
            $data['facebook']['sources'] = [];
        }

        // LinkedIn
        if (preg_match('/li_tag/i', $body) || preg_match('/partnerId[\'"]?\s*:\s*[\'"](\d{7,})[\'"]/', $body, $m_ln)) {
            $data['linkedin']['found'] = true;
            if (isset($m_ln[1])) $data['linkedin']['codes'][] = $m_ln[1];
        }

        // TikTok
        if (preg_match_all("/ttq\.load\(['\"](\w{8,})['\"]\)/", $body, $m_tt)) {
            $data['tiktok']['found'] = true;
            $data['tiktok']['codes'] = array_unique($m_tt[1]);
        }

        // Twitter/X
        if (preg_match_all("/twq\\(['\"]init['\"],['\"]([a-zA-Z0-9]{5,})['\"]\\)/", $body, $m_tw)) {
            $data['twitter']['found'] = true;
            $data['twitter']['codes'] = array_unique($m_tw[1]);
        }
    }
    wp_send_json_success($data);
}

/**
 * Recargar la tabla de etiquetas (admin). Un solo handler.
 */
add_action('wp_ajax_reu_recargar_tabla_etiquetas', 'reu_recargar_tabla_etiquetas');
function reu_recargar_tabla_etiquetas() {
    if ( ! current_user_can('manage_options')) {
        wp_die('No autorizado');
    }
    // Nonce OPCIONAL para no romper llamadas actuales.
    if (isset($_POST['nonce'])) {
        check_ajax_referer('reu_tabla_etiquetas_nonce', 'nonce');
    }

    $_GET['buscar_nombre'] = sanitize_text_field($_POST['buscar_nombre'] ?? '');
    $_GET['filtro_tipo']   = sanitize_text_field($_POST['filtro_tipo'] ?? '');
    $_GET['etq_paged']     = sanitize_text_field($_POST['etq_paged'] ?? 1);

    if (function_exists('reu_pinta_tabla_etiquetas')) {
        reu_pinta_tabla_etiquetas(true);
    }
    wp_die();
}

/**
 * Sincronizar etiquetas automáticas de posts (admin).
 */
add_action('wp_ajax_reu_sincronizar_etiquetas_posts', 'reu_sincronizar_etiquetas_posts');
function reu_sincronizar_etiquetas_posts() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['error' => 'No tienes permisos']);
        return;
    }
    // Nonce OPCIONAL para no romper llamadas actuales.
    if (isset($_POST['nonce'])) {
        check_ajax_referer('reu_posts_sync_nonce', 'nonce');
    }

    $etiquetas = get_option('reu_etiquetas', []);
    $ya_asignados = [];

    foreach ($etiquetas as $etq) {
        if (!empty($etq['selector'])) {
            $ya_asignados[$etq['selector']] = $etq;
        }
    }

    // Obtener todos los posts publicados
    $args = [
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];
    $post_ids = get_posts($args);

    $creados = 0;
    $actualizados = 0;

    foreach ($post_ids as $post_id) {
        $selector = 'post_id_' . $post_id;
        $titulo   = get_the_title($post_id);

        if (isset($ya_asignados[$selector])) {
            // Ya existe: actualiza nombre si ha cambiado
            if (($ya_asignados[$selector]['nombre_etiqueta'] ?? '') !== $titulo) {
                foreach ($etiquetas as &$e) {
                    if (($e['selector'] ?? '') === $selector) {
                        $e['nombre_etiqueta'] = $titulo;
                    }
                }
                $actualizados++;
            }
        } else {
            $etiquetas[] = [
                'tipo'            => 'Blog Post',
                'selector'        => $selector,
                'telefono'        => '',
                'nombre_etiqueta' => $titulo,
                'id_ads'          => ''
            ];
            $creados++;
        }
    }

    update_option('reu_etiquetas', $etiquetas);

    wp_send_json_success([
        'creados'      => $creados,
        'actualizados' => $actualizados,
        'total'        => count($post_ids),
    ]);
    wp_die();
}
