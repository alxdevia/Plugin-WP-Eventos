//assets/js/woo-ids-manager/woo-product-id.js

jQuery(function($){
  $('.single_add_to_cart_button').each(function(){
    var pid = $(this).val();
    var nombre = '';
    var cont = $(this).closest('.elementor-widget-container, .product, .woocommerce-product');

    if (cont.length) {
      nombre = cont.find('h1.product_title.entry-title.elementor-heading-title.elementor-size-default a').first().text().trim();
      if(!nombre) {
        nombre = cont.find('h1.product_title, h2.product_title, .product_title').first().text().trim();
      }
    }

    if(pid && !$(this).attr('id')) {
      if ($('body').hasClass('single-product')) {
        $(this).attr('id', 'single_add_to_cart_' + pid);
      } else {
        $(this).attr('id', 'add_to_cart_' + pid);
      }
      if(nombre) {
        $(this).attr('data-nombre-producto', nombre);
      }
    }
  });
});
