/**
 ------------------------------------------------------------------------
 JS - Creation de lot

 Copyright (C) 2018 Intersed
 http://www.intersed.fr/
 ------------------------------------------------------------------------

 @author    Cédric Bouillon
 @copyright Copyright (c) 2018 Intersed
 @version   1.0
 @since     2018

 ------------------------------------------------------------------------
 */
$(document).ready(function(){
    "use strict";

    // Si on change l'espèce, on adapte chasse...
        $('#composition').on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {

            if ($('#composition option:selected').text().toLowerCase().indexOf('gibier') !== -1) {

                $('#row-chasse').show('fast');

            } else if ($('#row-chasse').is(':visible') === true) {

                $('#row-chasse').hide('fast');
                $('#chasse').selectpicker('val', '');

            }


    });

$('.produits_en_pieces').change(function() {

        // Récupération de l'ID du lot et gestion des erreurs
        var id_lot = parseInt($(this).parents('tr').data('id-lot'));
        if (isNaN(id_lot) || id_lot === undefined || id_lot === 0) {
            alert('ERREUR\r\nIdentification du lot impossible !\r\nCode erreur : XP3UYS7H');
            return false;
        }

        // On récupère l'état de visibilité à appliquer
        var visible =  $(this).prop('checked') ? 1 : 0;

        // Maj en ajax de la visibilité du lot
        $.fn.ajax({
            'script_execute': 'fct_lots_negoce.php',
            'arguments':'mode=changeVisibilite&id_lot='+ id_lot + '&visible='+visible,
            'callBack' : function (retour) {
                if (parseInt(retour) !== 1) {
                    alert('ERREUR\r\nEchec lors du changement de visibilité !\r\nCode erreur : EH4XFMLZ');
                }
            } // FIN callBack
        }); // FIN ajax

    }); // FIN Changement visibilité


// Génération d'un numéro de lot
$('.btnAddLot').click(function() {    
    if ($('#num_bl').val() == ""){
        $('#num_bl').addClass('is-invalid');
        setTimeout(function(){
            $('#num_bl').removeClass('is-invalid');
        }, 3000);
        return false;
    }
    $(this).find('i.fa').removeClass('fa-check').addClass('fa-spin').addClass('fa-spinner');

    $('#formLot').submit();

});

$('#numlot').focus();



})

