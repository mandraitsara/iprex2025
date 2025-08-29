/**
 ------------------------------------------------------------------------
 JS - Administration Statistiques

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

   $('.boutons-droite a').click(function () {
       if ($(this).hasClass('jdv')) { return true; }
       $(this).find('i.fa').removeClass().addClass('fa fa-fw fa-spin fa-spinner');
       $('.boutons-droite a').addClass('disabled');
       $('.boutons-droite a').attr('disabled', 'disabled');

   });

    $('.btnPdf').off("click.btnPdf").on("click.btnPdf", function(e) {
        e.preventDefault();

        // On cherche l'onglet actif pour savoir quoi exporter dans le PDF
        var onglet = $('#stats .tab-content .tab-pane.active').attr('id');
        if (onglet === undefined) { onglet = 'cdp'; }

        var form = 'form'+onglet.charAt(0).toUpperCase() + onglet.slice(1);

        if (!$('#'+form).length) { alert("Cette action n'est pas disponible pour cette vue..."); return false; }

        var ln = loadingBtn($(this));

        $.fn.ajax({
            script_execute: 'fct_statistiques.php',
            form_id: form,
            callBack : function (url) {
                url+= '';

                removeLoadingBtn(ln);

                if (url.substring(0,4) !== 'http') {
                    alert("Génération du PDF impossible !\r\nCode erreur : "+url);
                    return false;
                }
                window.open(url, '_blank');
                //location.href = url;


            } // FIN callBack
        }); // FIN ajax

    });


 }); // FIN ready