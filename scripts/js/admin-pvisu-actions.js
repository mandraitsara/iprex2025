/**
 ------------------------------------------------------------------------
 JS - Admin actions correctives

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
        var id_pvisu_action = parseInt(btnSwitch.parents('tr').find('code').text());
        if (isNaN(id_pvisu_action)) { id_pvisu_action = 0; }
        if (id_pvisu_action === 0) {
            alert('Une erreur est survenue ! Action corrective non identifiée...');
            return false;
        }

        $.fn.ajax({
            'script_execute': 'fct_pvisu_actions.php',
            'arguments': 'mode=changeActivation&id=' + id_pvisu_action + '&actif=' + actif,
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
        if (!confirm("Supprimer cette action corrective ?")) { return false; }
    });



}); // FIN ready