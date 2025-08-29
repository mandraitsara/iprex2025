/**
 ------------------------------------------------------------------------
 JS - Vue Stock Produits

 Copyright (C) 2018 Intersed
 http://www.intersed.fr/
 ------------------------------------------------------------------------

 @author    Cédric Bouillon
 @copyright Copyright (c) 2019 Intersed
 @version   1.0
 @since     2019

 ------------------------------------------------------------------------
 */
$('document').ready(function() {
    "use strict";

    chargeEtape(0, 0);

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    // Ready -> Nettoyage des modales à leur fermeture
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    // Modale Filtre
    $('#modalFiltreStockPdt').on('hidden.bs.modal', function (e) {
        $('#modalFiltreStockPdtBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        $('.btnFiltreMoiCa').hide();
    });

    // Confirmation
    $('#modalConfirm').on('hidden.bs.modal', function (e) {
        $('#modalConfirmBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        $('#dataConfirmContexte').val('');
        $('#dataConfirmInput').val('0');carte-lot
    });

    // Opérations (transfert,...)
    $('#modalOperationsStockPdt').on('hidden.bs.modal', function (e) {
        $('#modalOperationsStockPdtTitle').html('<i class="fa fa-spin fa-spinner gris-9"></i>');
        $('#modalOperationsStockPdtBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        $('#modalOpStockPdtIdsCompos').val('');
        $('#modalOpStockPdtIdChoisi').val('');
        $('#modalOpStockPdtMode').val('');
        $('.btnValideModalOpStockPdt span').text('Valider');
        $('.btnValideModalOpStockPdt').hide();

        if ($('#stkAjaxVue #champ_clavier_des').length) {
            $('#stkAjaxVue #champ_clavier_des').attr('id', 'champ_clavier');
        }
        if ($('#modalOperationsStockPdtBody #champ_clavier').length) {
            $('#modalOperationsStockPdtBody #champ_clavier').attr('id', 'champ_clavier_lotr');
        }

    });



}); // FIN ready



// Fonction chargeant les étapes pour intégrer leur contenu
// ##############################################################
// ATTENTION
// Cette vue charges les étapes au sein d'un même conteneur ajax
// Elle n'as pas de ticket
// ##############################################################
function chargeEtape(numeroEtape, identifiant) {

    $('#stkAjaxVue').html('<div class="padding-50 text-center"><i class="fa fa-spin fa-spinner fa-5x gris-c"></i></div>');

    // Ajax qui charge le contenu de l'étape

    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_stk.php',
        'arguments': 'mode=chargeEtapeVue&etape=' + numeroEtape + '&id='+identifiant,
        'return_id': 'stkAjaxVue',
        'done': function () {
            var fctnom = "listenerEtape"+numeroEtape;
            var fn  = window[fctnom];
            fn(identifiant);
        } // FIN Callback
    }); // FIN ajax


} // FIN fonction chargeEtape


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Charge le Ticket
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function chargeTicket(etape, identifiant, filtres) {
    "use strict";

    if (filtres === undefined) { filtres = '' ;}

    // On récupère les totaux si on les as
    var total_nb_colis = $('#totalStockPdtsNbColis').length ? parseInt($('#totalStockPdtsNbColis').val()) : 0;
    var total_poids = $('#totalStockPdtsPoids').length ? parseFloat($('#totalStockPdtsPoids').val()) : 0;

    // Ajax qui charge le contenu du ticket
    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_stk.php',
        'arguments': 'mode=chargeTicket&etape='+etape+'&id=' + identifiant + '&total_nb_colis='+total_nb_colis+'&total_poids='+total_poids,
        'return_id': 'ticketContent',
        'done' : function() {

            if (filtres !== '') {
                filtres.forEach(function (item) {

                    $('#ticketContent button[data-filtre='+item+']').removeClass('btn-secondary').addClass('active btn-info');

                });
            }

            listenerTicket();
        }
    }); // FIN ajax

} // FIN fonction

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener du ticket
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerTicket() {
    "use strict";

    $('.btnShowHideFiltres').off("click.btnShowHideFiltres").on("click.btnShowHideFiltres", function(e) {
        e.preventDefault();
        $('#filtresTicket').toggle();
        $(this).find('i.fa').toggleClass('fa-angle-up fa-ellipsis-h');
            
        
    });

    // Sélection client (valider)
    $('.btnSelectCltsOk').off("click.btnSelectCltsOk").on("click.btnSelectCltsOk", function(e) {
        e.preventDefault();

        var ids_clts_array = [];
        var etape_suivante = 0;

        $('.carte-lot.choisi').each(function () {
            ids_clts_array.push(parseInt($(this).data('id-clt')));
            etape_suivante = parseInt($(this).data('etape-suivante'));
        });

        var ids_clts = ids_clts_array.join(',');

        if (ids_clts === '') { alert("Aucun client/dépot sélectionné ou identification échouée !"); return false; }
        if (etape_suivante === 0) { alert('ERREUR !\r\nIdentification de l\'étape suivante impossible...');return false; }

        chargeEtape(etape_suivante, ids_clts);

    }); // FIN sélection clients

    // Selection des palettes (étapes 10)
    $('.btnSelectionPalettes').off("click.btnSelectionPalettes'").on("click.btnSelectionPalettes", function(e) {

        if ($('#stkAjaxVue .selection').length === 0) { return false; }

        var ids_clients = '';
        var ids_palettes = '';

        // Boucle sur les cartes sélectionnées
        $('#stkAjaxVue .selection').each(function () {

            var id_palette = parseInt($(this).data('id-palette'));
            var id_client = parseInt($(this).data('id-clt'));
            if (isNaN(id_palette)) { id_palette = 0; }
            if (isNaN(id_client)) { id_client = 0; }
            ids_palettes+=id_palette+'|';
            ids_clients+=id_client+'|';

        }); // FIN boucle sur les cartes

        if (ids_clients === '') { alert('Identification des clients échouée !'); return false; }
        if (ids_palettes === '') { alert('Identification des palettes échouée !'); return false; }

        ids_palettes = ids_palettes.slice(0,-1);
        ids_clients = ids_clients.slice(0,-1);

        var filtres = 'clients:'+ids_clients+',palettes:'+ids_palettes;

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_stk.php',
            'arguments': 'mode=filtresListe&filtres=' + filtres,
            'return_id': 'stkAjaxVue',
            'done': function () {


                // On met à jour les totaux dans le ticket
                var nb_colis = $('#totalStockPdtsNbColis').val();
                var poids = $('#totalStockPdtsPoids').val();

                if (isNaN(poids) || isNaN(nb_colis)) {
                    poids = 0;
                    nb_colis = 0;
                }

                $('#ticketNbColis').text(nb_colis);
                $('#ticketPoids').text(number_format(poids,2, '.', ' '));

                listenerEtape1(1, false , ['client', 'palette']);

            }
        }); // FIN ajax

    }); // FIN selection palettes

    // Selection des produits (étapes 11)
    $('.btnSelectionProduits').off("click.btnSelectionPalettes'").on("click.btnSelectionPalettes", function(e) {

        if ($('#stkAjaxVue .selection').length === 0) { return false; }

        var id_client = 0;
        var ids_produits = '';

        // Boucle sur les cartes sélectionnées
        $('#stkAjaxVue .selection').each(function () {

            id_client = parseInt($(this).data('id-clt'));
            var id_produit = parseInt($(this).data('id-pdt'));
            if (isNaN(id_produit)) { id_produit = 0; }
            if (isNaN(id_client)) { id_client = 0; }
            ids_produits+=id_produit+'|';

        }); // FIN boucle sur les cartes

        if (id_client === 0) { alert('Identification du client échoué !'); return false; }
        if (ids_produits === '') { alert('Identification des produits échouée !'); return false; }

        ids_produits = ids_produits.slice(0,-1);

        var filtres = 'client:'+id_client+',produits:'+ids_produits;
        $('#memoireFiltreClient').val(id_client);
        $('#memoireFiltreProduit').val(ids_produits);
        $('#memoireFiltrePalette').val('');

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_stk.php',
            'arguments': 'mode=filtresListe&filtres=' + filtres,
            'return_id': 'stkAjaxVue',
            'done': function () {


                // On met à jour les totaux dans le ticket
                var nb_colis = $('#totalStockPdtsNbColis').val();
                var poids = $('#totalStockPdtsPoids').val();

                if (isNaN(poids) || isNaN(nb_colis)) {
                    poids = 0;
                    nb_colis = 0;
                }

                $('#ticketNbColis').text(nb_colis);
                $('#ticketPoids').text(number_format(poids,2, '.', ' '));

                listenerEtape1(1, false, ['client', 'produit']);

            }
        }); // FIN ajax

    }); // FIN selection produits

    // Retour étape 0 (bouton)
    $('.btnRetourEtape0').off("click.btnRetourEtape0'").on("click.btnRetourEtape0", function(e) {

        e.preventDefault();

        chargeEtape(0, 0);

    }); // FIN bouton retour étape 1

    // Retour étape 1 (bouton)
    $('.btnRetourEtape1').off("click.btnRetourEtape1'").on("click.btnRetourEtape1", function(e) {

        e.preventDefault();

        $('#clavier_virtuel').hide("slide", {direction: "down"});
        chargeEtape(1, 0);

    }); // FIN bouton retour étape 1


    // Transfert client/dépot
    $('.btnTransfertClient').off("click.btnTransfertClient'").on("click.btnTransfertClient", function(e) {

        e.preventDefault();

        // On récupère les IDs des compos concernés
        var ids_compos = $('#idsCompos').val();

        // Gestion des erreurs
        if (ids_compos === undefined || ids_compos === '') {
            alert('ERREUR !\r\nIdentification des compositions impossibles...\r\nCode erreur : S2VP2RJG');
            return falsse;
        }

        // On charge la modale de sélection du client...
        $('#modalOperationsStockPdtTitle').text('Transfert client/dépot');
        $('#modalOpStockPdtMode').val('transfertClient');
        $('#modalOpStockPdtIdsCompos').val(ids_compos);
        $('#modalOpStockPdtIdChoisi').val(0);
        $('.btnValideModalOpStockPdt span').text('Transférer');

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_stk.php',
            'arguments': 'mode=modalOperationsStockPdt&op=tr_clt',
            'return_id': 'modalOperationsStockPdtBody',
            'done' : function() {
                $('#modalOperationsStockPdt').modal('show');
                modalOperationsStockPdtListener();
            }
        }); // FIN ajax

    }); // FIN bouton transfert client/dépot

    // Transfert palette
    $('.btnTransfertPalette').off("click.btnTransfertPalette'").on("click.btnTransfertPalette", function(e) {

        e.preventDefault();

        // On récupère les IDs des compos concernés
        var ids_compos = $('#idsCompos').val();

        // Gestion des erreurs
        if (ids_compos === undefined || ids_compos === '') {
            alert('ERREUR !\r\nIdentification des compositions impossibles...\r\nCode erreur : IXCKIW9Z');
            return falsse;
        }

        // On charge la modale de sélection du client...
        $('#modalOperationsStockPdtTitle').text('Transfert palette');
        $('#modalOpStockPdtMode').val('transfertPalette');
        $('#modalOpStockPdtIdsCompos').val(ids_compos);
        $('#modalOpStockPdtIdChoisi').val(0);
        $('.btnValideModalOpStockPdt span').text('Transférer');

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_stk.php',
            'arguments': 'mode=modalOperationsStockPdt&op=tr_pal&ids_compos='+ids_compos,
            'return_id': 'modalOperationsStockPdtBody',
            'done' : function() {
                $('#modalOperationsStockPdt').modal('show');
                modalOperationsStockPdtListener();
            }
        }); // FIN ajax

    }); // FIN bouton transfert palette


    // Bouton supprimer de la palette
    $('.btnSupprPalette').off("click.btnSupprPalette'").on("click.btnSupprPalette", function(e) {

        e.preventDefault();

        // On récupère les IDs des compos concernés
        var ids_compos = $('#idsCompos').val();

        var htmConfirm = '<div class="alert alert-danger text-center"><strong class="text-24">ATTENTION !</strong><p>Vous allez supprimer un ou plusieurs produits de leurs palettes.<br>Cette action est irreversible.<br><p class="text-18">Continuer ?</p></div>';
        $('#modalConfirmBody').html(htmConfirm);
        $('#dataConfirmInput').val(ids_compos);
        $('#dataConfirmContexte').val('supprCompos');
        $('#modalConfirm').modal('show');


    }); // FIN bouton supprimer de la palette

    // Bouton lot de regroupement
    $('.btnLotRegroupement').off("click.btnLotRegroupement'").on("click.btnLotRegroupement", function(e) {

        e.preventDefault();

        // On récupère les IDs des compos concernés
        var ids_compos = $('#idsCompos').val();

        // Gestion des erreurs
        if (ids_compos === undefined || ids_compos === '') {
            alert('ERREUR !\r\nIdentification des compositions impossibles...\r\nCode erreur : IXCKIW9Z');
            return falsse;
        }

        // On charge la modale de sélection du client...
        $('#modalOperationsStockPdtTitle').text('Lot de regroupement');
        $('#modalOpStockPdtMode').val('lotsRegroupements');
        $('#modalOpStockPdtIdsCompos').val(ids_compos);
        $('#modalOpStockPdtIdChoisi').val(0);
        $('.btnValideModalOpStockPdt span').text('Affecter');


        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_stk.php',
            'arguments': 'mode=showLotsRegroupementsModal&ids_compos='+ids_compos,
            'return_id': 'modalOperationsStockPdtBody',
            'done' : function() {
                $('#modalOperationsStockPdt').modal('show');

                if ($('#stkAjaxVue #champ_clavier').length) {
                    $('#stkAjaxVue #champ_clavier').attr('id', 'champ_clavier_des');
                }
                if ($('#champ_clavier_lotr').length) {
                    $('#champ_clavier_lotr').attr('id', 'champ_clavier');
                }

                modalOperationsLotRegroupementListener();
            }
        }); // FIN ajax



    }); // FIN bouton lot de regroupement

    // Clic sur un filtre...
    $('.btn-filtre').off("click.btnfiltre'").on("click.btnfiltre", function(e) {

        e.preventDefault();

        // Peu importe sur lequel on clic, on va passer en paramètre à l'ajax tout les filtres cochés pour mise à jour de la liste, comme ça ça marche pour activer et désactiver à la fois.
        // Par contre pour les filtres nécéssitant une modale...
        // Si il n'a pas la classe active, on ouvre la modale avant toute chose, et c'est le listener de la modale qui va prendre la suite
        // Sinon, s'il a la classe active, on a le meme process


        $(this).toggleClass('active');

        // Si on a désactivé un filtre, on repasse l'id du choix à zéro
        if (!$(this).hasClass('active')) {
            $(this).find('input').val(0);
        }

        var objetDom = $(this);
        var modale = parseInt(objetDom.data('modale'));

        // Si le filtre ne nécéssite pas de modale ou qu'on désactive, on applique la mise à jour des filtres...
        if (modale === 0 || !$(this).hasClass('active')) {

            goFiltre();

            // On met en forme le bouton
            if (objetDom.hasClass('active')) {
                objetDom.addClass('btn-info');
                objetDom.removeClass('btn-secondary');
            } else {
                objetDom.removeClass('btn-info');
                objetDom.addClass('btn-secondary');
            }

            return false;

        // Ici on tente d'activer un filtre relevant d'une modale, on affiche donc la modale du filtre qui prendra le relais via son listener
        } else {

            $(this).removeClass('active');

            var filtre = $(this).data('filtre');

            var id_lots = $('#idsLots').val();

            $.fn.ajax({
                'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
                'script_execute': 'fct_vue_stk.php',
                'arguments': 'mode=modalFiltreStockPdt&filtre=' + filtre + '&id_lots='+id_lots,
                'return_id': 'modalFiltreStockPdtBody',
                'callBack': function (retour) {

                    // On affiche la modale
                    $('#modalFiltreStockPdt').modal('show');

                    retour+=''; // Indispensable pour travail sur le retour

                    // Si on a une erreur, on ne va surtout pas plus loin !
                    if (retour.substring(0,3) === '<!-') {
                        return false;
                    }




                    // On appelle le listener
                    modalFiltreStockPdtListener(filtre);

                }
            }); // FIN ajax

        } // FIN test filtre modale / activation

    }); // FIN clic filtre

    // Clic sur bouton correction
    $('.btnEditSelection').off("click.btnEditSelection'").on("click.btnEditSelection", function(e) {

        e.preventDefault();

        // On récupère les ID_compo cochées...
        var ids = '';
        $('.stksfr .icheck:checked').each(function () {
            var id_compo = $(this).parents('tr').data('id-compo');
            if (id_compo !== undefined) {
                ids+=id_compo+',';
            }
        });
        ids = ids.slice(0,-1);

        $('#ticketContent').html('<div class="text-center padding-top-50"><i class="fa fa-spin fa-spinner fa-3x"></i></div>');

        var filtres = '';
        $('.btn-filtre.active').each(function() {
            filtres+= $(this).data('filtre')+':'+$(this).find('input').val()+',';
        });
        if (filtres !== '') {
            filtres = filtres.slice(0,-1);
        }
      /*  $('#memoireFiltres').val(filtres);*/

        chargeEtape(2,ids);

    }); // FIN clic sur bouton correction

    // Bouton Bon de transfert
    $('.btnBonTransfert').off("click.btnBonTransfert'").on("click.btnBonTransfert", function(e) {

        e.preventDefault();

        // On récupère les IDs des compos concernés
        var ids_compos = $('#idsCompos').val();

        // Gestion des erreurs
        if (ids_compos === undefined || ids_compos === '') {
            alert('ERREUR !\r\nIdentification des compositions impossibles...\r\nCode erreur : IXCKIW9Z');
            return falsse;
        }

        var ln = loadingBtn($(this));

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_bl.php',
            'arguments': 'mode=genereBonTransfertFromCompos&ids_compos='+ids_compos,
            'callBack' : function(retour) {
                retour+= '';
                removeLoadingBtn(ln);

                var retourArray = retour.split('|');
                var id_bl = retourArray[0];
                var url = retourArray[1] !== undefined ? retourArray[1] : '';


                if (url === '') { alert('Echec de création du lien !'); return false;}

                // On redirige
                $('#lienPdf').attr('href', url);
                $('#lienPdf')[0].click();

                var id_bt = 3;

                // On redirige vers l'expédition
                $(location).attr('href', '../expedition/bt-'+id_bl);

            } // FIN callBack
        }); // FIN ajax


    }); // FIn bon de transfert


    // Bouton Bon de livraion (GesCom)
    $('.btnBonLivraison').off("click.btnBonLivraison'").on("click.btnBonLivraison", function(e) {

        e.preventDefault();

        // On récupère les IDs des compos concernés
        var ids_compos = $('#idsCompos').val();

        // Gestion des erreurs
        if (ids_compos === undefined || ids_compos === '') {
            alert('ERREUR !\r\nIdentification des compositions impossibles...\r\nCode erreur : IXCKIW9Z');
            return falsse;
        }
        var objDom = $(this);

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_bl.php',
            'arguments': 'mode=GetBlFrontUrl&ids_compos='+ids_compos,
            'callBack' : function(url) {
                url+= '';

                if (url === '') { alert('Echec de création du lien !'); return false;}

                // Si on a plusieurs clients dans ces compos
                else if (url === '0') {

                    // Si on a déjà une palette pour ce produit (mode upd) ou pas...
                    var textInfo = "Création du BL impossible !<br>Clients différents...";
                    $('#modalInfoBody').html("<div class='alert alert-danger'><i class='fa fa-exclamation-circle fa-lg mb-2'></i><p>"+textInfo+"</p></div>");
                    $('#modalInfo').modal('show');

                    return false;
                }

                // On redirige
                objDom.html('<i class="fa fa-spin fa-spinner"></i>');
                $(location).attr('href',url);

            } // FIN callBack
        }); // FIN ajax

    }); // FIN bouton bon de livraison

} // FIN listener ticket

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 0 (Sélection client au début)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape0(identifiant, skip_ticket) {
    "use strict";
    chargeTicket(0, identifiant);

    $('.carte-lot').off("click.cartelot'").on("click.cartelot", function(e) {
        e.preventDefault();

        // Si on a sélectionné un dépot (étape 11, on ne permet pas la selection multi)
        var es = parseInt($(this).data('etape-suivante'));
        if (es === 11) {
            $('.carte-lot.choisi.carte-clt').removeClass('choisi');
        } else {
            $('.carte-lot.choisi.carte-depot').removeClass('choisi');
        }


        $(this).toggleClass('choisi');
        if ($('.carte-lot.choisi').length > 0) {
            $('.btnSelectCltsOk').show();
        } else {
            $('.btnSelectCltsOk').hide();
        }
    });

}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 10 (Client mixte : sélection palettes)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape10(identifiant, skip_ticket) {
    "use strict";
    chargeTicket(10, identifiant);

    $('.carte-lot').off("click.cartelot'").on("click.cartelot", function(e) {


        $(this).toggleClass('selection bg-secondary bg-info');
        if ($('#stkAjaxVue .selection').length > 0) {
            $('.btnSelectionPalettes').removeClass('disabled');
            $('.btnSelectionPalettes').prop('disabled', false);
        } else if (!$('.btnSelectionPalettes').hasClass('disabled')) {
            $('.btnSelectionPalettes').addClass('disabled');
            $('.btnSelectionPalettes').attr('disabled', 'disabled');
        }
    });
} // FIN listener

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 11 (Stock plateforme : sélection produits)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape11(identifiant, skip_ticket) {
    "use strict";
    chargeTicket(11, identifiant);

    $('.carte-lot').off("click.cartelot'").on("click.cartelot", function(e) {
        var id_clt = parseInt($(this).data('id-clt'));
        var id_pdt = parseInt($(this).data('id-pdt'));
        if (isNaN(id_clt)) { id_clt = 0; }
        if (isNaN(id_pdt)) { id_pdt = 0; }
        if (id_clt === 0) { alert('Identification du client échoué !'); return false; }
        if (id_pdt === 0) { alert('Identification du produit échoué !'); return false; }


        $(this).toggleClass('selection bg-secondary bg-info');
        if ($('#stkAjaxVue .selection').length > 0) {
            $('.btnSelectionProduits').removeClass('disabled');
            $('.btnSelectionProduits').prop('disabled', false);
        } else if (!$('.btnSelectionProduits').hasClass('disabled')) {
            $('.btnSelectionProduits').addClass('disabled');
            $('.btnSelectionProduits').attr('disabled', 'disabled');
        }
    });
} // FIN listener



// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 1
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape1(identifiant, skip_ticket, filtres) {
    "use strict";

    if (filtres === undefined) { filtres = '' ;}

    // On charge le ticket au besoin
    if (skip_ticket !== true) {
        chargeTicket(1, identifiant, filtres);
    }


    // On intialise les checkbox
    $('.icheck').iCheck({checkboxClass: 'icheckbox_square-blue'});

    // On permet de cliquer sur la TD toute entière pour checker/dechecker...
    $('.icheck-td').off("click.ichecktd'").on("click.ichecktd", function(e) {
       $(this).find('.icheck').iCheck('toggle');
    });

    $('.check-tout').parent().off("click.checktout'").on("click.checktout", function(e) {
        $(this).find('.icheck').iCheck('toggle');
    });

    // Listerner de checkbox pour adapter le ticket...
    $('.check-tout').on('ifChanged', function(event) {
        var etat = $(this).is(':checked');
        var statut = etat ? 'check': 'uncheck';
        $('.stksfr .icheck').iCheck(statut);
    });


        // Listerner de checkbox pour adapter le ticket...
    $('.icheck').on('ifChanged', function(event){

        if ($(this).parents('td').hasClass('check-sous-total')) {

            checkSousTotal($(this));
            return false;
        }

        else if ($(this).parents('td').hasClass('check-sous-sous-total')) {

            checkSousSousTotal($(this));
            return false;
        }

        ticketNbSelectionnes();

    }); // FIN listner checkbox

    // Fonction déportée pour check du sous-total
    function checkSousTotal(objetDom) {

        var id_pdt = objetDom.parents('.check-sous-total').data('id-pdt');
        var etat = objetDom.is(':checked');
        var statut = etat ? 'check': 'uncheck';

        $('.check-pdt-'+id_pdt).find('.icheck').iCheck(statut);
        $('.check-sous-sous-total[data-id-pdt= '+id_pdt+']').find('.icheck').iCheck(statut);


    } // FIN fonction checkSousTotam

    // Fonction déportée pour check du sous-sous-total
    function checkSousSousTotal(objetDom) {

        var id_palette = objetDom.parents('.check-sous-sous-total').data('id-palette');
        var id_pdt = objetDom.parents('.check-sous-sous-total').data('id-pdt');
        var etat = objetDom.is(':checked');
        var statut = etat ? 'check': 'uncheck';

        $('.check-palette-'+id_palette+'.check-pdt-'+id_pdt ).find('.icheck').iCheck(statut);


    } // FIN fonction checkSousTotam

    // Fonction déportée pour adapter le ticker au nb de lignes sélectionnées
    function ticketNbSelectionnes() {

        // On compte le nb de checkbox cochées
        var check_sstotaux  = $('.check-sous-total').find('.icheck:checked').length;
        var check_sssstotaux  = $('.check-sous-sous-total').find('.icheck:checked').length;
        var nbCheckLignes   = $('.stksfr .icheck:checked').length - check_sstotaux - check_sssstotaux;
        var texte           = nbCheckLignes > 0 ? nbCheckLignes : 'Aucun';
        var pluriel         = nbCheckLignes > 1 ? 's' : '';
        texte+= ' élément'+pluriel+' sélectionné'+pluriel;
        texte+= nbCheckLignes === 0 ? '…' : ' :';
        $('.nb-selectionnes').text(texte);

        // On affiche le bouton Correction si au moins un élément est sélectionné.
        if (nbCheckLignes > 0) {
            $('.conteneurBtnCorrection').show();

            // On affiche étalement les totaux
            // #ticketNbColisSelection
            // #ticketPoidsSelection
            // total-selectionnes hid
            var total_colis = 0;
            var total_poids = 0;
            $('.stksfr .icheck:checked').each(function () {
                    var colis = parseInt($(this).parents('tr').find('.td-nb-colis').text());
                    if (isNaN(colis)) { colis = 0; }
                    total_colis+=colis;
                    var poids = parseFloat($(this).parents('tr').find('.td-poids').text().replace(/\s/g, ''));
                    if (isNaN(poids)) { poids = 0; }
                    total_poids+=poids;
            });
            $('.total-selectionnes').removeClass('hid');
            $('#ticketNbColisSelection').text(total_colis);
            $('#ticketPoidsSelection').text(total_poids.toFixed(3));


        }
        else { $('.conteneurBtnCorrection').hide();
            if (!$('.total-selectionnes').hasClass('hid')) {
                $('.total-selectionnes').addClass('hid');
            }
            $('#ticketNbColisSelection').text('');
            $('#ticketPoidsSelection').text('');
        }


    } // FIN fonction nb de sélectionnés


} // FIN listener etape




// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de la modale de filtre
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function modalFiltreStockPdtListener(filtre) {
    "use strict";

    // Gestion des erreurs
    if (filtre === undefined) { alert('ERREUR !\r\nIdentification du filtre impossible...\r\nCode erreur : N73DVYNE'); return false; }

    var objetDom = $('.btn-filtre[data-filtre='+filtre+']');
    if (objetDom === undefined) { alert('ERREUR !\r\nInstanciation du filtre impossible...\r\nCode erreur : F2L8D5SB'); return false; }
    if (objetDom.hasClass('active')) {  alert('ERREUR !\r\nFiltre déjà activé...\r\nCode erreur : 2J61UF1N'); return false; }

    $('#filtre_type').val(filtre);

    // Si on clic sur un choix...
    $(document).on("click", ".btnChoix", function(){

        // On récupère l'ID
        var id = $(this).data('id');

        // On marque le bouton comme sélectionné
        $('.btnChoix.btn-info').removeClass('btn-info').addClass('btn-secondary');
        $(this).removeClass('btn-secondary').addClass('btn-info');

        // On stocke d'ID dans le DOM
        $('#filtre_id').val(id);

        // on affiche le bouton "Filtrer"
        $('.btnFiltreMoiCa').show();

    }); // FIN clic choix


    // Si on utilise le clavier numérique (produit...)
    $(document).on("click", ".clavier-produit .btn", function(e) {

        e.stopImmediatePropagation();

        var touche = $(this).data('valeur');
        var codePdt = $('.codeProduit').text().toString().trim();
        if (codePdt.length > 10 && touche !== 'C') { return false; }
        var htmlListePdtVide = '<i class="fa fa-caret-left fa-lg mr-2 gris-c padding-top-20"></i><span class="text-18 gris-5 padding-top-15">Recherhe par code article...</span>';

        if (touche === 'I') {

            $('#referencesProduits').show();
            $('.btnFermerReferencesProduits').show();
            $('#selectionProduits').hide();


            return false;
        }

        if (touche === 'C') {
            $('.codeProduit').html('&nbsp;');
            $('#listeProduitsCode').html(htmlListePdtVide);
            $('#filtre_id').val('');
            $('.btnFiltreMoiCa').hide();
            return false;
        }

        if (codePdt === '') { $('.codeProduit').text(touche); searchProduitsCode(touche); return false; }

        var code = codePdt + touche.toString();
        $('.codeProduit').text(code);
        searchProduitsCode(code);
        return false;

    }); // FIN clavier numérique pour code produit

    $(document).on("click", ".btnFermerReferencesProduits", function(e) {

        e.stopImmediatePropagation();
        $('#referencesProduits').hide();
        $('.btnFermerReferencesProduits').hide();
        $('#selectionProduits').show();
        return false;
    });

    // Si on utilise le clavier numérique (numéro de traitement...)
    $(document).on("click", ".clavier-froid .btn", function(e){

        e.stopImmediatePropagation();

        // On récupère la valeur de la touche
        var touche = $(this).data('valeur');
        var codeFroid = $('.codeTraitementSaisi').text();
        if (codeFroid.length < 6) {
            if (touche === 'C') {
                $('.codeTraitementSaisi').text('');
            } else if (touche === 'N') {
                $('.codeTraitementSaisi').text('N');
            } else if (codeFroid.length === 0) {
                $('.codeTraitementSaisi').text(touche);
            } else {
                if (codeFroid === 'N') { codeFroid = ''; }
                $('.codeTraitementSaisi').text(codeFroid.toString() + touche.toString());
            }
        } else if (touche === 'C') {
            $('.codeTraitementSaisi').text('');
        }
        var newCodeFroid = $('.codeTraitementSaisi').text();
        if (newCodeFroid.length > 0) {
            $('#filtre_id').val(newCodeFroid);
            $('.btnFiltreMoiCa').show();
        } else {
            $('#filtre_id').val(0);
            $('.btnFiltreMoiCa').hide();
        }

    }); // FIN clavier pour code froid


        // Si on clic sur le bouton FILTRER
    $('.btnFiltreMoiCa').off("click.btnFiltreMoiCa'").on("click.btnFiltreMoiCa", function(e) {

        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        filtre = $('#filtre_type').val();
        objetDom = $('.btn-filtre[data-filtre='+filtre+']');
        var filtre_id = filtre === 'lot' && ($('#filtre_id').val().substring(0,1) === 'R' || $('#filtre_id').val().substring(0,1) === 'N') ? $('#filtre_id').val() : parseInt($('#filtre_id').val());
        if (filtre === 'froid' && $('#filtre_id').val() === 'N') { filtre_id = $('#filtre_id').val(); }
        objetDom.find('input').val(filtre_id);

        // L'objetDOM correspond au bouton du filtre à droite, on le met à jour pour gérer le rechargement
        objetDom.addClass('active btn-info');
        objetDom.removeClass('btn-secondary');


        goFiltre();

        // On ferme la modale
        $('#modalFiltreStockPdt').modal('hide');

        return false;
    }); // FIN clic sur bouton filtrer



} // FIN listener modale filtre

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Recherche d'un code produit (ajax)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function searchProduitsCode(code) {
    "use strict";

    $('#filtre_id').val('');
    $('.btnFiltreMoiCa').hide();

    // On intéroge en base tous les produis correspondant à ce code (%LIKE%)
    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_stk.php',
        'arguments': 'mode=searchProduitsCode&code=' + code,
        'return_id': 'listeProduitsCode',
        'done': function () {

            listenerProduitsListeByCode();

        }
    }); // FIN ajax


} // FIN fonction


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de la liste des produits par code
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerProduitsListeByCode() {
    "use strict";


    // Clic sur un produit
    $(document).on("click", ".btnChoixProduit", function(e) {

        e.stopImmediatePropagation();
        // On récupère l'ID
        var id = parseInt($(this).data('id'));

        // On marque le bouton comme sélectionné
        $('.btnChoixProduit.btn-info').removeClass('btn-info').addClass('btn-secondary');
        $(this).removeClass('btn-secondary').addClass('btn-info');

        // On stocke d'ID dans le DOM
        $('#filtre_id').val(id);

        // on affiche le bouton "Filtrer"
        $('.btnFiltreMoiCa').show();

    }); // FIN clic sur un produit




} // FIN listener

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 2
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape2(ids_compo) {
    "use strict";

    chargeTicket(2, ids_compo);

   clavierVirtuel();



    $('#clavier_virtuel').jkeyboard({
        input: $('#champ_clavier')
    });
    // Clavier pour désignation personnalisée



    // Clic sur le champ "Nb de colis" -> le rend actif
    $('.inputNbColis').focus(function () {
        $('#champActif').val('nb_colis');
    }); // FIN clic champ

    // Clic sur le champ "Poids" -> le rend actif
    $('.inputPoidsPdt').focus(function () {
        $('#champActif').val('poids_pdt');
    }); // FIN clic champ


    // Si on utilise le clavier numérique (numéro de traitement...)
    $(document).on("click", ".clavier .btn", function(e) {

        e.stopImmediatePropagation();

        var champ  = $('#champActif').val();
        var touche      = $(this).data('valeur');

        if (touche === 'C') {

            // On reset le champ en cours
            $('input[name='+champ+']').val('');

            // Si le champ en cours est le nb de colis, on remet à 0 le poids
            if (champ === 'nb_colis') { $('input[name=poids_pdt]').val(''); }

            return false;
        } // FIN touche C

        // Touche valider...
        if (touche === 'V') {

            // On vérifie si on a changé les valeurs... (sinon pas la peine de confirmer ni d'enregistrer)
            var old_poids = parseFloat($('.inputPoidsPdt').data('old'));
            var new_poids = parseFloat($('.inputPoidsPdt').val());
            var old_nb_colis = parseFloat($('.inputNbColis').data('old'));
            var new_nb_colis = parseFloat($('.inputNbColis').val());
            var old_designation = $('#champ_clavier').data('old');
            var designation = $('#champ_clavier').val().trim();

            // Si pas de modif, on retourne à l'étape 1
            if (old_poids === new_poids && old_nb_colis === new_nb_colis && designation === old_designation) {
                chargeEtape(1, 0);
                return false;
            }

            // Si le poids ou le nb de colis est à zéro, on ne fait rien...
            if (isNaN(new_poids) || new_poids < 0.1 || isNaN(new_nb_colis) || new_nb_colis < 1) { return false;}

            // Sinon, ici on a une modif... on demande confirmation...
            var htmConfirm = '<div class="text-center text-20 padding-20">Enregistrer les modifications ?</div>';
            $('#modalConfirmBody').html(htmConfirm);
            $('#dataConfirmInput').val(parseInt($(this).data('id-compo')));
            $('#dataConfirmContexte').val('updateQteCompo');
            $('#modalConfirm').modal('show');


            return false;
        } // FIN touche V

        // On complète la valeur en concaténant...

        if (!$('input[name='+champ+']').length) { return false; }
        var champVal    = $('input[name='+champ+']').val().trim();
        var poidsDefaut = $('.inputPoidsPdt').data('poids-defaut');





        // Si on est sur le champ du poids
        if (champ === 'poids_pdt') {

            var poidsProduit = $('.inputPoidsPdt').val().trim();

            // Si le champ était vide, il prend la valeur de la touche
            if (poidsProduit.length === 0) {
                $('.inputPoidsPdt').val(touche);

                // Sinon, et si on saisi un point, on vérifie qu'il n'y en a pas déjà un...
            } else if (touche === '.' && poidsProduit.indexOf('.') !== -1) {
                return false;
            } else {
                $('.inputPoidsPdt').val(poidsProduit + touche);
            } // FIN test point [.] ou chanmp vide

            // Sinon, on est sur le champ nb de colis
        } else {

            var nbcolis = $('.inputNbColis').val().trim();

            // Pas de décimales dans le nombre de colis
            if (touche === '.') { return false; }

            // Si le champ était vide, il prend la valeur de la touche
            if (nbcolis.length === 0) {
                $('.inputNbColis').val(touche);

                // Sinon on concatène la valeur...
            } else {
                $('.inputNbColis').val(nbcolis + touche);
            }

            // On formate le poids final proprement
            var poidsFinal = poidsDefaut * parseInt( $('.inputNbColis').val());
            $('.inputPoidsPdt').val(poidsFinal);

        } // FIN test sur le champ (poids/nb_colis)

    }); // FIN touche clavier

    // ----------------------------------------
    // (Listener Etape 2)
    // Confirmation (modale) - Générique
    // ----------------------------------------
    $('.btnModalConfirmOk').off("click.btnModalConfirmOk").on("click.btnModalConfirmOk", function(e) {

        e.preventDefault();

        // On passe un "contexte" pour définir le périmètre opérationnel de la confirmation
        var data_confirm_contexte = $('#dataConfirmContexte').val();

        // ----------------------------------------
        // (Listener Etape 2 -> Modal confirm OK)
        // Mise à jour quantitié produit unique
        // ----------------------------------------
        if (data_confirm_contexte === 'updateQteCompo') {

            // Récupération des variables
            var id_compo = parseInt($('#dataConfirmInput').val());
            var nb_colis = parseInt($('.inputNbColis').val());
            var poids    = parseFloat($('.inputPoidsPdt').val());
            var designation = $('#champ_clavier').val().trim();
            designation = designation.replace('+','[PLUS]');
            designation = designation.replace('&','[ET]');
            designation = designation.replace('=','[EGAL]');
            designation = designation.replace('"','[DQ]');
            designation = designation.replace('\'','[SQ]');

            // Gestion des erreurs
            if (isNaN(id_compo) || id_compo === undefined || id_compo === 0) { alert('ERREUR !\r\nIdentification de la composition impossible...\r\nCode erreur : MAK7KWQU'); return false; }
            if (isNaN(nb_colis) || nb_colis === undefined || nb_colis === 0 || isNaN(poids) || poids === undefined || poids === 0) { alert('ERREUR !\r\nRécupération des valeurs impossibles...\r\nCode erreur : V4ZTRFTT'); return false; }

            // OK, on peut proceder...
            $.fn.ajax({
                'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
                'script_execute': 'fct_vue_stk.php',
                'arguments': 'mode=updateQteCompo&id_compo=' + id_compo + '&nb_colis=' + nb_colis + '&poids=' + poids+'&designation='+designation,
                'callBack': function (retour) {

                    retour+= '';
                    if (parseInt(retour) !== 1) {
                        alert('ERREUR !\r\nEnregistrement impossible...\r\nCode erreur : ' + retour);
                        return false;
                    }

                    // OK, tout s'est bien passé, on ferme la modale et on retourne à l'étape 1
                    $('#modalConfirm').modal('hide');
                    chargeEtape(1, 0);

                }
            }); // FIN ajax

        } // FIN contexte "updateQteCompo"

        // ----------------------------------------
        // (Listener Etape 2 -> Modal confirm OK)
        // Supprimer produit(s) de la palette
        // ----------------------------------------
        if (data_confirm_contexte === 'supprCompos') {

            // Récupération des variables
            var ids_compos = $('#dataConfirmInput').val();

            // Gestion des erreurs
            if (ids_compos === undefined || ids_compos === '') { alert('ERREUR !\r\nIdentification des compositions impossible...\r\nCode erreur : 0RH325KZ'); return false; }

            // On supprime la ou les compos...
            $.fn.ajax({
                'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
                'script_execute': 'fct_vue_stk.php',
                'arguments': 'mode=supprimeCompos&ids_compos=' + ids_compos,
                'callBack': function (retour) {

                    retour+= '';
                    if (parseInt(retour) !== 1) {
                        alert('ERREUR !\r\nSuppression impossible...\r\nCode erreur : ' + retour);
                        return false;
                    }

                    // OK, tout s'est bien passé, on ferme la modale et on retourne à l'étape 1
                    $('#modalConfirm').modal('hide');
                    chargeEtape(1, 0);

                }
            }); // FIN ajax

        } // FIN contexte "updateQteCompo"


        }); // FIN confirmation modale

} // FIN listener étape 2


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Fonction déportée mettant à jour l'affichage selon les filtres sélectionnés
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function goFiltre() {
    "use strict";
    var filtres = '';
    $('.btn-filtre.active').each(function() {
        filtres+= $(this).data('filtre')+':'+$(this).find('input').val()+',';
    });
    if (filtres !== '') {
        filtres = filtres.slice(0,-1);
    }

/*    // On tente de récupérer une mise en mémoire des filtres
    if (filtres === '' && $('#memoireFiltres').val() !== '') {
        filtres = $('#memoireFiltres').val();

    }*/


    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_stk.php',
        'arguments': 'mode=filtresListe&filtres=' + filtres,
        'return_id': 'stkAjaxVue',
        'done': function () {


            // On met à jour les totaux dans le ticket
            var nb_colis = $('#totalStockPdtsNbColis').val();
            var poids = $('#totalStockPdtsPoids').val();

            if (isNaN(poids) || isNaN(nb_colis)) {
                poids = 0;
                nb_colis = 0;
            }

            $('#ticketNbColis').text(nb_colis);
            $('#ticketPoids').text(number_format(poids,2, '.', ' '));


            listenerEtape1(1, true);

        }
    }); // FIN ajax



} // FIN fonction

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de la modale opération (lots de regroupements)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function modalOperationsLotRegroupementListener() {

    $('#clavier_virtuel').jkeyboard({
        input: $('#champ_clavier')
    });

    $('#champ_clavier').focus(function() {
        $('#clavier_virtuel').show("slide", {direction: "down"});
    });

    $('.btn').click(function() {
        $('#clavier_virtuel').hide("slide" ,{ direction: "down"  });
    });

    // Nouveau lot de regroupement...
    $(document).on("click", ".btnAddLotRegroupement", function(e) {

        $('.listeLotsRegroupement').hide();
        $('.formNewLotRegroupement').show();
        $('#champ_clavier').focus();

    }); // FIN nouveau

    // Supprimer l'association au lot de regroupement
    $(document).on("click", ".btnSupprAssociationComposLotR", function(e) {

        var ids_compos = $('#modalOpStockPdtIdsCompos').val();
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_lots.php',
            'arguments': 'mode=supprAssociationComposLotR&&ids_compos='+ids_compos,
            'done': function () {
                $('#modalOperationsStockPdt').modal('hide');
                chargeEtape(1,0);
            } // FIN callback
        }); // FIN ajax


    }); // FIN supprimer

    // Sélection d'un lot de regroupement
    $(document).on("click", ".btnChoix", function(e) {

        e.stopImmediatePropagation();

        // On récupère l'ID
        var id = parseInt($(this).data('id'));

        // On marque le bouton comme sélectionné
        $('.btnChoix.btn-info').removeClass('btn-info').addClass('btn-secondary');
        $(this).removeClass('btn-secondary').addClass('btn-info');

        // On stocke d'ID dans le DOM
        $('#modalOpStockPdtIdChoisi').val(id);

        // on affiche le bouton "Filtrer"
        $('.btnValideModalOpStockPdt').show();

    }); // FIN clic sur un choix


    // Bouton Affecter
    $(document).on("click", ".btnValideModalOpStockPdt", function(e) {

        var id_lot_r = parseInt($('#modalOpStockPdtIdChoisi').val());
        var ids_compos = $('#modalOpStockPdtIdsCompos').val();

        if (isNaN(id_lot_r) || id_lot_r === undefined || id_lot_r === 0) {
            alert('ERREUR !\r\nIdentification du lot de regroupement impossible...\r\nCode erreur : WDL718L2');
            return false;
        }
        if (ids_compos === undefined || ids_compos === '') {
            alert('ERREUR !\r\nIdentification des compositions impossible...\r\nCode erreur : 7KNEE96C');
            return false;
        }

        addComposLotRegroupement(ids_compos, id_lot_r);
        return false;

    }); // FIN bouton affecter


    // Nouveau lot de regroupement...
    $('.btnSaveNewLotR').off("click.btnSaveNewLotR'").on("click.btnSaveNewLotR", function(e) {

        e.preventDefault();
        e.stopImmediatePropagation();

        var numlot = $('#champ_clavier').val();
        if (numlot === '') { return false; }

        $('#clavier_virtuel').hide("slide" ,{ direction: "down"  });

        // On crée le nouveau lot
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_lots.php',
            'arguments': 'mode=addNewLotRegroupement&numlot=' + numlot,
            'callBack': function (retour) {
                retour+= '';

                // Le retour devrait être l'ID du nouveau lot de regroupement
                if (parseInt(retour) === 0 || isNaN(retour)) {
                   alert('ERREUR\r\nEchec lors de la création du lot de regroupement !\r\nCode erreur : 6E5GJC81');
                   return false;
                }

                // Si on a bien un ID créé, on peut l'associer à la compo...
                var ids_compos = $('#idsCompos').val();
                if (ids_compos === undefined || ids_compos === '') {
                   alert('ERREUR\r\nIdentification des compositions impossible !\r\nCode erreur : CV7I4VRC');
                   return false;
                }
                $('#modalOperationsStockPdtBody').html('<p class="text-center padding-20"><i class="fa fa-spin fa-spinner fa-2x gris-c"></i></p>');
                addComposLotRegroupement(ids_compos, retour);

            }
        }); // FIN ajax


    }); // FIN créer nouveau lot


} // FIN Listener

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de la modale opération (transfert client, palette...)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function modalOperationsStockPdtListener() {
    "use strict";


    // Clic sur le champ "Nb de colis" -> le rend actif
    $('.inputPoidsPdtTransfert').focus(function () {
        $('#champActifTransfertPalette').val('inputPoidsPdtTransfert');
    }); // FIN clic champ

    // Clic sur le champ "Poids" -> le rend actif
    $('.inputNbColisTransfert').focus(function () {
        $('#champActifTransfertPalette').val('inputNbColisTransfert');
    }); // FIN clic champ

    // Si on clic sur un choix...
    $(document).on("click", ".btnChoix", function(e) {

        e.stopImmediatePropagation();

        // On récupère l'ID
        var id = parseInt($(this).data('id'));

        // On marque le bouton comme sélectionné
        $('.btnChoix.btn-info').removeClass('btn-info').addClass('btn-secondary');
        $(this).removeClass('btn-secondary').addClass('btn-info');

        // On stocke d'ID dans le DOM
        $('#modalOpStockPdtIdChoisi').val(id);

        // on affiche le bouton "Filtrer"
        $('.btnValideModalOpStockPdt').show();

    }); // FIN clic sur un choix


    // Si on valide le choix
    $(document).on("click", ".btnValideModalOpStockPdt", function(e) {

        e.stopImmediatePropagation();

        // Si on doit vérifier le poids...
        if ($('.inputPoidsPdtTransfert').length) {

            // On bloque si il est vide ou supérieur au poids maxi
            var poids       = parseFloat($('.inputPoidsPdtTransfert').val());
            var poids_max   = parseFloat($('.inputPoidsPdtTransfert').data('old'));
            if (isNaN(poids) || poids === '' || poids === undefined || poids <= 0) { return false; }

            if (poids > poids_max) {

                $('#modalInfoBody').html('<div class="alert alert-danger"><i class="fa fa-2x fa-exclamation-circle mb-1"></i><p>Le poids saisi est supérieur au maximum transférable (' + poids_max + ' kg)...<br>Veuillez corriger.</p></div>');
                $('#modalInfo').modal('show');

                return false;

            } //  FIN poids dépassé !

        } // FIN doit-on tester le poids ici ?


        // On identifie les variables
        var id_choix    = parseInt($('#modalOpStockPdtIdChoisi').val());
        var ids_compos  = $('#modalOpStockPdtIdsCompos').val();
        var mode        = $('#modalOpStockPdtMode').val();

        // Si on transmet un poids à transférer (palette)
        var poids_transfert = poids !== undefined ? poids : 0;


        // Gestion des erreurs
        if (isNaN(id_choix) || id_choix === undefined || id_choix === 0) {  alert('ERREUR !\r\nIdentification de la sélection impossible...\r\nCode erreur : QWQG7YRE'); return false; }
        if (ids_compos === undefined || ids_compos === '') {                alert('ERREUR !\r\nIdentification des compositions impossible...\r\nCode erreur : O17QNIAL'); return false; }
        if (mode === undefined || mode === '') {                            alert('ERREUR !\r\nIdentification du mode impossible...\r\nCode erreur : TX1YVWU9'); return false; }

        // OK, on peut faire l'appel ajax pour la modif...
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_stk.php',
            'arguments': 'mode='+mode+'&ids_compos=' + ids_compos+'&id_choix='+id_choix+'&poids='+poids_transfert,
            'callBack': function (retour) {

                retour+= '';

                // Gestion des erreurs en retour
                if (parseInt(retour) !== 1) {
                    alert('ERREUR !\r\nEchec lors du traitement de la mise à jour...\r\nCode erreur : '+retour);
                    return false;
                }

                // Ok, on ferme la modale et on retourne à l'étape 1
                $('#modalOperationsStockPdt').modal('hide');
                chargeEtape(1,0);
                return false;

            }
        }); // FIN ajax

    }); // FIN clic valider


    // Si on utilise le clavier numérique (poids transfert de palette)
    $('.clavier-transfert .btn').click(function(e) {

        e.stopImmediatePropagation();

        var champClass = $('#champActifTransfertPalette').val();



        // On récupère la valeur de la touche
        var touche      = $(this).data('valeur');
        var poidsVoulu  = $('.inputPoidsPdtTransfert').val();
        var valeur      = $('.'+champClass).val();



        if (touche === 'C') {
            $('.'+champClass).val('');
        } else if (touche === 'T') {
            $('.inputPoidsPdtTransfert').val($('.inputPoidsPdtTransfert').data('old'));
            $('.inputNbColisTransfert').val($('.inputNbColisTransfert').data('old'));
            return false;
        } else if (valeur.length === 0 || parseFloat(valeur) === parseFloat($('.'+champClass).data('old'))) {
            $('.'+champClass).val(touche);
        }  else if (touche === '.' && ((champClass === 'inputPoidsPdtTransfert' && poidsVoulu.indexOf('.') !== -1) || champClass === 'inputNbColisTransfert')) {
            return false;
        } else {
            $('.'+champClass).val(valeur.toString() + touche.toString());
        }

        // On calcule le prorata de l'autre champ par rapport a la valeur changée
        var autre_champ = champClass === 'inputPoidsPdtTransfert' ? 'inputNbColisTransfert' : 'inputPoidsPdtTransfert';

        // (valeur modifiée * valeur_old_autre_champ) / valeur_old_champ_modifié
        var autre_valeur = (parseFloat($('.'+champClass).val()) * parseFloat($('.'+autre_champ).data('old'))) / parseFloat($('.'+champClass).data('old'));
        if (isNaN(autre_valeur)) { autre_valeur = 0.0; }

        // Si le calcul concerne le nb de colis, on parse en entier
        if (autre_champ === 'inputNbColisTransfert') { autre_valeur = parseInt(autre_valeur) ;}

        $('.'+autre_champ).val(autre_valeur);

    }); // FIN clavier


} // FIN listener de la modale opération



/** -------------------------------------------------------------
 * Associe des compositions à un lot de regroupement
 ------------------------------------------------------------- */
function addComposLotRegroupement(ids_compos, id_lot_r) {
    "use strict";

    // OK, on peut faire l'appel ajax pour la modif...
    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_stk.php',
        'arguments': 'mode=addComposLotRegroupement&ids_compos=' + ids_compos+'&id_lot_r='+id_lot_r,
        'callBack': function (retour) {

            retour+= '';

            if (parseInt(retour) !== 1) {
                alert('ERREUR !\r\nEchec de l\'association des produits au lot de regroupement...\r\nCode erreur : S5VFI92Q');
                return false;
            }

            // On ferme la modale et on recharge la liste pour afficher les changements
            $('#modalOperationsStockPdt').modal('hide');
            chargeEtape(1,0);

        }
    }); // FIN ajax


} // FIN mode


/** -------------------------------------------------------------
 * Clavier virtuel - Ne pas changer le noms des champs
 * Ou adapater le fichier jkeyboard.js
 ------------------------------------------------------------- */
function clavierVirtuel() {


    $('#champ_clavier').focus(function() {
        $('#clavier_virtuel').show("slide", {direction: "down"});
    });
    $('.btn').click(function() {
        $('#clavier_virtuel').hide("slide", {direction: "down"});
    });

    $('#champ_clavier').jkeyboard({

        input: $('#champ_clavier'),
        layout: 'francais',
        customLayouts: {
            selectable: ["francais"],
            francais: [
                ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
                ['A', 'Z', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P'],
                ['Q', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'M'],
                [ 'W', 'X', 'C', 'V', 'B', 'N', '/'],
                ['layout_switch', 'space' , 'backspace']
            ],
        },

    });

    return true;

}