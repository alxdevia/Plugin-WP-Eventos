//assets/js/reu-detect-ads-ajax.js

jQuery(document).ready(function($){
    // Solo si existe el título del panel
    var titulo = $('.wrap h1').first();
    if (!titulo.length) return;

    // Añade el botón
    var btn = $('<button class="button button-primary" style="margin-bottom:18px;">Comprobar Etiquetas de Tracking</button>');
    titulo.after(btn);

    btn.on('click', function(e){
        e.preventDefault();
        btn.prop('disabled', true).text('Comprobando...');
        $('.reu-tracking-aviso').remove(); // Elimina avisos previos

        $.post(
            reuDetectAds.ajaxurl,
            {
                action: 'reu_detectar_etiqueta_ads',
                nonce: reuDetectAds.nonce
            },
            function(resp){
                btn.prop('disabled', false).text('Comprobar etiquetas de tracking');

                if(!resp.success || !resp.data) return;

                // ---- Google Ads ----
                var avisoAds = $('<div class="reu-tracking-aviso"></div>').css({
                    'padding': '15px',
                    'margin': '18px 0 24px 0',
                    'border-radius': '5px',
                    'font-weight': '500',
                    'font-size': '15px'
                });

                if(resp.data.ads && resp.data.ads.found) {
                    var codigos = resp.data.ads.codes.map(function(code){
                        return `<code style="background:#fff;padding:2px 8px;border-radius:4px;">${code}</code>`;
                    }).join(' ');
                    avisoAds.css({
                        'background': '#e6faea',
                        'border': '1px solid #27bb6e',
                        'color': '#18572b'
                    });
                    avisoAds.html(`
                        <span style="font-size:18px;">✅</span> 
                        <b>Etiqueta global de Google Ads detectada:</b><br><br>
                        ${codigos}
                        <br><br>¡Todo está listo para medir conversiones!
                    `);
                } else {
                    avisoAds.css({
                        'background': '#fff9e3',
                        'border': '1px solid #ffe98a',
                        'color': '#856404'
                    });
                    avisoAds.html(`
                        <span style="font-size:18px;">⚠️</span> 
                        <b>No se ha detectado la etiqueta global de Google Ads (<code>AW-XXXXXXX</code>) en la portada de la web.</b>
                        <br><br>
                        <b>¿Cómo instalar la etiqueta global?</b>
                        <ul style="margin:12px 0 0 20px; padding:0; font-size:14px;">
                            <li>
                                <b>Método fácil (recomendado):</b>
                                <br>
                                Instala y configura <a href="https://es.wordpress.org/plugins/google-site-kit/" target="_blank"><b>Site Kit by Google</b></a> desde el repositorio oficial de WordPress.
                                <ul style="margin:6px 0 0 18px;">
                                    <li>Activa el plugin y sigue el asistente para vincular Google Analytics y Google Ads.</li>
                                    <li>Site Kit se encargará de añadir la etiqueta automáticamente.</li>
                                    <li>
                                        <a href="https://sitekit.withgoogle.com/documentation/" target="_blank">Ver guía oficial de Site Kit</a>
                                    </li>
                                </ul>
                            </li>
                            <li style="margin-top:10px;">
                                <b>Instalación manual:</b>
                                <br>
                                Añade este código en el <b>&lt;head&gt;</b> de tu web usando un plugin como <a href="https://es.wordpress.org/plugins/insert-headers-and-footers/" target="_blank">Insert Headers and Footers</a>:
                                <pre style="background:#f8f8f8;padding:9px 12px;border-radius:6px;font-size:12px;margin-top:6px;">
&lt;script async src="https://www.googletagmanager.com/gtag/js?id=AW-XXXXXXXXX"&gt;&lt;/script&gt;
&lt;script&gt;
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'AW-XXXXXXXXX');
&lt;/script&gt;</pre>
                            </li>
                        </ul>
                        <div style="margin-top:10px;color:#a7500a;font-size:13px;">
                            <b>Importante:</b> Sin esta etiqueta, Google Ads <b>NO podrá registrar conversiones</b> aunque configures etiquetas aquí.<br>
                            <a href="https://support.google.com/google-ads/answer/6095821?hl=es" target="_blank">Más información oficial</a>
                        </div>
                    `);
                }
                titulo.after(avisoAds);

                // ---- Google Analytics ----
                var avisoAnalytics = $('<div class="reu-tracking-aviso"></div>').css({
                    'padding': '15px',
                    'margin': '18px 0 24px 0',
                    'border-radius': '5px',
                    'font-weight': '500',
                    'font-size': '15px'
                });

                if(resp.data.analytics && resp.data.analytics.found) {
                    var codigosGA = resp.data.analytics.codes.map(function(code){
                        return `<code style="background:#fff;padding:2px 8px;border-radius:4px;">${code}</code>`;
                    }).join(' ');
                    avisoAnalytics.css({
                        'background': '#e6faea',
                        'border': '1px solid #27bb6e',
                        'color': '#18572b'
                    });
                    avisoAnalytics.html(`
                        <span style="font-size:18px;">✅</span> 
                        <b>Etiqueta de Google Analytics detectada:</b><br><br>
                        ${codigosGA}
                        <br><br>¡Analytics está activo!
                    `);
                } else {
                    avisoAnalytics.css({
                        'background': '#fff9e3',
                        'border': '1px solid #ffe98a',
                        'color': '#856404'
                    });
                    avisoAnalytics.html(`
                        <span style="font-size:18px;">⚠️</span> 
                        <b>No se ha detectado la etiqueta de Google Analytics (<code>G-XXXXXXX</code> o <code>UA-XXXXX</code>) en la portada de la web.</b>
                        <br>
                        <b>¿Cómo instalar Google Analytics?</b>
                        <ul style="margin:12px 0 0 20px; padding:0; font-size:14px;">
                            <li>
                                <b>Recomendado:</b> Usa <a href="https://es.wordpress.org/plugins/google-site-kit/" target="_blank"><b>Site Kit by Google</b></a> para instalar y vincular Google Analytics.
                            </li>
                            <li style="margin-top:10px;">
                                <b>Instalación manual:</b> Añade el código de Google Analytics en el <b>&lt;head&gt;</b> con tu ID.
                            </li>
                        </ul>
                        <div style="margin-top:10px;color:#a7500a;font-size:13px;">
                            <b>Importante:</b> Sin esta etiqueta, no tendrás estadísticas de tráfico fiables.<br>
                            <a href="https://support.google.com/analytics/answer/1008015?hl=es" target="_blank">Más información oficial</a>
                        </div>
                    `);
                }
                avisoAds.after(avisoAnalytics);

                // ---- Facebook Pixel: Solo si existe ----
                if (resp.data.facebook && resp.data.facebook.found && resp.data.facebook.codes.length > 0) {
                    var avisoFB = $('<div class="reu-tracking-aviso"></div>').css({
                        'background': '#eaf5ff',
                        'border': '1px solid #207cfb',
                        'color': '#18577b',
                        'margin': '18px 0 24px 0',
                        'padding': '15px',
                        'border-radius': '5px',
                        'font-weight': '500',
                        'font-size': '15px',
                        'display': 'flex',
                        'align-items': 'center',
                        'gap': '8px'
                    });
                    var lista = resp.data.facebook.codes.map(function(code){
                        var via = '';
                        if (resp.data.facebook.sources && resp.data.facebook.sources[code]) {
                            var fuente = resp.data.facebook.sources[code].join(', ');
                            if (fuente.indexOf('PixelYourSite') !== -1) {
                                via = ' <span style="color:#207cfb;font-size:14px;">(vía el plugin PixelYourSite)</span>';
                            } else {
                                via = ' <span style="color:#888;font-size:14px;">(' + fuente + ')</span>';
                            }
                        }
                        return `<code style="background:#fff;padding:2px 8px;border-radius:4px;">${code}</code>` + via;
                    }).join(' ');

                    avisoFB.html(
                        `<span style="display:inline-flex;align-items:center;justify-content:center;width:23px;height:23px;">
                            <svg width="23" height="23" viewBox="0 0 23 23">
                                <circle cx="11.5" cy="11.5" r="10.5" fill="#207cfb" stroke="#207cfb" stroke-width="2"/>
                                <polyline points="6,12 10,16 17,8" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span>
                            <b>Meta Pixel detectado:</b> ${lista}
                        </span>`
                    );
                    avisoAnalytics.after(avisoFB);
                }
            }
        );
    });
});
