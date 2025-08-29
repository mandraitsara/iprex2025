/**
 ------------------------------------------------------------------------
 JS - All (BO)

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

    $('#modalEnvoiMail .btnMarquerEnvoye').removeClass('d-none');

    chargeListe();

    $('.btnMarquerEnvoye').off("click.btnMarquerEnvoye").on("click.btnMarquerEnvoye", function(e) {
        e.preventDefault();
        var docs = [];
        var ids_lignes = [];
        $('#listeAll tbody .icheck:checked').each(function() {
            var doc = $(this).data('type')+':'+parseInt($(this).data('id'));
            docs.push(doc);
            ids_lignes.push($(this).parents('tr').attr('id'));
        });




        $.fn.ajax({
            script_execute: 'fct_gesdocs.php',
            arguments: 'mode=marquerDocsEnvoyee&docs='+docs,
            callBack: function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) {
                    alert(retour);
                    return false;
                }
                $('#modalEnvoiMail').modal('hide');
                var d = new Date();
                var month = d.getMonth()+1;
                var day = d.getDate();
                var output =
                    (day<10 ? '0' : '') + day + '/' +
                    (month<10 ? '0' : '') + month + '/' +
                    d.getFullYear();
                var i;
                for (i = 0; i < ids_lignes.length; ++i) {
                    var tr = $('#'+ids_lignes[i]);
                    tr.find('.date-envoi').text(output).addClass('text-success');
                    tr.find('.coche').html('<i class="fa fa-check fa-fw fa-lg text-success"></i>');
                }

            }
        });

    });

    $('.btnRecherche').click(function () {
        chargeListe();
    });

    $('form#filtres input[type=text]').keyup(function (e) {
        var code = (e.keyCode ? e.keyCode : e.which);
        if (code === 13) {
            e.preventDefault();

            $('form#filtres input[name=date_du]').val('');
            $('form#filtres input[name=date_au]').val('');
            $('form#filtres input[name=numblorfact]').val('');
            $('form#filtres select[name=id_client]').selectpicker('val', 0);
            $('form#filtres select[name=status]').selectpicker('val', -1);
            $('form#filtres select[name=envoi]').selectpicker('val', -1);
            $('.btnRecherche').trigger('click');
            return false;
        }
    });

    // Nettoyage du contenu de la modale à sa fermeture
    $('#modalEnvoiMail').on('hidden.bs.modal', function (e) {
        $('#modalEnvoiMailBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
    }); // F

}); // FIN ready


// Charge la liste
function chargeListe() {
    "use strict";

    $('#listeAll').html('<i class="fa fa-spin fa-spinner"></i> Recherche des documents en cours, veuillez patienter...');

    $.fn.ajax({
        script_execute: 'fct_gesdocs.php',
        form_id: 'filtres',
        return_id: 'listeAll',
        done: function () {

            listeListener();

        }
    }); // FIN ajax

} // FIN fonction


// Listener de la liste
function listeListener() {
    "use strict";

    $("input[type=checkbox].icheck").iCheck(
        {checkboxClass: "icheckbox_square-blue"}
    );


    $('.client-filtre').off("click.btnFiltreClient").on("click.btnFiltreClient", function(e) {
        e.preventDefault();
        var id_client = parseInt($(this).data('id'));
        if (isNaN(id_client) || id_client === 0) { return false; }
        $('#filtres select[name=id_client]').selectpicker('val', id_client);
        chargeListe();
    });

    $('#listeAll thead .icheck').on('ifChanged', function(){
        var action = $(this).is(':checked') ?  'check' : 'uncheck';
        $('#listeAll tbody .icheck').iCheck(action);
    });

    $('.btnEnvoyerSelection').off("click.btnEnvoyerSelection").on("click.btnEnvoyerSelection", function(e) {


        var nb_clients = [];
        var refs = [];
        var docs = [];
        var ids_lignes = [];
        $('#listeAll tbody .icheck:checked').each(function() {
            var id_client = parseInt($(this).data('clt'));
            if (isNaN(id_client) || id_client === 0) { alert('ERREUR IDENTIFICATION CLIENT !');return false; }
            if (nb_clients.indexOf(id_client) === -1) {
                nb_clients.push(id_client);
            }
            var ref = $(this).parents('tr').find('td.reference a').text();
            refs.push(ref);
            var doc = $(this).data('type')+':'+parseInt($(this).data('id'));
            docs.push(doc);
            ids_lignes.push($(this).parents('tr').attr('id'));

        });

        var id_client = nb_clients[0];

        if (nb_clients.length === 0) {
            alert('Aucun document sélectionné.');
            return false;
        } else if (nb_clients.length > 1) {
            id_client = nb_clients;
        }



        $.fn.ajax({
            script_execute: 'fct_gesdocs.php',
            arguments: 'mode=modalEnvoiMail&id_client='+id_client+'&refs='+refs,
            return_id: 'modalEnvoiMailBody',
            done: function () {
                $('#modalEnvoiMail').modal('show');
                $('#modalEnvoiMail .selectpicker').selectpicker('render');
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
                        'script_execute': 'fct_gesdocs.php',
                        'arguments': 'mode=envoiPdfClient&id_ctc='+id_ctc+'&cc='+cc+'&mail='+mail+'&docs='+docs+'&id_client='+id_client,
                        'callBack': function (retour) {
                            retour+='';
                            if (parseInt(retour) === 1) {
                                $('#modalEnvoiMail').modal('hide');

                                var d = new Date();
                                var month = d.getMonth()+1;
                                var day = d.getDate();
                                var output =
                                    (day<10 ? '0' : '') + day + '/' +
                                    (month<10 ? '0' : '') + month + '/' +
                                    d.getFullYear();
                                var i;
                                for (i = 0; i < ids_lignes.length; ++i) {
                                    var tr = $('#'+ids_lignes[i]);
                                    tr.find('.date-envoi').text(output).addClass('text-success');
                                    tr.find('.coche').html('<i class="fa fa-check fa-fw fa-lg text-success"></i>');
                                }
                            } else {
                                alert(retour);
                            }
                            return false;
                        }
                    }); // FIN ajax


                }); // Fin bouton envoi

            }
        });

    });


    /*$('.btnEnvoiBlMail').off("click.btnEnvoiBlMail").on("click.btnEnvoiBlMail", function(e) {

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


    }); */
    // Fin chargement du contenu de la modale

    //


} // FIN listener