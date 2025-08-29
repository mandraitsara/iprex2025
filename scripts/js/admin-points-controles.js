/**
 ------------------------------------------------------------------------
 JS - Admin points de contrôles

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
        var id_point = parseInt(btnSwitch.parents('tr').find('code').text());
        if (isNaN(id_point)) { id_point = 0; }
        if (id_point === 0) {
            alert('Une erreur est survenue ! Point de contrôle non identifié...');
            return false;
        }

        $.fn.ajax({
            'script_execute': 'fct_points_controle.php',
            'arguments': 'mode=changeActivation&id=' + id_point + '&actif=' + actif,
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
        if (!confirm("Supprimer ce point de contrôle ?")) { return false; }
    });


    $('.position-up').off("click.btnPositionUp").on("click.btnPositionUp", function(e) {
        e.preventDefault();

        var id_point = parseInt($(this).parents('tr').find('code').text());
        if (isNaN(id_point)) { id_point = 0; }

        var doc =  parseInt($('#filtres select[name=filtre_type]').val());
        if (isNaN(doc)) { doc = 0; }

        $('body, body *, td, i').css('cursor', 'wait');

        $.fn.ajax({
            'script_execute': 'fct_points_controle.php',
            'arguments': 'mode=changePosition&id=' + id_point + '&deplacement=-1',
            'done': function () {
                $(location).attr('href',"admin-points-controles.php?doc="+doc);
            }
        });

    }); // FIN position Up

    $('.position-down').off("click.btnPositionDown").on("click.btnPositionDown", function(e) {
        e.preventDefault();

        var id_point = parseInt($(this).parents('tr').find('code').text());
        if (isNaN(id_point)) { id_point = 0; }

        var doc =  parseInt($('#filtres select[name=filtre_type]').val());
        if (isNaN(doc)) { doc = 0; }

        $('body, body *, td, i').css('cursor', 'wait');

        $.fn.ajax({
            'script_execute': 'fct_points_controle.php',
            'arguments': 'mode=changePosition&id=' + id_point + '&deplacement=1',
            'done': function () {
                $(location).attr('href',"admin-points-controles.php?doc="+doc);
            }
        });

    }); // FIN position Down

    $('#filtres select[name=filtre_type]').change(function () {
        var doc = parseInt($(this).val());
        if (isNaN(doc)) { doc = 0; }
        $(location).attr('href',"admin-points-controles.php?doc="+doc);
    });

}); // FIN ready