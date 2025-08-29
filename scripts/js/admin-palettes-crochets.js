/**
 ------------------------------------------------------------------------
 JS - Administration palettes et crochets

 Copyright (C) 2020 Intersed
 http://www.intersed.fr/
 ------------------------------------------------------------------------

 @author    Cédric Bouillon
 @copyright Copyright (c) 2020 Intersed
 @version   1.0
 @since     2018

 ------------------------------------------------------------------------
 */
$(document).ready(function() {
    "use strict";
    chargeListe();

    $('.btnRecherche').click(function () {
        $('#filtres input[name=mode]').val('showListePalettesCrochets');
        chargeListe();
    });


    $('.btnExportPdf').click(function () {
        $('#filtres input[name=mode]').val('generePdfListePalettesCrochets');

        var objetDomBtnExport = $(this);
        objetDomBtnExport.find('i').removeClass('fa-file-csv').addClass('fa-spin fa-spinner');
        objetDomBtnExport.attr('disabled', 'disabled');

        $.fn.ajax({
            'script_execute': 'fct_transporteurs.php',
            'form_id': 'filtres',
            'callBack' : function (url_fichier) {
                $('#filtre input[name=mode]').val('showListePalettesCrochets');
                objetDomBtnExport.find('i').removeClass('fa-spin fa-spinner').addClass('fa-file-pdf');
                objetDomBtnExport.prop('disabled', false);
                $('#lienPdf').attr('href', url_fichier);
                $('#lienPdf')[0].click();

            } // FIN callBack
        });

    });


}); // FIN ready



// Charge la liste
function chargeListe() {
    "use strict";
    $.fn.ajax({
        'script_execute': 'fct_transporteurs.php',
        'form_id': 'filtres',
        'return_id': 'listePalCro',
        'done': function () {
            listeListener();
        }
    });

} // FIN fonction

// Listener de la liste
function listeListener() {
    "use strict";

    // Pagination impossible avec UNION et SQL_CALC_FOUND_ROWS !


} // FIN listener