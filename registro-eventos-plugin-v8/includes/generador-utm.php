<?php
// Generador de URLs con parámetros UTM para el plugin Registro de Eventos de Usuario

function reu_generador_utm_html() {
    // Carga todos los posts y páginas publicados para el selector
    $args = [
        'post_type'      => ['post', 'page'],
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC'
    ];
    $posts = get_posts($args);
    $site_url = site_url();

    ?>
    <div class="wrap">
        <h1>Generador de URLs UTM</h1>
        <form id="reu-utm-generator-form" style="max-width: 620px; margin-top:24px;">
            <style>
                #utm-url-selector { margin-top:7px;width:100%; }
                .utm-explain { color:#666; font-size:13px; margin:2px 0 6px 0; display:block; }
                #utm-url-copy-btn { margin-left: 10px; }
                @media (max-width:480px){
                    #utm-url-result textarea, #utm-url-result button { width:100% !important; margin-top:6px; }
                    #utm-url-copy-btn { margin-left:0; }
                }
            </style>
            <table class="form-table">
                <tr>
                    <th><label for="utm-url">URL base</label></th>
                    <td>
                        <input type="text" id="utm-url" class="regular-text" style="width:99%;" placeholder="https://tusitio.com/landing/" value="<?php echo esc_attr($site_url); ?>" required>
                        <span class="utm-explain">
                            Introduce la URL completa de destino (incluye <b>https://</b>) o selecciona una página ya publicada.
                        </span>
                        <select id="utm-url-selector">
                            <option value="">— Selecciona una página o post existente —</option>
                            <?php foreach($posts as $p): ?>
                                <option value="<?php echo esc_url(get_permalink($p->ID)); ?>">
                                    <?php echo esc_html($p->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="utm_source">utm_source</label></th>
                    <td>
                        <input type="text" id="utm_source" class="regular-text" placeholder="Ej: linkedin, facebook, google">
                        <span class="utm-explain">
                            Fuente principal del tráfico. Ejemplo: <b>linkedin</b>, <b>facebook</b>, <b>google</b>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th><label for="utm_medium">utm_medium</label></th>
                    <td>
                        <input type="text" id="utm_medium" class="regular-text" placeholder="Ej: cpc, organic, email, social">
                        <span class="utm-explain">
                            Medio del tráfico. Ejemplo: <b>organic</b>, <b>cpc</b>, <b>email</b>, <b>social</b>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th><label for="utm_campaign">utm_campaign</label></th>
                    <td>
                        <input type="text" id="utm_campaign" class="regular-text" placeholder="Nombre campaña/post">
                        <span class="utm-explain">
                            Nombre de la campaña, post o promoción. Ejemplo: <b>post-julio</b>, <b>newsletter-summer</b>
                        </span>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="button" class="button button-primary" onclick="generarUTMUrl()">Generar URL con UTM</button>
            </p>
            <div id="utm-url-result" style="margin-top:18px; font-size:16px; font-weight:500;"></div>
        </form>
    </div>
    <script>
    // Si seleccionas una página, pone la URL en el input
    document.addEventListener('DOMContentLoaded', function() {
        var selector = document.getElementById('utm-url-selector');
        var urlInput = document.getElementById('utm-url');
        selector.addEventListener('change', function() {
            if (selector.value) urlInput.value = selector.value;
        });
    });

    function generarUTMUrl() {
        var url = document.getElementById('utm-url').value.trim();
        if (!url) {
            alert("Debes indicar la URL base.");
            return;
        }
        var params = [];
        ['utm_source','utm_medium','utm_campaign'].forEach(function(key){
            var val = document.getElementById(key).value.trim();
            if (val) params.push(encodeURIComponent(key) + '=' + encodeURIComponent(val));
        });
        var glue = (url.indexOf('?') > -1) ? '&' : '?';
        var finalUrl = url + (params.length ? glue + params.join('&') : '');

        document.getElementById('utm-url-result').innerHTML =
            '<div style="margin-bottom:6px;">Copia la URL para tu campaña:</div>'+
            '<div style="display:flex;align-items:center;gap:7px;">'+
            '<textarea id="utm-url-final" style="width:99%;font-size:15px;padding:7px;" rows="2" readonly onclick="this.select()">' + finalUrl + '</textarea>'+
            '<button type="button" class="button" id="utm-url-copy-btn" onclick="copiarUrlUtm()">Copiar</button>'+
            '</div>' +
            '<span id="utm-copy-feedback" style="display:none;margin-left:9px;color:green;font-size:14px;">¡Copiado!</span>';
    }

    function copiarUrlUtm() {
        var urlInput = document.getElementById('utm-url-final');
        urlInput.select();
        urlInput.setSelectionRange(0, 99999);
        document.execCommand('copy');
        var feedback = document.getElementById('utm-copy-feedback');
        if(feedback) {
            feedback.style.display = 'inline';
            setTimeout(function(){ feedback.style.display = 'none'; }, 1300);
        }
    }
    </script>
    <?php
}
