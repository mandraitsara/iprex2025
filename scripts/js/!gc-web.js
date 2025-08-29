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

    $('#modalInfo .modal-dialog').addClass('modal-xxl');
    $('#modalInfo .modal-title').html('<i class="fa fa-globe mr-1"></i> Détail de la commande Web');
    $('#modalInfo .modal-footer').hide();

    chargeListeOrder();

    $('.btnRecherche').click(function () {
        chargeListeOrder();
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


// Charge la liste des Orders
function chargeListeOrder() {
    "use strict";

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

    // Pagination Ajax
    $(document).on('click','#listeOrders .pagination li a',function(e){
        e.stopImmediatePropagation();
        if ($(this).attr('data-url') === undefined) { return false; }
        // on affiche le loading d'attente
        $('#listeOrders').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        // on fait l'appel ajax qui va rafraichir la liste
        $.fn.ajax({script_execute:'fct_web.php'+$(this).attr('data-url'),return_id:'listeOrders',
        done:function () {
            listeOrderListener();
        }
        });
        // on désactive le lien hypertexte
        return false;
    }); // FIN pagination ajax

    // Détails
    $('.btnDetails').off("click.clickbtnDetails").on("click.clickbtnDetails", function(e) {
        e.preventDefault();

        var id = parseInt($(this).parents('tr').data('id'));
        if (isNaN(id)) { id = 0; }
        if (id === 0) { alert('Identification ID impossible !'); return false; }

        chargeModaleOrder(id);

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

} // FIN fonction