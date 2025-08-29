/**
 ------------------------------------------------------------------------
 JS - Logs
 Copyright (C) 2021 Koesio
 http://www.koesio.com/
 ------------------------------------------------------------------------

 @author    Cédric Bouillon
 @copyright Copyright (c) 2018-2021 Koesio
 @version   1.1
 @since     2018

 ------------------------------------------------------------------------
 */
$(document).ready(function() {
    "use strict";
    chargeListe();

    // Rafraichissement de la liste toutes les minutes
    setInterval(function() {
        chargeListe();
    }, 60 * 1000);

}); // FIN ready

function chargeListe() {
    "use strict";
console.log('charge liste function')
    $.fn.ajax({
        script_execute: 'fct_ajax.php',
        form_id: 'filtres',
        return_id: 'listeLogs',
        done: function () {
            listener();
        }
    }); // FIN aJax

} // FIN fonction

function listener() {


    // Pagination Ajax
    $(document).on('click','#listeLogs .pagination li a',function(){

        if ($(this).attr('data-url') === undefined) { return false; }

        // on affiche le loading d'attente
        $('#listeLogs').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');

        // on fait l'appel ajax qui va rafraichir la liste
        $.fn.ajax({script_execute:'fct_ajax.php'+$(this).attr('data-url'),return_id:'listeLogs'});

        // on désactive le lien hypertexte
        return false;

    }); // FIN pagination ajax

}
