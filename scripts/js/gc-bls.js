/**
 ------------------------------------------------------------------------
 JS - BL (BO)

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

    $('#modalEnvoiMail .btnMarquerEnvoye').removeClass('d-none');

    chargeListeBl();

    $('.btnMarquerEnvoye').off("click.btnMarquerEnvoye").on("click.btnMarquerEnvoye", function(e) {
        e.preventDefault();
        var id_bl = parseInt($('#idBlFromModaleMail').val());
        if (isNaN(id_bl) || id_bl === 0) { alert('BL non identifié !'); return false; }

        $.fn.ajax({
            script_execute: 'fct_bl.php',
            arguments: 'mode=marquerBlEnvoye&id='+id_bl,
            callBack: function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) {
                    alert(retour);
                    return false;
                }
                $('#modalEnvoiMail').modal('hide');
                var objDom = $('.btnEnvoiBlMail[data-id-bl='+id_bl+']');
                objDom.removeClass('btn-secondary btn-success').addClass('btn-success');
            }
        });

    });

    $('.btnRecherche').click(function () {
        chargeListeBl();
    });

    $('form#filtres input[type=text]').keyup(function (e) {
        var code = (e.keyCode ? e.keyCode : e.which);
        if (code === 13) {
            e.preventDefault();

            $('form#filtres input[name=date_du]').val('');
            $('form#filtres input[name=date_au]').val('');
            $('form#filtres input[name=numcmd]').val('');
            $('form#filtres select[name=id_client]').selectpicker('val', 0);
            $('form#filtres select[name=chiffre]').selectpicker('val', -1);
            $('form#filtres select[name=facture]').selectpicker('val', -1);
            $('form#filtres select[name=statut]').selectpicker('val', -1);

            $('.btnRecherche').trigger('click');
            return false;
        }
    });

    // Nettoyage du contenu de la modale à sa fermeture
    $('#modalEnvoiMail').on('hidden.bs.modal', function (e) {
        $('#modalEnvoiMailBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
    }); // F

}); // FIN ready


// Charge la liste des BL
function chargeListeBl() {
    "use strict";

    $('#listeBls').html('<i class="fa fa-spin fa-spinner"></i> Recherche des BL en cours, veuillez patienter...');

    $.fn.ajax({
        'script_execute': 'fct_bl.php',
        'form_id': 'filtres',
        'return_id': 'listeBls',
        'done': function () {

            listeBlListener();

        }
    }); // FIN ajax

} // FIN fonction


// Listener de la liste des BLs
function listeBlListener() {
    "use strict";


    $("input[type=checkbox].icheck").iCheck(
        {checkboxClass: "icheckbox_square-blue"}
    );


    $('.btnEnvoiBlMail').off("click.btnEnvoiBlMail").on("click.btnEnvoiBlMail", function(e) {

        var objDom = $(this);
        e.preventDefault();

        var id_bl = objDom.data('id-bl');
        if (isNaN(id_bl) || id_bl === 0) { alert('Identification du BL impossible !');return false; }

        $.fn.ajax({
            'script_execute': 'fct_bl.php',
            'arguments': 'mode=modalEnvoiBlMail&id_bl='+id_bl,
            'return_id': 'modalEnvoiMailBody',
            'done': function () {
                $('#modalEnvoiMail').modal('show');
                $('.selectpicker').selectpicker('render');
                $('.togglemaster').bootstrapToggle();

                $('.btnEnvoiMail').off("click.btnEnvoiMail").on("click.btnEnvoiMail", function(e) {

                    e.preventDefault();

                    var id_ctc = $('#modalEnvoiMailBody select').val();
                    var cc = $('#modalEnvoiMailBody .togglemaster').is(':checked') ? 1 : 0;
                    var mail = $('#emailcustom').val();
                    var regexMail           = new RegExp('^[A-Za-z0-9._-]+@[A-Za-z0-9._-]{2,}\\.[A-Za-z]{2,4}$');
                    if (mail !== '' && !regexMail.test(mail)) {
                        alert('Adresse e-mail invalide !');
                        return false;
                    }
                    if (cc === '' && mail === '') {
                        alert('Aucun destinataire !');
                        return false;
                    }

                    $.fn.ajax({
                        'script_execute': 'fct_bl.php',
                        'arguments': 'mode=envoiPdfBlClient&id_bl=' + id_bl+'&id_ctc='+id_ctc+'&cc='+cc+'&mail='+mail,
                        'callBack': function (retour) {
                            retour+='';
                            if (parseInt(retour) === 1) {
                                $('#modalEnvoiMail').modal('hide');
                                objDom.removeClass('btn-secondary btn-success').addClass('btn-success');

                            } else {
                                alert("Envoi du BL échoué !");
                            }
                            return false;
                        }
                    }); // FIN ajax


                }); // Fin bouton envoi

            } // FIN done
        });// FIN ajax


    }); // Fin chargement du contenu de la modale

    //

    // Imprimer BL
/*    $('.btnPdfBl').off("click.btnpdfbl").on("click.btnpdfbl", function(e) {

        e.preventDefault();

        var id_bl = parseInt($(this).data('id'));
        if (isNaN(id_bl)) { id_bl = 0; }
        if (id_bl === 0) { alert("Erreur d'identification du BL !"); return false; }

        var objetDomBtn = $(this).find('i.fa');
        objetDomBtn.removeClass('fa-file-pdf').addClass('fa-spin fa-spinner');

        $.fn.ajax({
            'script_execute': 'fct_bl.php',
            'arguments': 'mode=getUrlBlPdf&id_bl='+id_bl,
            'callBack' : function (url) {
                url+='';
                window.addEventListener('focus', window_focus, false);
                function window_focus(){
                    window.removeEventListener('focus', window_focus, false);
                    URL.revokeObjectURL(url);
                    objetDomBtn.removeClass('fa-spin fa-spinner').addClass('fa-file-pdf');
                }
                location.href = url;

            } // FIN callBack
        }); // FIN ajax

    }); // FIN imprimer BL*/

    // Pagination Ajax
    $(document).on('click','#listeBls .pagination li a',function(){
        if ($(this).attr('data-url') === undefined) { return false; }
        // on affiche le loading d'attente
        $('#listeBls').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        // on fait l'appel ajax qui va rafraichir la liste
        $.fn.ajax({script_execute:'fct_bl.php'+$(this).attr('data-url'),return_id:'listeBls',
        done:function () {
            listeBlListener();
        }
        });
        // on désactive le lien hypertexte
        return false;
    }); // FIN pagination ajax


    // Créer une facture
    $('.btnCreerFacture').off("click.btnCreerFacture").on("click.btnCreerFacture", function(e) {
        e.preventDefault();

        if ($('#listeBls .checkFacture:checked').length === 0) {
            //alert('Sélectionnez au moins un BL pour créer une facture...');
            alert('Sélectionnez un BL pour créer une facture...');
            return false;
        }


        if ($('#listeBls .checkFacture:checked').length > 1) {
            alert('Sélectionnez un seul BL pour créer une facture...');
            return false;
        }

        var id_bls = [];
        var erreur = false;
        $('#listeBls .checkFacture:checked').each(function () {
            var idbl = parseInt($(this).val());
            var deja = parseInt($(this).data('facture'));
            if (isNaN(deja)) { deja = 0; }
            if (deja === 1) {

                //alert('Facture déjà générée pour un des BL sélectionnés !');
                alert('Facture déjà générée pour le BL sélectionné !');
                erreur = true;
                return false;
            }
            id_bls.push(idbl);
        });

        if (erreur) { return false; }

        //var supprStock = confirm("Retirer les produits du stock et cloturer les palettes ?") ? 1 : 0;
        var supprStock = 1;

        var ifa = $(this).find('i.fa');
        ifa.removeClass('fa-file-invoice-dollar').addClass('fa-spin fa-spinner');

        $.fn.ajax({
            'script_execute': 'fct_factures.php',
            'arguments': 'mode=createFactureFromBls&id_bls='+id_bls+'&supprStock='+supprStock,
            'callBack' : function (retour) {

                ifa.removeClass('fa-spin fa-spinner').addClass('fa-file-invoice-dollar');
                chargeListeBl();

            } // FIN callBack
        }); // FIN ajax

    }); // FIN bouton créer une facture



    // Créer une packing list
    $('.btnCreerPackingList').off("click.btnCreerPackingList").on("click.btnCreerPackingList", function(e) {
        e.preventDefault();

        if ($('#listeBls .checkFacture:checked').length === 0) {
            alert('Sélectionnez au moins un BL pour créer une Packing List...');
            return false;
        }

        var id_bls = [];
        var id_clt = 0;
        var erreur = false;
        $('#listeBls .checkFacture:checked').each(function () {
            var idbl = parseInt($(this).val());
            var deja = parseInt($(this).data('packing'));
            var clt = parseInt($(this).data('clt'));
            if (isNaN(deja)) { deja = 0; }
            if (isNaN(clt)) { clt = 0; }

            if (clt > 0 && id_clt === 0) { id_clt = clt; }
            if (clt > 0 && id_clt > 0 && clt !== id_clt) {
                if (!confirm('Les BL sélectionnés correspondent à des clients différents !\r\nLa packing list sera adressée au premier client.\r\nContinuer ?')) {
                    erreur = true;
                    return false;
                }

            }

            if (deja === 1) {

                if (!confirm('packing List déjà générée pour un ou plusieurs des BL sélectionnés !\r\nCelles-ci seront écrasées...\r\nContinuer ?')) {
                    erreur = true;
                    return false;
                }
            }
            id_bls.push(idbl);
        });

        if (erreur) { return false; }

        var ifa = $(this).find('i.fa');
        ifa.removeClass('fa-file-alt').addClass('fa-spin fa-spinner');

        $.fn.ajax({
            'script_execute': 'fct_packing_list.php',
            'arguments': 'mode=createPackingListFromBls&id_bls='+id_bls,
            'callBack' : function (retour) {

                ifa.removeClass('fa-spin fa-spinner').addClass('fa-file-alt');
                chargeListeBl();

            } // FIN callBack
        }); // FIN ajax


    }); // FIN bouton créer une packing list



    // Supprimer un BL
    $('.btnSupprBl').off("click.btnSupprBl").on("click.btnSupprBl", function(e) {
        e.preventDefault();

        e.preventDefault();

        if (!confirm("Supprimer ce BL ?")) { return false; }

        var id = parseInt($(this).data('id'));
        if (isNaN(id)) { id = 0; }
        if (id === 0) { alert('Identification du BL impossible !'); return false; }

        $.fn.ajax({
            'script_execute': 'fct_bl.php',
            'arguments': 'mode=supprBl&id_bl='+id,
            'callBack' : function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) { alert('Echec de la suppression !'); return false; }

                chargeListeBl();

            } // FIN callBack
        }); // FIN ajax

    }); // FIN supprimer




} // FIN listener