/**
 ------------------------------------------------------------------------
 JS - Administration EANS13/14

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

    // Supprimer une Racine
    $('.btnSupprRacine').click(function () {
        if (!confirm("Supprimer cette racine EAN ?")) { return false; }
        var id = parseInt($(this).parents('tr').data('id'));
        if (isNaN(id)) { id = 0 ;}
        if (id === 0) { alert('Identification impossible...'); return false; }
        $(this).find('i.fa').removeClass('fa-trash-alt').addClass('fa-spin fa-spinner');
        $(location).attr('href',"admin-eans13.php?k="+id);

    }); // FIN supprimer

    // Activation
    $('.activationRacine').on('ifChecked', function(event){
        var id = parseInt($(this).parents('tr').data('id'));
        if (isNaN(id)) { id = 0 ;}
        if (id === 0) { alert('Identification impossible...'); return false; }

        $.fn.ajax({
            script_execute: 'fct_produits.php',
            arguments: 'mode=activeRacineEan13&id=' + id,
            callBack: function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) { alert('Une erreur est survenue : l\'activation n\'a pas été modifiée !'); }
            }
        });

    }); // FIN modifier

}); // FIN ready