<?php
// includes/dashboard.php

function reu_dashboard_page() {
?>
<div class="wrap">
    <div class="reu-plugin-guide">
        <h2><span class="reu-icons">🎯 <strong>Bienvenido a</strong></span>Registro de Eventos de Usuario</span></h2>
        <div class="reu-flex">
            <div class="reu-col">
                <div class="reu-steps">
                    <h2>¿Para qué sirve?</h2>
                    <ul>
                        <li>Registrar <code>formularios</code>, <code>clics en teléfono</code>, <code>WhatsApp</code>, <code>banners</code>, <code>descargas</code> y más.</li>
                        <li>Diferenciar entre <b>Google Ads</b>, <b>Facebook</b>, <b>Instagram</b>, <b>LinkedIn</b>, <b>orgánico</b> o tráfico de pago.</li>
                        <li>Ver estadísticas detalladas, filtrar, exportar <b>CSV</b> y medir conversiones fácilmente.</li>
                    </ul>
                </div>

                <div class="reu-steps">
                    <h2>¿Cómo se usa?</h2>
                    <ol>
                        <li>Ve a <b><a href="admin.php?page=registro_eventos">Gestión de Etiquetas</a></b> y añade las etiquetas (ID CSS) que quieras rastrear.</li>
                        <li>El plugin detecta automáticamente los eventos y los guarda.</li>
                        <li>Consulta los resultados en <b><a href="admin.php?page=registro_eventos_log">Eventos Registrados</a></b>.</li>
                    </ol>
                </div>

                <div class="reu-steps">
                    <h2>¿Qué es un <b>ID de CSS</b>?</h2>
                    <p>
                        Un <b>ID de CSS</b> es un identificador único para un elemento de tu web. Ejemplo: Si tu formulario tiene <code>id="formulario_contacto"</code>, el ID de CSS es <code>formulario_contacto</code>.<br>
                        <b>No uses espacios ni caracteres especiales.</b> Usa solo letras, números o guiones bajos.
                    </p>
                </div>

                <div class="reu-steps">
                    <h2>¿Cómo asigno el ID CSS?</h2>
                    <ul>
                        <li><b>En Elementor:</b> Edita el widget, ve a <b>Avanzado</b> &rarr; <b>ID de CSS</b>, y pon ahí tu ID (ej: <code>formulario_contacto</code>). <a href="https://elementor.com/help/css-id/" target="_blank">Guía oficial sobre ID CSS en Elementor</a></li>
                        <li><b>En HTML puro:</b> Añade el atributo <code>id</code> al elemento. Ejemplo:<br>
                            <code>&lt;form id="formulario_contacto"&gt;...&lt;/form&gt;</code>
                        </li>
                    </ul>
                </div>

                <div class="reu-steps">
                    <h2>¿Cómo sé si estoy configurando bien mi etiqueta?</h2>
                    <ul>
                        <li>Haz clic derecho en el elemento que quieres rastrear y selecciona <b>Inspeccionar</b> en tu navegador.</li>
                        <li>Busca <code>id="algo"</code> en el código. Ese es el ID que debes usar.</li>
                        <li><b>Escribe solo el valor</b> (sin <code>#</code>, sin espacios, sin comillas).</li>
                    </ul>
                </div>

                <div class="reu-steps">
                    <h2>¿Dónde veré los resultados?</h2>
                    <p>
                        Todos los clics y envíos registrados aparecerán en <b>Eventos Registrados</b>. Puedes filtrar por fecha, tipo de evento, fuente (Google Ads, Facebook, etc.) o descargar todo en CSV.
                    </p>
                </div>

                <div class="reu-steps">
                    <h2>¿No funciona el registro de eventos?</h2>
                    <ul>
                        <li>Comprueba que el <b>ID de CSS</b> está bien escrito y asignado.</li>
                        <li>Verifica que el plugin está <b>activo</b> y actualizado.</li>
                        <li>Recarga la caché del navegador y de WordPress si usas plugins de caché.</li>
                    </ul>
                </div>

                <div class="reu-tip">
                    <span class="reu-icons">💡</span>
                    <b>TIP:</b> Para rastrear conversiones en Google Ads necesitas la etiqueta global instalada en tu web.
                    <br><a href="https://support.google.com/google-ads/answer/6095821?hl=es" target="_blank">¿Cómo instalar la etiqueta global de Google Ads?</a>
                </div>

                <!-- NUEVA SECCIÓN UTM -->
                <div class="reu-steps">
                    <h2 style="font-size:20px;">¿Cómo asegurar que los eventos se registran con la fuente correcta (Google Ads, Facebook, Instagram, etc.)?</h2>
                    <div style="font-size:15px; margin-bottom: 7px;">
                        <b style="color:#2563eb;">Importante:</b> Las redes sociales <b>no añaden automáticamente parámetros UTM</b> al enlazar a tu web desde posts o anuncios.
                        Si quieres saber si un clic viene de Facebook, Instagram, LinkedIn, X, TikTok, WhatsApp, etc., debes <b>añadir los parámetros UTM</b> a cada enlace que pongas en esas redes.
                    </div>

                    <h3 style="margin:14px 0 6px 0;">¿Cómo hacerlo?</h3>
                    <div style="font-size:14px;">
                        Cuando pongas un enlace a tu web en un post o perfil, añade siempre:<br>
                        <code>?utm_source=facebook&utm_medium=organic</code>
                        O cambia <code>facebook</code> por <code>instagram</code>, <code>linkedin</code>, <code>x</code>, <code>tiktok</code>, etc.
                        <br>
                        <b>Ejemplo:</b>
                        <code>https://miweb.com/?utm_source=facebook&utm_medium=organic</code>
                    </div>
                    <div style="font-size:14px; margin-top:8px;">
                        <b>En anuncios de pago:</b> añade <code>&utm_medium=paid</code> o <code>cpc</code>.<br>
                        <b>Ejemplo:</b>
                        <code>https://miweb.com/?utm_source=facebook&utm_medium=paid</code>
                    </div>

                    <h3 style="margin:14px 0 6px 0;">¿Por qué es importante?</h3>
                    <div style="font-size:14px;">
                        Si no añades los UTM, los clics se registrarán como <b>directo</b> u <b>orgánico sin identificar</b>.<br>
                        <b>Solo Google Ads</b> añade el parámetro <code>gclid</code> en los anuncios, el resto de plataformas no.
                    </div>

                    <h3 style="margin:14px 0 6px 0;">¿Dónde poner los UTM?</h3>
                    <div style="font-size:14px;">
                        En <b>cada enlace</b> a tu web publicado en redes, emails, WhatsApp, SMS, etc.<br>
                        Usa <a href="admin.php?page=generador_utm"><b>el generador de URLs con UTM del plugin</b></a> para crear enlaces fácilmente.
                    </div>

                    <h3 style="margin:14px 0 6px 0;">Resumen UTM para las redes principales:</h3>
                    <ul style="font-size:14px; list-style-type: disc; margin-left:28px;">
                        <li><b>Facebook:</b> <code>?utm_source=facebook&utm_medium=organic</code> <span style="color:#888;">(post)</span> o <code>&utm_medium=paid</code> <span style="color:#888;">(anuncio)</span></li>
                        <li><b>Instagram:</b> <code>?utm_source=instagram&utm_medium=organic</code></li>
                        <li><b>LinkedIn:</b> <code>?utm_source=linkedin&utm_medium=organic</code></li>
                        <li><b>X (Twitter):</b> <code>?utm_source=x&utm_medium=organic</code></li>
                        <li><b>TikTok:</b> <code>?utm_source=tiktok&utm_medium=organic</code></li>
                        <li><b>Email marketing:</b> <code>?utm_source=email&utm_medium=campaign</code></li>
                        <li><b>WhatsApp:</b> <code>?utm_source=whatsapp&utm_medium=direct</code></li>
                    </ul>

                    <div style="font-size:13.5px; color:#333; margin-top:12px;">
                        <b>¿Qué ocurre si no añado UTM?</b><br>
                        El plugin marcará la fuente del evento como <b>directo</b> (tráfico directo/no identificado) y no podrás saber de dónde viene la conversión.
                    </div>
                </div>
                <!-- FIN NUEVA SECCIÓN UTM -->

            </div>
        </div>
    </div>
</div>
<?php
}
