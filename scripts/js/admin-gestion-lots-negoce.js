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

    // Affiche la liste des lots negoce (aJax)
    chargeListeLots();

    // Rafraichissement de la liste toutes les minutes
    setInterval(function() {
        chargeListeLots();
    }, 60 * 1000);


    // Chargement du contenu de la modale Lot à son ouverture
 $('#modalLotInfo').on('show.bs.modal', function (e) {    

    // On récupère l'ID du lot
    var lot_id = e.relatedTarget.attributes['data-lot-id'] === undefined ? 0 : parseInt(e.relatedTarget.attributes['data-lot-id'].value);
    
    // On récupère le contenu de la modale
    $.fn.ajax({
        'script_execute': 'fct_gestion_negoce.php',
        'arguments': 'mode=modalLotInfo&id=' + lot_id,
        'callBack': function (retour) {

            if (parseInt(retour) === -1 ) {
                alert('Une erreur est survenue\r\nCode erreur : VMMT0W79');
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

})

 


/** ******************************************
 * Affiche la liste des lots negoce
 ****************************************** */
function chargeListeLots() {
    "use strict";
    var statut =  $('#listeLots').data('statut');

    var page = parseInt($('.pagination .page-item.active a').text());
    if (isNaN(page)) { page = 1;  }


    var recherche = $('#recherche_numlot').val().trim();
    if (recherche === undefined) { recherche = ''; }

    $.fn.ajax({
        'script_execute': 'fct_gestion_negoce.php',
        'arguments': 'mode=showListeLotsNegoce&statut='+statut+'&page='+page+'&recherche='+recherche,
        'return_id': 'listeLots',
        'done': function() {
            switchListener();
        },
        'callBack': function (retour) {
            listeLotsListener();
        } // FIN Callback
    }); // FIN ajax

    $.fn.ajax({
        'script_execute': 'fct_validations.php',
        'arguments': 'mode=updateBadgeCompteurMenu',
        'return_id': 'ajaxCompteurValid'
    }); // FIN aJax

    return true;

} // FIN fonction

/** ******************************************
 * Listener de la liste des lots de negoce
 ****************************************** */
function listeLotsListener() {

    // Clic sur un icone d'incident (supprimer)
    $(document).on('click','#listeLots .ico-incident',function(){

        var verbose = $(this).data('verbose');
        var id_incident = parseInt($(this).data('id-incident'));
        var date_incident =  $(this).data('date');
        var user_incident =  $(this).data('user');

        var htmlIncident = '<div class="alert alert-danger text-20 text-center">'+verbose+'</div><p class="margin-0 text-center gris-5 text-14">'+date_incident+'<br/>par '+user_incident+'</br>';


        $('#id_incident_suppr').val(id_incident);
        $('#modalLotIncidentBody').html(htmlIncident);

        $('#modalLotIncident').modal('show');

    }); // Fin clic icone incident


    // Bouton supprimer incident
        $('.btn-suppr-incident').off("click.btnsupprincident").on("click.btnsupprincident", function(e) {
        e.preventDefault();


       var id_incident = parseInt($('#id_incident_suppr').val());

       if (isNaN(id_incident) || id_incident === undefined || id_incident === 0) {
           alert("ERREUR !\r\nIdentification de l'incident impossible\r\nCode erreur : XD3VXTT9"); return false;
       }

        $.fn.ajax({
                'script_execute': 'fct_lots.php',
                'arguments': 'mode=supprIncident&id_incident='+id_incident,
                'callBack': function() {
                    chargeListeLots();
                    $('#modalLotIncident').modal('hide');
                }
            }); // FIN ajax

            return false;

    }); // Fin clic supprimer incident




    // Pagination Ajax
    $(document).on('click','#listeLots .pagination li a',function(){
        if ($(this).attr('data-url') === undefined) { return false; }
        // on affiche le loading d'attente
        $('#listeLots').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        // on fait l'appel ajax qui va rafraichir la liste
        $.fn.ajax({script_execute:'fct_lots.php'+$(this).attr('data-url'),return_id:'listeLots',done:function() { switchListener();} });
        // on désactive le lien hypertexte
        return false;
    }); // FIN pagination ajax


    // Bouton saisie rapide du poids à la réception
    $(document).on('click','#listeLots .btnPoidsReceptionLot',function(){

        var id_lot = parseInt($(this).parents('tr').data('id-lot'));

        $.fn.ajax({
            'script_execute': 'fct_lots.php',
            'arguments': 'mode=modalPoidsReception&id_lot='+id_lot,
            'return_id': 'modalLotPoidsBody',
            'done': function () {

                // Listener
                modalePoidsReceptionListener();

            } // FIN Callback
        }); // FIN ajax

        $('#modalLotPoids').modal('show');

    }); // FIN bouton rapide poids réception lot


} // FIN listener liste des lots

/** ******************************************
 * Listener des switchs de la liste des lots
 * (isolé car conflit avec le listener général)
 ****************************************** */
function switchListener() {

    // Toogle pour visibilités
    $('.togglemaster').bootstrapToggle();

    // Changement visibilité
    $('.switch-visibilite-lot').change(function() {

        // Récupération de l'ID du lot et gestion des erreurs
        var id_lot = parseInt($(this).parents('tr').data('id-lot'));
        if (isNaN(id_lot) || id_lot === undefined || id_lot === 0) {
            alert('ERREUR\r\nIdentification du lot impossible !\r\nCode erreur : GI4UF0VA');
            return false;
        }

        // On récupère l'état de visibilité à appliquer
        var visible =  $(this).prop('checked') ? 1 : 0;

        // Maj en ajax de la visibilité du lot
        $.fn.ajax({
            'script_execute': 'fct_gestion_negoce.php',
            'arguments':'mode=changeVisibilite&id_lot='+ id_lot + '&visible='+visible,
            'callBack' : function (retour) {
                if (parseInt(retour) !== 1) {
                    alert('ERREUR\r\nEchec lors du changement de visibilité !\r\nCode erreur : 6AU1QIUN');
                }
            } // FIN callBack
        }); // FIN ajax

    }); // FIN Changement visibilité


    // Changement test traçabilité
    $('.switch-tracabilite-lot').change(function() {

        // Récupération de l'ID du lot et gestion des erreurs
        var id_lot = parseInt($(this).parents('tr').data('id-lot'));
        if (isNaN(id_lot) || id_lot === undefined || id_lot === 0) {
            alert('ERREUR\r\nIdentification du lot impossible !\r\nCode erreur : 1LKCCONJ');
            return false;
        }

        // On récupère l'état à appliquer
        var tracabilite =  $(this).prop('checked') ? 1 : 0;

        // Maj en ajax de la visibilité du lot
        $.fn.ajax({
            'script_execute': 'fct_gestion_negoce.php',
            'arguments':'mode=changeTracabilite&id_lot='+ id_lot + '&tracabilite='+tracabilite,
            'callBack' : function (retour) {
                if (parseInt(retour) !== 1) {
                    alert('ERREUR\r\nEchec lors du changement de visibilité !\r\nCode erreur : 7IX66NH1');
                }
            } // FIN callBack
        }); // FIN ajax

    }); // FIN Changement test traçabilité

} // FIN listener


// Listener de la modale détails du lot
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
        $.fn.ajax({script_execute:'fct_gestion_negoce.php'+$(this).attr('data-url'),return_id:'lom', 'done' : function() {
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
        if (isNaN(id_lot) || id_lot < 0) { alert("ERREUR\r\nUne erreur est survenue.\r\nCode erreur : 0J18GB46"); return false; }

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

    // Boutton Ré-ourvrir un lot terminé
    $('.btn-reopenlot').off("click.btnreopenlot").on("click.btnreopenlot", function(e) {
        e.preventDefault();

        var id_lot = parseInt($('#general').data('id-lot'));
        if (isNaN(id_lot) || id_lot < 0) {
            alert("ERREUR\r\nUne erreur est survenue.\r\nCode erreur : J0COJT0P");
            return false;
        }

        if (!confirm("ATTENTION\r\nVous allez ré-ouvrir un lot terminé !\r\nContinuer ?")) { return false; }

        $(this).html('<i class="fa fa-spin fa-spinner"></i>');

        // OK, ajax qui réouvre le lot...
        $.fn.ajax({
            'script_execute': 'fct_gestion_negoce.php',
            'arguments':'mode=reouvrirLotTermine&id_lot='+ id_lot,
            'done' : function () {
                $(location).attr('href', 'admin-gestion-lots-negoce.php')
            } // FIN callBack
        }); // FIN ajax


    }); // FIN bouton révouvrir lot terminé


    // Bouton re-envoyer vers Bizerba
    $('.btnReBizerba').off("click.btnReBizerba").on("click.btnReBizerba", function(e) {
        e.preventDefault();
        if (!confirm("Re-envoyer ce lot à BizTrack ?\r\nAttention : risque de doublon !\r\nÊtes-vous sûr ?")) { return false; }

        var id_lot = parseInt($('#general').data('id-lot'));
        if (isNaN(id_lot) || id_lot < 0) {
            alert("ERREUR\r\nIdentification du lot impossible !");
            return false;
        }

        var ifa = $(this).find('i.fa');
        ifa.removeClass('fa-redo-alt').addClass('fa-spin fa-spinner');
        // OK, ajax qui réouvre le lot...
        $.fn.ajax({
            'script_execute': 'fct_gestion_negoce.php',
            'arguments':'mode=envoiLotBizerna&id_lot='+ id_lot,
            'callBack' : function (retour) {

                ifa.removeClass('fa-spin fa-spinner').addClass('fa-redo-alt');

                retour = retour + '';
                if (parseInt(retour) !== 1) {
                    alert('ERREUR !\r\nEchec lors de l\'envoi du lot vers Bizerba...\r\nCode erreur : ' + retour);
                    return false;
                }

                $('.btnBizerba').hide();
                alert('Lot envoyé vers Bizerba.');
            } // FIN callBack
        }); // FIN ajax

    }); // FIN re-bizerba

        // Bouton envoyer vers Bizerba
    $('.btnBizerba').off("click.btnBizerba").on("click.btnBizerba", function(e) {
        e.preventDefault();

        var id_lot = parseInt($('#general').data('id-lot'));
        if (isNaN(id_lot) || id_lot < 0) {
            alert("ERREUR\r\nUne erreur est survenue.\r\nCode erreur : HMOWI7IF");
            return false;
        }

        var ifa = $(this).find('i.fa');
        ifa.removeClass('fa-share-alt').addClass('fa-spin fa-spinner');
        // OK, ajax qui réouvre le lot...
        $.fn.ajax({
            'script_execute': 'fct_gestion_negoce.php',
            'arguments':'mode=envoiLotBizerna&id_lot='+ id_lot,
            'callBack' : function (retour) {

                ifa.removeClass('fa-spin fa-spinner').addClass('fa-share-alt');

                retour = retour + '';
                if (parseInt(retour) !== 1) {
                    alert('ERREUR !\r\nEchec lors de l\'envoi du lot vers Bizerba...\r\nCode erreur : ' + retour);
                    return false;
                }

                $('.btnBizerba').hide();
                alert('Lot envoyé vers Bizerba.');
            } // FIN callBack
        }); // FIN ajax



    }); // FIN bouton envoyer vers Bizerba



} // FIN fonction


