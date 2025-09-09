// assets/js/registro-eventos.js

// ---------------------- Utils URL/Storage ----------------------
function getURLParams() {
  var params = {};
  var q = window.location.search.replace(/^\?/, '');
  if (!q) return params;
  q.split('&').forEach(function (pair) {
    if (!pair) return;
    var p = pair.split('=');
    if (p.length >= 2) {
      var k = decodeURIComponent(p[0] || '').toLowerCase();
      var v = decodeURIComponent(p[1] || '');
      if (typeof params[k] === 'undefined' && v !== '') params[k] = v;
    }
  });
  return params;
}

// Claves que ya usaba el plugin para persistir
const reu_tracking_keys = [
  'utm_source',
  'utm_medium',
  'utm_campaign',
  'gclid',
  'gbraid',
  'gad_campaignid',
  'gad_source'
];

// TTL 24h para la atribución
const REU_TTL_MS = 24 * 60 * 60 * 1000;
function reuNow() { return Date.now(); }
function reuGetLastTouchMs() {
  var v = localStorage.getItem('reu_last_touch_ms');
  return v ? parseInt(v, 10) : 0;
}
function reuSetLastTouchNow() {
  localStorage.setItem('reu_last_touch_ms', String(reuNow()));
}
function reuIsLastTouchValid() {
  var t = reuGetLastTouchMs();
  return t && (reuNow() - t) <= REU_TTL_MS;
}
function reuClearAttribution() {
  reu_tracking_keys.forEach(function (k) { localStorage.removeItem('reu_' + k); });
  localStorage.removeItem('reu_last_touch_ms');
}

// No limpiar al entrar sin tracking; solo limpiar si el TTL expiró
function reuMaybeExpireAttribution() {
  if (!reuIsLastTouchValid()) {
    reuClearAttribution();
  }
}

// Guardar utm_* y otros si llegan en la URL
function reuSaveTrackingParams(params) {
  var savedSomething = false;
  reu_tracking_keys.forEach(function (key) {
    if (params[key]) {
      localStorage.setItem('reu_' + key, params[key]);
      savedSomething = true;
    }
  });
  if (savedSomething) reuSetLastTouchNow();
}

// Leer de localStorage si no está en la URL
function reuGetTrackingParam(key, params) {
  return params[key] || localStorage.getItem('reu_' + key) || '';
}

// ---------------------- Heurísticas de atribución ----------------------
// 1) Click IDs de redes pagadas → asignan utm_source + utm_medium=paid
function reuApplyClickIdHeuristics(params) {
  // No pisar utm_* si ya vienen
  if (!params.utm_source) {
    if (params.fbclid) {
      params.utm_source = 'facebook';
      params.utm_medium = params.utm_medium || 'paid';
    } else if (params.ttclid) {
      params.utm_source = 'tiktok';
      params.utm_medium = params.utm_medium || 'paid';
    } else if (params.li_fat_id) {
      params.utm_source = 'linkedin';
      params.utm_medium = params.utm_medium || 'paid';
    } else if (params.twclid) {
      params.utm_source = 'x';
      params.utm_medium = params.utm_medium || 'paid';
    }
  }
  return params;
}

// 2) Referrer orgánico de redes si no hay UTM ni click IDs
function reuMaybeInferFromReferrer(params) {
  if (params.utm_source || params.gclid || params.gbraid) return params;

  var ref = (document.referrer || '').toLowerCase();
  if (!ref) return params;

  // Redes sociales (incluye lnkd.in para LinkedIn)
  if (ref.indexOf('facebook.') !== -1 || ref.indexOf('fb.') !== -1) {
    params.utm_source = 'facebook';
    params.utm_medium = params.utm_medium || 'organic';
  } else if (ref.indexOf('instagram.') !== -1) {
    params.utm_source = 'instagram';
    params.utm_medium = params.utm_medium || 'organic';
  } else if (ref.indexOf('linkedin.') !== -1 || ref.indexOf('lnkd.in') !== -1) {
    params.utm_source = 'linkedin';
    params.utm_medium = params.utm_medium || 'organic';
  } else if (ref.indexOf('tiktok.') !== -1) {
    params.utm_source = 'tiktok';
    params.utm_medium = params.utm_medium || 'organic';
  } else if (ref.indexOf('twitter.') !== -1 || ref.indexOf('t.co') !== -1 || ref.indexOf('x.com') !== -1) {
    params.utm_source = 'x';
    params.utm_medium = params.utm_medium || 'organic';
  } else if (
    // Buscadores orgánico
    ref.indexOf('google.') !== -1 ||
    ref.indexOf('bing.') !== -1 ||
    ref.indexOf('yahoo.') !== -1
  ) {
    params.utm_source = 'google'; // lo marcamos genérico para "orgánico"
    params.utm_medium = params.utm_medium || 'organic';
  }

  return params;
}

// 3) Pipeline: decide y persiste la atribución de esta visita
function reuHandleAttributionOnLoad() {
  var urlParams = getURLParams();

  // Expira si corresponde (no borra si aún válido)
  reuMaybeExpireAttribution();

  // ¿Hay señales nuevas en URL? (utm_*, gclid/gbraid/gad*, click-ids)
  var hasNewSignals = false;
  for (var k in urlParams) {
    if (!urlParams.hasOwnProperty(k)) continue;
    if (k.indexOf('utm_') === 0 || k === 'gclid' || k === 'gbraid' || k === 'gad_campaignid' || k === 'gad_source' ||
        k === 'fbclid' || k === 'ttclid' || k === 'li_fat_id' || k === 'twclid') {
      hasNewSignals = true; break;
    }
  }

  if (hasNewSignals) {
    // 1) Interpretar click-ids de pago si no hay UTM
    urlParams = reuApplyClickIdHeuristics(urlParams);
    // 2) Guardar utm/gclid/gbraid/gad_*
    reuSaveTrackingParams(urlParams);
  } else {
    // Sin señales nuevas: si no hay atribución activa, intenta inferir por referrer (solo orgánico)
    if (!reuIsLastTouchValid()) {
      var inferred = reuMaybeInferFromReferrer({});
      if (inferred.utm_source) {
        // Persistimos como UTM sintéticas para los siguientes clics durante el TTL
        reuSaveTrackingParams({
          utm_source: inferred.utm_source,
          utm_medium: inferred.utm_medium || 'organic'
        });
      }
    }
  }
}

// ---------------------- Fuente de la visita (para el log) ----------------------
function getFuenteVisita(params) {
  var utm_source = (reuGetTrackingParam('utm_source', params) || '').toLowerCase();
  var utm_medium = (reuGetTrackingParam('utm_medium', params) || '').toLowerCase();

  // Google Ads: gclid/gbraid o google+cpc
  if (
    reuGetTrackingParam('gclid', params) ||
    reuGetTrackingParam('gbraid', params) ||
    (utm_source === 'google' && utm_medium === 'cpc')
  ) {
    return 'google_ads';
  }

  // Redes con utm
  if (utm_source === 'facebook') {
    if (['cpc', 'paid_social', 'paid'].indexOf(utm_medium) !== -1) return 'facebook_paid';
    return 'facebook_organic';
  }
  if (utm_source === 'instagram') {
    if (['cpc', 'paid_social', 'paid'].indexOf(utm_medium) !== -1) return 'instagram_paid';
    return 'instagram_organic';
  }
  if (utm_source === 'linkedin') {
    if (utm_medium.indexOf('paid') !== -1 || utm_medium === 'cpc') return 'linkedin_paid';
    return 'linkedin_organic';
  }
  if (utm_source === 'tiktok') {
    if (utm_medium.indexOf('paid') !== -1 || utm_medium === 'cpc') return 'organico';
    return 'organico';
  }
  if (utm_source === 'x' || utm_source === 'twitter') {
    if (utm_medium.indexOf('paid') !== -1 || utm_medium === 'cpc') return 'organico';
    return 'organico';
  }

  // Orgánico buscadores
  if (
    (utm_source === 'google' && (utm_medium === 'organic' || utm_medium === 'seo')) ||
    (utm_source === 'bing'   && utm_medium === 'organic') ||
    (utm_source === 'yahoo'  && utm_medium === 'organic')
  ) {
    return 'organico';
  }

  // Si no hay utm y tampoco gclid/gbraid, intenta referrer directo en esta página
  if (!utm_source && !reuGetTrackingParam('gclid', params) && !reuGetTrackingParam('gbraid', params) && document.referrer) {
    var ref = document.referrer.toLowerCase();
    if (ref.match(/google\./))  return 'organico';
    if (ref.match(/bing\./))    return 'organico';
    if (ref.match(/yahoo\./))   return 'organico';
    if (ref.match(/facebook\./) || ref.match(/fb\./)) return 'facebook_organic';
    if (ref.match(/instagram\./)) return 'instagram_organic';
    if (ref.match(/linkedin\./) || ref.match(/lnkd\.in/))  return 'linkedin_organic';
    if (ref.match(/tiktok\./))    return 'organico';
    if (ref.match(/twitter\./) || ref.match(/t\.co/) || ref.match(/x\.com/)) return 'organico';
  }

  return 'directo';
}

// --------- Detección de dispositivo (desktop, tablet, mobile) ----------
function reuDetectDevice() {
  var ua = navigator.userAgent.toLowerCase();
  if (/ipad|tablet|kindle|playbook|silk/.test(ua)) return 'tablet';
  if (/mobile|android|touch|webos|hpwos|iphone|ipod|blackberry|iemobile|opera mini/.test(ua)) return 'mobile';
  return 'desktop';
}

// ---------------------- Main ----------------------
jQuery(document).ready(function ($) {
  // Decide/persiste atribución de la visita
  reuHandleAttributionOnLoad();

  // Carga etiquetas y enlaza eventos
  $.ajax({
    url: registroEventosAjax.ajaxurl,
    method: 'POST',
    data: { action: 'reu_obtener_etiquetas' },
    success: function (resp) {
      if (resp.success && Array.isArray(resp.data)) {
        resp.data.forEach(function (etiqueta) {
          var sel = '#' + etiqueta.selector;
          var tipo = (etiqueta.tipo || '').toLowerCase();

          if (tipo === 'formulario') {
            $(document).on('submit', sel, function () {
              reuRegistrarEvento(etiqueta.tipo, etiqueta, window.location.href);
            });
          } else if (tipo === 'teléfono') {
            $(document).on('click', sel + ' a[href^="tel"]', function () {
              reuRegistrarEvento(etiqueta.tipo, etiqueta, window.location.href);
            });
          } else if (/audio|mp3|media|podcast|music|sonido|sound/i.test(etiqueta.tipo)) {
            var $audio = $(sel);
            if ($audio.length) {
              $audio.each(function () {
                this.addEventListener('play', function () {
                  reuRegistrarEvento(etiqueta.tipo, etiqueta, window.location.href);
                });
              });
            }
          } else if (/video|media|pelicula|movie|mp4|mpg|webm/i.test(etiqueta.tipo)) {
            var $video = $(sel);
            if ($video.length) {
              $video.each(function () {
                this.addEventListener('play', function () {
                  reuRegistrarEvento(etiqueta.tipo, etiqueta, window.location.href);
                });
              });
            }
          } else if (tipo === 'blog post') {
            $(document).on('click', sel, function (e) {
              reuRegistrarEvento(etiqueta.tipo, etiqueta, window.location.href);
              e.stopPropagation();
            });
          } else {
            $(document).on('click', sel, function () {
              reuRegistrarEvento(etiqueta.tipo, etiqueta, window.location.href);
            });
          }
        });
      }
    }
  });

  // Función global para debug y uso manual (NO CAMBIADA)
  window.reuRegistrarEvento = function (tipo, etiqueta, pagina) {
    var params = getURLParams();
    // Coge de localStorage si no está en la URL
    var tracking = {};
    reu_tracking_keys.forEach(function (key) {
      tracking[key] = reuGetTrackingParam(key, params);
    });

    var fuente = getFuenteVisita(params);
    var dispositivo = reuDetectDevice();

    $.post(registroEventosAjax.ajaxurl, {
      action: 'reu_guardar_evento',
      nonce: registroEventosAjax.nonce,
      tipo: tipo,
      selector: etiqueta.selector,
      nombre_etiqueta: etiqueta.nombre_etiqueta,
      id_ads: etiqueta.id_ads,
      telefono: etiqueta.telefono,
      pagina: pagina,
      utm_source: tracking.utm_source,
      utm_medium: tracking.utm_medium,
      utm_campaign: tracking.utm_campaign,
      gclid: tracking.gclid,
      gbraid: tracking.gbraid,
      gad_campaignid: tracking.gad_campaignid,
      gad_source: tracking.gad_source,
      fuente: fuente,
      dispositivo: dispositivo
    });

    // Medición de conversiones con Google Ads si aplica
    if (window.gtag && etiqueta.id_ads && etiqueta.id_ads.startsWith('AW-')) {
      gtag('event', 'conversion', {
        'send_to': etiqueta.id_ads
      });
    }
  };
});
