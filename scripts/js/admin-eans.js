/**
 ------------------------------------------------------------------------
 JS - Administration EANS7

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

    // Chargement des données
    $.fn.ajax({
        'script_execute': 'fct_produits.php',
        'arguments': 'mode=listeEans',
        'return_id' : 'listeEans'
    }); // FIN ajax

    // Export PDF
    $('.btnExportPdf').click(function() {

        var objetDomBtnExport = $(this);
        objetDomBtnExport.find('i').removeClass('fa-file-pdf').addClass('fa-spin fa-spinner');
        objetDomBtnExport.attr('disabled', 'disabled');

        $.fn.ajax({
            'script_execute': 'fct_produits.php',
            'arguments': 'mode=exportEansPdf',
            'callBack' : function (url_fichier) {
                objetDomBtnExport.find('i').removeClass('fa-spin fa-spinner').addClass('fa-file-pdf');
                objetDomBtnExport.prop('disabled', false);

                $('#lienPdf').attr('href', url_fichier);
                $('#lienPdf')[0].click();

            } // FIN callBack
        }); // FIN ajax
    }); // FIN bouton PDF


 }); // FIN ready