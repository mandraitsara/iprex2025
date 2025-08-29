/**
 ------------------------------------------------------------------------
 JS - Commandes Web

 Copyright (C) 2021 Koesio
 https://www.koesio.com/
 ------------------------------------------------------------------------

 @author    Cédric Bouillon
 @copyright Copyright (c) 2021 Koesio
 @version   1.0
 @since     2021

 ------------------------------------------------------------------------
 */
$(document).ready(function() {
    "use strict";

/*    $('#modalInfo .modal-dialog').addClass('modal-xxl');
    $('#modalInfo .modal-title').html('<i class="fa fa-globe mr-1"></i> Détail de la commande Web');
    $('#modalInfo .modal-footer').hide();*/

    chargeListeOrder();

/*    $('.btnRecherche').click(function () {
        chargeListeOrder();
    });*/

    $('.btnSelectAll').click(function() {
        if ($(this).find('i').hasClass('fa-square')) {
            $(this).find('i').removeClass('far fa-square').addClass('fa fa-check-square');
            $(this).find('span').text('tout');
            $('tr.order-detail .icheck').iCheck('uncheck');
        } else {
            $(this).find('i').removeClass('fa fa-check-square').addClass('far fa-square');
            $(this).find('span').text('aucun');
            $('tr.order-detail .icheck').iCheck('check');
        }
        $(this).blur();
    });


    $('.btnCreerBl').click(function() {

        $('#listeBlsWeb').hide();

        var nb_coches  = $('tr.order-detail .icheck:checked').length;
        if (nb_coches === 0) {
            alert("Aucune ligne de commande sélectionnée !");
            return false;
        }

        var ln = loadingBtn($(this));

        $.fn.ajax({
            script_execute: 'fct_web.php',
            arguments: 'mode=getSelectListeBlsWeb',
            return_id: 'listeBlsWeb',
            done: function () {
                selectBlListener();

            },
            callBack: function (retour) {
                retour+= '';
                removeLoadingBtn(ln);
                if (parseInt(retour) === 1) {
                    creerBlDepuisOds();
                } else {
                    $('#listeBlsWeb').show();
                }
            } // FIN Callback
        }); // FIN ajax

        return false;

    });


    // Export PDF
    $('.btnExportPdf').click(function() {

        var objetDomBtnExport = $(this);
        objetDomBtnExport.find('i').removeClass('fa-file-pdf').addClass('fa-spin fa-spinner');
        objetDomBtnExport.attr('disabled', 'disabled');

        $('#filtres input[name=mode]').val('exportPdf');

        $.fn.ajax({
            script_execute: 'fct_web.php',
            form_id: 'filtres',
            callBack : function (url_fichier) {
                objetDomBtnExport.find('i').removeClass('fa-spin fa-spinner').addClass('fa-file-pdf');
                objetDomBtnExport.prop('disabled', false);
                $('#filtres input[name=mode]').val('getListeCommandes');
                $('#lienPdf').attr('href', url_fichier);
                $('#lienPdf')[0].click();

            } // FIN callBack
        }); // FIN ajax
    }); // FIN export

}); // FIN ready


function selectBlListener() {
    "use strict";

    $('.selectpicker').selectpicker('refresh');
    $('.btnBlSelected').click(function() {
        var id_bl = parseInt($('.selectBl2add').selectpicker('val'));
        if (isNaN(id_bl)) { alert('Erreur idenfitication selecteur !'); return false;}
        if (id_bl === 0) { creerBlDepuisOds(); }
        else { addBlDepuisOds(id_bl); }
    });
}

function getIdsOdsCheckeds() {
    "use strict";
    var ids_od = '';
    $('tr.order-detail .icheck:checked').each(function () {
        var id_od = parseInt($(this).parents('.order-detail').data('id'));
        if (id_od === 0 || isNaN(id_od)) { alert('ERREUR ID !'); }
        ids_od+=id_od+',';
    });
    if (ids_od.slice(-1) === ',') {
        ids_od = ids_od.slice(0, -1);
    }

    return ids_od;
} // FIN fonction

function addBlDepuisOds(id_bl) {
    "use strict";

    if (isNaN(id_bl) || id_bl === 0) {
        alert('ERREUR identification BL !');
        return false;
    }

    var ids_od = getIdsOdsCheckeds();

    $.fn.ajax({
        script_execute: 'fct_web.php',
        arguments: 'mode=addBlFromOrderDetails&ids_od='+ids_od+'&id_bl='+id_bl,
        callBack: function (retour) {
            retour+= '';

            if (parseInt(retour) !== 1) { alert(retour); return false; }
            chargeListeOrder();
        } // FIN Callback
    }); // FIN ajax
} // FIN fonction

function creerBlDepuisOds() {
    "use strict";
    var ids_od = getIdsOdsCheckeds();

    $.fn.ajax({
        script_execute: 'fct_web.php',
        arguments: 'mode=creerBlFromOrderDetails&ids_od='+ids_od,
        callBack: function (retour) {
            retour+= '';

            if (parseInt(retour) !== 1) { alert(retour); return false; }
            chargeListeOrder();
        } // FIN Callback
    }); // FIN ajax

} // FIN fonction

// Charge la liste des Orders
function chargeListeOrder() {
    "use strict";

    $('#listeBlsWeb').hide();

    $.fn.ajax({
        script_execute: 'fct_web.php',
        form_id: 'filtres',
        return_id: 'listeOrders',
        done: function () {

            listeOrderListener();

        }
    }); // FIN ajax

} // FIN fonction


// Listener de la liste des ORDERS
function listeOrderListener() {
    "use strict";

    $("input[type=checkbox].icheck").iCheck(
        {checkboxClass: "icheckbox_square-blue"}
    );



    $('.btnDesassocieLigneBl').off("click.btnDesassocieLigneBl").on("click.btnDesassocieLigneBl", function(e) {
        e.preventDefault();
        if (!confirm("Supprimer ce produit du BL ?")) { return false; }
        var id_order_detail = parseInt($(this).data('id-od'));
        if (isNaN(id_order_detail) || id_order_detail === 0) { alert('ERREUR ID ORDER DETAIL 0 !');return false; }
        $.fn.ajax({
            script_execute: 'fct_web.php',
            arguments: 'mode=supprimeWebLigne&id_order_detail=' + id_order_detail,
            callBack: function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) {
                    alert(retour);
                    return false;
                }
                chargeListeOrder();
            }
        });
    });

    // Traitée
    $('.btnTraitee').off("clickbtnTraitee").on("click.clickbtnTraitee", function(e) {
        e.preventDefault();

        if (!confirm("Confirmer le traitement de cette commande Web ?\r\nElle n'apparaitra plus ici.")) { return false; }

        var id = parseInt($(this).parents('tr').data('id'));

        if (isNaN(id)) { id = 0; }
        if (id === 0) { alert('Identification ID impossible !'); return false; }

        $.fn.ajax({
            script_execute: 'fct_web.php',
            arguments: 'mode=orderTraitee&id='+id,
            callBack : function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) { alert(retour); return false; }

                chargeListeOrder();

            } // FIN callBack
        }); // FIN ajax

    }); // FIN supprimer




} // FIN listener

/*
function modaleOrderListener(id) {
    "use strict";

    if (isNaN(id)) {
        alert('ERREUR identification id !');
        return false;
    }

    $('.btnDesassocieLigneBl').off("click.btnDesassocieLigneBl").on("click.btnDesassocieLigneBl", function(e) {
        e.preventDefault();
        var id_order_detail = parseInt($(this).data('id-od'));
        if (isNaN(id_order_detail) || id_order_detail === 0) { alert('ERREUR ID ORDER DETAIL 0 !');return false; }
        $.fn.ajax({
            script_execute: 'fct_web.php',
            arguments: 'mode=desassocieWebLigne&id_order_detail=' + id_order_detail,
            callBack: function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) {
                    alert(retour);
                    return false;
                }
                chargeModaleOrder(id)
            }
        });
    });

} // FIN listener
*/

/*

function chargeModaleOrder(id) {
    "use strict";

    if (isNaN(id)) {
        alert('ERREUR identification id !');
        return false;
    }

    $.fn.ajax({
        script_execute: 'fct_web.php',
        arguments: 'mode=modaleDetailOrder&id='+id,
        return_id: 'modalInfoBody',
        done : function () {
            $('#modalInfo').modal('show');
            modaleOrderListener(id);
        } // FIN callBack
    }); // FIN ajax

} // FIN fonction*/
