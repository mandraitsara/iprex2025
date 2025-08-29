/**
 ------------------------------------------------------------------------
 JS - Zones (DEV)

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

    // Switch activation
    $('.togglemaster').change(function() {

        var id_zone = parseInt($(this).parents('tr').data('id'));
        if (isNaN(id_zone) || id_zone === 0 || id_zone === undefined) { alert("Identification de l'ID zone impossible !"); return false; }

        var valeur = $(this).prop('checked') ? 1 : 0;

        $.fn.ajax({
            'script_execute': 'fct_zones.php',
            'arguments': 'mode=activation&id_zone='+id_zone+'&actif='+valeur,
            'callBack': function (retour) {
                retour+= '';
                if (parseInt(retour) !== 1) {
                    alert("Une erreur est survenue ! :(");
                }
            } // FIN callback
        }); // FIN aJax
    }); // FIN toggle


    // Suppr zone
    $('.btnSupprZone').click(function () {

        if (!confirm("Supprimer cette zone ?\r\n\r\nATTENTION !\r\nCela peut provoquer des erreurs dans la génération des docuements de la Gescom !\r\n\r\nContinuer ?")) { return false; }

        var id_zone = parseInt($(this).parents('tr').data('id'));
        if (isNaN(id_zone) || id_zone === 0 || id_zone === undefined) { alert("Identification de l'ID zone impossible !"); return false; }

        $.fn.ajax({
            'script_execute': 'fct_zones.php',
            'arguments': 'mode=supprZone&id_zone='+id_zone,
            'callBack': function (retour) {
                retour+= '';
                if (parseInt(retour) !== 1) {
                    alert("Une erreur est survenue ! :(");
                    return false;
                }

                location.reload();


            } // FIN callback
        }); // FIN aJax

    }); // FIN suppr zone


}); // FIN ready