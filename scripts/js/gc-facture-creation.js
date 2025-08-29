/**
 ------------------------------------------------------------------------
 JS - Facture ADD (BO)

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

    updAdressesClt();

    $('select[name=id_pdt]').change(function() {

       var objDom = $(this);
       var id_pdt = parseInt($(this).val());
       if (id_pdt > 0) {
           $.fn.ajax({
               'script_execute': 'fct_tarifs.php',
               'arguments': 'mode=getTarifFournisseurByProduct&id_pdt='+id_pdt+'&id_frs=0',
               'callBack': function (retour) {
                   retour+= '';

                   var valeur = retour !== '' ? retour : '';
                   objDom.parents('tr').find('input[name=pa_ht]').val(valeur);

               }
           });
       }
    });


    // Quand on change de client
    $('#id_tiers_livraison').change(function () {
        // On met à jour les adresses dispos
        updAdressesClt();
        updTransporteurClt();

    }); // FIN selection client

    // Ajout d'un produit
    $('.btnAjouterProduit').click(function () {

        var id_pdt= parseInt($(this).parents('tr').find('select[name=id_pdt]').val());
        if (isNaN(id_pdt)) { id_pdt = 0; }
        if (id_pdt === 0) { return false; }

        var qte = parseInt($(this).parents('tr').find('input[name=qte]').val());
        if (isNaN(qte) || qte === 0) { qte = 1; }

        var id_pays = parseInt($(this).parents('tr').find('select[name=id_pays]').val());
        if (isNaN(id_pays)) { id_pays = 0; }

        var nb_colis = parseInt($(this).parents('tr').find('input[name=nb_colis]').val());
        if (isNaN(nb_colis)) { nb_colis = 0; }

        var poids = parseFloat($(this).parents('tr').find('input[name=poids]').val());
        if (isNaN(poids)) { poids = 0; }

        var pu_ht = parseFloat($(this).parents('tr').find('input[name=pu_ht]').val());
        if (isNaN(pu_ht)) { pu_ht = 0; }

        var pa_ht = parseFloat($(this).parents('tr').find('input[name=pa_ht]').val());
        if (isNaN(pa_ht)) { pa_ht = 0; }

        var numlot = $(this).parents('tr').find('input[name=numlot]').val();

        // On ajoute la ligne
        $.fn.ajax({
            'script_execute': 'fct_factures.php',
            'arguments': 'mode=addLigneNouvelleFacture&id_pdt='+id_pdt+'&id_pays='+id_pays+'&numlot='+numlot+'&nb_colis='+nb_colis+'&qte='+qte+'&poids='+poids+'&pu_ht='+pu_ht+'&pa_ht='+pa_ht,
            'callBack': function (retour) {
                retour+= '';

                var retours = retour.split('^');
                var retour_tr = retours[0];
                var retour_input = retours[1];
                if (retour_input === undefined) { return false; }



                $('#listeProduitsFacture').append(retour_tr);
                $('#produits').append(retour_input);

                videForumulaire();
                listenerListeProduits();
                calculePrix();

            }
        });



    }); // FIN ajout produit


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

        calculePrix();

    }); // FIN changmeent client facturé



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


function videForumulaire() {

    $('.formulaire-table select').selectpicker('val', 0);
    $('.formulaire-table input').val('');
    $('.formulaire-table input[name=qte]').val('1');

} // FIN fonction

function listenerListeProduits() {

    // Supprime une ligne produit
    $('.btnSupprLigneFacture').off("click.btnSupprLigneFacture").on("click.btnSupprLigneFacture", function(e) {
        e.preventDefault();

        if (!confirm("Supprimer cette ligne ?")) { return false; }

        var id = parseInt($(this).parents('tr').find('.id_pdt').data('id'));
        if (isNaN(id)) { id = 0; }
        if (id === 0) { return false; }

        $(this).parents('tr').remove();
        $('#produits .pdt'+id).remove();

    });


    // Enregistrement de la facture
    $('#btnEnregistreFacture').off("click.btnEnregistreFacture").on("click.btnEnregistreFacture", function(e) {
        e.preventDefault();
        // Si pas de produits
        if ($('.id_pdt').length === 0) {
            alert('Aucun produit !');
            return false;
        }

        // Si pas de numéro de commande, on demande confirmation
        var numcmd = $('input[name=num_cmd]').val();
        if (numcmd === '') {
            if (!confirm("Aucun numéro de commande spécifié...\r\nContinuer quand même ?")) { return false; }
        }

        // ON vérifie qu'on a bien au moins un client facturé...
        var id_t_fact = parseInt($('select[name=id_t_fact]').val());
        var id_t_livr = parseInt($('select[name=id_t_livr]').val());
        if (isNaN(id_t_fact)) { id_t_fact = 0; }
        if (isNaN(id_t_livr)) { id_t_livr = 0; }

        if (id_t_livr === 0 && id_t_fact === 0) {
            alert('Sélectionnez au moins un client pour enregistrer...');
            return false;
        }


        $.fn.ajax({
            'script_execute': 'fct_factures.php',
            'form_id': 'formFacture',
            'callBack' : function (retour) {
                retour+= '';

                if (parseInt(retour) !== 1) {
                    alert("Echec de l'enregistrement de la facture !");
                    return false;
                }

                $('body').css('cursor', 'wait');
                $(location).attr('href',"gc-factures.php");


            } // FIN callBack
        }); // FIN ajax

    }); // FIN enregistrer le BL

} // FIN fonction

// On recalcule les prix (fonction qui rempli les prix à zéro avec le client sélectionné)
function calculePrix() {
    "use strict";
    var na = '<span class="gris-9">&mdash;</span>';
    var id_client = parseInt($('select[name=id_t_fact]').val());
    if (isNaN(id_client)) { id_client = 0; }

    // Pas de client ; on met à -- les tarifs à zéro
    if (id_client === 0) {
        $('#listeProduitsFacture .pu_ht').each(function () {
            var id_pdt = parseInt($(this).parents('tr').find('.id_pdt').data('id'));
            if (isNaN(id_pdt)) { id_pdt = 0; }
            var pu_ht = parseFloat($(this).text());
            if (isNaN(pu_ht)) { pu_ht = 0; }
            if (pu_ht === 0) {
                $('#listeProduitsFacture .pu_ht').html(na);
                $('#listeProduitsFacture .total_prix').html(na);

                var jsonPdt = $.parseJSON($('#formFacture .pdt'+id_pdt).val());
                jsonPdt.pu_ht = pu_ht;
                $('#formFacture .pdt'+id_pdt).val(JSON.stringify(jsonPdt));
            }
        });
        return false;
    }

    // Pour chaque ligne pour laquelle le prix est à zéro on récupère le tarif client
    $('#listeProduitsFacture .pu_ht').each(function () {

        var pu_ht = parseFloat($(this).text());
        if (isNaN(pu_ht)) { pu_ht = 0; }
        if (pu_ht === 0) {

            var id_pdt = parseInt($(this).parents('tr').find('.id_pdt').data('id'));
            if (isNaN(id_pdt)) { id_pdt = 0; }
            if (id_pdt === 0) { return false; }

            var objDom_pu_ht = $(this);
            var objDom_pu_total = $(this).parents('tr').find('.total_prix');
            var qte = parseInt($(this).parents('tr').find('.qte').text());
            if (isNaN(qte) || qte === 0) { qte = 1; }

            $.fn.ajax({
                'script_execute': 'fct_tarifs.php',
                'arguments': 'mode=getTarifClientByProduct&id_clt='+id_client+'&id_pdt='+id_pdt,
                'callBack' : function (tarif) {
                    tarif+= '';
                    var tarif_float = parseFloat(tarif);
                    if (isNaN(tarif_float) || tarif_float === 0) { return false; }
                    objDom_pu_ht.text(tarif_float);
                    objDom_pu_total.text(tarif_float * qte);

                    var jsonPdt = $.parseJSON($('#formFacture .pdt'+id_pdt).val());
                    jsonPdt.pu_ht = tarif_float;
                    $('#formFacture .pdt'+id_pdt).val(JSON.stringify(jsonPdt));


                } // FIN callBack
            }); // FIN ajax

        }

    });


} // FIN fonction