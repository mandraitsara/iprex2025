/**
 ------------------------------------------------------------------------
 JS - Tracabilité

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
    'use strict';

    chargeListeLots();
    // Chargement du contenu de la modale Lot à son ouverture
    $('#modalLotInfo').on('show.bs.modal', function (e) {

        // On récupère l'ID du lot
        var lot_id = e.relatedTarget.attributes['data-lot-id'] === undefined ? 0 : parseInt(e.relatedTarget.attributes['data-lot-id'].value);

        // On récupère le contenu de la modale
        $.fn.ajax({
            'script_execute': 'fct_lots.php',
            'arguments': 'mode=modalLotInfo&id=' + lot_id,
            'callBack': function (retour) {

                if (parseInt(retour) === -1 ) {
                    alert('ERREUR !\r\Une erreur est survenue...\r\nCode erreur : AJOODWON');
                    return false;
                }

                // On intègre les différents contenus
                var retours = retour.toString().split('^');
                var titre = retours[0];
                var body = retours[1];

                $('#modalLotInfoTitre').html(titre);
                $('#modalLotInfoBody').html(body);
                modaleLotDetailsListener(lot_id);


            } // FIN Callback
        }); // FIN aJax
    }); // Fin chargement du contenu de la modale





    // Chargement du contenu de la modale documents du lot
    $('#modalLotDocs').on('show.bs.modal', function (e) {

        // On récupère l'ID du lot
        var lot_id = e.relatedTarget.attributes['data-lot-id'] === undefined ? 0 : parseInt(e.relatedTarget.attributes['data-lot-id'].value);

        // On récupère le contenu de la modale
        $.fn.ajax({
            'script_execute': 'fct_documents.php',
            'arguments': 'mode=modalLotDocs&showonly&id=' + lot_id,
            'callBack': function (retour) {

                if (parseInt(retour) === -1 ) {
                    alert('ERREUR\r\nUne erreur est survenue...\r\nCode erreur : GJOLFJTL');
                    return false;
                }

                // On intègre les différents contenus
                var retours = retour.toString().split('^');
                var titre = retours[0];
                var body = retours[1];

                $('#modalLotDocsTitre').html(titre);
                $('#modalLotDocsBody').html(body);

                modaleDocumentsListener();

            } // FIN Callback
        }); // FIN aJax
    }); // Fin chargement du contenu de la modale

    // Nettoyage du contenu de la modale Détails à sa fermeture
    $('#modalLotInfo').on('hidden.bs.modal', function (e) {

        $('#modalLotInfoTitre').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        $('#modalLotInfoBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');

    }); // FIN fermeture modale Détails



    // Nettoyage du contenu de la modale Documents à sa fermeture
    $('#modalLotDocs').on('hidden.bs.modal', function (e) {

        $('#modalLotDocsTitre').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        $('#modalLotDocsBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');

    }); // FIN fermeture modale documents


    // Télécharger
    $('.btnTelecharger').click(function() {
        $("input[name=mode]").val('telecharger');
        // On récupère le contenu de la modale
        $.fn.ajax({
            'script_execute': 'fct_lots.php',
            'form_id':'filtres',
            'callBack': function (retour) {


                window.location.href  = retour;


            } // FIN Callback
        }); // FIN aJax

    });

}); // FIN ready


function modaleLotDetailsListener(lot_id) {



    if (lot_id === undefined) { return false; }

    $('.cbo-popover').popover({
        trigger: 'focus'
    });

    // Pagination Ajax (Produits)
    $(document).on('click','#pdts .pagination li a',function(e){

        e.stopImmediatePropagation();

        if ($(this).attr('data-url') === undefined) { return false; }

        // on affiche le loading d'attente
        $('#pdts').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');

        // on fait l'appel ajax qui va rafraichir la liste
        $.fn.ajax({script_execute:'fct_lots.php'+$(this).attr('data-url'),return_id:'pdts'});

        // on désactive le lien hypertexte
        return false;

    }); // FIN pagination ajax Produits

    // Pagination Ajax (Emballages)
    $(document).on('click','#emb .pagination li a',function(e){

        e.stopImmediatePropagation();

        if ($(this).attr('data-url') === undefined) { return false; }

        // on affiche le loading d'attente
        $('#emb').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');

        // on fait l'appel ajax qui va rafraichir la liste
        $.fn.ajax({script_execute:'fct_lots.php'+$(this).attr('data-url'),return_id:'emb'});

        // on désactive le lien hypertexte
        return false;

    }); // FIN pagination ajax Emballages

    // Pagination Ajax (Loma)

    $(document).on('click','#lom .pagination li a',function(e){

        e.stopImmediatePropagation();

        if ($(this).attr('data-url') === undefined) { return false; }

        // on affiche le loading d'attente
        $('#lom').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');

        // on fait l'appel ajax qui va rafraichir la liste
        $.fn.ajax({script_execute:'fct_lots.php'+$(this).attr('data-url'),return_id:'lom', 'done' : function() {
                $('.cbo-popover').popover({
                    trigger: 'focus'
                });
            }});

        // on désactive le lien hypertexte
        return false;

    }); // FIN pagination ajax Emballages

    // Bouton export PDF
    $('.btn-export-lot-pdf').off("click.btnexportlotpdf").on("click.btnexportlotpdf", function(e) {
        e.preventDefault();

        var id_lot = parseInt($('#general').data('id-lot'));
        if (isNaN(id_lot) || id_lot < 0) { alert("ERREUR\r\nUne erreur est survenue.\r\nCode erreur : 6EBO3LEA"); return false; }

        var objetDomBtnExport = $(this);
        objetDomBtnExport.find('i').removeClass('fa-file-pdf').addClass('fa-spin fa-spinner');
        objetDomBtnExport.attr('disabled', 'disabled');

        $.fn.ajax({
            'script_execute': 'fct_lots.php',
            'arguments':'mode=generePdf&id_lot='+ id_lot,
            'callBack' : function (url_fichier) {
                objetDomBtnExport.find('i').removeClass('fa-spin fa-spinner').addClass('fa-file-pdf');
                objetDomBtnExport.prop('disabled', false);

                $('#lienPdfLot').attr('href', url_fichier);
                $('#lienPdfLot')[0].click();

            } // FIN callBack
        }); // FIN ajax
    }); // FIN bouton export PDF


} // FIN fonction

// Listener de la modale documents du lot
function modaleDocumentsListener() {

    $('.selectpicker').selectpicker('render');

    // Bouton export PDF (liste documents)
    $('.btn-doc-genere-pdf').off("click.btndocgenerepdf").on("click.btndocgenerepdf", function(e) {
        e.preventDefault();

        var id_lot = parseInt($(this).data('id-lot'));
        if (isNaN(id_lot) || id_lot < 0) { alert("ERREUR\r\nUne erreur est survenue.\r\nCode erreur : TLS8VWX7"); return false; }

        var objetDomBtnExport = $(this);
        objetDomBtnExport.find('i').removeClass('fa-external-link-alt').addClass('fa-spin fa-spinner');
        objetDomBtnExport.attr('disabled', 'disabled');

        $.fn.ajax({
            'script_execute': 'fct_lots.php',
            'arguments':'mode=generePdf&id_lot='+ id_lot,
            'callBack' : function (url_fichier) {
                objetDomBtnExport.find('i').removeClass('fa-spin fa-spinner').addClass('fa-external-link-alt');
                objetDomBtnExport.prop('disabled', false);

                $('#lienPdfLotDoc').attr('href', url_fichier);
                $('#lienPdfLotDoc')[0].click();

            } // FIN callBack
        }); // FIN ajax
    }); // FIN bouton export PDF

} // FIN listener

function chargeListeLots(){
    var page = parseInt($('').text());
    if (isNaN(page)) { page = 1;  };

    $.fn.ajax({
        'script_execute': 'fct_search.php', // On récupère l'action (ici action.php)
        //   type: $this.attr('method'), // On récupère la méthode (post)
        'arguments': 'mode=showListeLots', // On sérialise les données = Envoi des valeurs du formulaire
        //   dataType: 'json', // JSON
        'return_id': 'listeResultats',
        'done': function() {
                console.log('Done')
            },
            'callBack': function (retour) {
                listeLotsListener();
            } // FIN Callback
        });
 
    $('#filtres').on('submit', function(e) {
        e.preventDefault(); // On empêche de soumettre le formulaire
        // <input type="hidden" name="mode" value="telecharger" />
        $("input[name=mode]").val('showListeLots');
        var $this = $(this); // L'objet jQuery du formulaire
        $('#listeResultats').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        // Envoi de la requête HTTP en mode asynchrone
        $.fn.ajax({
            'script_execute': 'fct_search.php', // On récupère l'action (ici action.php)
            //   type: $this.attr('method'), // On récupère la méthode (post)
            'arguments': $this.serialize(), // On sérialise les données = Envoi des valeurs du formulaire
            //   dataType: 'json', // JSON
            'return_id': 'listeResultats',
            'done': function() {
                    console.log('Done')
                },
                'callBack': function (retour) {
                    listeLotsListener();

                } // FIN Callback
            });
      });










    // var recherche = $('#recherche_numlot').val().trim();
    // if (recherche === undefined) { recherche = ''; }

    // $.fn.ajax({
    //     'script_execute': 'fct_lots.php',
    //     'arguments': 'mode=showListeLots&statut='+statut+'&page='+page+'&recherche='+recherche,
    //     'return_id': 'listeLots',
    //     'done': function() {
    //         switchListener();
    //     },
    //     'callBack': function (retour) {
    //         listeLotsListener();
    //     } // FIN Callback
    // }); // FIN ajax
}

/** ******************************************
 * Listener de la liste des lots
 ****************************************** */

function listeLotsListener() {
    "use strict";
    $(document).on('click','#listeResultats .pagination li a',function(){
        if ($(this).attr('data-url') === undefined) { return false; }
        // on affiche le loading d'attente
        $('#listeResultats').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        // on fait l'appel ajax qui va rafraichir la liste
        $.fn.ajax({script_execute:'fct_search.php'+$(this).attr('data-url'),return_id:'listeResultats'});
        // on désactive le lien hypertexte
        return false;
    }); // FIN pagination ajax 
}