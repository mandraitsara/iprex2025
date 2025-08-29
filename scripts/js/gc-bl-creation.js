/**
 ------------------------------------------------------------------------
 JS - BL ADD (BO)

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

    $('#modalInfo .modal-dialog').addClass('modal-xl');
    $('#modalInfo .modal-title').html('<i class="fa fa-globe mr-1"></i> Ccommandes Web à traiter');
    $('#modalInfo .modal-footer').hide();

    updAdressesClt();

    chargeProduitsBl();

    $('#modalEmballagesPalette').on('hide.bs.modal', function (e) {
        $('#modalEmballagesPaletteBody').html('<i class="fa fa-spin fa-spinner"></i>');
    });

    $('#modalAddLigneBl').on('hide.bs.modal', function (e) {
        $('#modalAddLigneBlBody input[type=text]').val('');
        $('#modalAddLigneBlBody select.selectpicker').selectpicker('val', 0);
    });
    $('#modalAddProduitBl').on('hide.bs.modal', function (e) {
        $('#btnAddPdtBl').hide();
    });

    $('.btnBleuAjouterProduitBl').off("click.btnBleuAjouterProduitBl").on("click.btnBleuAjouterProduitBl", function(e) {
        e.preventDefault();
        var idWeb = parseInt($('#idClientWeb').val());
        var id_client = parseInt($('select[name=id_t_fact]').val());

        if (idWeb > 0 && idWeb === id_client) {
            var id_bl = parseInt($('#formBl input[name=id]').val());
            if (isNaN(id_bl)) { id_bl = 0; }

            if (id_bl === 0) { alert('ERREUR ID BL 0'); return false; }
            var ln = loadingBtn($('.btnBleuAjouterProduitBl'));

            $.fn.ajax({
                script_execute: 'fct_bl.php',
                arguments:'mode=addProduitWeb&id_bl='+id_bl,
                callBack : function (retour) {
                    retour+='';
                    removeLoadingBtn(ln);
                    if (parseInt(retour) !== 1) { alert(retour); return false; }
                    chargeProduitsBl();
                } // FIN callBack
            }); // FIN ajax
        } else {
            $('#modalAddProduitBl').modal('show');
        }
        return false;
    });


    // Chargement du contenu de la modale à son ouverture
    $('#modalEmballagesPalette').on('shown.bs.modal', function (e) {

        // On récupère l'ID
        var id_palette = e.relatedTarget.attributes['data-id-palette'] === undefined ? 0 : parseInt(e.relatedTarget.attributes['data-id-palette'].value);
        if (isNaN(id_palette)) { id_palette = 0; }
        if (id_palette === 0) { alert('Identification de la palette impossible !'); return false; }

        $.fn.ajax({
            'script_execute': 'fct_palettes.php',
            'arguments': 'mode=showModaleEmballagesPalette&id_palette='+id_palette,
            'return_id': 'modalEmballagesPaletteBody',
            'done' : function () {
                $('#modalEmballagesPaletteBody .selectpicker').selectpicker('render');
            } // FIN callBack
        }); // FIN ajax
    }); // Fin chargement du contenu de la modale

    // Ajout d'une ligne
    $('#btnAddLigneBl').off("click.btnAddLigneBl").on("click.btnAddLigneBl", function(e) {
        e.preventDefault();

        var id_bl = parseInt($('#formBl input[name=id]').val());
        if (isNaN(id_bl) || id_bl === 0) { alert('Identification ID BL impossible !'); return false; }
        $('#modalAddLigneBlBody input[name=id_bl]').val(id_bl);

        var regexInt = /^[0-9]*$/;
        var qte = $('#modalAddLigneBlBody input[name=qte]').val();
        if (!regexInt.test(qte)) { alert ('La quantité doit être un nombre entier !'); return false; }

        var id_clt = parseInt($('#id_tiers_livraison').val());
        if (isNaN(id_clt)) { id_clt = 0; }
        $('#modalAddLigneBlBody input[name=id_clt]').val(id_clt);

        var objDom = $(this);
        var htmlBtn = objDom.html();

        if ($('#modalAddLigneBlBody input[name=nom]').val().length < 1) { return false; }
        objDom.html('<i class="fa fa-spin fa-spinner"></i>');


        $.fn.ajax({
            'script_execute': 'fct_bl.php',
            'form_id': 'modalAddLigneBlBody',
            'callBack' : function (retour) {
                retour+='';
                objDom.html(htmlBtn);
                if (parseInt(retour) !== 1) {alert(retour); return false; }
                $('#modalAddLigneBl').modal('hide');
                chargeProduitsBl();

            } // FIN callBack
        }); // FIN ajax


    }); // FIN ajout d'une ligne


    // Quand on change de client
    $('#id_tiers_livraison').change(function () {
        // On met à jour les adresses dispos
        updAdressesClt();
        updTransporteurClt();

    }); // FIN selection client

    // save modale emballage palettes
    $('.btnSaveEmballagesPalette').off("click.btnSaveEmballagesPalette").on("click.btnSaveEmballagesPalette", function(e) {

        e.preventDefault();

        $.fn.ajax({
            'script_execute': 'fct_palettes.php',
            'form_id': 'modalEmballagesPaletteBody',
            'callBack' : function (retour) {
                retour+= '';
                if (parseInt(retour) !== 1) { alert("Echec de l'enregistrement des modifications..."); return false; }
                recalculeTotauxPoids();
                $('#modalEmballagesPalette').modal('hide');
            } // FIN callBack
        }); // FIN ajax
    }); // FIN save modale emballage palettes

    // Si on change le client facturé...
    $('select[name=id_t_fact]').change(function () {

        var id_t_fact =  parseInt($(this).val());
        var id_t_livr =  parseInt(parseInt($('#id_tiers_livraison').val()));

        if (isNaN(id_t_fact)) { id_t_fact = 0; }
        if (isNaN(id_t_livr)) { id_t_livr = 0; }

        // ... et qu'on a pas de client livré, alors on impacte le même
        if (id_t_livr === 0) {
            $('#id_tiers_livraison').selectpicker('val',id_t_fact);
        }

        // Mise à jour des tarifs
        recalculeTarifsClients();


    }); // FIN changmeent client facturé


    // Regroupement
    $('#regroupement').change(function () { // Au changement
        changeRegroupementProduits();
    });

    // Chiffrage
    adapteChiffrage();
    $('#chiffrage').change(function () {
        adapteChiffrage();
    }); // FIN changement chiffrage


    // Sélection d'un produit à ajouter
    $('#selectProduitNewBl').change(function (e) {

        e.stopImmediatePropagation();
        $('#btnAddPdtBl').hide();
        var id_pdt = parseInt($(this).val());
        if (isNaN(id_pdt)) { id_pdt = 0; }
        if (id_pdt === 0) { return false; }

        $.fn.ajax({
            'script_execute': 'fct_bl.php',
            //'arguments': 'mode=rechercheProduitsStock&id_pdt='+id_pdt,
            'arguments': 'mode=rechercheProduitsBlManuel&id_pdt='+id_pdt,
            'return_id' : 'produitsNewBl',
            'done' : function () {

                selectPdtAddBlListener();

            } // FIN callBack
        }); // FIN ajax

    }); // FIN sélection d'un produit

    // fermeture modale
    $('#modalAddProduitBl').on('hidden.bs.modal', function (e) {
        $('#selectProduitNewBl').selectpicker('val', 0);
        $('#produitsNewBl').html('');
    }); // FIN fermeture modale


    // ouverture modale
    $('#modalAddProduitBl').on('show.bs.modal', function (e) {
        var id_t_fact = parseInt($('select[name=id_t_fact]').val());
        if (isNaN(id_t_fact)) { id_t_fact = 0; }

        if (id_t_fact === 0) {
            alert('Sélectionnez un client facturé pour ajouter un produit !');
            $('#modalAddProduitBl').modal('hide');
            return false;
        }

    }); // FIN ouverture modale

    // Ajout d'un produit
    $('#btnAddPdtBl').click(function () {

        var id_pdt = parseInt($('#selectProduitNewBl').val());
        if (isNaN(id_pdt)) { id_pdt = 0; }

        var id_compo = parseInt($('#modalAddProduitBlBody input[name=id_compo]').val());
        if (isNaN(id_compo)) { id_compo = 0; }
        if (id_compo === 0) {
            id_compo = parseInt($('#modalAddProduitBlBody .selectCompoMulti option:selected').val());
            if (isNaN(id_compo)) { id_compo = 0; }
        }

        var id_pdt_negoce = $("#id_pdt_negoce").data('id-negoce');

        var id_pdt_negoce = parseInt($('#modalAddProduitBlBody input[name=id_pdt_negoce]').val(id_pdt_negoce));       


        if (isNaN(id_pdt_negoce)) { id_pdt_negoce = 0; }
        if (id_pdt_negoce === 0) {
            id_pdt_negoce = parseInt($('#modalAddProduitBlBody .selectNegoceMulti option:selected').val());
            if (isNaN(id_pdt_negoce)) { id_pdt_negoce = 0; }
        }

        if (id_compo === 0 && id_pdt === 0) { return false; }

        var id_bl = parseInt($('#formBl input[name=id]').val());
        if (isNaN(id_bl)) { id_bl = 0; }
        if (id_bl === 0) { alert('ERREUR !\r\nIdentification du BL impossible...'); return false; }

        // On vérifie la nb de colis
        var colis = parseFloat($('#produitsNewBl input[name=colis]').val());
        if (isNaN(colis)) { colis = 0; }
        var poids = parseFloat($('#produitsNewBl input[name=poids]').val());
        if (isNaN(poids)) { poids = 0; }
        if (poids === 0 && colis === 0) {
            alert('Aucun nombre de colis et poids spécifié !');return false;
        }
        var regexInt = /^[0-9]*$/;
        if (!regexInt.test(colis)) { alert ('Nombre de colis invalide !'); return false; }


        verifNumeroLotAddBl();

        return false;

    }); // FIN ajout produit


    // Enregistrement du BL
    $('#btnEnregistreBl').click(function () {

        // ON vérifie qu'on a bien au moins un client facturé...
        var id_t_fact = parseInt($('select[name=id_t_fact]').val());
        var id_t_livr = parseInt($('select[name=id_t_livr]').val());
        if (isNaN(id_t_fact)) { id_t_fact = 0; }
        if (isNaN(id_t_livr)) { id_t_livr = 0; }

        if (id_t_livr === 0 && id_t_fact === 0) {
            alert('Sélectionnez au moins un client pour enregistrer...');
            return false;
        }

        $('#formBl input[name=mode]').val('saveBl');

        $.fn.ajax({
            'script_execute': 'fct_bl.php',
            'form_id': 'formBl',
            'callBack' : function (retour) {
                retour+= '';

                if (parseInt(retour) !== 1) {
                    alert("Echec de l'enregistrement du BL !");
                    return false;
                }

                if (confirm("Ce BL a bien été enregistré.\r\nRevenir à la liste des BL ?")) {
                    $('body').css('cursor', 'wait');
                    $(location).attr('href',"gc-bls.php");
                }

            } // FIN callBack
        }); // FIN ajax

    }); // FIN enregistrer le BL

}); // FIN ready



// Chargement select adresses du client sélectionné
function updAdressesClt() {
    "use strict";

    var id_clt = parseInt($('#id_tiers_livraison').val());
    if (isNaN(id_clt)) {id_clt = 0;}
    if (id_clt === 0) { return false; }


    $.fn.ajax({
        'script_execute': 'fct_clients.php',
        'arguments': 'mode=adressesCltSelect&id_clt='+id_clt,
        'return_id' : 'selectAdresses',
        'done' : function () {
            $('#selectAdresses').selectpicker('refresh');
        } // FIN callBack
    }); // FIN ajax

} // FIN Fonction



// Recalcule les totaux
function recalculeTotaux() {
    "use strict";

/*    // Ligne par ligne : on calcul le total HT
    $('.qteLigne').each(function () {
        var qte = parseFloat($(this).val());
        var pu = parseFloat($(this).parents('tr').find('.puLigne').val());
        if (isNaN(qte)) { qte = 0; }
        if (isNaN(pu)) { pu = 0; }
        var totalLigne = qte * pu;
        $(this).parents('tr').find('.totalLigne').text(totalLigne.toFixed(2));
    });*/

    // Ligne par ligne : on calcul le total HT
    $('.multiplicateurQte').each(function () {
        var qte = parseFloat($(this).val());
        var pu = parseFloat($(this).parents('tr').find('.puLigne').val());
        if (isNaN(qte)) { qte = 0; }
        if (isNaN(pu)) { pu = 0; }
        var totalLigne = qte * pu;
        $(this).parents('tr').find('.totalLigne').text(totalLigne.toFixed(2));
    });

    // Totaux palette
    $('.totalPalette').each(function () {
        var id_palette = parseInt($(this).data('id'));
        if (isNaN(id_palette)) { id_palette = 0 ;}
        var total_palette_colis = 0;
        var total_palette_poids = 0;
        // Boucle sur les lignes de la palette
        $('.lignePalette'+id_palette).each(function () {
            var ligne_colis = parseInt($(this).find('.nbColisLigne').val());
            var ligne_poids = parseFloat($(this).find('.poidsLigne').val().replace(/\s/g, ''));
            if (isNaN(ligne_colis)) { ligne_colis = 0; }
            if (isNaN(ligne_poids)) { ligne_poids = 0; }
            total_palette_colis+=ligne_colis;
            total_palette_poids+=ligne_poids;
        }); // FIN boucle sur les lignes de la palette
        $(this).find('.totalColis').text(total_palette_colis);
        $(this).find('.totalPoids').text(total_palette_poids.toFixed(2));
    });

    // Totaux expédition
    var total_colis = 0;
    var total_poids = 0;
    var total_pu_ht = 0;
    var total_bl_ht = 0;
    $('.ligneBl').each(function () {
        var ligne_colis = parseInt($(this).find('.nbColisLigne').val());
        var ligne_poids = parseFloat($(this).find('.poidsLigne').val().replace(/\s/g, ''));
        var ligne_pu_ht = parseFloat($(this).find('.puLigne').val());
        var ligne_bl_ht = parseFloat($(this).find('.totalLigne').text());
        if (isNaN(ligne_colis)) { ligne_colis = 0; }
        if (isNaN(ligne_poids)) { ligne_poids = 0; }
        if (isNaN(ligne_pu_ht)) { ligne_pu_ht = 0; }
        if (isNaN(ligne_bl_ht)) { ligne_bl_ht = 0; }
        total_colis+=ligne_colis;
        total_poids+=ligne_poids;
        total_pu_ht+=ligne_pu_ht;
        total_bl_ht+=ligne_bl_ht;
    });
    $('.ligneTotalBl').find('.totalColis').text(total_colis);
    $('.ligneTotalBl').find('.totalPoids').text(total_poids.toFixed(2));
    $('.ligneTotalBl').find('.totalPu').text(total_pu_ht.toFixed(2));
    $('.ligneTotalBl').find('.totalBl').text(total_bl_ht.toFixed(2));

} // FIN fonction


// Regroupement produits
function changeRegroupementProduits() {
    "use strict";

    var id_bl = parseInt($('#formBl input[name=id]').val());
    if (isNaN(id_bl)) { id_bl = 0; }
    if (id_bl === 0) { alert('ERREUR !\r\nIdentification du BL impossible...'); return false; }

    $('#formBl input[name=mode]').val('saveBl');
    $.fn.ajax({
        'script_execute': 'fct_bl.php',
        'form_id': 'formBl',
        'done' : function () {

            $('body').css('cursor', 'wait');
            $(location).attr('href',"gc-bl-creation.php?idbl="+id_bl);


        } // FIN callBack
    }); // FIN ajax

    return false;

} // FIN fonction regroupement produits

function adapteChiffrage() {
    "use strict";
    var chiffrage = parseInt($('#chiffrage').val());
    if (chiffrage === 1) {
        $('.bl-chiffre').removeClass('hid');
    }  else {
        $('.bl-chiffre').addClass('hid');
    }
}

// On met à jour le transporteur par défaut du client sélectionné
function updTransporteurClt() {
    "use strict";

    var id_client = parseInt($('#id_tiers_livraison').val());
    if (isNaN(id_client)) { id_client = 0; }
    if (id_client === 0) {
        id_client = parseInt($('select[name=id_t_fact]').val());
        if (isNaN(id_client)) { id_client = 0; }
    }

    if (id_client === 0) { alert('Identification du client impossible !'); return false; }

    $.fn.ajax({
        'script_execute': 'fct_clients.php',
        'arguments': 'mode=getIdTransporteurClient&id_client='+id_client,
        'callBack' : function (retour) {
            retour+= '';
            var id_transporteur = parseInt(retour);
            if (isNaN(id_transporteur)) { id_transporteur = 0; }

            if (id_transporteur < 1) { return false; }
            $('select[name=id_transp]').selectpicker('val', id_transporteur);


        } // FIN callBack
    }); // FIN ajax

} // FIN fonction


// Charge la liste des produits du BL
function chargeProduitsBl() {
    "use strict";


    var id_bl = parseInt($('#formBl input[name=id]').val());
    if (isNaN(id_bl)) { id_bl = 0; }
    if (id_bl === 0) { alert('ERREUR !\r\nIdentification du BL impossible au chargement des produits...'); return false; }

    var id_t_fact =  parseInt($('#formBl select[name=id_t_fact]').val());
    if (isNaN(id_t_fact)) { id_t_fact = 0; }

    $.fn.ajax({
        'script_execute': 'fct_bl.php',
        'arguments': 'mode=listeProduitsBlCreation&id='+id_bl+'&id_t_fact='+id_t_fact,
        'return_id': 'listeProduitsBl',
        'done': function () {
            listeProduitsListener();
            recalculeTotauxPoids();
            recalculeTarifsClients();
        }
    }); // FIN ajax

} // FIN fonction

// Listener de la liste des produits
function listeProduitsListener() {
    "use strict";

    $('#listeProduitsBl .selectpicker').selectpicker('render');
    adapteChiffrage();

    $('.idFrs').change(function(e) {
        e.stopImmediatePropagation();
        var id_frs = parseInt($(this).val());
        if (isNaN(id_frs)) { alert('ERREUR id frs inconnu !'); }
        var id_ligne =parseInt($(this).data('id-ligne'));
        $.fn.ajax({
            script_execute: 'fct_bl.php',
            arguments: 'mode=updFrsLigne&id_ligne='+id_ligne+'&id_frs='+id_frs,
            callBack: function (retour) {
                retour += '';
                if (parseInt(retour) !== 1) { alert(retour);}
            }
        });
    });

    // Commande web associée
    $('.btnLigneWeb').off("click.btnLigneWeb").on("click.btnLigneWeb", function(e) {
        e.preventDefault();

        var id_bl_ligne = parseInt($(this).data('id'));
        if (isNaN(id_bl_ligne) || id_bl_ligne === 0) { alert('ERREUR ID BL LIGNE 0 !'); return false; }

        // On charge une modale avec la liste des commandes web à traiter
        $.fn.ajax({
            script_execute: 'fct_web.php',
            arguments: 'mode=modaleCommandesWebForBl&id_bl_ligne='+id_bl_ligne,
            return_id: 'modalInfoBody',
            done : function () {
                $('#modalInfo').modal('show');
                webOrdersListener();
            } // FIN callBack
        }); // FIN ajax
        // selection de la commande
        // selection du produit
        // validation -> association en base
    });


    $('.btnDesassocieWebLigne').off("click.btnDesassocieWebLigne").on("click.btnDesassocieWebLigne", function(e) {
        e.preventDefault();
        var id_bl_ligne = parseInt($(this).parents('tr').data('id-ligne'));
        if (isNaN(id_bl_ligne) || id_bl_ligne === 0) { alert('ERREUR ID BL LIGNE 0 !'); return false; }
        var id_od = parseInt($(this).data('id-od'));
        if (isNaN(id_od) || id_od === 0) { alert('ERREUR ID ORDER DETAIL 0 !'); return false; }

        if (!confirm("Supprimer l'association avec cette commande web ?")) { return false; }

        $.fn.ajax({
            script_execute: 'fct_web.php',
            arguments: 'mode=desassocieWebLigne&id_order_detail='+id_od+'&id_bl_ligne='+id_bl_ligne,
            callBack: function(retour) {
                retour+='';
                if (parseInt(retour) !== 1) {
                    alert(retour);
                    return false;
                }
               chargeProduitsBl();
            }
        }); // FIN ajax
    });


    // Changement Id du produit
    $('select.selectProduitBl').on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {
        e.stopPropagation();

        var id_produit = parseInt($(this).val());
        if (isNaN(id_produit)) { id_produit = 0; }

        var id_ligne_bl = parseInt($(this).parents('tr').data('id-ligne'));
        if (isNaN(id_ligne_bl)) { id_ligne_bl = 0; }

        if (id_produit === 0) { alert("ERREUR !\r\nIdentification du produit impossible !"); return false; }
        if (id_ligne_bl === 0) { alert("ERREUR !\r\nIdentification de la ligne impossible !"); return false; }

        var objDom = $(this);

        $.fn.ajax({
            'script_execute': 'fct_bl.php',
            'arguments': 'mode=changeProduitLigne&id_ligne_bl='+id_ligne_bl+'&id_produit='+id_produit,
            'callBack' : function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de l'enregistrement des modifications.");}

                // On change le code du produit dans le tableau
                var codePdt = objDom.find('option:selected').data('subtext');
                if (codePdt === undefined) { codePdt = ''; }
                objDom.parents('tr').find('.codeProduit').text(codePdt);


            } // FIN callBack
        }); // FIN ajax
    }); // FIN changement produit


    // Changement de numéro de lot
    $('.numlotLigne').blur(function () {
        var numlot = $(this).val().trim();
        var id_ligne_bl = parseInt($(this).parents('tr').data('id-ligne'));
        if (isNaN(id_ligne_bl)) { id_ligne_bl = 0; }
        if (id_ligne_bl === 0) { alert("ERREUR !\r\nIdentification de la ligne impossible !"); return false; }
        $.fn.ajax({
            'script_execute': 'fct_bl.php',
            'arguments': 'mode=changeNumLotLigne&id_ligne_bl='+id_ligne_bl+'&numlot='+numlot,
            'callBack' : function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de l'enregistrement des modifications.");}
            } // FIN callBack
        }); // FIN ajax
    }); // FIN changement numlot

    // Changement du numéro de palette
    $('.numPalette').blur(function () {

        var id_bl = parseInt($('#formBl input[name=id]').val());
        if (isNaN(id_bl)) { id_bl = 0; }
        if (id_bl === 0) { alert('ERREUR !\r\nIdentification du BL impossible...'); return false; }
        var num_palette = $(this).val().trim();
        var id_palette = parseInt($(this).parents('tr').data('id'));
        if (isNaN(id_palette)) { id_palette = 0; }
        if (id_palette === 0) { alert("ERREUR !\r\nIdentification de la palette source impossible !"); return false; }
        $.fn.ajax({
            'script_execute': 'fct_bl.php',
            'arguments': 'mode=changeNumPaletteLigne&id_bl='+id_bl+'&id_palette='+id_palette+'&num_palette='+num_palette,
            'callBack' : function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de l'enregistrement des modifications.");}
            } // FIN callBack
        }); // FIN ajax

    }); // FIN changement numéro palette

    // Changement du type de poids palette
    $('select.selectTypePoidsPalette').on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {

        var id_type_pp = parseInt($(this).val());
        if (isNaN(id_type_pp)) { id_type_pp = 0; }
        if (id_type_pp === 0) { alert('Type de palette non identifié !'); return false; }

        var id_palette = parseInt($(this).parents('tr.total').data('id'));
        if (isNaN(id_palette)) { id_palette = 0; }
        if (id_palette === 0) { alert('Identification de la palette impossible !'); return false; }

        $.fn.ajax({
            'script_execute': 'fct_palettes.php',
            'arguments': 'mode=setPoidsPalette&id_palette='+id_palette+'&id_type_pp='+id_type_pp,
            'callBack' : function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de l'enregistrement des modifications.");}
                recalculeTotauxPoids();
            } // FIN callBack
        }); // FIN ajax

    }); // FIN changement type poids palette


    // Changement du nombre de colis
    $('.nbColisLigne').blur(function () {
        var nb_colis = parseInt($(this).val());
        var id_ligne_bl = parseInt($(this).parents('tr').data('id-ligne'));
        if (isNaN(nb_colis)) { nb_colis = 0; }
        if (isNaN(id_ligne_bl)) { id_ligne_bl = 0; }
        if (id_ligne_bl === 0) { alert("ERREUR !\r\nIdentification de la ligne impossible !"); return false; }

            $.fn.ajax({
                'script_execute': 'fct_bl.php',
                'arguments': 'mode=changeNbColisLigne&id_ligne_bl='+id_ligne_bl+'&nb_colis='+nb_colis,
                'callBack' : function (retour) {
                    retour+='';
                    if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de l'enregistrement des modifications.");}
                    recalculeTotaux();
                    recalculeTotauxPoids();
                } // FIN callBack
            }); // FIN ajax

    }); // FIN changement nb_colis

    // Changement de la quantité
    $('.qteLigne').blur(function () {
        var qte = parseInt($(this).val());
        var id_ligne_bl = parseInt($(this).parents('tr').data('id-ligne'));
        if (isNaN(qte)) { qte = ''; }
        if (qte === '') { return false; }
        if (isNaN(id_ligne_bl)) { id_ligne_bl = 0; }
        if (id_ligne_bl === 0) { alert("ERREUR !\r\nIdentification de la ligne impossible !"); return false; }

        // Si on a mis zéro... suppression
        if (qte === 0) {

            if (!confirm("ATTENTION !\r\nQuantité nulle : souhaitez-vous supprimer cette ligne du BL ?")) { return false; }
            var ligneDom = $(this).parents('tr');
            $.fn.ajax({
                'script_execute': 'fct_bl.php',
                'arguments': 'mode=supprLigneBl&id_ligne_bl='+id_ligne_bl,
                'callBack' : function (retour) {
                    retour+='';
                    if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de l'enregistrement des modifications.");}
                    ligneDom.remove();
                    recalculeTotaux();
                } // FIN callBack
            }); // FIN ajax


            // Sinon, maj de la valeur
        } else {
            $.fn.ajax({
                'script_execute': 'fct_bl.php',
                'arguments': 'mode=changeQteLigne&id_ligne_bl='+id_ligne_bl+'&qte='+qte,
                'callBack' : function (retour) {
                    retour+='';
                    if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de l'enregistrement des modifications.");}
                    recalculeTotaux();
                } // FIN callBack
            }); // FIN ajax
        } // FIN test > 0
    }); // FIN changement qté

    // Changement du poids
    $('.poidsLigne').blur(function () {

        var poids = parseFloat($(this).val().replace(/\s/g, ''));
        var id_ligne_bl = parseInt($(this).parents('tr').data('id-ligne'));

        if (isNaN(poids)) { poids = 0; }
        if (isNaN(id_ligne_bl)) { id_ligne_bl = 0; }
        if (id_ligne_bl === 0) { alert("ERREUR !\r\nIdentification de la ligne impossible !"); return false; }

        // Si on a mis zéro... suppression
        if (poids === 0) {

            if (!confirm("ATTENTION !\r\nPoids nul : souhaitez-vous supprimer cette ligne du BL ?")) { return false; }
            var ligneDom = $(this).parents('tr');
            $.fn.ajax({
                'script_execute': 'fct_bl.php',
                'arguments': 'mode=supprLigneBl&id_ligne_bl='+id_ligne_bl,
                'callBack' : function (retour) {
                    retour+='';
                    if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de l'enregistrement des modifications.");}
                    ligneDom.remove();
                    recalculeTotaux();
                    recalculeTotauxPoids();

                } // FIN callBack
            }); // FIN ajax

            // Sinon, maj de la valeur
        } else {
            $.fn.ajax({
                'script_execute': 'fct_bl.php',
                'arguments': 'mode=changePoidsLigne&id_ligne_bl='+id_ligne_bl+'&poids='+poids,
                'callBack' : function (retour) {
                    retour+='';
                    if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de l'enregistrement des modifications.");}
                    recalculeTotaux();
                    recalculeTotauxPoids();
                } // FIN callBack
            }); // FIN ajax
        } // FIN test > 0
    }); // FIN changement poids

    // Changement du prix unitaire
    $('.puLigne').blur(function () {

        var pu = parseFloat($(this).val());
        var id_ligne_bl = parseInt($(this).parents('tr').data('id-ligne'));
        if (isNaN(id_ligne_bl)) { id_ligne_bl = 0; }

        if ($(this).hasClass('rougeTemp') && pu > 0) {
            $(this).removeClass('rougeTemp');
        } else if (!$(this).hasClass('rougeTemp') && pu === 0) {
            $(this).addClass('rougeTemp');
        }

        var id_produit = parseInt($(this).parents('tr').find('select.selectProduitBl').val());
        if (isNaN(id_produit)) { id_produit = 0; }

        // Si différent de l'ancienne valeur
        var oldDom = $(this).parents('tr').find('.puLigneOld');
        var old = parseFloat(oldDom.val());
        if (isNaN(old)) { old = 0; }

        if (id_ligne_bl === 0) { alert("ERREUR !\r\nIdentification de la ligne impossible !"); return false; }
        $.fn.ajax({
            'script_execute': 'fct_bl.php',
            'arguments': 'mode=changePuLigne&id_ligne_bl='+id_ligne_bl+'&pu='+pu,
            'callBack' : function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de l'enregistrement des modifications.");}
                recalculeTotaux();
            } // FIN callBack
        }); // FIN ajax


        if (old !== pu && pu > 0 && id_produit > 0) {
            if (confirm("ATTENTION !\r\nCe prix est différent du tarif client...\r\nMettre à jour le tarif du client facturé ?")) {
                oldDom.val(pu); // On met à jour la référence dans la ligne
                var id_client = parseInt($('select[name=id_t_fact]').val());
                if (isNaN(id_client)) { id_client = 0; }
                if (id_client === 0) { alert("ERREUR !\r\nIdentification du client impossible !"); return false; }
                if (id_produit === 0) { alert("ERREUR !\r\nIdentification du produit impossible !"); return false; }

                $.fn.ajax({
                    'script_execute': 'fct_tarifs.php',
                    'arguments': 'mode=updTarifClient&id_client='+id_client+'&pu='+pu+'&id_produit='+id_produit,
                    'callBack' : function (retour) {
                        retour+='';
                        if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de la mise à jour du tarif client.");}
                    } // FIN callBack
                }); // FIN ajax

            } // FIN confirmation

        } // FIN test PU différent du tarif client

    }); // FIN changement prix unitaire

    // Changement du prix d'achat
/*    $('.paLigne').blur(function () {

        var pa = parseFloat($(this).val());
        var id_ligne_bl = parseInt($(this).parents('tr').data('id-ligne'));
        if (isNaN(id_ligne_bl)) { id_ligne_bl = 0; }

        var id_produit = parseInt($(this).parents('tr').find('select.selectProduitBl').val());
        if (isNaN(id_produit)) { id_produit = 0; }

        // Si différent de l'ancienne valeur
        var oldDom = $(this).parents('tr').find('.paLigneOld');
        var old = parseFloat(oldDom.val());
        if (isNaN(old)) { old = 0; }

        if (id_ligne_bl === 0) { alert("ERREUR !\r\nIdentification de la ligne impossible !"); return false; }
        $.fn.ajax({
            'script_execute': 'fct_bl.php',
            'arguments': 'mode=changePaLigne&id_ligne_bl='+id_ligne_bl+'&pa='+pa,
            'callBack' : function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de l'enregistrement des modifications.");}
            } // FIN callBack
        }); // FIN ajax

        if (old !== pa && pa > 0 && id_produit > 0) {
            if (confirm("ATTENTION !\r\nCe prix est différent du tarif fournisseur...\r\nMettre à jour le tarif fournisseur ?")) {
                oldDom.val(pa); // On met à jour la référence dans la ligne
                var id_frs = 0;
                if (id_produit === 0) { alert("ERREUR !\r\nIdentification du produit impossible !"); return false; }

                $.fn.ajax({
                    'script_execute': 'fct_tarifs.php',
                    'arguments': 'mode=updTarifFrs&id_frs='+id_frs+'&pa='+pa+'&id_produit='+id_produit,
                    'callBack' : function (retour) {
                        retour+='';
                        if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de la mise à jour du tarif fournisseur.");}
                    } // FIN callBack
                }); // FIN ajax

            } // FIN confirmation

        } // FIN test PU différent du tarif client

    }); // FIN changement prix unitaire*/

    // Supprimer une ligne du Bl
    $('.btnSupprLigneBl').off("click.btnSupprLigneBl").on("click.btnSupprLigneBl", function(e) {
        e.preventDefault();

        var id_ligne = parseInt($(this).data('id'));
        if (isNaN(id_ligne)) { id_ligne = 0; }
        if (id_ligne === 0) { alert("Identification de la ligne impossible !"); return false; }

        if (!confirm("Supprimer cette ligne du BL ?")) { return false; }

        $.fn.ajax({
            'script_execute': 'fct_bl.php',
            'arguments': 'mode=supprLigneBl&id_ligne_bl='+id_ligne,
            'callBack' : function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de la suppression de la ligne.");}

                chargeProduitsBl();

            } // FIN callBack
        }); // FIN ajax

    }); // FIN supprimer ligne Bl

} // FIN listener

// Recalcule les totaux des poids
function recalculeTotauxPoids() {
    "use strict";

    var total_palettes_seuls = 0.0;
    var total_palettes_total = 0.0;

    var nb_palettes = $('.totalPalette').length;

    var p = 1;
    // Pour chaque palette on prends l'id,
    $('.totalPalette').each(function () {

        var id_palette = parseInt($(this).data('id'));
        if (isNaN(id_palette)) { id_palette = 0; }
        if (id_palette === 0) { return false; }

        var objetTrPoidsPalette = $(this).next().find('.totalPoids');
        var objetTrPoidsTotalPl = $(this).next().next().find('.totalPoids');


        // On récup en ajax le poids de la palette + emballages (hors produits)
        $.fn.ajax({
            'script_execute': 'fct_palettes.php',
            'arguments': 'mode=getPoidsPalettePoids&id_palette='+id_palette,
            'callBack' : function (retour) {
                retour+= '';

                var poids = parseFloat(retour);
                if (isNaN(poids)) { poids = 0; }

                // On affiche le total pour la palette en cours
                objetTrPoidsPalette.text(poids.toFixed(3));

                // On calcul la somme avec le poids des produits de la palette en cours
                var total_palette_poids = 0;
                // Boucle sur les lignes de la palette
                $('.lignePalette'+id_palette).each(function () {
                    var ligne_poids = parseFloat($(this).find('.poidsLigne').val().replace(/\s/g, ''));
                    if (isNaN(ligne_poids)) { ligne_poids = 0; }
                    total_palette_poids+=ligne_poids;
                }); // FIN boucle sur les lignes de la palette
                var total_total_palette = total_palette_poids + poids;
                total_palettes_seuls+=poids;
                total_palettes_total+=total_total_palette;
                objetTrPoidsTotalPl.text(total_total_palette.toFixed(3));

                // Si dernière palette, on met à jour les totaux du Bl
                if (p === nb_palettes) {
                    $('.totalPoidsPalettes').text(total_palettes_seuls.toFixed(3));
                    $('.totalPoidsTotal').text(total_palettes_total.toFixed(3));
                }
                p++;

            } // FIN callBack
        }); // FIN ajax
    }); // FIN boucle



} // FIN fonction


function recalculeTarifsClients() {
    "use strict";
    var ids_pdts = '';
    var id_t_fact = $('select[name=id_t_fact] option:selected').val();
    if (isNaN(id_t_fact) || id_t_fact === 0) {
        $('tr.ligneBl input.puLigne').val('');
        return false;
    }
    $('.puLigne.rougeTemp').removeClass('rougeTemp');

    if ($('select[name=id_t_fact] option:selected').text() === 'WEB') {
        updTarifsClientLignes();
        return true;
    }


    $('td.codeProduit').each(function() {
        var id_pdt = parseInt($(this).data('id'));
        if (!isNaN(id_pdt) && id_pdt > 0) {
            ids_pdts+=id_pdt+',';
        }
    });

    var ids_produits = ids_pdts.split(',');

    // On récupère les tarifs client
    ids_produits.forEach(function(id_pdt) {
        if (parseInt(id_pdt) > 0) {
            $.fn.ajax({
                'script_execute': 'fct_tarifs.php',
                'arguments': 'mode=getTarifClientByProduct&id_clt='+id_t_fact+'&id_pdt='+id_pdt+'&th_sep=', // On ne met pas de séparateur de milliers
                'callBack' : function (retour) {
                    retour+='';

                        $('.lignePdt'+id_pdt).find('input.puLigne').val(retour);
                        if (parseFloat(retour) === 0 || isNaN(parseFloat(retour))) {
                            $('.lignePdt'+id_pdt).find('input.puLigne').addClass('rougeTemp');
                        }

                    // On va mettre à jour les pu_ht des lignes du BL par rapport au client facturé pour éviter l'alert confirm sur chaque ligne
                    updTarifsClientLignes();
                } // FIN callBack
            }); // FIN ajax
        }
    });

} // FIN fonction
function selectPdtAddBlListener() {
    "use strict";

    $('#produitsNewBl .selectpicker').selectpicker('render');

    $('.btnAjouterProduitBl').off("click.btnAjouterProduitBl").on("click.btnAjouterProduitBl", function(e) {
        e.preventDefault();

        // Si hors stock

        var valPdt = $('#produitsNewBl .selectProductionProduit').length > 0 ? parseInt($('#produitsNewBl select.selectProductionProduit option:selected').val()) : 0;
        var type = $('#produitsNewBl .selectProductionProduit').length > 0 ? $('#produitsNewBl select.selectProductionProduit option:selected').parent('optgroup').data('type') : 0;
        if (isNaN(valPdt)) { valPdt = 0; }
        if (type === undefined || parseInt(type) === 0) { type = ''; }

        if (valPdt === 0) {
            valPdt = parseInt($('#selectProduitNewBl').val());
            if (isNaN(valPdt)) { valPdt = 0; }
            if (valPdt === 0) {
                alert('Erreur identification ID !');
                return false;
            }
        }

        $.fn.ajax({
            'script_execute': 'fct_bl.php',
            'arguments': 'mode=formProduitsBlManuel&id='+valPdt+'&type='+type,
            'return_id' : 'produitsNewBl',
            'done' : function () {

                formPdtAddBlListener();

            } // FIN callBack
        }); // FIN ajax
    });
} // FIN fonction

function formPdtAddBlListener() {
    "use strict";
    $('#btnAddPdtBl').show();

} // FIN fonction

function addLigneProduitBl() {
    "use strict";


    var id_bl = parseInt($('#formBl input[name=id]').val());
    if (isNaN(id_bl)) { id_bl = 0; }
    if (id_bl === 0) { alert("ID BL non identifé !"); return false; }
    var id_client = parseInt($('#formBl select[name=id_t_fact] option:selected').val());
    if (isNaN(id_client)) { id_client = 0; }
    $('#modalAddProduitBlBody input[name=id_bl]').val(id_bl);
    $('#modalAddProduitBlBody input[name=id_client]').val(id_client);

    var ln = loadingBtn($('#btnAddPdtBl'));

    // On intègre la compo ou le produit au bl
    $.fn.ajax({
        script_execute: 'fct_bl.php',
        form_id:'modalAddProduitBlBody',
        callBack : function (retour) {
            retour+='';
            if (parseInt(retour) < 1) { alert("Echec lors de l'ajout du produit au BL !"); return false; }
            if (parseInt(retour) === 2) { alert("Cette ligne de stock est déjà présente dans un BL !"); return false; }
            removeLoadingBtn(ln);
            $('#modalAddProduitBl').modal('hide');
            chargeProduitsBl();

        } // FIN callBack
    }); // FIN ajax
} // FIN fonction


function verifNumeroLotAddBl() {
    "use strict";

    // On vérifie le lot - Ajax
    var numlot = ($('#modalAddProduitBlBody input[name=num_lot]').val());
    var type = $('#produitsNewBl input[name=type_item]').val();
    if (numlot !== '') {


        if (type === 'neg') {
            verifPaletteAddBl();
            return false;
        }

        $.fn.ajax({
            script_execute: 'fct_lots.php',
            arguments: 'mode=checkNumLotExiste&numlot=' + numlot,
            callBack: function (retour) {
                retour += '';

                // Si le lot n'existe pas, on demande confirmation pour le créer
                if (parseInt(retour) === 0) {
                    if (!confirm("Le lot " + numlot + " n'existe pas !\r\nSouhaitez-vous le créer automatiquement ?")) {
                        return false;
                    }
                    verifPaletteAddBl();
                } else {
                    verifPaletteAddBl();
                }

            } // FIN callBack
        }); // FIN ajax

    } else {
        if (type === '' || type === 'pdt') {
            if (!confirm('ATTENTION NUMÉRO DE LOT ABSENT !\r\nAucune traçabilité ne sera disponible.\r\nContinuer malgré tout ?')) {
                return false;
            } else {
                verifPaletteAddBl();
                return false;
            }
        }
        verifPaletteAddBl();
    } // FIN test numéro de lot
} // FIN fonction


function verifPaletteAddBl() {
    "use strict";

    // On vérifie le lot - Ajax
    var palette = ($('#produitsNewBl input[name=num_palette]').val());
    if (palette !== '') {

        var id_client = parseInt($('#formBl select[name=id_t_fact] option:selected').val());
        if (isNaN(id_client)) {
            id_client = 0;
        }
        var infoclient = id_client > 0 ? ' pour le client sélectionné ! ' : ', spécifiez le client.';
        $.fn.ajax({
            script_execute: 'fct_palettes.php',
            arguments: 'mode=checkNumeroPaletteExiste&palette=' + palette + '&id_client=' + id_client,
            callBack: function (retour) {
                retour += '';

                // Si la palette n'existe pas, on demande confirmation pour le créer
                if (parseInt(retour) === 0) {
                    if (!confirm("La palette " + palette + " n'existe pas pour ce client !\r\nSouhaitez-vous la créer automatiquement ?")) {
                        return false;
                    }
                    addLigneProduitBl();
                } else if (parseInt(retour) > 1) {
                    alert("Il y a actuellement " + retour + " palettes N°" + palette + " non expédiées" + infoclient);
                    return false;
                } else {
                    addLigneProduitBl();
                }

            } // FIN callBack
        }); // FIN ajax
    } else {
        addLigneProduitBl();
    } // FIN test numéro de lot

} // FIN fonction

function updTarifsClientLignes() {
    "use strict";

    $('.puLigne').each(function() {
        var pu = parseFloat($(this). val());
        if (pu > 0) {
            var id_ligne_bl = parseInt($(this).parents('tr').data('id-ligne'));
            if (isNaN(id_ligne_bl)) { id_ligne_bl = 0; }
            if (id_ligne_bl === 0) { alert("ERREUR !\r\nIdentification de la ligne impossible !"); return false; }
            $.fn.ajax({
                'script_execute': 'fct_bl.php',
                'arguments': 'mode=changePuLigne&id_ligne_bl='+id_ligne_bl+'&pu='+pu,
                'callBack' : function (retour) {
                    retour+='';
                    if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de l'enregistrement des modifications.");}
                    recalculeTotaux();
                } // FIN callBack
            }); // FIN ajax
        }
    });

} // FIN fonction

// listener de la modale d'association des commandes web à la ligne de bl
function webOrdersListener() {
    "use strict";
    $('#modalInfo .selectpicker').selectpicker('render');

    // Selection d'une commande web : affichage des lignes
    $('#webOrders').change(function() {
        var id_order = parseInt($(this).val());
        if (isNaN(id_order)) { alert('ERREUR ID ORDER 0 !'); return false;}
        $.fn.ajax({
            script_execute: 'fct_web.php',
            arguments: 'mode=modaleSelectOrderDetailForBl&id_order='+id_order,
            return_id: 'webOrdersDetails',
            done : function () {
                $('#modalInfo .selectpicker').selectpicker('render');
                webOrdersListener();
            } // FIN callBack
        }); // FIN ajax
    });

    // Sélection du produit a associer à la ligne du BL
    $('#btnAssocierWebBlLigne').off("click.btnAddLigneBl").on("click.btnAddLigneBl", function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        var id_order_detail = parseInt($('#webOrderDetail').val());
        if (isNaN(id_order_detail) || id_order_detail === 0) { alert('ERREUR ID_ORDER_DETAIL 0 !'); return false; }


        var id_bl_ligne = parseInt($('#idBlLigneOrderDetail').val());
        if (isNaN(id_bl_ligne) || id_bl_ligne === 0) { alert('ERREUR ID_BL_LIGNE 0 !'); return false; }

        $.fn.ajax({
            script_execute: 'fct_web.php',
            arguments: 'mode=associeBlLigneOrderDetail&id_order_detail='+id_order_detail+'&id_bl_ligne='+id_bl_ligne,
            callBack : function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) {
                    alert(retour);
                    return false;
                }
                $('#modalInfo').modal('hide');
                chargeProduitsBl();
            } // FIN callBack
        }); // FIN ajax
    });


} // FIN fonction