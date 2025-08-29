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

    // Supprimer une plage
    $('.btnSupprPlage').click(function () {
        if (!confirm("Supprimer cette plage de séquences ?")) { return false; }
        var id = parseInt($(this).parents('tr').data('id'));
        if (isNaN(id)) { id = 0 ;}
        if (id === 0) { alert('Identification impossible...'); return false; }
        $(this).find('i.fa').removeClass('fa-trash-alt').addClass('fa-spin fa-spinner');
        $(location).attr('href',"admin-eans7.php?k="+id);

    }); // FIN supprimer


    // Modifier une plage
    $('.btnEditPlage').click(function () {
        $(this).parents('tr').find('.show-max, .show-min').hide();
        $(this).parents('tr').find('.edit-max, .edit-min').show();
        $(this).parents('tr').find('.edit-min').focus();
        $(this).parents('tr').find('.btnUpdPlage').show();
        $(this).hide();
    }); // FIN modifier

    // Appliquer les modifications d'une plage
    $('.btnUpdPlage').click(function () {
        var id = parseInt($(this).parents('tr').data('id'));
        var min = parseInt($(this).parents('tr').find('input.edit-min').val());
        var max = parseInt($(this).parents('tr').find('input.edit-max').val());
        if (isNaN(id)) { id = 0 ;}
        if (isNaN(min)) { min = 0 ;}
        if (isNaN(max)) { max = 0 ;}
        if (id === 0) { alert('Identification impossible...'); return false; }
        if (min === 0 || max === 0 || max <= min) { alert('Plage incorrecte !'); return false; }

        $(this).find('i.fa').removeClass('fa-trash-alt').addClass('fa-spin fa-spinner');
        $(location).attr('href',"admin-eans7.php?u="+id+'&umin='+min+'&umax='+max);

    }); // FIN modifier

}); // FIN ready