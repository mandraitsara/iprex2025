/**
 ------------------------------------------------------------------------
 JS - BT (BO)

 Copyright (C) 2021 Intersed
 http://www.intersed.fr/
 ------------------------------------------------------------------------

 @author    Cédric Bouillon
 @copyright Copyright (c) 2021 Intersed
 @version   1.0
 @since     2020

 ------------------------------------------------------------------------
 */
$(document).ready(function() {
    "use strict";

    chargeListeBt();

    $('.btnRecherche').click(function () {
        chargeListeBt();
    });

}); // FIN ready


// Charge la liste des Bt
function chargeListeBt() {
    "use strict";

    $.fn.ajax({
        'script_execute': 'fct_bl.php',
        'form_id': 'filtres',
        'return_id': 'listeBts',
        'done': function () {

            listeBtListener();

        }
    }); // FIN ajax

} // FIN fonction


// Listener de la liste des BTs
function listeBtListener() {
    "use strict";


    // Pagination Ajax
    $(document).on('click','#listeBts .pagination li a',function(){
        if ($(this).attr('data-url') === undefined) { return false; }
        // on affiche le loading d'attente
        $('#listeBls').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        // on fait l'appel ajax qui va rafraichir la liste
        $.fn.ajax({script_execute:'fct_bl.php'+$(this).attr('data-url'),return_id:'listeBls',
        done:function () {
            listeBtListener();
        }
        });
        // on désactive le lien hypertexte
        return false;
    }); // FIN pagination ajax


    // Supprimer un BT
    $('.btnSupprBl').off("click.btnSupprBt").on("click.btnSupprBt", function(e) {
        e.preventDefault();

        if (!confirm("Supprimer ce Bon de Transfert ?")) { return false; }

        var id = parseInt($(this).data('id'));
        if (isNaN(id)) { id = 0; }
        if (id === 0) { alert('Identification du BT impossible !'); return false; }

        $.fn.ajax({
            'script_execute': 'fct_bl.php',
            'arguments': 'mode=supprBl&id_bl='+id,
            'callBack' : function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) { alert('Echec de la suppression !'); return false; }

                chargeListeBt();

            } // FIN callBack
        }); // FIN ajax

    }); // FIN supprimer




} // FIN listener