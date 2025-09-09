<?php
// includes/woo-ids-manager/woo-ids-manager.php

function woo_ids_manager_page() {
    $woocommerce_active = class_exists('WooCommerce');
    ?>
    <div class="wrap">
        <h1>Gestor de IDs WooCommerce</h1>
        <?php if (!$woocommerce_active): ?>
            <div class="notice notice-warning" style="margin:20px 0 0 0">
                <p>
                    <strong>WooCommerce no está instalado o activado.</strong><br>
                    No se pueden sincronizar las etiquetas de productos ni botones automáticos.
                </p>
            </div>
        <?php endif; ?>
        <div style="margin:24px 0 22px 0">
            <button id="sincronizar-todo-wc" class="button button-primary" <?php if(!$woocommerce_active) echo 'disabled'; ?>>
                Sincronizar etiquetas de productos y botones WooCommerce
            </button>
            <span id="sincronizar-todo-msg" style="margin-left:15px; color:green; vertical-align:middle;"></span>
        </div>
        <div style="margin-top:18px; font-size:14px; color:#888;">
            <b>¿Qué hace este botón?</b><br>
            Añade y sincroniza automáticamente:
            <ul style="margin-top:3px;">
                <li>Todas las etiquetas para los productos (botón añadir al carrito, tanto en el catálogo como en la página individual).</li>
                <li>Las 3 etiquetas estándar de WooCommerce: <b>Ver carrito</b>, <b>Finalizar compra</b> y <b>Realizar pedido</b>.</li>
            </ul>
            Si ya existe una etiqueta, solo actualiza su nombre. Puedes editar el <b>nombre de la etiqueta</b> después desde la gestión principal.<br>
            <i>No crea registros de evento/clic hasta que ocurra un clic real.</i>
        </div>
    </div>
    <script>
    jQuery(function($){
        $('#sincronizar-todo-wc').on('click', function(e){
            e.preventDefault();
            $('#sincronizar-todo-msg').text('Sincronizando...');
            $.post(ajaxurl, {
                action: 'woo_ids_manager_sincronizar_todo_wc',
                nonce: '<?php echo esc_js( wp_create_nonce('woo_ids_manager_nonce') ); ?>'
            }, function(resp){
                if(resp && resp.success && resp.data){
                    $('#sincronizar-todo-msg').html('Se han creado <b>' + resp.data.creados + '</b> nuevas etiquetas y actualizado <b>' + resp.data.actualizados + '</b>.');
                } else {
                    $('#sincronizar-todo-msg').text('No se pudo sincronizar.');
                }
            }).fail(function(){
                $('#sincronizar-todo-msg').text('Error de conexión.');
            });
        });
    });
    </script>
    <?php
}

// ---- SOLO UNA ACCIÓN AJAX PARA TODO ----
add_action('wp_ajax_woo_ids_manager_sincronizar_todo_wc', function() {
    if ( ! current_user_can('manage_options')) {
        wp_send_json_error(['error' => 'Sin permisos']);
        return;
    }
    check_ajax_referer('woo_ids_manager_nonce', 'nonce');

    if (!class_exists('WooCommerce')) {
        wp_send_json_error(['error' => 'WooCommerce no activo']);
        return;
    }
    $etiquetas = get_option('reu_etiquetas', []);
    $ya_asignados = [];
    foreach ($etiquetas as $etq) {
        if (!empty($etq['selector'])) $ya_asignados[$etq['selector']] = $etq;
    }
    // Productos (loop de productos y producto individual)
    $args = [
        'limit'     => -1,
        'status'    => 'publish',
        'visibility'=> 'visible',
        'return'    => 'ids'
    ];
    $ids = function_exists('wc_get_products') ? wc_get_products($args) : [];
    $creados = 0; $actualizados = 0;

    foreach ($ids as $id) {
        $nombre = get_the_title($id);

        // Loop product add_to_cart_XXXXX
        $selector_loop = 'add_to_cart_' . $id;
        if (isset($ya_asignados[$selector_loop])) {
            if (($ya_asignados[$selector_loop]['nombre_etiqueta'] ?? '') !== $nombre) {
                foreach ($etiquetas as &$e) {
                    if (($e['selector'] ?? '') === $selector_loop) $e['nombre_etiqueta'] = $nombre;
                }
                $actualizados++;
            }
        } else {
            $etiquetas[] = [
                'tipo' => 'Producto',
                'selector' => $selector_loop,
                'telefono' => '',
                'nombre_etiqueta' => $nombre,
                'id_ads' => ''
            ];
            $creados++;
        }

        // Single product single_add_to_cart_XXXXX
        $selector_single = 'single_add_to_cart_' . $id;
        if (isset($ya_asignados[$selector_single])) {
            if (($ya_asignados[$selector_single]['nombre_etiqueta'] ?? '') !== $nombre) {
                foreach ($etiquetas as &$e) {
                    if (($e['selector'] ?? '') === $selector_single) $e['nombre_etiqueta'] = $nombre;
                }
                $actualizados++;
            }
        } else {
            $etiquetas[] = [
                'tipo' => 'Producto (ficha)',
                'selector' => $selector_single,
                'telefono' => '',
                'nombre_etiqueta' => $nombre,
                'id_ads' => ''
            ];
            $creados++;
        }
    }
    // ---- Añadir las 3 etiquetas estándar de WooCommerce (si no existen) ----
    $std_buttons = [
        ['tipo'=>'WooCommerce', 'selector'=>'view_cart',   'nombre_etiqueta'=>'Ver carrito'],
        ['tipo'=>'WooCommerce', 'selector'=>'checkout',    'nombre_etiqueta'=>'Finalizar compra'],
        ['tipo'=>'WooCommerce', 'selector'=>'place_order', 'nombre_etiqueta'=>'Realizar pedido'],
    ];
    foreach ($std_buttons as $b) {
        if (!isset($ya_asignados[$b['selector']])) {
            $etiquetas[] = [
                'tipo'           => $b['tipo'],
                'selector'       => $b['selector'],
                'telefono'       => '',
                'nombre_etiqueta'=> $b['nombre_etiqueta'],
                'id_ads'         => ''
            ];
            $creados++;
        }
    }
    update_option('reu_etiquetas', $etiquetas);
    wp_send_json_success(['creados' => $creados, 'actualizados' => $actualizados]);
    wp_die();
});

// INYECTAR LOS IDs AUTOMÁTICOS Y MANUALES EN LOS BOTONES (loop, single y manuales)
add_action('wp_footer', function() {
    if (!function_exists('is_woocommerce')) return;
    if (!is_woocommerce() && !is_cart() && !is_checkout() && !is_product()) return;

    // Versión eficiente: asigna IDs solo a elementos presentes en el DOM actual
    ob_start(); ?>
    <script type="text/javascript">
    jQuery(function($){
        function setProductIds() {
            // Catálogo / listados / widgets
            $('button[name="add-to-cart"][value]').each(function(){
                var pid = $(this).val();
                if (pid && !$(this).attr('id')) {
                    $(this).attr('id', 'add_to_cart_' + pid);
                }
            });

            // Producto individual
            if ($('body.single-product').length) {
                var pidSingle = $('form.cart input[name="add-to-cart"]').val() || '';
                if (pidSingle) {
                    var $btn = $('form.cart button[type=submit][name="add-to-cart"]');
                    if ($btn.length && !$btn.attr('id')) {
                        $btn.attr('id', 'single_add_to_cart_' + pidSingle);
                    }
                }
            }

            // Botones estándar Woo
            $('.view-cart').attr('id', 'view_cart');
            $('.checkout-button').attr('id', 'checkout');
            $('#place_order').attr('id', 'place_order');
        }
        setProductIds();
        setTimeout(setProductIds, 350);
        setTimeout(setProductIds, 700);
        setTimeout(setProductIds, 1400);
    });
    </script>
    <?php
    echo ob_get_clean();
});

// FUNCIÓN PARA OBTENER EL NOMBRE DEL PRODUCTO POR ID (usada por tu plugin actual)
function woo_ids_manager_get_product_name($product_id) {
    if (!function_exists('wc_get_product')) return '';
    $product = wc_get_product($product_id);
    return $product ? $product->get_name() : '';
}
