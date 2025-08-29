/**
 ------------------------------------------------------------------------
 JS - Bons de transfert (BO)

 Copyright (C) 2020 Intersed
 http://www.intersed.fr/
 ------------------------------------------------------------------------

 @author    Cédric Bouillon
 @copyright Copyright (c) 2020 Intersed
 @version   1.0
 @since     2020

 ------------------------------------------------------------------------
 */
$(document).ready(function() {
    "use strict";

    chargeListeBons();

    $('.btnRecherche').click(function () {
        chargeListeBons();
    });


}); // FIN ready


// Charge la liste des Bons
function chargeListeBons() {
    "use strict";

    $.fn.ajax({
        'script_execute': 'fct_bl.php',
        'form_id': 'filtres',
        'return_id': 'listeBons',
        'done': function () {

            listeBonsListener();

        }
    }); // FIN ajax

} // FIN fonction


// Listener de la liste des Bons
function listeBonsListener() {
    "use strict";


    // Imprimer
    $('.btnPdfBon').off("click.btnpdfbon").on("click.btnpdfbon", function(e) {

        e.preventDefault();

        var id_bon = parseInt($(this).data('id'));
        if (isNaN(id_bon)) { id_bon = 0; }
        if (id_bon === 0) { alert("Erreur d'identification du bon de transfert !"); return false; }

        var objetDomBtn = $(this).find('i.fa');
        objetDomBtn.removeClass('fa-file-pdf').addClass('fa-spin fa-spinner');

        $.fn.ajax({
            'script_execute': 'fct_bl.php',
            'arguments': 'mode=getUrlBonTransfertPdf&id_bon='+id_bon,
            'callBack' : function (url) {
                url+='';
                window.addEventListener('focus', window_focus, false);
                function window_focus(){
                    window.removeEventListener('focus', window_focus, false);
                    URL.revokeObjectURL(url);
                    objetDomBtn.removeClass('fa-spin fa-spinner').addClass('fa-file-pdf');
                }
                location.href = url;

            } // FIN callBack
        }); // FIN ajax

    }); // FIN imprimer BL

    // Pagination Ajax
    $(document).on('click','#listeBons .pagination li a',function(){
        if ($(this).attr('data-url') === undefined) { return false; }
        // on affiche le loading d'attente
        $('#listeBls').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        // on fait l'appel ajax qui va rafraichir la liste
        $.fn.ajax({script_execute:'fct_bl.php'+$(this).attr('data-url'),return_id:'listeBons',
            done:function () {
                listeBonsListener();
            }
        });
        // on désactive le lien hypertexte
        return false;
    }); // FIN pagination ajax

    // Supprimer un bon de transfert
    $('.btnSupprBon').off("click.btnSupprBon").on("click.btnSupprBon", function(e) {

        e.preventDefault();

        if (!confirm("Supprimer ce bon de transfert ?")) { return false; }

        var id = parseInt($(this).data('id'));
        if (isNaN(id)) { id = 0; }
        if (id === 0) { alert('Identification de lu bon de transfert impossible !'); return false; }

        $.fn.ajax({
            'script_execute': 'fct_bl.php',
            'arguments': 'mode=supprBonTransfert&id_bon='+id,
            'callBack' : function (retour) {
              retour+='';
              if (parseInt(retour) !== 1) { alert('Echec de la suppression !'); return false; }

              chargeListeBons();

            } // FIN callBack
        }); // FIN ajax


    }); // FIN supprimer bon



} // FIN listener

