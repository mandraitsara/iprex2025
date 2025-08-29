/**
 ------------------------------------------------------------------------
 JS

 Copyright (C) 2021 Intersed
 http://www.intersed.fr/
 ------------------------------------------------------------------------

 @author    Cédric Bouillon
 @copyright Copyright (c) 2021 Intersed
 @version   1.0
 @since     2018

 ------------------------------------------------------------------------
 */
$(document).ready(function() {
    "use strict";
    $("code").css("display","none")
    
    $('.btnSupprSignature').off("click.btnExportPdf").on("click.btnExportPdf", function(e) {
        e.preventDefault();

        var id = parseInt($(this).data('id'));
        
        if (isNaN(id) || id === 0) { return false; }

        if (!confirm('Supprimer cette signature ?')) { return false; }

        $.fn.ajax({
            script_execute: 'fct_planning_nettoyage.php',
            arguments: 'mode=supprSignature&id='+id,
            done: function () {
                location.reload();
            } // FIN Callback
        }); // FIN ajax

    });
    //Ajouter pour le validateur  
 $('.btnValiderSignature').off("click.btnValiderSignature").on("click.btnValiderSignature", function(e) {
    e.preventDefault();
    var id_signature = parseInt($(this).parents('tr').find('code').text());
          
    if (isNaN(id_signature)) { id_signature = 0; }
    if (id_signature === 0) { alert('Identification Nettoyage échouée !'); return false; }    
    $.fn.ajax({
        script_execute: 'fct_planning_nettoyage.php',
        arguments: 'mode=saveSignature&id_signature='+id_signature,
        done: function(){
            location.reload();
        }
        
    }); // FIN ajax

}); // FIN valider
    //Fin
    $('.btnExportPdf').off("click.btnExportPdf").on("click.btnExportPdf", function(e) {
        e.preventDefault();

        var objetDomBtnExport = $(this);
        objetDomBtnExport.find('i').removeClass('fa-file-pdf').addClass('fa-spin fa-spinner');
        objetDomBtnExport.attr('disabled', 'disabled');

        var mois = $('#moisCalendrier').val();
        var an = $('#anCalendrier').val();



        $.fn.ajax({
            script_execute: 'fct_planning_nettoyage.php',
            arguments: 'mode=generePdfSignatures&mois='+mois+'&an='+an,
            callBack: function (url_fichier) {
                url_fichier+='';
                objetDomBtnExport.find('i').removeClass('fa-spin fa-spinner').addClass('fa-file-pdf');
                objetDomBtnExport.prop('disabled', false);          
                var url = url_fichier.trim()
                if (url.slice(0,4) !== 'http') { alert('ERREUR\r\nLe fichier n\'a pas pu être généré !');return false; }
                $('#lienPdf').attr('href', url_fichier);
                $('#lienPdf')[0].click();               
                

            } // FIN Callback
        }); // FIN ajax
    });

});