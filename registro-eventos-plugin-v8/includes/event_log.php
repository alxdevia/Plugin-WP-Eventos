<?php
//// includes/event_log.php

// --- FUNCIÓN PARA MOSTRAR FUENTE "BONITA" ---
function reu_pretty_fuente($fuente, $row = null) {
    if (is_null($fuente)) return '';

    // Leer campos auxiliares del registro (si están disponibles)
    $gclid        = $row ? (is_array($row) ? ($row['gclid'] ?? '')        : ($row->gclid ?? ''))        : '';
    $gbraid       = $row ? (is_array($row) ? ($row['gbraid'] ?? '')       : ($row->gbraid ?? ''))       : '';
    $utm_source   = $row ? (is_array($row) ? strtolower($row['utm_source']   ?? '') : strtolower($row->utm_source   ?? '')) : '';
    $utm_medium   = $row ? (is_array($row) ? strtolower($row['utm_medium']   ?? '') : strtolower($row->utm_medium   ?? '')) : '';

    // Normalizaciones útiles
    $is_paid_medium = (strpos($utm_medium, 'paid') !== false) || $utm_medium === 'cpc';

    // Regla especial: Google Ads (detalla por gclid/gbraid)
    switch (strtolower($fuente)) {
        case 'google_ads':
        case 'googleads':
            if ($gclid && $gbraid) return 'Google Ads (Dual)';
            if ($gclid)           return 'Google Ads (Tradicional)';
            if ($gbraid)          return 'Google Ads (PMax)';
            return 'Google Ads';
    }

    // Redes ya soportadas explícitamente por fuente
    switch (strtolower($fuente)) {
        case 'facebook_paid':      return 'Facebook (Pago)';
        case 'facebook_organic':   return 'Facebook (Orgánico)';
        case 'instagram_paid':     return 'Instagram (Pago)';
        case 'instagram_organic':  return 'Instagram (Orgánico)';
        case 'linkedin_paid':      return 'LinkedIn (Pago)';
        case 'linkedin_organic':   return 'LinkedIn (Orgánico)';
        case 'directo':            return 'Directo';
        case 'organico':           // abajo intentamos embellecer con utm_source si es TikTok/X, si no, devolvemos "Orgánico"
            // Embellecer orgánico para redes sin mapeo propio en getFuenteVisita (p. ej., TikTok y X/Twitter)
            if ($utm_source === 'tiktok') {
                return $is_paid_medium ? 'TikTok (Pago)' : 'TikTok (Orgánico)';
            }
            if ($utm_source === 'x' || $utm_source === 'twitter') {
                return $is_paid_medium ? 'X (Pago)' : 'X (Orgánico)';
            }
            // También podemos mejorar casos de facebook/instagram/linkedin si por algún motivo llegaron como "orgánico"
            if ($utm_source === 'facebook')   return $is_paid_medium ? 'Facebook (Pago)'   : 'Facebook (Orgánico)';
            if ($utm_source === 'instagram')  return $is_paid_medium ? 'Instagram (Pago)'  : 'Instagram (Orgánico)';
            if ($utm_source === 'linkedin')   return $is_paid_medium ? 'LinkedIn (Pago)'   : 'LinkedIn (Orgánico)';
            return 'Orgánico';
        default:
            // Si la fuente no está mapeada pero tenemos utm_source de redes conocidas, mostrarlas bonito
            if ($utm_source === 'tiktok')     return $is_paid_medium ? 'TikTok (Pago)'     : 'TikTok (Orgánico)';
            if ($utm_source === 'x' || $utm_source === 'twitter')
                                              return $is_paid_medium ? 'X (Pago)'          : 'X (Orgánico)';
            if ($utm_source === 'facebook')   return $is_paid_medium ? 'Facebook (Pago)'   : 'Facebook (Orgánico)';
            if ($utm_source === 'instagram')  return $is_paid_medium ? 'Instagram (Pago)'  : 'Instagram (Orgánico)';
            if ($utm_source === 'linkedin')   return $is_paid_medium ? 'LinkedIn (Pago)'   : 'LinkedIn (Orgánico)';

            // Si nada de lo anterior aplica, capitalizamos la fuente tal cual
            return ucfirst($fuente);
    }
}

// --- PANEL ADMIN ---
function reu_eventos_log_panel() {
    global $wpdb;
    $por_pagina = 20;
    $pagina_actual = max(1, intval($_GET['paged'] ?? 1));
    $offset = ($pagina_actual - 1) * $por_pagina;
    $tabla = $wpdb->prefix . 'registro_eventos_log';

    // Leer filtros
    $where = [];
    $params = [];

    $fecha_filtro = $_GET['filtro_fecha'] ?? '';
    if (!empty($fecha_filtro)) {
        $where[] = 'DATE(fecha) = %s';
        $params[] = $fecha_filtro;
    }
    if (!empty($_GET['filtro_tipo'])) {
        $where[] = 'tipo = %s';
        $params[] = $_GET['filtro_tipo'];
    }
    if (!empty($_GET['filtro_etiqueta'])) {
        $where[] = 'nombre_etiqueta = %s';
        $params[] = $_GET['filtro_etiqueta'];
    }
    if (!empty($_GET['filtro_fuente'])) {
        $where[] = 'fuente = %s';
        $params[] = $_GET['filtro_fuente'];
    }
    if (!empty($_GET['busqueda'])) {
        $where[] = "(selector LIKE %s OR pagina LIKE %s OR telefono LIKE %s)";
        $busq = '%' . $_GET['busqueda'] . '%';
        $params[] = $busq; $params[] = $busq; $params[] = $busq;
    }
    $where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

    // Valores únicos para filtros
    $etiquetas_existentes = $wpdb->get_col("SELECT DISTINCT nombre_etiqueta FROM $tabla");
    $tipos_existentes = $wpdb->get_col("SELECT DISTINCT tipo FROM $tabla");
    $fuentes_existentes = $wpdb->get_col("SELECT DISTINCT fuente FROM $tabla");

    // ¿Vista agrupada o desagrupada?
    $modo_bruto = isset($_GET['vista_bruta']) && $_GET['vista_bruta'] == '1';

    // Nonce para exportación
    $nonce = wp_create_nonce('reu_exportar_eventos_excel');

    // Columnas disponibles (nombres amigables) - DISPOSITIVO después de fuente
    $columnas = [
        'id'             => 'ID',
        'fecha'          => 'Fecha',
        'tipo'           => 'Tipo',
        'selector'       => 'Selector',
        'nombre_etiqueta'=> 'Nombre Etiqueta',
        'id_ads'         => 'ID Google Ads',
        'telefono'       => 'Teléfono',
        'fuente'         => 'Fuente',
        'dispositivo'    => 'Dispositivo', // NUEVA COLUMNA
        'pagina'         => 'Página',
        'utm_source'     => 'Fuente (UTM)',
        'utm_medium'     => 'Medio (UTM)',
        'utm_campaign'   => 'Campaña (UTM)',
        'gclid'          => 'ID clic Google Ads (gclid)',
        'gbraid'         => 'ID clic PMax (gbraid)',
        'gad_campaignid' => 'ID campaña Google Ads',
        'gad_source'     => 'Fuente Google Ads',
        'ip'             => 'IP',
        'user_agent'     => 'User agent'
    ];
    $columnas_visibles_default = ['id','fecha','tipo','selector','nombre_etiqueta','telefono','fuente','dispositivo','pagina'];

    ?>
    <div class="wrap">
    <style>
    #tabla_eventos th.col-ellipsis,
    #tabla_eventos td.col-ellipsis {
        max-width: 210px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .reu-checkbox-cols { margin-bottom:10px;padding:7px 16px;background:#f8fafc;border-radius:7px; }
    .reu-checkbox-cols label { margin-right:12px; }
    #exportar_csv_bruto,
    #exportar_csv_agrupada {
        margin-bottom: 18px !important;
        margin-top: 0 !important;
        display: inline-block;
    }
    </style>
    <h1>Eventos Registrados</h1>
    <form method="get" style="margin-bottom: 1em;" id="filtro_eventos_form">
        <input type="hidden" name="page" value="registro_eventos_log">
        <input type="hidden" name="vista_bruta" id="input_vista_bruta" value="<?php echo $modo_bruto ? '1' : '0'; ?>">
        <input type="date" name="filtro_fecha" value="<?php echo esc_attr($fecha_filtro); ?>" />
        <select name="filtro_tipo">
            <option value="">--Tipo--</option>
            <?php foreach($tipos_existentes as $tipo): ?>
                <option value="<?php echo esc_attr($tipo); ?>" <?php if(($_GET['filtro_tipo'] ?? '') === $tipo) echo 'selected'; ?>>
                    <?php echo esc_html($tipo); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="filtro_etiqueta">
            <option value="">--Etiqueta--</option>
            <?php foreach($etiquetas_existentes as $eti): ?>
                <option value="<?php echo esc_attr($eti); ?>" <?php if(($_GET['filtro_etiqueta'] ?? '') === $eti) echo 'selected'; ?>><?php echo esc_html($eti); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="filtro_fuente">
            <option value="">--Fuente--</option>
            <?php foreach($fuentes_existentes as $f): ?>
                <option value="<?php echo esc_attr($f); ?>" <?php if(($_GET['filtro_fuente'] ?? '') === $f) echo 'selected'; ?>><?php echo esc_html(reu_pretty_fuente($f)); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="busqueda" placeholder="Buscar..." value="<?php echo esc_attr($_GET['busqueda'] ?? ''); ?>" />
        <button class="button">Filtrar</button>
        <input type="hidden" name="reu_exportar_nonce" value="<?php echo $nonce; ?>">
        <button class="button" type="submit"
            onclick="document.getElementById('input_vista_bruta').value='<?php echo $modo_bruto ? '0' : '1'; ?>'">
            <?php echo $modo_bruto ? 'Ver Vista Agrupada' : 'Ver Vista Individual'; ?>
        </button>
    </form>

    <?php if ($modo_bruto): ?>
        <h2 style="font-size:17px;">Registros en bruto (sin agrupar)</h2>
        <div class="reu-checkbox-cols">
            <?php foreach ($columnas as $col => $label): ?>
                <label>
                    <input type="checkbox" class="col-toggle" data-col="<?php echo $col; ?>"
                        <?php echo in_array($col, $columnas_visibles_default) ? 'checked' : ''; ?>>
                    <?php echo esc_html($label); ?>
                </label>
            <?php endforeach; ?>
        </div>
        <button class="button" id="exportar_csv_bruto" type="button">Exportar CSV</button>
        <table class="widefat striped" id="tabla_eventos">
            <thead>
                <tr>
                <?php foreach ($columnas as $col => $label): ?>
                    <th class="col-ellipsis col-<?php echo $col; ?>"><?php echo esc_html($label); ?></th>
                <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $params[] = $por_pagina;
                $params[] = $offset;
                $sql = "SELECT * FROM $tabla $where_sql ORDER BY fecha DESC LIMIT %d OFFSET %d";
                $registros = $wpdb->get_results($wpdb->prepare($sql, $params));

                if ($registros): foreach($registros as $row): ?>
                    <tr>
                        <?php foreach ($columnas as $col => $label): ?>
                            <td class="col-ellipsis col-<?php echo $col; ?>" title="<?php echo esc_attr($row->$col); ?>">
                                <?php echo ($col === 'fuente') ? esc_html(reu_pretty_fuente($row->$col, $row)) : esc_html($row->$col); ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="<?php echo count($columnas); ?>">No hay registros.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php else: ?>
        <h2 style="font-size:17px;">Vista Agrupada (estadística)</h2>
        <button class="button" id="exportar_csv_agrupada" type="button">Exportar CSV</button>
        <table class="widefat striped" id="tabla_eventos">
            <thead>
                <tr>
                    <th class="col-ellipsis">Nº de Clicks</th>
                    <th class="col-ellipsis">Tipo</th>
                    <th class="col-ellipsis">Selector</th>
                    <th class="col-ellipsis">Nombre Etiqueta</th>
                    <th class="col-ellipsis">Teléfono</th>
                    <th class="col-ellipsis">Fuente</th>
                    <th class="col-ellipsis">Dispositivo</th>
                    <th class="col-ellipsis">Página</th>
                    <th class="col-ellipsis">Fecha último evento</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Agrupar por campos relevantes
                $campos = [
                    'tipo', 'selector', 'nombre_etiqueta', 'telefono', 'fuente', 'dispositivo', 'pagina'
                ];
                $group_by = implode(', ', $campos);
                $select = $group_by . ', COUNT(*) as num_eventos, MAX(fecha) as fecha_max';

                $params[] = $por_pagina;
                $params[] = $offset;
                $sql = "SELECT $select FROM $tabla $where_sql GROUP BY $group_by ORDER BY fecha_max DESC LIMIT %d OFFSET %d";
                $registros = $wpdb->get_results($wpdb->prepare($sql, $params));
                if ($registros): foreach($registros as $row): ?>
                    <tr>
                        <td class="col-ellipsis"><?php echo esc_html($row->num_eventos); ?></td>
                        <td class="col-ellipsis"><?php echo esc_html($row->tipo); ?></td>
                        <td class="col-ellipsis"><?php echo esc_html($row->selector); ?></td>
                        <td class="col-ellipsis"><?php echo esc_html($row->nombre_etiqueta); ?></td>
                        <td class="col-ellipsis"><?php echo esc_html($row->telefono); ?></td>
                        <td class="col-ellipsis"><?php echo esc_html(reu_pretty_fuente($row->fuente, $row)); ?></td>
                        <td class="col-ellipsis"><?php echo esc_html($row->dispositivo); ?></td>
                        <td class="col-ellipsis" title="<?php echo esc_attr($row->pagina); ?>"><?php echo esc_html($row->pagina); ?></td>
                        <td class="col-ellipsis"><?php echo esc_html($row->fecha_max); ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="9">No hay registros.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php endif;
        // PAGINACIÓN PARA AMBAS VISTAS
        $total_sql = $modo_bruto
            ? "SELECT COUNT(*) FROM $tabla $where_sql"
            : "SELECT COUNT(*) FROM (SELECT 1 FROM $tabla $where_sql GROUP BY $group_by) as sub";
        $total_registros = $wpdb->get_var($wpdb->prepare($total_sql, array_slice($params, 0, -2)));
        $total_paginas = ceil($total_registros / $por_pagina);

        if ($total_paginas > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">';
            
            // Base URL con todos los filtros aplicados
            $base_url = remove_query_arg('paged');
            $base_url = add_query_arg([
                'filtro_fecha'    => $_GET['filtro_fecha'] ?? '',
                'filtro_tipo'     => $_GET['filtro_tipo'] ?? '',
                'filtro_etiqueta' => $_GET['filtro_etiqueta'] ?? '',
                'filtro_fuente'   => $_GET['filtro_fuente'] ?? '',
                'busqueda'        => $_GET['busqueda'] ?? '',
                'vista_bruta'     => $_GET['vista_bruta'] ?? '',
                'page'            => 'registro_eventos_log'
            ], $base_url);

            if ($pagina_actual > 1) {
                echo '<a class="prev-page" href="' . esc_url(add_query_arg('paged', $pagina_actual - 1, $base_url)) . '">&#171; Anterior</a> ';
            }

            for ($i = 1; $i <= $total_paginas; $i++) {
                if ($i == $pagina_actual) {
                    echo '<span class="current">' . $i . '</span> ';
                } elseif ($i <= 3 || $i > $total_paginas - 2 || abs($i - $pagina_actual) <= 1) {
                    echo '<a class="page-number" href="' . esc_url(add_query_arg('paged', $i, $base_url)) . '">' . $i . '</a> ';
                } elseif ($i == 4 || $i == $total_paginas - 2) {
                    echo '<span class="dots">...</span> ';
                }
            }

            if ($pagina_actual < $total_paginas) {
                echo '<a class="next-page" href="' . esc_url(add_query_arg('paged', $pagina_actual + 1, $base_url)) . '">Siguiente &#187;</a>';
            }

            echo '</div></div>';
        }
        ?>
    <script>
    jQuery(document).ready(function($){
        // Mostrar/ocultar columnas según checkboxes (solo modo bruto)
        function toggleColumns() {
            $('.reu-checkbox-cols .col-toggle').each(function(){
                var col = $(this).data('col');
                var visible = $(this).is(':checked');
                $('.col-' + col).toggle(visible);
            });
        }
        $('.reu-checkbox-cols .col-toggle').on('change', toggleColumns);
        toggleColumns(); // Al cargar

        // Export para vista en bruto
        $('#exportar_csv_bruto').off('click').on('click', function(e){
            e.preventDefault();
            var data = $('#filtro_eventos_form').serialize();
            // Añade solo columnas visibles
            var cols = [];
            $('.reu-checkbox-cols .col-toggle:checked').each(function(){
                cols.push($(this).data('col'));
            });
            if(cols.length > 0){
                data += '&columnas=' + cols.join(',');
            }
            $.ajax({
                url: reuExport.ajaxurl,
                method: 'POST',
                data: data + '&action=reu_exportar_eventos_excel',
                xhrFields: { responseType: 'blob' },
                success: function(blob){
                    var url = window.URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'eventos_resumen.csv';
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(url);
                }
            });
        });

        // Export para vista agrupada
        $('#exportar_csv_agrupada').off('click').on('click', function(e){
            e.preventDefault();
            var data = $('#filtro_eventos_form').serialize();
            $.ajax({
                url: reuExport.ajaxurl,
                method: 'POST',
                data: data + '&action=reu_exportar_eventos_excel',
                xhrFields: { responseType: 'blob' },
                success: function(blob){
                    var url = window.URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'eventos_resumen.csv';
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(url);
                }
            });
        });
    });
    </script>
    </div>
    <?php
}

// --- ENDPOINT AJAX PARA GUARDAR EVENTOS DESDE EL FRONTEND ---
add_action('wp_ajax_nopriv_reu_guardar_evento', 'reu_guardar_evento_log');
add_action('wp_ajax_reu_guardar_evento', 'reu_guardar_evento_log');
function reu_guardar_evento_log() {
    check_ajax_referer('registro_eventos_nonce', 'nonce');
    if (!isset($_POST['tipo']) || !isset($_POST['selector'])) wp_send_json_error('Faltan parámetros.');

    global $wpdb;
    $tabla = $wpdb->prefix . 'registro_eventos_log';
    $datos = [
        'fecha'             => current_time('mysql'),
        'tipo'              => sanitize_text_field($_POST['tipo']),
        'selector'          => sanitize_text_field($_POST['selector']),
        'nombre_etiqueta'   => sanitize_text_field($_POST['nombre_etiqueta'] ?? ''),
        'id_ads'            => sanitize_text_field($_POST['id_ads'] ?? ''),
        'telefono'          => sanitize_text_field($_POST['telefono'] ?? ''),
        'fuente'            => sanitize_text_field($_POST['fuente'] ?? ''),
        'dispositivo'       => sanitize_text_field($_POST['dispositivo'] ?? ''),
        'pagina'            => esc_url_raw($_POST['pagina'] ?? ''),
        'utm_source'        => sanitize_text_field($_POST['utm_source'] ?? ''),
        'utm_medium'        => sanitize_text_field($_POST['utm_medium'] ?? ''),
        'utm_campaign'      => sanitize_text_field($_POST['utm_campaign'] ?? ''),
        'gclid'             => sanitize_text_field($_POST['gclid'] ?? ''),
        'gbraid'            => sanitize_text_field($_POST['gbraid'] ?? ''),
        'gad_campaignid'    => sanitize_text_field($_POST['gad_campaignid'] ?? ''),
        'gad_source'        => sanitize_text_field($_POST['gad_source'] ?? ''),
        'ip'                => $_SERVER['REMOTE_ADDR'],
        'user_agent'        => $_SERVER['HTTP_USER_AGENT']
    ];

    // -------- FILTRO ANTI-NULL para todos los campos de campaña/ads --------
    foreach([
        'utm_source','utm_medium','utm_campaign',
        'gclid','gbraid','gad_campaignid','gad_source','dispositivo'
    ] as $k){
        if(empty($datos[$k])) $datos[$k] = '';
    }
    // ----------------------------------------------------------------------

    // Insertar en base de datos
    $wpdb->insert($tabla, $datos);
    if ($wpdb->last_error) {
        error_log('ERROR SQL: ' . $wpdb->last_error);
        error_log('Datos insert: ' . print_r($datos, true));
        wp_send_json_error('ERROR SQL: ' . $wpdb->last_error);
    }
    wp_send_json_success('Evento registrado');
}

// --- EXPORTACIÓN AJAX CSV EXCEL SEGÚN VISTA ACTUAL Y COLUMNS ---
add_action('wp_ajax_reu_exportar_eventos_excel', function() {
    // Seguridad: nonce + permisos
    if (!isset($_POST['reu_exportar_nonce']) || !wp_verify_nonce($_POST['reu_exportar_nonce'], 'reu_exportar_eventos_excel')) {
        wp_die('No tienes permisos para exportar (nonce).');
    }
    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos para exportar (rol).');
    }
    global $wpdb;
    $tabla = $wpdb->prefix . 'registro_eventos_log';

    // Filtros iguales al panel
    $where = [];
    $params = [];
    if (!empty($_POST['filtro_fecha'])) {
        $where[] = 'DATE(fecha) = %s';
        $params[] = $_POST['filtro_fecha'];
    }
    if (!empty($_POST['filtro_tipo'])) {
        $where[] = 'tipo = %s';
        $params[] = $_POST['filtro_tipo'];
    }
    if (!empty($_POST['filtro_etiqueta'])) {
        $where[] = 'nombre_etiqueta = %s';
        $params[] = $_POST['filtro_etiqueta'];
    }
    if (!empty($_POST['filtro_fuente'])) {
        $where[] = 'fuente = %s';
        $params[] = $_POST['filtro_fuente'];
    }
    if (!empty($_POST['busqueda'])) {
        $where[] = "(selector LIKE %s OR pagina LIKE %s OR telefono LIKE %s)";
        $busq = '%' . $_POST['busqueda'] . '%';
        $params[] = $busq; $params[] = $busq; $params[] = $busq;
    }
    $where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

    // ¿Vista bruta/individual?
    $modo_bruto = isset($_POST['vista_bruta']) && $_POST['vista_bruta'] == '1';

    // Columnas seleccionadas en bruto (o todas si no se envían)
    $todas_columnas = [
        'id', 'fecha', 'tipo', 'selector', 'nombre_etiqueta', 'id_ads', 'telefono',
        'fuente', 'dispositivo', 'pagina', 'utm_source', 'utm_medium', 'utm_campaign', 'gclid',
        'gbraid', 'gad_campaignid', 'gad_source', 'ip', 'user_agent'
    ];
    $columnas = $todas_columnas;
    if ($modo_bruto && !empty($_POST['columnas'])) {
        $columnas = explode(',', $_POST['columnas']);
        $columnas = array_intersect($todas_columnas, $columnas);
    }

    // CSV compatible Excel (UTF-8 BOM + delimitador ;)
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="eventos_resumen.csv"');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel

    if ($modo_bruto) {
        // --- VISTA INDIVIDUAL ---
        $encabezados = [];
        foreach($columnas as $c) $encabezados[] = ucfirst(str_replace('_', ' ', $c));
        fputcsv($out, $encabezados, ';');
        $sql = "SELECT * FROM $tabla $where_sql ORDER BY fecha DESC";
        $registros = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        foreach ($registros as $row) {
            $fila = [];
            foreach ($columnas as $c) {
                if ($c === 'fuente') {
                    $fila[] = reu_pretty_fuente($row[$c], $row);
                } else {
                    $fila[] = $row[$c];
                }
            }
            fputcsv($out, $fila, ';');
        }
    } else {
        // --- VISTA AGRUPADA ---
        fputcsv($out, [
            'Nº de Clicks', 'Tipo', 'Selector', 'Nombre Etiqueta', 'Teléfono',
            'Fuente', 'Dispositivo', 'Página', 'Fecha último evento'
        ], ';');
        $campos = [
            'tipo', 'selector', 'nombre_etiqueta', 'telefono', 'fuente', 'dispositivo', 'pagina'
        ];
        $group_by = implode(', ', $campos);
        $select = $group_by . ', COUNT(*) as num_eventos, MAX(fecha) as fecha_max';
        $sql = "SELECT $select FROM $tabla $where_sql GROUP BY $group_by ORDER BY fecha_max DESC";
        $registros = $wpdb->get_results($wpdb->prepare($sql, $params));
        foreach ($registros as $row) {
            fputcsv($out, [
                $row->num_eventos,
                $row->tipo,
                $row->selector,
                $row->nombre_etiqueta,
                $row->telefono,
                reu_pretty_fuente($row->fuente, $row),
                $row->dispositivo,
                $row->pagina,
                $row->fecha_max
            ], ';');
        }
    }
    fclose($out);
    exit;
});
