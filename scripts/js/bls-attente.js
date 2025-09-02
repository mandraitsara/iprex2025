/**
 ------------------------------------------------------------------------
 JS - BL ADD (FO)

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
    "use strict";

    $('.carte-bl').click(function () {

        var id_bl = parseInt($(this).data('id'));
        if (isNaN(id_bl)) { id_bl = 0; }
        if (id_bl === 0 ) { alert("ERREUR !\r\nIdentification du BL impossible...");return false; }
        $(this).find('.card-header i.fa').removeClass('fa-file-invoice').addClass('fa-spin fa-spinner');
        $(this).addClass('opacity-08');
        $(location).attr('href',"bl-addupd.php?idbl="+btoa(id_bl));

    });

}); // FIN ready
