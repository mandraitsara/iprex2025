/**
 ------------------------------------------------------------------------
 JS - Upd facture/avoir

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

    listenerTableau();

    $('.btnAddLigne').off("click.btnAddLigne").on("click.btnAddLigne", function(e) {
        e.preventDefault();

        var htmlLigne = '<tr class="ligne">'+$('#htmlLigne .ligne').html()+'</tr>';



        $('#lignesUpdFacture tfoot').hide();
        $('#lignesUpdFacture tbody').append(htmlLigne);
        $('#lignesUpdFacture tbody textarea:last-child').focus();
        $('.selectpicker').selectpicker('render');
        $('#infoNewLigne').show();
        listenerTableau();

    });
    $('.btnAnnuler').off("click.btnAnnuler").on("click.btnAnnuler", function(e) {
        return confirm("Abandonner les modifications en cours ?");
    });

    // Changement du prix d'achat
    $('.paLigne').blur(function () {
       savePaLigne($(this));
    }); // FIN changement prix achat

    $('.idfrs').change(function(e) {
        e.stopImmediatePropagation();
        var id_frs = parseInt($(this).val());
        if (isNaN(id_frs)) { alert('ERREUR id frs inconnu !'); }
        var id_ligne =parseInt($(this).data('id-ligne'));
        $.fn.ajax({
            script_execute: 'fct_factures.php',
            arguments: 'mode=updFrsLigne&id_ligne='+id_ligne+'&id_frs='+id_frs,
            callBack: function (retour) {
                retour += '';
                if (parseInt(retour) !== 1) { alert(retour);}
            }
        });
    });

    $('.btnUpdFacture').off("click.btnUpdFacture").on("click.btnUpdFacture", function(e) {
        e.preventDefault();

        var erreur = false;

        $('#lignesUpdFacture tbody tr.ligne').each(function () {
            var txt = $(this).find('textarea').val().trim();
            var qte = parseFloat($(this).find('.qte').val());
            var puht = parseFloat($(this).find('.puht').val());
            if (txt === '' || txt === undefined || isNaN(qte) || isNaN(puht) || qte <= 0 || puht === 0) { erreur = true; }
        });

        if (erreur) {
            alert("Certains champs sont vides...\r\nLe document ne peut être enregistré.");
            return false;
        }

        var ln = loadingBtn($(this));
        submitFactureFormWithAjaxAndLoadingState(ln)
    });

    $('.btnRegenere').off("click.btnRegenere").on("click.btnRegenere", function (e) {
        e.preventDefault();

        var ln = loadingBtn($('.btnRegenere'));
        submitFactureFormWithAjaxAndLoadingState(ln)
    });

    function submitFactureFormWithAjaxAndLoadingState(ln) {
        $.fn.ajax({
            script_execute: 'fct_factures.php',
            form_id: 'formUpdFacture',
            callBack: function (retour) {
                retour += '';
                removeLoadingBtn(ln);

                if (parseInt(retour) !== 1) {
                    alert('Enregistrement du document échoué !\r\nCode erreur : ' + retour);
                    return false;
                }

                regenererPdf();

            }
        });
    }
}); // FIN ready


function regenererPdf(ln) {
    "use strict";
    var id_facture = parseInt($('#formUpdFacture input[name=id_facture]').val());
    // On regénère le PDF
    $.fn.ajax({
        script_execute: 'fct_factures.php',
        arguments: 'mode=regenerePdfFacture&id=' + id_facture,
        callBack: function(retour) {
            retour+='';
            if (retour.substr(0,4) === 'DEV#') {
                console.log('Mode DEV - Redirection bloquée !');
                if (ln !== undefined) {
                    removeLoadingBtn(ln);
                }
                return false;
            }
            var filters = $('#formUpdFacture input[name=filters]').val();
            $(location).attr('href','gc-factures.php?filters='+filters);
        }
    });
} // FIN fonction

// Calcule les totaux des lignes
function calculeTotauxLignes() {
    "use strict";
    $('#lignesUpdFacture tbody tr.ligne').each(function () {
        var qte = parseFloat($(this).find('.qte').val());
        var poids = parseFloat($(this).find('.poids').val());
        var puht = parseFloat($(this).find('.puht').val());
        var is_vendu_piece = parseInt($(this).data('piece')) === 1;
        if (isNaN(qte)) { qte = 0; }
        if (isNaN(poids)) { poids = 0; }
        if (isNaN(puht)) { puht = 0; }
        var mult = is_vendu_piece ? qte : poids;
        var total = (mult * puht).toFixed(2);
        $(this).find('.total').text(total+' €');
        
        var totalHT = (mult * puht).toFixed(2);
 
        var ht = parseFloat($(this).find('.totalHT').val());
        
    });

} // FIN fonction


function listenerTableau() {
    "use strict";

    $('#lignesUpdFacture .qte, #lignesUpdFacture .puht').keyup(function (e) {
        calculeTotauxLignes();
    });



    $('.btnSupprLigne').off("click.btnSupprLigne").on("click.btnSupprLigne", function(e) {
        e.preventDefault();

        if (!confirm("Supprimer cette ligne ?")) { return false; }

        $(this).parents('tr').find('.qte').val(0);
        $(this).parents('tr').find('.puht').val(0);
        $(this).parents('tr').removeClass('ligne').hide();

        if ($('#lignesUpdFacture tbody tr.ligne').length === 0) {
            $('#lignesUpdFacture tfoot').show();
            $('#infoNewLigne').hide();
        }

    });

}

function savePaLigne(objDom) {
    "use strict";

    var pa = parseFloat(objDom.val());

    var id_ligne = parseInt(objDom.data('id-ligne'));
    if (isNaN(id_ligne)) { id_ligne = 0; }

    var id_produit = parseInt(objDom.data('id-produit'));
    if (isNaN(id_produit)) { id_produit = 0; }

    // Si différent de l'ancienne valeur
    var oldDom = objDom.parents('tr').find('.paLigneOld');
    var old = parseFloat(oldDom.val());
    if (isNaN(old)) { old = 0; }

    if (id_ligne === 0) { alert("ERREUR !\r\nIdentification de la ligne impossible !"); return false; }
    $.fn.ajax({
        'script_execute': 'fct_factures.php',
        'arguments': 'mode=changePaLigne&id_ligne='+id_ligne+'&pa='+pa,
        'callBack' : function (retour) {
            retour+='';
            if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de l'enregistrement des modifications.");}

        } // FIN callBack
    }); // FIN ajax


    if (old !== pa && pa > 0) {

        var id_frs = parseInt(objDom.parents('tr').next().find('select.idfrs').val());
        if (isNaN(id_frs)|| id_frs === 0) {id_frs = 0; }

        if (old > 0) {
            if (confirm("ATTENTION !\r\nCe prix est différent du tarif fournisseur...\r\nMettre à jour le tarif fournisseur ?")) {
                oldDom.val(pa); // On met à jour la référence dans la ligne
                if (id_produit === 0) { alert("ERREUR !\r\nIdentification du produit impossible !"); return false; }

                $.fn.ajax({
                    'script_execute': 'fct_tarifs.php',
                    'arguments': 'mode=updTarifFrs&id_frs='+id_frs+'&pa='+pa+'&id_produit='+id_produit,
                    'callBack' : function (retour) {
                        retour+='';
                        if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de la mise à jour du tarif fournisseur."); return false; }

                        // On met à jour ce prix pour toutes les lignes du même produit et du même frs (sans avoir à refaire F5)
                        $('.lignePdt'+id_produit+'.ligneFrs'+id_frs).find('input.paLigne').val(pa);

                    } // FIN callBack
                }); // FIN ajax

            } // FIN confirmation
        } else {

                oldDom.val(pa); // On met à jour la référence dans la ligne
                if (id_produit === 0) { alert("ERREUR !\r\nIdentification du produit impossible !"); return false; }

                $.fn.ajax({
                    'script_execute': 'fct_tarifs.php',
                    'arguments': 'mode=updTarifFrs&id_frs='+id_frs+'&pa='+pa+'&id_produit='+id_produit,
                    'callBack' : function (retour) {
                        retour+='';
                        if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de la mise à jour du tarif fournisseur."); return false; }

                        // On met à jour ce prix pour toutes les lignes du même produit (sans avoir à refaire F5)
                        $('.lignePdt'+id_produit+'.ligneFrs'+id_frs).find('input.paLigne').val(pa);

                    } // FIN callBack
                }); // FIN ajax


        }


    } // FIN test PU différent du tarif client
    return true;
} // FIN fonction


$(document).ready(function(){
// Changement de la quantité
$('.qte').blur(function () {
    
   saveQteLigne($(this));

}); // FIN changement qté



})

function saveQteLigne(objDom) {
    "use strict";
    var qte = parseInt(objDom.val());
    console.log(qte);
    var id_ligne_fct = parseInt(objDom.parents('tr').data('id-ligne'));
    if (isNaN(qte)) { qte = ''; }
    if (qte === '') { return false; }
    if (isNaN(id_ligne_fct)) { id_ligne_fct = 0; }
    if (id_ligne_fct === 0) { alert("ERREUR !\r\nIdentification de la ligne impossible !"); return false; }

    // Si on a mis zéro... suppression
    if (qte === 0) {

        if (!confirm("ATTENTION !\r\nQuantité nulle : souhaitez-vous supprimer cette ligne du BL ?")) { return false; }
        var ligneDom = objDom.parents('tr');
        $.fn.ajax({
            'script_execute': 'fct_factures.php',
            'arguments': 'mode=supprLigneBl&id_ligne_bl='+id_ligne_fct,
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
            'script_execute': 'fct_factures.php',
            'arguments': 'mode=changeQteLigne&id_ligne_fct='+id_ligne_fct+'&qte='+qte,
            'callBack' : function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de l'enregistrement des modifications.");}
                recalculeTotaux();
            } // FIN callBack
        }); // FIN ajax
    } // FIN test > 0
    return true;
} // FIN fonction
