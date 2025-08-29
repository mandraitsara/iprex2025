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
$(document).ready(function() {
    "use strict";

    // Si on change un élément du générateur...
    $('#genlot_date').change(function() {
        genereLot();
    });

    $('#genlot_abattoir').on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {
        genereLot();
    });

    $('#genlot_origine').on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {
        
        genereLot();
    });

    $('#composition').on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {


        var composition =  $('#composition').val();


        var numlot =  $('#numlot').val();
        if (numlot.length < 6) { return false; }

        // Si c'est un lot Abats et qu'on a pas le A, on le rajoute
        if (composition.substr(0,1) === 'A' &&  numlot.substr(numlot.length - 1).toUpperCase() !== 'A') {
            $('#numlot').val(numlot + 'A');

        // Si c'est pas un lot Abats et qu'on a un A, on l'enlève.
        } else if (composition.substr(0,1) !== 'A' &&  numlot.substr(numlot.length - 1).toUpperCase() === 'A') {
            $('#numlot').val(numlot.slice(0,-1));
        } // FIN contrôle du numéro de lot
    });


    // Création
    $('.btnAddLot').click(function() {

        // Un numéro de lot ne peux pas faire moins de 6 caractères
        if ($('#numlot').val().length < 6) {
            $('#numlot').addClass('is-invalid');
            setTimeout(function(){
                $('#numlot').removeClass('is-invalid');
            }, 3000);
            return false;
        }

        $(this).find('i.fa').removeClass('fa-check').addClass('fa-spin').addClass('fa-spinner');

        $('#formLot').submit();

    });

    $('#numlot').focus();



}); // FIN ready


// Génération d'un numéro de lot
function genereLot() {
    "use strict";

    var date        = $('#genlot_date').val();
    var abattoir    = $('#genlot_abattoir option:selected').data('subtext');
    var origine     = $('#genlot_origine option:selected').data('subtext');
    var abats       = $('#composition').val().substr(0,1);

    if (date === '' || abattoir === '' || origine === '' || date === undefined || abattoir === undefined || origine === undefined) { return false; }

    $.fn.ajax({
        'script_execute': 'fct_lots.php',
        'arguments': 'mode=genereNumLot&date='+date+'&abattoir='+abattoir+'&origine='+origine,
        'callBack': function (retour) {

            if (abats === 'A') {
                retour+= 'A';
            }

            $('#numlot').val(retour);

        } // FIN callback
    }); // FIN aJax


} // FIN fonction