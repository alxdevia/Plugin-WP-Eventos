<?php
// includes/etiquetas.php
/**
 * Pantalla de gestión de etiquetas (añadir/editar/eliminar) con validación y avisos.
 */
function reu_configurar_etiqueta() {
    $mensaje = "";
    $editando = false;
    $edit_selector = null;
    $datos_editar = [];

    // Mostrar mensajes de éxito tras recarga
    if (isset($_GET['success'])) {
        switch ($_GET['success']) {
            case 'guardado':
                $mensaje = "<div class='updated'><p>Etiqueta guardada correctamente.</p></div>";
                break;
            case 'editado':
                $mensaje = "<div class='updated'><p>Etiqueta editada correctamente.</p></div>";
                break;
            case 'eliminado':
                $mensaje = "<div class='updated'><p>Etiqueta eliminada correctamente.</p></div>";
                break;
        }
    }

    // Obtener etiquetas
    $etiquetas = get_option('reu_etiquetas', []);

    // Eliminar etiqueta por selector único (redirige después) + nonce
    if (isset($_GET['eliminar_selector'])) {
        if ( ! isset($_GET['_wpnonce']) || ! wp_verify_nonce($_GET['_wpnonce'], 'reu_eliminar_etiqueta')) {
            wp_die('Acción no autorizada (nonce).');
        }
        $eliminar_selector = sanitize_text_field($_GET['eliminar_selector']);
        $nuevas = [];
        foreach ($etiquetas as $etq) {
            if (($etq['selector'] ?? '') !== $eliminar_selector) {
                $nuevas[] = $etq;
            }
        }
        update_option('reu_etiquetas', $nuevas);
        wp_safe_redirect( admin_url('admin.php?page=registro_eventos&success=eliminado') );
        exit;
    }

    // Editar etiqueta - cargar valores
    if (isset($_GET['editar_selector'])) {
        $editando = true;
        $edit_selector = sanitize_text_field($_GET['editar_selector']);
        foreach ($etiquetas as $idx => $etq) {
            if (($etq['selector'] ?? '') === $edit_selector) {
                $datos_editar = $etq;
                $edit_index = $idx;
                break;
            }
        }
    }

    // Guardar nueva o editar etiqueta (redirige después)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reu_guardar_etiqueta'])) {
        $tipo = ($_POST['tipo_evento'] === 'Otro') ? sanitize_text_field($_POST['tipo_evento_otro']) : sanitize_text_field($_POST['tipo_evento']);
        $selector = sanitize_text_field($_POST['selector_css']);
        $telefono = sanitize_text_field($_POST['telefono']);
        $nombre_etiqueta = sanitize_text_field($_POST['nombre_etiqueta']);
        $id_ads = sanitize_text_field($_POST['id_ads']);

        // VALIDACIONES
        $errores = [];

        if (empty($selector)) {
            $errores[] = "El campo <b>Selector CSS</b> es obligatorio.";
        } elseif (preg_match('/[^a-zA-Z0-9_\-]/', $selector)) {
            $errores[] = "El <b>Selector CSS</b> solo puede contener letras, números, guiones y guiones bajos.";
        }

        if (empty($nombre_etiqueta)) {
            $errores[] = "El campo <b>Nombre de la etiqueta</b> es obligatorio.";
        }

        if (empty($tipo)) {
            $errores[] = "El campo <b>Tipo de evento</b> es obligatorio.";
        }

        // Duplicados
        if (!$editando) {
            foreach ($etiquetas as $etq) {
                if (($etq['selector'] ?? '') === $selector) {
                    $errores[] = "Ya existe una etiqueta con ese <b>Selector CSS</b>.";
                    break;
                }
            }
        } else {
            foreach ($etiquetas as $idx => $etq) {
                if (($etq['selector'] ?? '') === $selector && $etq['selector'] !== $edit_selector) {
                    $errores[] = "Ya existe otra etiqueta con ese <b>Selector CSS</b>.";
                    break;
                }
            }
        }

        if ($errores) {
            $mensaje = "<div class='notice notice-error'><ul style='margin:0 0 0 18px'>";
            foreach ($errores as $err) $mensaje .= "<li>$err</li>";
            $mensaje .= "</ul></div>";
            // Mantener valores en el formulario tras error
            if ($editando) {
                $datos_editar = [
                    'tipo' => $tipo,
                    'selector' => $selector,
                    'telefono' => $telefono,
                    'nombre_etiqueta' => $nombre_etiqueta,
                    'id_ads' => $id_ads
                ];
            }
        } else {
            if ($editando && isset($edit_index)) {
                $etiquetas[$edit_index] = [
                    'tipo' => $tipo,
                    'selector' => $selector,
                    'telefono' => $telefono,
                    'nombre_etiqueta' => $nombre_etiqueta,
                    'id_ads' => $id_ads
                ];
                update_option('reu_etiquetas', $etiquetas);
                wp_safe_redirect( admin_url('admin.php?page=registro_eventos&success=editado') );
                exit;
            } else {
                $etiquetas[] = [
                    'tipo' => $tipo,
                    'selector' => $selector,
                    'telefono' => $telefono,
                    'nombre_etiqueta' => $nombre_etiqueta,
                    'id_ads' => $id_ads
                ];
                update_option('reu_etiquetas', $etiquetas);
                wp_safe_redirect( admin_url('admin.php?page=registro_eventos&success=guardado') );
                exit;
            }
        }
    }

    // Recargar etiquetas tras edición/eliminación
    $etiquetas = get_option('reu_etiquetas', []);

    // Tipos predefinidos
    $tipos_predef = ['Formulario', 'Teléfono', 'WhatsApp', 'Descarga PDF', 'Banner', 'Audio', 'Video'];
    $tipo_actual = $editando ? ($datos_editar['tipo'] ?? '') : '';
    $es_personalizado = ($editando && !in_array($tipo_actual, $tipos_predef));
    ?>
    <style>
      p.reu-submit {
        padding-bottom: 0px !important;
      }
    </style>
    <div class="wrap">
        <h1><?php echo $editando ? 'Editar Etiqueta' : 'Configurar Etiqueta'; ?></h1>
        <?php echo $mensaje; ?>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="tipo_evento">Tipo de Evento</label></th>
                    <td>
                        <select name="tipo_evento" id="tipo_evento" onchange="mostrarTelefonoYInput(this.value)">
                            <option value="">-- Selecciona tipo --</option>
                            <?php foreach ($tipos_predef as $tipo_pre): ?>
                                <option value="<?php echo esc_attr($tipo_pre); ?>" <?php if ($editando && $tipo_actual == $tipo_pre) echo 'selected'; ?>><?php echo esc_html($tipo_pre); ?></option>
                            <?php endforeach; ?>
                            <option value="Otro" <?php if ($es_personalizado) echo 'selected'; ?>>Otro (personalizado)</option>
                        </select>
                        <input name="tipo_evento_otro" type="text" id="tipo_evento_otro" style="margin-top:5px;width:320px;box-sizing:border-box;<?php echo $es_personalizado ? '' : 'display:none;'; ?>" value="<?php echo $es_personalizado ? esc_attr($tipo_actual) : ''; ?>" placeholder="Escribe tu propio tipo de evento">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="selector_css">Selector CSS</label></th>
                    <td>
                        <input name="selector_css" type="text" id="selector_css" value="<?php echo $editando ? esc_attr($datos_editar['selector'] ?? '') : ''; ?>" class="regular-text" required>
                        <div style="margin-top:4px; font-size:12px; color:#888;">
                            Escribe solo el <strong>nombre del ID</strong> del elemento, <b>sin el símbolo</b> <code>#</code> ni espacios. Ejemplo: <code>mi_formulario</code><br>
                            <b>¿Dónde se coloca?</b> Debe ser el <b>ID</b> del formulario o elemento HTML que quieras rastrear.
                            <ul style="margin:5px 0 0 15px;padding:0;font-size:12px;">
                                <li>
                                    <b>HTML clásico:</b> Si tu formulario es <code>&lt;form id="mi_formulario"&gt;...&lt;/form&gt;</code>, escribe <code>mi_formulario</code>.
                                </li>
                                <li>
                                    <b>Elementor Pro:</b> Selecciona el formulario en el editor de Elementor, ve a la pestaña <b>Avanzado</b>, añade un valor en el campo <b>ID de CSS</b> (por ejemplo, <code>formulario_contacto</code>) y usa ese mismo nombre aquí.
                                </li>
                            </ul>
                            <span style="font-size:11px;color:#666;">
                                Si tienes dudas, haz clic derecho en el formulario y selecciona “Inspeccionar” para ver su atributo <code>id</code> en el código HTML.
                            </span>
                        </div>
                    </td>
                </tr>
                <tr id="fila-telefono" style="<?php
                    $mostrar_tel = ($editando && (strtolower($tipo_actual) === 'teléfono' || (isset($es_personalizado) && strpos(strtolower($tipo_actual), 'tel') !== false)));
                    echo $mostrar_tel ? '' : 'display:none;';
                ?>">
                    <th scope="row"><label for="telefono">Teléfono</label></th>
                    <td>
                        <input name="telefono" type="text" id="telefono" value="<?php echo $editando ? esc_attr($datos_editar['telefono'] ?? '') : ''; ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="nombre_etiqueta">Nombre de la etiqueta</label></th>
                    <td>
                        <input name="nombre_etiqueta" type="text" id="nombre_etiqueta" value="<?php echo $editando ? esc_attr($datos_editar['nombre_etiqueta'] ?? '') : ''; ?>" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="id_ads">ID de conversión de Google Ads</label></th>
                    <td>
                        <input name="id_ads" type="text" id="id_ads" value="<?php echo $editando ? esc_attr($datos_editar['id_ads'] ?? '') : ''; ?>" class="regular-text" placeholder="AW-XXXXXX/XXXXXX">
                        <div style="margin-top:4px; font-size:12px; color:#888;">
                            Introduce aquí el ID de conversión (Conversion ID) de Google Ads para este evento.<br>
                            Lo encontrarás en Google Ads &gt; Herramientas &gt; Conversiones &gt; Detalles de la acción de conversión.<br>
                            <a href="https://support.google.com/google-ads/answer/12216226?hl=es" target="_blank">¿Dónde encontrar el ID de conversión?</a>
                        </div>
                    </td>
                </tr>
            </table>
            <p class="submit reu-submit">
                <input type="submit" name="reu_guardar_etiqueta" id="submit" class="button button-primary" value="<?php echo $editando ? 'Actualizar Etiqueta' : 'Guardar Etiqueta'; ?>">
                <?php if ($editando): ?>
                    <a href="<?php echo admin_url('admin.php?page=registro_eventos'); ?>" class="button">Cancelar</a>
                <?php endif; ?>
                <?php if (class_exists('WooCommerce')): ?>
                    <br><br>
                    <button id="reu-actualizar-woo-etiquetas" type="button" class="button button-secondary">
                        Asignar/Actualizar etiquetas WooCommerce
                    </button>
                    <p style="margin-top:4px; font-size:12px; color:#888;">
                        Sincroniza todas las etiquetas automáticas para los productos (botón Añadir al carrito en catálogo y ficha) y los botones estándar de WooCommerce: Ver carrito, Finalizar compra y Realizar pedido.
                    </p>
                    <span id="reu-woo-etiquetas-msg" style="margin-left:10px; color:green;"></span>
                <?php endif; ?>

                <br><br>

                <button id="reu-actualizar-posts-etiquetas" type="button" class="button button-secondary">
                    Asignar/Actualizar etiquetas Blog (posts)
                </button>
                <p style="margin-top:4px; font-size:12px; color:#888;">
                    Sincroniza etiquetas automáticas para las entradas del blog usando su ID de post.
                </p>
                <span id="reu-posts-etiquetas-msg" style="margin-left:10px; color:green;"></span>

                <script>
                jQuery(function($){
                    $('#reu-actualizar-woo-etiquetas').on('click', function(e){
                        e.preventDefault();
                        $('#reu-woo-etiquetas-msg').text('Sincronizando WooCommerce...');
                        $.post(ajaxurl, {
                            action:'woo_ids_manager_sincronizar_todo_wc',
                            nonce:  '<?php echo esc_js( wp_create_nonce('woo_ids_manager_nonce') ); ?>'
                        }, function(resp){
                            if(resp && resp.success && resp.data){
                                $('#reu-woo-etiquetas-msg').html(
                                    'Se han <b>creado ' + resp.data.creados + '</b> y <b>actualizado ' + resp.data.actualizados + '</b> etiquetas WooCommerce.'
                                );
                                // Recargar solo la tabla de etiquetas, no toda la página
                                $.post(ajaxurl, {
                                    action:'reu_recargar_tabla_etiquetas',
                                    nonce: '<?php echo esc_js( wp_create_nonce('reu_tabla_etiquetas_nonce') ); ?>'
                                }, function(tabla){
                                    $('#reu-etiquetas-table-wrap').html(tabla);
                                });
                            } else {
                                $('#reu-woo-etiquetas-msg').text('No se pudo sincronizar WooCommerce.');
                            }
                        }).fail(function(){
                            $('#reu-woo-etiquetas-msg').text('Error de conexión.');
                        });
                    });

                    $('#reu-actualizar-posts-etiquetas').on('click', function(e){
                        e.preventDefault();
                        $('#reu-posts-etiquetas-msg').text('Sincronizando Posts...');
                        $.post(ajaxurl, {
                            action:'reu_sincronizar_etiquetas_posts',
                            nonce:  '<?php echo esc_js( wp_create_nonce('reu_posts_sync_nonce') ); ?>'
                        }, function(resp){
                            if(resp && resp.success && resp.data){
                                $('#reu-posts-etiquetas-msg').html(
                                    'Se han <b>creado ' + resp.data.creados + '</b> y <b>actualizado ' + resp.data.actualizados + '</b> etiquetas de posts.'
                                );
                                // Recargar solo la tabla de etiquetas, no toda la página
                                $.post(ajaxurl, {
                                    action:'reu_recargar_tabla_etiquetas',
                                    nonce: '<?php echo esc_js( wp_create_nonce('reu_tabla_etiquetas_nonce') ); ?>'
                                }, function(tabla){
                                    $('#reu-etiquetas-table-wrap').html(tabla);
                                });
                            } else {
                                $('#reu-posts-etiquetas-msg').text('No se pudo sincronizar posts.');
                            }
                        }).fail(function(){
                            $('#reu-posts-etiquetas-msg').text('Error de conexión.');
                        });
                    });
                });
                </script>
                <script>
                jQuery(function($) {
                    const contenedor = $('#reu-etiquetas-table-wrap');
                    const $form = $('#reu-filtro-etiquetas-form');

                    function recargarEtiquetas(extra = {}) {
                        const data = {
                            action: 'reu_recargar_tabla_etiquetas',
                            nonce:  '<?php echo esc_js( wp_create_nonce('reu_tabla_etiquetas_nonce') ); ?>',
                            buscar_nombre: $form.find('input[name="buscar_nombre"]').val(),
                            filtro_tipo:   $form.find('select[name="filtro_tipo"]').val(),
                            ...extra
                        };

                        contenedor.html('<p style="font-weight:bold">Cargando etiquetas...</p>');

                        $.post(ajaxurl, data, function(tabla) {
                            contenedor.html(tabla);
                            contenedor[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
                        });
                    }

                    // Al cambiar el select: siempre actualiza
                    $(document).off('change', 'select[name="filtro_tipo"]'); // por si había duplicados
                    $(document).on('change', 'select[name="filtro_tipo"]', function() {
                        recargarEtiquetas();
                    });

                    // Al hacer submit manual (Enter o botón)
                    $form.off('submit').on('submit', function(e) {
                        e.preventDefault();
                        recargarEtiquetas();
                    });

                    // Al hacer clic en paginación
                    $(document).off('click', '.tablenav-pages a.page-number, .tablenav-pages a.prev-page, .tablenav-pages a.next-page');
                    $(document).on('click', '.tablenav-pages a.page-number, .tablenav-pages a.prev-page, .tablenav-pages a.next-page', function(e) {
                        e.preventDefault();
                        const url = new URL($(this).attr('href'), window.location.origin);
                        const paged = url.searchParams.get('etq_paged');
                        recargarEtiquetas({ etq_paged: paged });
                    });
                });

                </script>
            </p>
        </form>

        <h2>Etiquetas guardadas</h2>
        <div id="reu-etiquetas-filtros-wrap">
            <?php reu_pinta_filtro_form(); ?>
        </div>

        <div id="reu-etiquetas-table-wrap">
            <?php reu_pinta_tabla_etiquetas(); ?>
        </div>
    </div>
    <script>
    function mostrarTelefonoYInput(valor) {
        var fila = document.getElementById('fila-telefono');
        var inputOtro = document.getElementById('tipo_evento_otro');
        if (valor === 'Teléfono') {
            fila.style.display = '';
        } else {
            fila.style.display = 'none';
        }
        if (valor === 'Otro') {
            inputOtro.style.display = '';
        } else {
            inputOtro.style.display = 'none';
            inputOtro.value = '';
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        var tipoEvento = document.getElementById('tipo_evento');
        if (tipoEvento) mostrarTelefonoYInput(tipoEvento.value);
        tipoEvento.addEventListener('change', function() {
            mostrarTelefonoYInput(this.value);
        });
    });
    </script>
    <?php
}

// Función para pintar la tabla de etiquetas, reutilizable y llamada por AJAX
function reu_pinta_tabla_etiquetas($from_ajax = false) {
    $etiquetas = get_option('reu_etiquetas', []);

    // Recoge filtros desde POST (AJAX) o GET
    $filtro_tipo   = isset($_POST['filtro_tipo']) ? sanitize_text_field($_POST['filtro_tipo']) : (isset($_GET['filtro_tipo']) ? sanitize_text_field($_GET['filtro_tipo']) : '');
    $buscar_nombre = isset($_POST['buscar_nombre']) ? sanitize_text_field($_POST['buscar_nombre']) : (isset($_GET['buscar_nombre']) ? sanitize_text_field($_GET['buscar_nombre']) : '');
    $pagina_actual = isset($_POST['etq_paged']) ? intval($_POST['etq_paged']) : (isset($_GET['etq_paged']) ? intval($_GET['etq_paged']) : 1);
    if ($pagina_actual < 1) $pagina_actual = 1;

    // Filtros
    if ($filtro_tipo !== '') {
        $etiquetas = array_filter($etiquetas, function($e) use ($filtro_tipo) {
            return isset($e['tipo']) && stripos($e['tipo'], $filtro_tipo) !== false;
        });
    }

    if ($buscar_nombre !== '') {
        $etiquetas = array_filter($etiquetas, function($e) use ($buscar_nombre) {
            return isset($e['nombre_etiqueta']) && stripos($e['nombre_etiqueta'], $buscar_nombre) !== false;
        });
    }

    // Paginación
    $por_pagina = 20;
    $total_etiquetas = count($etiquetas);
    $total_paginas = ceil($total_etiquetas / $por_pagina);
    $offset = ($pagina_actual - 1) * $por_pagina;
    $etiquetas = array_slice($etiquetas, $offset, $por_pagina);

    // Tabla
    echo '<div id="reu-etiquetas-table-wrap">';
    echo '<table id="tabla_eventos" class="widefat striped">';
    echo '<thead>
        <tr>
            <th class="col-ellipsis">#</th>
            <th class="col-ellipsis">Tipo</th>
            <th class="col-ellipsis">Selector</th>
            <th class="col-ellipsis">Teléfono</th>
            <th class="col-ellipsis">Nombre etiqueta</th>
            <th class="col-ellipsis">ID Google Ads</th>
            <th class="col-ellipsis">Acciones</th>
        </tr>
    </thead>';
    echo '<tbody>';
    if (empty($etiquetas)) {
        echo '<tr><td colspan="7" class="col-ellipsis">No se encontraron etiquetas.</td></tr>';
    } else {
        foreach ($etiquetas as $i => $etiqueta) {
            $delete_url = wp_nonce_url(
                admin_url('admin.php?page=registro_eventos&eliminar_selector=' . urlencode($etiqueta['selector'])),
                'reu_eliminar_etiqueta',
                '_wpnonce'
            );
            echo '<tr>';
            echo '<td class="col-ellipsis">' . ($offset + $i + 1) . '</td>';
            echo '<td class="col-ellipsis">' . esc_html($etiqueta['tipo']) . '</td>';
            echo '<td class="col-ellipsis" title="' . esc_attr($etiqueta['selector']) . '">' . esc_html($etiqueta['selector']) . '</td>';
            echo '<td class="col-ellipsis">' . esc_html($etiqueta['telefono']) . '</td>';
            echo '<td class="col-ellipsis" title="' . esc_attr($etiqueta['nombre_etiqueta']) . '">' . esc_html($etiqueta['nombre_etiqueta']) . '</td>';
            echo '<td class="col-ellipsis">' . esc_html($etiqueta['id_ads'] ?? '') . '</td>';
            echo '<td class="col-ellipsis">
                    <a href="' . esc_url( admin_url('admin.php?page=registro_eventos&editar_selector=' . urlencode($etiqueta['selector'])) ) . '" class="button">Editar</a>
                    <a href="' . esc_url( $delete_url ) . '" class="button" onclick="return confirm(\'¿Seguro que deseas eliminar esta etiqueta?\');">Eliminar</a>
                </td>';
            echo '</tr>';
        }
    }
    echo '</tbody>';
    echo '</table>';

    // Paginación
    if ($total_paginas > 1) {
        echo '<div class="tablenav"><div class="tablenav-pages">';
        $base_url = remove_query_arg('etq_paged');
        $base_url = add_query_arg([
            'buscar_nombre' => $buscar_nombre,
            'filtro_tipo' => $filtro_tipo
        ], $base_url);
        if ($pagina_actual > 1) {
            echo '<a class="prev-page" href="' . esc_url(add_query_arg('etq_paged', $pagina_actual - 1, $base_url)) . '">&#171; Anterior</a> ';
        }
        for ($i = 1; $i <= $total_paginas; $i++) {
            if ($i == $pagina_actual) {
                echo '<span class="current">' . $i . '</span> ';
            } elseif ($i <= 3 || $i > $total_paginas - 2 || abs($i - $pagina_actual) <= 1) {
                echo '<a class="page-number" href="' . esc_url(add_query_arg('etq_paged', $i, $base_url)) . '">' . $i . '</a> ';
            } elseif ($i == 4 || $i == $total_paginas - 2) {
                echo '<span class="dots">...</span> ';
            }
        }
        if ($pagina_actual < $total_paginas) {
            echo '<a class="next-page" href="' . esc_url(add_query_arg('etq_paged', $pagina_actual + 1, $base_url)) . '">Siguiente &#187;</a>';
        }
        echo '</div></div>';
    }

    echo '</div>'; // reu-etiquetas-table-wrap
}

function reu_pinta_filtro_form() {
    $filtro_tipo = isset($_GET['filtro_tipo']) ? sanitize_text_field($_GET['filtro_tipo']) : '';
    $buscar_nombre = isset($_GET['buscar_nombre']) ? sanitize_text_field($_GET['buscar_nombre']) : '';

    echo '<form id="reu-filtro-etiquetas-form" method="get" style="margin-bottom: 15px;">';
    echo '<input type="hidden" name="page" value="registro_eventos">';

    echo '<select name="filtro_tipo" style="margin-right:10px;">';
    echo '<option value="">Todos los tipos</option>';
    $tipos_unicos = array_unique(array_column(get_option('reu_etiquetas', []), 'tipo'));
    foreach ($tipos_unicos as $tipo) {
        echo '<option value="' . esc_attr($tipo) . '" ' . selected($filtro_tipo, $tipo, false) . '>' . esc_html($tipo) . '</option>';
    }
    echo '</select>';

    echo '<input type="text" name="buscar_nombre" placeholder="Buscar por nombre de etiqueta" value="' . esc_attr($buscar_nombre) . '" style="margin-right:10px; width:320px;">';
    echo '<button type="submit" id="reu-filtrar-btn" class="button">Filtrar</button>';
    echo '</form>';
}
