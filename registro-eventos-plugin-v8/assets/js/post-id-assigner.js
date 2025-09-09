//assets/js/post-id-assigner.js

jQuery(function($){
  // Para cada contenedor que podría representar un post
  // Lo ideal es apuntar a un selector común que englobe cada post,
  // si no se sabe, puedes usar elementos con clase que contengan "post" o similares.
  // Aquí puedes adaptar el selector base si tienes un contenedor más específico.

  // Por ejemplo, buscar elementos con clases que contengan "post" (ajusta si quieres)
  var posiblesContenedores = $('[data-post-id], [class*="post-"], [aria-labelledby^="uael-post-"]');

  posiblesContenedores.each(function(){
    var $elem = $(this);

    // Evitar asignar ID a elementos anidados que ya tengan ID asignado a su contenedor padre
    var postId = null;

    // 1) Intentar sacar ID desde data-post-id (más fiable)
    if ($elem.data('post-id')) {
      postId = $elem.data('post-id');
    } else {
      // 2) Buscar clase post-XXXX y extraer XXXX (solo si postId no asignado)
      var clases = $elem.attr('class');
      if (clases) {
        var match = clases.match(/post-(\d+)/);
        if (match) postId = match[1];
      }

      // 3) Buscar aria-labelledby="uael-post-XXXX"
      if (!postId && $elem.attr('aria-labelledby')) {
        var al = $elem.attr('aria-labelledby');
        var matchAl = al.match(/uael-post-(\d+)/);
        if (matchAl) postId = matchAl[1];
      }

      // 4) Buscar elementos hijos con id="uael-post-XXXX" y extraer número
      if (!postId) {
        var hijo = $elem.find('[id^="uael-post-"]').first();
        if (hijo.length) {
          var idHijo = hijo.attr('id');
          var matchHijo = idHijo.match(/uael-post-(\d+)/);
          if (matchHijo) postId = matchHijo[1];
        }
      }
    }

    // Finalmente asignar id si no tiene y postId está definido
    if (postId && !$elem.attr('id')) {
      // Comprobar que no hay un padre con ese mismo postId para evitar IDs duplicados
      if ($elem.parents('#post_id_' + postId).length === 0) {
        $elem.attr('id', 'post_id_' + postId);
      }
    }
  });
});
