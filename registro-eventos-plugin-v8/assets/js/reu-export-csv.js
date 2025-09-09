//assets/js/reu-export-csv.js

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
    toggleColumns();

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
