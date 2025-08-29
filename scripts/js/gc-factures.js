/**
 ------------------------------------------------------------------------
 JS - Factures (BO)

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

    chargeListeFactures();

    $('.btnMarquerEnvoye').off("click.btnMarquerEnvoye").on("click.btnMarquerEnvoye", function(e) {
        e.preventDefault();
        var id_facture = parseInt($('#idFactureFromModaleMail').val());
        if (isNaN(id_facture) || id_facture === 0) { alert('Facture non identifiée !'); return false; }

        $.fn.ajax({
            script_execute: 'fct_factures.php',
            arguments: 'mode=marquerFactureEnvoyee&id='+id_facture,
            callBack: function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) {
                    alert(retour);
                    return false;
                }
                $('#modalEnvoiMail').modal('hide');
                var objDom = $('.btnEnvoiFactureMail[data-id-facture='+id_facture+']');
                objDom.removeClass('btn-secondary btn-success').addClass('btn-success');
            }
        });

    });


    $('#modalFactureFrais').on('hide.bs.modal', function (e) {

        $('#listeFraisFacture').html('<i class="fa fa-spin fa-spinner"></i>');
        $('#modalFactureFraisBody select[name=type]').selectpicker('val', 0);
        $('#symboleFf').html('€');
        $('#modalFactureFraisBody input[name=nom]').val('');
        $('#modalFactureFraisBody input[name=valeur]').val('');
        $('#modalFactureFraisBody input[name=id_facture]').val(0);

    });

    $('#modalAvoir').on('hide.bs.modal', function (e) {
        $('#modalAvoirBody').html('<i class="fa fa-spin fa-spinner"></i>');
        $('#modalAvoirBody input[name=mode]').val('modaleAvoir');
        $('#messageErreurConteneur').hide();
        //if (!$('.btnCreerAvoir').hasClass('d-none')) { $('.btnCreerAvoir').addClass('d-none');}
    });

    $('.btnCreerAvoir').off("click.btnCreerAvoir").on("click.btnCreerAvoir", function(e) {
        e.preventDefault();

        $('#modalAvoirBody input[name=mode]').val('creerAvoir');
        $('#messageErreurConteneur').hide();


        $.fn.ajax({
            'script_execute': 'fct_factures.php',
            'form_id': 'modalAvoirBody',
            'callBack' : function (retour) {
                retour+= '';

                $('#modalAvoirBody input[name=mode]').val('modaleAvoir');


                if (parseInt(retour) === -1) {
                    $('#messageErreurConteneur').show();
                    $('#messageErreur').text("Aucun client sélectionné !");
                    return false; }
                if (parseInt(retour) === -2) {
                    $('#messageErreurConteneur').show();
                    $('#messageErreur').text("Sélectionnez une facture ou entrez un montant libre...");
                    return false; }
                if (parseInt(retour) === -3) {
                    $('#messageErreurConteneur').show();
                    $('#messageErreur').html("Le montant saisi ne correspond pas aux lignes sélectionnées !<p class='texte-fin text-12 mb-0'>Plusieurs lignes sont sélectionnées et le montant total ne permet pas de définir sur laquelle cet écart porte. Sélectionnez une seule ligne ou corrigez le montant.</p>");
                    return false; }
                if (parseInt(retour) === -4) {
                    $('#messageErreurConteneur').show();
                    $('#messageErreur').text("Erreur d'identification du client !");
                    return false; }
                if (parseInt(retour) === -5) {
                    $('#messageErreurConteneur').show();
                    $('#messageErreur').text("Erreur d'instanciation de l'adresse du client !");
                    return false; }
                if (parseInt(retour) === -6) {
                    $('#messageErreurConteneur').show();
                    $('#messageErreur').text("Erreur d'instanciation de la facture d'origine !");
                    return false; }
                if (parseInt(retour) === -7) {
                    $('#messageErreurConteneur').show();
                    $('#messageErreur').text("Erreur d'enregistrement de l'avoir' !");
                    return false; }
                if (parseInt(retour) === -9) {
                    $('#messageErreurConteneur').show();
                    $('#messageErreur').text("Erreur lors de l'enregistrement du total de l'avoir !");
                    return false; }
                if (parseInt(retour) === 0) {
                    $('#messageErreurConteneur').show();
                    $('#messageErreur').text("Erreur lors de l'enregistrement des lignes de l'avoir !");
                    return false; }
                if (parseInt(retour) !== 1) {
                    $('#messageErreurConteneur').show();
                    $('#messageErreur').html("Une erreur est survenue !<br>Enregistrement échoué.");
                    return false; }

                $('#modalAvoir').modal('hide');
                chargeListeFactures();

            } // FIN callBack

        }); // FIN ajax

    });

    $('#modalFactureFrais select[name=type]').on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {
        e.stopPropagation();

        var symbole = parseInt($(this).val()) === 1 ? '%' : '€';
        $('#symboleFf').html(symbole);
    });


    $('#modalAvoir').on('shown.bs.modal', function (e) {

        e.preventDefault();

        // On intègre la liste des frais
        $.fn.ajax({
            'script_execute': 'fct_factures.php',
            'arguments': 'mode=modaleAvoir',
            'return_id': 'modalAvoirBody',
            'done' : function () {
                modaleAvoirListener();
            } // FIN callBack

        }); // FIN ajax

    });

    $('#modalFactureFrais').on('shown.bs.modal', function (e) {

        e.preventDefault();

        var id_facture = e.relatedTarget.attributes['data-id'] === undefined ? 0 : parseInt(e.relatedTarget.attributes['data-id'].value);
        if (isNaN(id_facture)) { id_facture = 0; }
        $('#modalFactureFraisBody input[name=id_facture]').val(id_facture);

        // On intègre la liste des frais
        $.fn.ajax({
            'script_execute': 'fct_factures.php',
            'arguments': 'mode=getFraisFactureModale&id_facture='+id_facture,
            'return_id': 'listeFraisFacture',
            'done' : function () {
              fraisModaleListener();
            } // FIN callBack

        }); // FIN ajax

    });

    // Ajout de frais additionnels
    $('.btnAjoutFraisFacture').off("click.btnAjoutFraisFacture").on("click.btnAjoutFraisFacture", function(e) {

        e.preventDefault();

        var id_facture = parseInt($('#modalFactureFraisBody input[name=id_facture]').val());
        if (isNaN(id_facture)) { id_facture = 0; }
        if (id_facture === 0) { alert('Identification de la facture impossible !'); return false; }

        var nom = $('#modalFactureFrais input[name=nom]').val();
        var valeur = parseFloat($('#modalFactureFrais input[name=valeur]').val());
        if( nom === '' || valeur === 0) { return false; }

        var ifa =$(this).find('i.fa');
        ifa.removeClass('fa-check').addClass('fa-spin fa-spinner');

        $.fn.ajax({
            'script_execute': 'fct_factures.php',
            'form_id': 'modalFactureFraisBody',
            'callBack' : function (retour) {
                retour+= '';
                ifa.removeClass('fa-spin fa-spinner').addClass('fa-check');
                if (parseInt(retour) !== 1) { alert('Echec enregistrement !'); return false; }
                $('#modalFactureFrais').modal('hide');

            } // FIN callBack

        }); // FIN ajax

    }); // FIN ajout de frais

    $('.btnRecherche').click(function () {
        chargeListeFactures();
    });

    $('form#filtres input[type=text]').keyup(function (e) {
        var code = (e.keyCode ? e.keyCode : e.which);
        if (code === 13) {
            e.preventDefault();
            $('form#filtres input[name=date_du]').val('');
            $('form#filtres input[name=date_au]').val('');
            $('form#filtres input[name=numcmd]').val('');
            $('form#filtres select[name=id_client]').selectpicker('val', 0);
            $('form#filtres select[name=reglee]').selectpicker('val', -1);
            $('form#filtres select[name=factavoirs]').selectpicker('val', '');
            $('.btnRecherche').trigger('click');
            return false;
        }
    });

    // Nettoyage du contenu de la modale à sa fermeture
    $('#modalEnvoiMail').on('hidden.bs.modal', function (e) {
        $('#modalEnvoiMailBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
    }); // FIN fermeture modale Détails

    // Nettoyage du contenu de la modale à sa fermeture
    $('#modalReglement').on('hidden.bs.modal', function (e) {
        $('#modalReglementBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        chargeListeFactures();
    }); // FIN fermeture modale Détails




    // Chargement du contenu de la modale à son ouverture
    $('#modalReglement').on('shown.bs.modal', function (e) {

        e.preventDefault();

        var id_facture = e.relatedTarget.attributes['data-id'] === undefined ? 0 : parseInt(e.relatedTarget.attributes['data-id'].value);
        if (isNaN(id_facture)) { id_facture = 0;}
        if (id_facture === 0) { alert('Identification de la facture impossible !'); return false; }


        $.fn.ajax({
            'script_execute': 'fct_factures.php',
            'arguments': 'mode=modaleReglementFacture&id_facture='+id_facture,
            'return_id': 'modalReglementBody',
            'done': function () {

                modaleReglementsListener();

            }
        }); // FIN ajax



    });

    // Règlement de la facture
    $('.btnSaveReglement').off("click.btnSaveReglement").on("click.btnSaveReglement", function(e) {

        e.preventDefault();

        $.fn.ajax({
            'script_execute': 'fct_factures.php',
            'form_id': 'modalReglementBody',
            'callBack': function (retour) {
                retour+= '';
                if (parseInt(retour) !== 1) { alert("Echec lors de l'enregistrement du règlement."); return false;}

                $('#modalReglement').modal('hide');
                chargeListeFactures();

            }
        }); // FIN ajax


    });

    // Générer l'état mensuel interbev


    $('.btnPdfInterbev').off("click.btnPdfInterbev").on("click.btnPdfInterbev", function(e) {

        e.preventDefault();

        var objdom = $(this);
        var objdom_html = objdom.html();
        objdom.html('<i class="fa fa-spin fa-spinner"></i>');
        objdom.attr('disabled', 'disabled');

        $.fn.ajax({

            'script_execute': 'fct_factures.php',
            'form_id': 'modalInterbevBody',
            'callBack': function (url_fichier) {
                url_fichier+='';
                objdom.html(objdom_html);
                objdom.prop('disabled', false);
                // Fermeture de la modale
                $('#modalInterbev').modal('hide');

                // Téléchargement du pdf
                $('#lienPdf').attr('href', url_fichier);
                $('#lienPdf')[0].click();

            }
        }); // FIN ajax
    });


}); // FIN ready


// Charge la liste des Factures
function chargeListeFactures() {
    "use strict";

    $('#listeFactures').html('<i class="fa fa-spin fa-spinner"></i> Recherche des factures en cours, veuillez patienter...');
    $('#filtres input[name=page]').val('1');
    $.fn.ajax({
        'script_execute': 'fct_factures.php',
        'form_id': 'filtres',
        'return_id': 'listeFactures',
        'done': function () {

            listeFacturesListener();

        }
    }); // FIN ajax

} // FIN fonction


// Listener de la liste des Factures
function listeFacturesListener() {
    "use strict";

    $('.btnEnvoiFactureMail').off("click.btnEnvoiFactureMail").on("click.btnEnvoiFactureMail", function(e) {

        e.preventDefault();

        var objDom = $(this);

        var id_facture = objDom.data('id-facture');
        if (isNaN(id_facture) || id_facture === 0) { alert('Identification de la facture impossible !');return false; }

        $.fn.ajax({
            'script_execute': 'fct_factures.php',
            'arguments': 'mode=modalEnvoiMail&id_facture='+id_facture,
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
                        'script_execute': 'fct_factures.php',
                        'arguments': 'mode=envoiPdfClient&id_ctc='+id_ctc+'&cc='+cc+'&id_facture='+id_facture+'&mail='+mail,
                        'callBack': function (retour) {
                            retour+='';
                            if (parseInt(retour) === 1) {
                                $('#modalEnvoiMail').modal('hide');
                                objDom.removeClass('btn-secondary btn-success').addClass('btn-success');
                            } else {
                                alert("Envoi de la facture échouée !");
                            }
                            return false;
                        }
                    }); // FIN ajax


                }); // Fin bouton envoi

            } // FIN done
        });// FIN ajax


    }); // Fin chargement du contenu de la modale

/*

    $('.btnSupprReglement').off("click.btnSupprReglement").on("click.btnSupprReglement", function(e) {

        e.preventDefault();

        var id_facture = parseInt($(this).parent().data('id'));
        if (isNaN(id_facture)) { id_facture = 0; }
        if (id_facture === 0) { alert('Echec identification facture !'); return false; }

        if (!confirm("ATTENTION\r\nSupprimer le règlement de cette facture ?")) { return false; }

        $.fn.ajax({
            'script_execute': 'fct_factures.php',
            'arguments': 'mode=supprReglementFacture&id_facture='+id_facture,
            'done' : function () {

                chargeListeFactures();

            } // FIN callBack
        }); // FIN ajax


    });*/


    // Imprimer BL
    $('.btnPdfFacture').off("click.btnpdffacture").on("click.btnpdffacture", function(e) {

        e.preventDefault();

        var id_facture = parseInt($(this).data('id'));
        if (isNaN(id_facture)) { id_facture = 0; }
        if (id_facture === 0) { alert("Erreur d'identification de la facture !"); return false; }

        var objetDomBtn = $(this).find('i.fa');
        objetDomBtn.removeClass('fa-file-pdf').addClass('fa-spin fa-spinner');

        $.fn.ajax({
            'script_execute': 'fct_factures.php',
            'arguments': 'mode=getUrlFacturePdf&id_facture='+id_facture,
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

    }); // FIN imprimer BL

    // Pagination Ajax
    $(document).on('click','#listeFactures .pagination li a',function(e){
        e.stopPropagation();
        e.stopImmediatePropagation();
        if ($(this).attr('data-url') === undefined) { return false; }
        // on affiche le loading d'attente
        $('#listeBls').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        // on fait l'appel ajax qui va rafraichir la liste

        var page = parseInt($(this).data('page'));
        $('#filtres input[name=page]').val(page);

        $.fn.ajax({script_execute:'fct_factures.php'+$(this).attr('data-url'),return_id:'listeFactures',
            done:function () {
                listeFacturesListener();
            }
        });
        // on désactive le lien hypertexte
        return false;
    }); // FIN pagination ajax

    // Supprimer une facture
    $('.btnSupprFacture').off("click.btnSupprFacture").on("click.btnSupprFacture", function(e) {

        e.preventDefault();

        var typedoc = $(this).parents('tr').find('.numfact').text().slice(0, 2) === 'AV' ? 'cet avoir' : 'cette facture';
        if (!confirm("Supprimer "+typedoc+" ?")) { return false; }

        var id = parseInt($(this).data('id'));
        if (isNaN(id)) { id = 0; }
        if (id === 0) { alert('Identification de la facture impossible !'); return false; }

        $.fn.ajax({
            'script_execute': 'fct_factures.php',
            'arguments': 'mode=supprFacture&id_facture='+id,
            'callBack' : function (retour) {
              retour+='';
              if (parseInt(retour) !== 1) { alert('Echec de la suppression !'); return false; }

              chargeListeFactures();

            } // FIN callBack
        }); // FIN ajax


    }); // FIN supprimer facture



} // FIN listener

// listener de la modale des frais
function fraisModaleListener() {
    "use strict";

    // Supprimer...
    $('.btnSupprFactureFrais').off("click.btnSupprFactureFrais").on("click.btnSupprFactureFrais", function(e) {

        e.preventDefault();

        var ln = loadingBtn($(this));

        var id_frais = parseInt($(this).data('id'));
        if (isNaN(id_frais)) { id_frais = 0; }
        if (id_frais === 0) { alert('Identification ID frais impossible !');return false; }

        var objDomTr = $(this).parents('tr');

        $.fn.ajax({
            'script_execute': 'fct_factures.php',
            'arguments': 'mode=supprFactureFrais&id_frais='+id_frais,
            'callBack' : function (retour) {
                removeLoadingBtn(ln);
                retour+='';
                if (parseInt(retour) !== 1) { alert('Echec de la suppression !'); return false; }

                objDomTr.remove();

            } // FIN callBack
        }); // FIN ajax


    });



} // FIN listner

// Listener de la modale de règlement
function modaleReglementsListener() {
    "use strict";

    $('.selectpicker').selectpicker('render');

    $( ".datepicker" ).datepicker({

        dateFormat:		"dd/mm/yy",
        dayNames:			[ "Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi" ],
        dayNamesMin:		[ "Di", "Lu", "Ma", "Me", "Je", "Ve", "Sa" ],
        dayNamesShort:		[ "Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam" ],
        firstDay:			1,
        monthNames:		[ "Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "octobre", "Novembre", "Décembre" ],
        monthNamesShort:	[ "Jan", "Fév", "Mar", "Avr", "Mai", "Juin", "Juil", "Août", "Sep", "Oct", "Nov", "Déc" ],
        nextText:			"Mois suivant",
        prevText:			"Mois précédent",
        beforeShow: function() {
            setTimeout(function(){
                $('.ui-datepicker').css('z-index', 10000);
            }, 0);
        }

    }); // FIN DatePicker

    $('.btnAjoutReglement').off("click.btnAjoutReglement").on("click.btnAjoutReglement", function(e) {
        e.preventDefault();

/*        var montant = parseFloat($('#formNewReglement input[name=montant]').val());
        var id_mode = parseInt($('#formNewReglement select[name=id_mode]').val());
        if (isNaN(montant)) { montant = 0; }
        if (isNaN(id_mode)) { id_mode = 0; }
        if (montant <= 0) {
            alert('Montant incorrect !'); return false;
        }
        if (id_mode === 0) {
            alert('Sélectionnez un mode de règlement !'); return false;
        }*/


        $.fn.ajax({
            'script_execute': 'fct_factures.php',
            'form_id': 'formNewReglement',
            'callBack' : function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) { alert(retour); return false; }

                $('#modalReglement').modal('hide');

            } // FIN callBack
        }); // FIN ajax

    });

    $('.btnSupprReglement').off("click.btnSupprReglement").on("click.btnSupprReglement", function(e) {
        e.preventDefault();
        var id = parseInt($(this).data('id'));
        if (isNaN(id)) {id = 0;}
        if (id === 0) { alert('Identification ID reglement échoué !'); return false; }

        if (!confirm("Supprimer ce règlement ?")) { return false; }

        $.fn.ajax({
            'script_execute': 'fct_factures.php',
            'arguments': 'mode=supprReglementFacture&id='+id,
            'callBack' : function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) { alert('Echec suppression !'); return false; }

                $('#modalReglement').modal('hide');

            } // FIN callBack
        }); // FIN ajax


    }); // FIN suppr




} // FIN listner

// Listener de la modale création d'avoir
function modaleAvoirListener() {
    "use strict";

    $('.selectpicker').selectpicker('render');




    $('#modalAvoirBody .selectpicker').on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {
        e.stopPropagation();

/*        if ($(this).attr('name') ===  'ids_facture_ligne[]') {
            $('.btnCreerAvoir.d-none').removeClass('d-none');
        } else {
            if (!$('.btnCreerAvoir').hasClass('d-none')) { $('.btnCreerAvoir').addClass('d-none');}
        }*/

        if ($(this).attr('name') ===  'id_client') {
            $('#modalAvoirBody .select-ids-facture').selectpicker('val', '');
            $('#modalAvoirBody .select-ids-facture_ligne').selectpicker('val', '');
        }

        if ($(this).attr('name') ===  'ids_facture') {
            $('#modalAvoirBody .select-ids-facture_ligne').selectpicker('val', '');
        }

        $('#messageErreurConteneur').hide();
        // On intègre la liste des frais
        $.fn.ajax({
            'script_execute': 'fct_factures.php',
            'form_id': 'modalAvoirBody',
            'return_id': 'modalAvoirBody',
            'done' : function () {
                modaleAvoirListener();
            } // FIN callBack

        }); // FIN ajax

    });


} // FIN listener