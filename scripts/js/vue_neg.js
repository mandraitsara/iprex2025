/**
 ------------------------------------------------------------------------
 JS - Vue Negoce

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

    chargeEtape(1, 0);

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    // Ready -> Nettoyage des modales à leur fermeture
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    // Confirmation
    $('#modalConfirm').on('hidden.bs.modal', function (e) {
        $('#modalConfirmBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        $('#dataConfirmContexte').val('');
        $('#dataConfirmInput').val('0');
    });


}); // FIN ready



// Fonction chargeant les étapes pour intégrer leur contenu
// ##############################################################
// ATTENTION
// Cette vue charges les étapes au sein d'un même conteneur ajax
// ##############################################################
function chargeEtape(numeroEtape, identifiant) {

    $('#negAjaxVue').html('<div class="padding-50 text-center"><i class="fa fa-spin fa-spinner fa-5x gris-c"></i></div>');

    // Ajax qui charge le contenu de l'étape

    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_neg.php',
        'arguments': 'mode=chargeEtapeVue&etape=' + numeroEtape + '&id='+identifiant,
        'return_id': 'negAjaxVue',
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
function chargeTicket(etape, identifiant) {
    "use strict";

    // Ajax qui charge le contenu du ticket
    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_neg.php',
        'arguments': 'mode=chargeTicket&etape='+etape+'&id=' + identifiant,
        'return_id': 'ticketContent',
        'done' : function() {
            listenerTicket();
        }
    }); // FIN ajax

} // FIN fonction

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener du ticket
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerTicket() {
    "use strict";

    // Retour étape 1 (bouton)
    $('.btnRetourEtape1').off("click.btnRetourEtape1'").on("click.btnRetourEtape1", function(e) {

        e.preventDefault();
        chargeEtape(1, 0);

    }); // FIN bouton retour étape 1

    // Retour étape 2 (bouton)
    $('.btnRetourEtape2').off("click.btnRetourEtape2'").on("click.btnRetourEtape2", function(e) {

        e.preventDefault();

        var id_lot = parseInt($(this).data('id-lot'));
        if (isNaN(id_lot) || id_lot === 0) { alert('ERREUR !\r\nIdentification du lot de négoce impossible...\r\nCode erreur :'); return false; }

        chargeEtape(2, id_lot);

    }); // FIN bouton retour étape 1



} // FIN listener ticket


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 1
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape1(identifiant) {
    "use strict";

    // On charge le ticket au besoin
    chargeTicket(1, identifiant);

    // Clic sur un lot...
    $('.carte-lot').off("click.selectcartelot").on("click.selectcartelot", function(e) {
        e.preventDefault();

        var id_lot = parseInt($(this).data('id-lot'));
        if (id_lot === 0 || isNaN(id_lot) || id_lot === undefined) { return false; }

        var objetDomCarte = $(this);

        chargeEtape(2, id_lot);

    }); // FIN clic sur un lot


} // FIN listener etape

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 2
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape2(identifiant) {
    "use strict";
    $('.togglemaster').bootstrapToggle();

    // On charge le ticket au besoin
    chargeTicket(2, identifiant);

    // Switch "Traité"
    $('.switchTraite').change(function() {

        var traite  = $(this).prop('checked') === true ? 1 : 0;
        var id_lot_pdt_negoce    = parseInt($(this).data('id'));

        if (isNaN(id_lot_pdt_negoce) || id_lot_pdt_negoce === 0) {
            alert('ERREUR !\r\nIdentification du produit impossible...\r\nCode erreur : ');
            return false;
        }

        // On modifie...
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_neg.php',
            'arguments': 'mode=switchTraite&id_lot_pdt_negoce=' + id_lot_pdt_negoce + '&traite='+traite,
            'callBack': function (retour) {
                retour+= '';

                // Si erreur on alerte
                if (parseInt(retour) !== 1) {
                    alert('ERREUR !\r\nUne erreur est survenue...\r\nCode erreur : CVX1');
                    return false;
                }

            } // FIN callback
        }); // FIN aJax
    }); // Fin changement de l'activation d'une alerte


    // Bouton Modifier... -> Etape 3
    $('.btnModierPdtNeg').off("click.btnModierPdtNeg").on("click.btnModierPdtNeg", function(e) {
        e.preventDefault();

        var id_lot_pdt_negoce    = parseInt($(this).data('id'));

        chargeEtape(3, id_lot_pdt_negoce);
        return false;

    }); // FIN bouton modifier produit négoce

    // Validation partielle
    $('.btnPartiel').off("click.btnPartiel").on("click.btnPartiel", function(e) {
        e.preventDefault();

        var id_lot_pdt_negoce    = parseInt($(this).data('id'));

        chargeEtape(4, id_lot_pdt_negoce);
        return false;

    }); // FIN validation partielle



} // FIN listener etape


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 3
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape3(identifiant) {
    "use strict";

    // On charge le ticket au besoin
    chargeTicket(3, identifiant);

    $('input[name=nb_cartons]').focus(function()   { $('#champ').val('nb_cartons');  });
    $('input[name=quantite]').focus(function()   { $('#champ').val('quantite');  });
    $('input[name=poids]').focus(function()  {$('#champ').val('poids'); });

    // ----------------------------------------
    // (Listener Etape 3)
    // Saisie via le pavé numérique
    // ----------------------------------------
    $('.clavier .btn').off("click.appuietoucheclaviernbcolis").on("click.appuietoucheclaviernbcolis", function(e) {

        e.preventDefault();

        // On récupère la valeur de la touche
        var touche = $(this).data('valeur');

        var champ = $('#champ').val();

        // ----------------------------------------
        // (Listener Etape 3 -> Clavier)
        // Touche [C] - Effacer
        // ----------------------------------------
        if (touche === 'C') {

            // On reset le champ en cours
            $('input[name='+champ+']').val('');
            $('input[name='+champ+']').focus();

            // Si le champ en cours est le nb de colis, on remet à 0 le poids
            if (champ === 'nb_cartons') {
                $('input[name=poids]').val('');
            }

            return false;
        } // FIN touche C



        // ----------------------------------------
        // (Listener Etape 3 -> Clavier)
        // Touche [V] - Valider l'étape 3
        // ----------------------------------------
        if (touche === 'V') {

            // Si quantité 0 pour carton ou poids, on demande de confirmer qu'on veut supprimer le produit...
            var nb_cartons = parseInt($('.inputNbCartons').val());
            var poids = parseFloat($('.inputPoids').val());
            //var quantite = parseInt($('.inputQuantite').val());
            var id_lot_pdt_negeoce = parseInt($('#idLotPdtNegeoce').val());

            if (isNaN(nb_cartons) || nb_cartons < 1 || isNaN(poids) || poids < 0.01) {

                var htmConfirm = '<div class="alert alert-danger text-center"><strong class="text-24">ATTENTION !</strong><p>Vous allez supprimer ce produit du lot de négoce...<br>Cette action est irreversible.<br><p class="text-18">Continuer ?</p></div>';
                $('#modalConfirmBody').html(htmConfirm);
                $('#dataConfirmInput').val(id_lot_pdt_negeoce);
                $('#dataConfirmContexte').val('supprPdtNegoce');
                $('#modalConfirm').modal('show');
                return false;

            } // FIN test quantité vide

            // Ok, on a des valeurs... on valide !
            goUpdPdtNegoce();
            return false;

        } // FIN touche V


        // ----------------------------------------
        // (Listener Etape 3 -> Clavier)
        // Autre touche numérique [0-9]
        // ----------------------------------------

        // Si on est sur le champ du poids
        if (champ === 'poids') {

            var poidsProduit = $('.inputPoids').val().trim();

            // Si le champ était vide, il prend la valeur de la touche
            if (poidsProduit.length === 0) {
                $('.inputPoids').val(touche);

            // Sinon, et si on saisi un point, on vérifie qu'il n'y en a pas déjà un...
            } else if (touche === '.' && poidsProduit.indexOf('.') !== -1) {
                return false;
            } else {
                $('.inputPoids').val(poidsProduit + touche);
            } // FIN test point [.] ou chanmp vide

        // Sinon, on est sur le champ nb de cartons
        } else if(champ === 'nb_cartons') {

            var nbcartons = $('.inputNbCartons').val().trim();

            // Pas de décimales dans le nombre de cartons
            if (touche === '.') { return false; }

            // Si le champ était vide, il prend la valeur de la touche
            if (nbcartons.length === 0) {
                $('.inputNbCartons').val(touche);

            // Sinon on concatène la valeur...
            } else {
                $('.inputNbCartons').val(nbcartons + touche);
            }

        } else{

            var quantite = $('.inputQuantite').val().trim();
            // Pas de décimales dans le nombre de quantite
            if (touche === '.') { return false; }

             // Si le champ était vide, il prend la valeur de la touche
             if (quantite.length === 0) {
                $('.inputQuantite').val(touche);

            // Sinon on concatène la valeur...
            } else {
                $('.inputQuantite').val(quantite + touche);
            }

        }
        
        
        // FIN test sur le champ (poids/nb_colis)

    }); // FIN Clavier numérique


    // ----------------------------------------
    // (Listener Etape 3)
    // Confirmation (modale) - Générique
    // ----------------------------------------
    $('.btnModalConfirmOk').off("click.btnModalConfirmOk").on("click.btnModalConfirmOk", function(e) {

        e.preventDefault();

        // On passe un "contexte" pour définir le périmètre opérationnel de la confirmation
        var data_confirm_contexte = $('#dataConfirmContexte').val();

        // ----------------------------------------
        // (Listener Etape 3 -> Modal confirm OK)
        // Suppression produit négoce
        // ----------------------------------------
        if (data_confirm_contexte === 'supprPdtNegoce') {

          goUpdPdtNegoce();

        } // FIN contexte "updateQteCompo"

    }); // FIN confirmation modale




    // ----------------------------------------
    // (Listener Etape 3)
    // Bouton nouvelle palette (ajouter)
    // ----------------------------------------
    $('.btnNouvellePalette').off("click.btnNouvellePalette").on("click.btnNouvellePalette", function(e) {

        e.preventDefault();

        var id_produit = $('.id_pdt_negoce').val();

        // On recharge le contenu de la modale pour création nouvelle palette...
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_palettes.php',
            'arguments': 'mode=modaleNouvellePalette&id_produit='+id_produit,
            'return_id' : 'modalPaletteFrontBody',
            'done': function () {

                $('#modalPaletteFront').modal('show');
                nouvellePaletteListener();

            } // FIN Callback
        }); // FIN ajax

    }); // FIN bouton nouvelle palette


    // ----------------------------------------
    // (Listener Etape 3)
    // Sélection d'une palette (negoce)
    // ----------------------------------------
    $('.carte-palette').off("click.cartepalette'").on("click.cartepalette", function(e) {

        e.preventDefault();

        // Si on clic sur la palette déjà sélectionnée, inutile d'aller plus loin
        if ($(this).hasClass('palette-selectionnee')) { return false; }

        // Capacité restante de la palette
        var id_palette            = parseInt($(this).data('id-palette'));

        // Valeur du produit

        // Gestion des erreurs
        if (isNaN(id_palette) || id_palette === 0 ) {
            alert("ERREUR !\r\nEchec lors de la récupération des données...\r\nCode erreur : IRFNA2N9");
            return false;
        } // FIN échap erreurs

        // On met à jour d'ID palette et on met en évidence la palette selectionnée
        $('#idPalettePdtNegoce').val(id_palette);
        $('.carte-palette').removeClass('palette-selectionnee');
        $(this).addClass('palette-selectionnee');

    }); // FIN sélection d'une palette

    // ----------------------------------------
    // (Listener Etape 3)
    // Affichage des paletes complètes (droite)
    // ----------------------------------------
    $('.btnPalettesCompletes').off("click.btnPalettesCompletes").on("click.btnPalettesCompletes", function(e) {

        e.preventDefault();

        var id_pdt  = parseInt($('#inputIdProduit').val());
        var objDom = $(this);

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_palettes.php',
            'arguments': 'mode=listePalettesCompletes&id_pdt='+id_pdt+'&negoce',
            'append_id' : 'listePalettesPdt',
            'done': function () {

                objDom.hide('fast');
                palettesCompletesListener();

            } // FIN Callback
        }); // FIN ajax

    }); // FIN affichage des paletes complètes

} // FIN listener etape 3

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 4
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape4(identifiant) {
    "use strict";

    chargeTicket(4, identifiant);

    $('input[name=nb_cartons]').focus(function()   { $('#champ').val('nb_cartons');  });
    $('input[name=quantite]').focus(function()   { $('#champ').val('quantite');  });
    $('input[name=poids]').focus(function()  {$('#champ').val('poids'); });

    // ----------------------------------------
    // (Listener Etape 4)
    // Saisie via le pavé numérique
    // ----------------------------------------
    $('.clavier .btn').off("click.appuietoucheclaviernbcolis").on("click.appuietoucheclaviernbcolis", function(e) {

        e.preventDefault();

        // On récupère la valeur de la touche
        var touche = $(this).data('valeur');

        var champ = $('#champ').val();

        // ----------------------------------------
        // (Listener Etape 4 -> Clavier)
        // Touche [C] - Effacer
        // ----------------------------------------
        if (touche === 'C') {

            // On reset le champ en cours
            $('input[name='+champ+']').val('');
            $('input[name='+champ+']').focus();

            // Si le champ en cours est le nb de colis, on remet à 0 le poids
            if (champ === 'nb_cartons') {
                $('input[name=poids]').val('');
            }

            return false;
        } // FIN touche C



        // ----------------------------------------
        // (Listener Etape 4 -> Clavier)
        // Touche [V] - Valider l'étape 4
        // ----------------------------------------
        if (touche === 'V') {

            // Si quantité 0 pour carton ou poids, on demande de confirmer qu'on veut supprimer le produit...
            var nb_cartons = parseInt($('.inputNbCartons').val());
            var quantite = parseInt($('.inputQuantite').val());
            var poids = parseFloat($('.inputPoids').val());
            var id_lot_pdt_negeoce = parseInt($('#idLotPdtNegeoce').val());

            if (isNaN(nb_cartons) || nb_cartons < 1 || isNaN(poids) || poids < 0.01 || isNaN(quantite) || quantite < 1) {
               return false;
            } // FIN test quantité vide

            // Ok, on a des valeurs... on valide !
            goSplitPdtNegoce();
            return false;

        } // FIN touche V


        // ----------------------------------------
        // (Listener Etape 3 -> Clavier)
        // Autre touche numérique [0-9]
        // ----------------------------------------

        // Si on est sur le champ du poids
        if (champ === 'poids') {

            var poidsProduit = $('.inputPoids').val().trim();

            // Si le champ était vide, il prend la valeur de la touche
            if (poidsProduit.length === 0) {
                $('.inputPoids').val(touche);

                // Sinon, et si on saisi un point, on vérifie qu'il n'y en a pas déjà un...
            } else if (touche === '.' && poidsProduit.indexOf('.') !== -1) {
                return false;
            } else {
                $('.inputPoids').val(poidsProduit + touche);
            } // FIN test point [.] ou chanmp vide

            // Sinon, on est sur le champ nb de cartons
        } else {

            var nbcartons = $('.inputNbCartons').val().trim();

            // Pas de décimales dans le nombre de cartons
            if (touche === '.') { return false; }

            // Si le champ était vide, il prend la valeur de la touche
            if (nbcartons.length === 0) {
                $('.inputNbCartons').val(touche);

                // Sinon on concatène la valeur...
            } else {
                $('.inputNbCartons').val(nbcartons + touche);
            }

        } // FIN test sur le champ (poids/nb_colis)

    }); // FIN Clavier numérique


}// FIN listener etape


// Valide le traitement partiel d'un produit de négoce
function goSplitPdtNegoce() {
    "use strict";

    var nb_cartons = parseInt($('.inputNbCartons').val());
    var quantite = parseInt($('.inputQuantite').val());
    var poids = parseFloat($('.inputPoids').val());
    var id_lot_pdt_negoce = parseInt($('#idLotPdtNegeoce').val());

    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_neg.php',
        'arguments': 'mode=splitPdtNegoceTraite&id_lot_pdt_negoce=' + id_lot_pdt_negoce + '&nb_cartons='+nb_cartons+'&poids='+poids,
        'callBack': function (retour) {
            retour+='';

            if (parseInt(retour) !== 1) {
                alert('ERREUR !\r\nUne erreur est survenue...');
                return false;
            }


            var id_lot_negoce = $('input[name=id_lot_negoce]').val();
            chargeEtape(2,id_lot_negoce);
            return false;

        } // FIN Callback
    }); // FIN ajax

} // FIN fonction

// Mise à jour du produit de négoce
function goUpdPdtNegoce() {
    "use strict";

    var nb_cartons = parseInt($('.inputNbCartons').val());
    var poids = parseFloat($('.inputPoids').val());
    var id_lot_pdt_negoce = parseInt($('#idLotPdtNegeoce').val());
    var id_palette = parseInt($('#idPalettePdtNegoce').val());
    var id_client = parseInt($('#idPaletteIdClient').val());


    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_neg.php',
        'arguments': 'mode=updPdtNegoce&id_lot_pdt_negoce=' + id_lot_pdt_negoce + '&nb_cartons='+nb_cartons+'&poids='+poids+'&id_palette='+id_palette+'&id_client='+id_client,
        'callBack': function (retour) {            
            if (retour.length !== 1) {
                alert('ERREUR !\r\nUne erreur est survenue...\r\nCode erreur :CVX2');
                return false;
            }
        
            chargeEtape(1,0);
            return false;
            
        } // FIN Callback
    }); // FIN ajax

    return false;

} // FIN fonction mise à jour produit

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de la modale "Nouvelle Palette"
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function nouvellePaletteListener() {
    "use strict";





    // Voir plus de clients...
    $('.btnVoirTousClient').off("click.btnVoirTousClient").on("click.btnVoirTousClient", function(e) {

        e.preventDefault();

        var id_produit = $('.id_pdt_negoce').val();

        // On recharge le contenu de la modale avec plus de clients
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_palettes.php',
            'arguments': 'mode=modaleNouvellePalette&tous&id_produit='+id_produit,
            'return_id' : 'modalPaletteFrontBody',
            'done': function () {

                nouvellePaletteListener();

            } // FIN Callback
        }); // FIN ajax

    }); // FIn bouton voir plus de clients

    // Sélection du client
    $('.btnClientNewPalette').off("click.btnClientNewPalette").on("click.btnClientNewPalette", function(e) {

        e.preventDefault();

        var id_client = parseInt($(this).data('id-client'));
        var num_palette   = $('#numeroNextPalette').length ?  parseInt($('#numeroNextPalette').text()) : parseInt($(this).data('palette-suiv'));
        var id_lot_pdt_negoce = parseInt($('#idLotPdtNegeoce').val());

        if (isNaN(num_palette) || num_palette === 0) {
            alert("ERREUR !\r\nIdentification du numéro de palette impossible.\r\nCode erreur : 8PWOF3VX");
            return false;
        } // FIN gestion des erreurs

        if (isNaN(id_client) || id_client === 0) {
            alert("ERREUR !\r\nIdentification du client impossible.\r\nCode erreur : 3DLMRBXM");
            return false;
        } // FIN gestion des erreurs


        // Création de la nouvelle palette + d'une compo "vierge"...
        // (contrairement aux traitements froid, on viendra mettre à jour la quantité à l'upd du produit)
        // Ici pas de reprise partielle, pas de création de lot_pdt_froid, bref... plus simple
        // En plus : 1 pdt_negoce = 1 compo (pas d'éclatement)
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_palettes.php',
            'arguments': 'mode=creationNouvellePalettePdtNegoce&id_client=' + id_client + '&id_lot_pdt_negoce=' + id_lot_pdt_negoce + '&num_palette=' + num_palette,
            'callBack': function (retour) {
                retour+='';

                // Gestion des erreurs
                if (retour.substring(0, 2).toLowerCase() !== 'ok') {

                    alert("ERREUR !\r\nEchec de la mise en palette du produit...\r\nCode erreur : "+retour);
                    return false;

                // OK, on ferme la modale et on passe à l'étape suivante
                } else {

                    var ids_retour   = retour.toLowerCase().replace('ok', '');
                    var retoursArray = ids_retour.split('|');
                    var id_palette   = parseInt(retoursArray[0]);
                    var id_client   = parseInt(retoursArray[0]);
                    var htmlCarte    = retoursArray[2];

                    $('#idPalettePdtNegoce').val(id_palette);
                    $('#idPaletteIdClient').val(id_client);

                    // On affiche la carte de la nouvelle palette créée
                    $('#listePalettesPdt').append(htmlCarte);

                    $('.carte-palette').removeClass('palette-selectionnee');
                    $('#cartepaletteid'+id_palette).addClass('palette-selectionnee');

                    $('#modalPaletteFront').modal('hide');

                    // On masque le bouton ajouter nouvelle palette
                    $('.btnPalettesCompletes').removeClass('mt-2');
                    $('.btnPalettesCompletes').parent().find('br').remove();
                    $('.btnNouvellePalette').hide('fast');

                    nouvellesPalettesCreeListener();

                } // FIN gestion des erreurs

            } // FIN callBack
        }); // FIN ajax


    }); // FIN sélection d'un client pour création d'une nouvelle palette


} // FIN listener modale "Nouvelle Palette"


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'affichage d'une nouvelle palette fraichement créée
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function nouvellesPalettesCreeListener() {
    "use strict";

    // Clic sur une carte d'une palette nouvellement créee
    $('.carte-palette-nouvelle').off("click.cartepalettenouvelle'").on("click.cartepalettenouvelle", function(e) {

        e.preventDefault();

        // SI c'est celle qui est déjà sélectionnée, on ne vas pas plus loin
        if ($(this).hasClass('palette-selectionnee')) { return false; }

        // Capacité restante de la palette
        var id_palette            = parseInt($(this).data('id-palette'));

        // On met à jour d'ID palette et on met en évidence la palette selectionnée
        $('#idPalettePdtNegoce').val(id_palette);
        $('.carte-palette').removeClass('palette-selectionnee');
        $(this).addClass('palette-selectionnee');


    }); // FIN sélection d'une palette

} // FIN listner apres création nouvelle palette

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'affichage des palettes complètes (Etape 3)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function palettesCompletesListener() {

    $('.carte-palette-complete').off("click.cartepalettecomplete'").on("click.cartepalettecomplete", function(e) {

        e.preventDefault();

        // SI c'est celle qui est déjà sélectionnée, on ne vas pas plus loin
        if ($(this).hasClass('palette-selectionnee')) {
            return false;
        }

        // Capacité restante de la palette
        var id_palette = parseInt($(this).data('id-palette'));

        // On met à jour d'ID palette et on met en évidence la palette selectionnée
        $('#idPalettePdtNegoce').val(id_palette);
        $('.carte-palette').removeClass('palette-selectionnee');
        $(this).addClass('palette-selectionnee');

    }); // FIN clic sur palette complète

} // FIN listener palettes complètes
