/**
 ------------------------------------------------------------------------
 JS - Admin taxes

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

    $('.changeActivation').change(function(e) {

        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        var btnSwitch = $(this);
        var actif = btnSwitch.is(':checked') ? 1 : 0;
        var id_taxe = parseInt(btnSwitch.parents('tr').find('code').text());
        if (isNaN(id_taxe)) { id_taxe = 0; }
        if (id_taxe === 0) {
            alert('Une erreur est survenue ! Taxe non identifiée...');
            return false;
        }

        $.fn.ajax({
            'script_execute': 'fct_taxes.php',
            'arguments': 'mode=changeActivation&id=' + id_taxe + '&actif=' + actif,
            'callBack': function (retour) {
                retour+= '';
                if (parseInt(retour) !== 1) {
                    alert('Une erreur est survenue ! Activation non changée...');
                    return false;
                }
            }
        });
    });


    $('.btnSupprimer').click(function () {
        if (!confirm("Supprimer cette taxe ?")) { return false; }
    });



}); // FIN ready