/**
 ------------------------------------------------------------------------
 JS - BL ADD (FO)

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

    // 18/01/2021 suite mauvaises manips de quittage intempestifs, on perds de BL, on force donc le statut en attente dès qu'un BL est généré s'il est en statut en cours
    setTimeout(function(){
        var id_bl = parseInt($('#formBl input[name=id]').val());
        if (isNaN(id_bl)) { id_bl = 0; }

        $('#formBl input[name=mode]').val('miseEnAttenteBl');

        $.fn.ajax({
            script_execute: 'fct_bl.php',
            form_id : 'formBl',
            arguments: '&init_attente=1'
        }); // FIN ajax

        return false;
    }, 800);

    // On met à jour les adresses dispos
    updAdressesClt();

    // On met à jour les traductions des produits à l'affichage
    updNomsProduits();

    // On calcule les poids bruts
    calculePoidsBruts();

    $('.btnAddProduitBLForWeb').off("click.btnAddProduitBLForWeb").on("click.btnAddProduitBLForWeb", function(e) {
        e.preventDefault();
        var id_bll = parseInt($(this).data('id-bll'));
        if (isNaN(id_bll) || id_bll === 0) { alert("ERREUR IDENTIFICATION LIGNE BL WEB !"); return false; }
        var colis_web = parseInt($(this).parents('tr').find('input.nbColisLigne').val());
        var poids_web = parseFloat($(this).parents('tr').find('input.poidsLigne ').val().replace(/\s/g, ''));
        var qte_web = parseInt($(this).parents('tr').find('input.qteLigne').val());
       $('#modalAddProduitBl input[name=id_bl_ligne]').val(id_bll);
       $('#modalAddProduitBl input[name=colis_web]').val(colis_web);
       $('#modalAddProduitBl input[name=poids_web]').val(poids_web);
       $('#modalAddProduitBl input[name=qte_web]').val(qte_web);
       $('#modalAddProduitBl').modal('show');
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
                var id_bl = parseInt($('#formBl input[name=id]').val());
                if (isNaN(id_bl)) { id_bl = 0; }
                $('body').css('cursor', 'wait');
                var paramBo = $('body').hasClass('bobl') ? '&bo' : '';
                $(location).attr('href',"bl-addupd.php?idbl="+id_bl+paramBo);
            }
        }); // FIN ajax
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

    $('#modalAddLigneBl').on('hide.bs.modal', function (e) {
        $('#modalAddLigneBlBody input[type=text]').val('');
        $('#modalAddLigneBlBody select.selectpicker').selectpicker('val', 0);
    });
    $('#modalAddProduitBl').on('hide.bs.modal', function (e) {
        $('#btnAddPdtBl').hide();
        $('#modalAddProduitBl input[name=id_bl_ligne]').val('0');
    });


    // Quand on change de client
    $('#id_tiers_livraison').change(function () {

        // On met à jour les adresses dispos
        updAdressesClt();
        updTransporteurClt(); // et le transporteur par défaut

    }); // FIN selection clientgenerePdfBl

    // Si on change le client facturé...
    $('select[name=id_t_fact]').change(function () {

        var id_t_fact =  parseInt($(this).val());
        var id_t_livr =  parseInt(parseInt($('#id_tiers_livraison').val()));

        if (isNaN(id_t_fact)) { id_t_fact = 0; }
        if (isNaN(id_t_livr)) { id_t_livr = 0; }

        $('#id_tiers_livraison').selectpicker('val',id_t_fact);


    }); // FIN changmeent client facturé

    // Quand on change de langue
    $('#id_langue').change(function () {

        // On met à jour les traductions
        updNomsProduits();

    }); // FIN selection langue


    // Regroupement
    $('#regroupement').change(function () { // Au changement
        changeRegroupementProduits();
    });

    // Chiffrage
    adapteChiffrage();
    $('#chiffrage').change(function () {
        adapteChiffrage();
    }); // FIN changement chiffrage


    // Génération du BL
    $('#btnGenereBl').click(function () {
        genereEtTelechargeBl();
    });

    // Impression etiquette
    $('#btnEtiquette').click(function () {

        var id_bl = parseInt($('#formBl input[name=id]').val());
        if (isNaN(id_bl)) { id_bl = 0; }
        if (id_bl === 0) { alert('ERREUR !\r\nIdentification du BL impossible...'); return false; }

        $.fn.ajax({
            script_execute: 'fct_bl.php',
            arguments: 'mode=imprimEtiquetteBl&id_bl='+id_bl,
            callBack: function (retour) {
                retour+= '';
                if (retour === '') {
                    alert('Impression impossible !');
                    return false;
                }

                var doc = document.getElementById('etiquetteFrame').contentWindow.document;
                doc.open();
                doc.write(retour);
                doc.close();

                window.frames["imprimerEtiquette"].focus();
                window.frames["imprimerEtiquette"].print();
            }
        });

    }); // Fin impression étiquette

    // Chargement du contenu de la modale à son ouverture
    $('#modalEnvoiMail').on('shown.bs.modal', function (e) {

        // On récupère l'ID
        var id_client = parseInt($('#id_tiers_livraison').val());
        if (isNaN(id_client) || id_client === 0) { alert('Identification du client impossible !');return false; }

        var id_bl = parseInt($('#formBl input[name=id]').val());
        if (isNaN(id_bl)) { id_bl = 0; }
        if (id_bl === 0) { alert('ERREUR !\r\nIdentification du BL impossible...'); return false; }

        $.fn.ajax({
            'script_execute': 'fct_bl.php',
            'arguments': 'mode=modalEnvoiMail&id_client='+id_client+'&id_bl='+id_bl,
            'return_id': 'modalEnvoiMailBody',
            'done': function () {
                $('.selectpicker').selectpicker('render');
                $('.togglemaster').bootstrapToggle();

                $('.btnEnvoiMail').off("click.btnEnvoiMail").on("click.btnEnvoiMail", function(e) {

                    e.preventDefault();

                    // On intègre dans le form l'id du contact correspondant au mail et si on veut une cc
                    var id_ctc = $('#modalEnvoiMailBody select').val();
                    var cc = $('#modalEnvoiMailBody .togglemaster').is(':checked') ? 1 : 0;

                    $('#formBl input[name=cc]').val(cc);
                    $('#formBl input[name=id_ctc]').val(id_ctc);
                    $('#formBl input[name=mode]').val('envoiBl'); // On change le mode du formulaire

                    var objetDomBtnExport = $('#btnEnvoiBl');
                    objetDomBtnExport.find('i').removeClass('fa-paper-plane').addClass('fa-spin fa-spinner');
                    objetDomBtnExport.attr('disabled', 'disabled');

                    $.fn.ajax({
                        'script_execute': 'fct_bl.php',
                        'form_id': 'formBl',
                        'callBack' : function (retour) {
                            retour+='';

                            $('#formBl input[name=mode]').val('generePdfBl'); // On remet comme c'était
                            objetDomBtnExport.find('i').removeClass('fa-spin fa-spinner').addClass('fa-paper-plane');
                            objetDomBtnExport.prop('disabled', false);
                            objetDomBtnExport.find('.btn').blur();

                           if (parseInt(retour) !== 1) { alert("Une erreur est survenue lors de l'envoi du Bl !..."); }
                           else {
                               $('#btnEnvoiBl.btn-info').removeClass('btn-info').addClass('btn-success');
                               alert('Le BL a bien été envoyé au client.');
                               $('#modalEnvoiMail').modal('hide');
                               $('#btnEnvoiBl').blur();
                           }
                        } // FIN callBack
                    }); // FIN ajax
                }); // Fin bouton envoi

            } // FIN done
        });// FIN ajax
    }); // Fin chargement du contenu de la modale


    $('#modalEmballagesPalette').on('hide.bs.modal', function (e) {
        $('#modalEmballagesPaletteBody').html('<i class="fa fa-spin fa-spinner"></i>');
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
        saveNumLotLigne($(this));
    }); // FIN changement numlot

    // Changement du pays d'origine
    $('.origLigne').change(function (e) {
        e.stopImmediatePropagation();
        var id_pays = parseInt($(this).val());
        if (isNaN(id_pays)) { id_pays = 0;}
        if (id_pays === 0) { alert('ERREUR !\r\nOrigine inconnue !');return false; }
        var id_ligne_bl = parseInt($(this).parents('tr').data('id-ligne'));
        if (isNaN(id_ligne_bl)) { id_ligne_bl = 0; }
        if (id_ligne_bl === 0) { alert("ERREUR !\r\nIdentification de la ligne impossible !"); return false; }
        $.fn.ajax({
            'script_execute': 'fct_bl.php',
            'arguments': 'mode=changeOrigLigne&id_ligne_bl='+id_ligne_bl+'&id_pays='+id_pays,
            'callBack' : function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de l'enregistrement des modifications.");}
            } // FIN callBack
        }); // FIN ajax
    }); // FIN changement pays d'origine

    // Changement du numéro de palette
    $('.numPalette').blur(function () {
        saveNumPalette($(this));
    }); // FIN changement numéro palette

    // Changement du numéro de palette
    $('.numPaletteSelect').change(function (e) {
        e.stopImmediatePropagation();
        saveNumPaletteSelect($(this));
    }); // FIN changement numéro palette

    // Changement du nombre de colis
    $('.nbColisLigne').blur(function () {
       saveNbColisLigne($(this));
    }); // FIN changement nb_colis

    // Changement de la quantité
    $('.qteLigne').blur(function () {
        saveQteLigne($(this));
    }); // FIN changement qté

    // Changement du poids
    $('.poidsLigne').blur(function () {
       savePoidsLigne($(this));
    }); // FIN changement poids

    // Changement du prix unitaire
    $('.puLigne').blur(function () {
        savePuLigne($(this));
    }); // FIN changement prix unitaire



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

    // Mise en attente du BL
    $('#btnAttenteBl').click(function () {
        if (!confirm("Mettre ce BL en attente ?")) { return false; }
        $('#formBl input[name=mode]').val('miseEnAttenteBl');
        $('#btnAttenteBl').html('<i class="fa fa-spin fa-spinner"></i>').blur();
        $.fn.ajax({
            'script_execute': 'fct_bl.php',
            'form_id' : 'formBl',
            'callBack' : function (retour) {
                retour+='';
                $('#btnAttenteBl').html('<i class="fa fa-fw fa-hourglass-end mb-1"></i><br>Mettre le BL en attente');
                if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec de l'enregistrement du BL..."); return false; }
                if ($('body').hasClass('bobl')) {
                    alert('Le BL a bien été mis en attente');
                } else {
                    if (confirm("Le BL a bien été mis en attente.\r\n\r\nSouhaitez-vous revenir au stock produits ?")) {
                        $(location).attr('href',"stock-produits/");
                    }
                }
            } // FIN callBack
        }); // FIN ajax
    });

    // Au changement du client facturé
    $('select[name=id_t_fact]').change(function() {
        // On recharge les tarifs de ce client
        var id_clt = $(this).val();
        if (isNaN(id_clt)) { id_clt = 0; }
        if (id_clt === 0) { return false; }
        // On prends tous les ids produits de la page
        var ids_produits = $('#ids_produits').val().split(',');
        $('.puLigne.rougeTemp').removeClass('rougeTemp');
        // On récupère les tarifs client
        ids_produits.forEach(function(id_pdt) {
            $.fn.ajax({
                'script_execute': 'fct_tarifs.php',
                'arguments': 'mode=getTarifClientByProduct&id_clt='+id_clt+'&id_pdt='+id_pdt+'&th_sep=', // On ne met pas de séparateur de milliers
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
        });
    }); // FIN changement client facturé

    $('.puLigne.rougeTemp').keyup(function () {
       $(this).removeClass('rougeTemp');
    });

    $('.btnSaveSafari').off("click.btnSaveSafari").on("click.btnSaveSafari", function(e) {
        e.preventDefault();
        $('.poidsLigne').each(function () {
            savePoidsLigne($(this));
        });
        $('.puLigne').each(function () {
            savePuLigne($(this));
        });

        $('.numlotLigne').each(function () {
            saveNumLotLigne($(this));
        });
        $('.numPalette').each(function () {
            saveNumPalette($(this));
        });
        $('.nbColisLigne').each(function () {
            saveNbColisLigne($(this));
        });
        $('.qteLigne').each(function () {
            saveQteLigne($(this));
        });
    }); // FIN bouton Save pour Safari

    $('.origBl').change(function (e) {
        e.stopImmediatePropagation();
        var id_pays = parseInt($(this).val());
        if (isNaN(id_pays)) { id_pays = 0;}
        if (id_pays === 0) { alert('Pays inconnu !'); return false; }
        $('.origLigne').each(function () {
            $(this).selectpicker('val', id_pays);
        });
    });

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

        var id_pdt_negoce = parseInt($('#modalAddProduitBlBody input[name=id_pdt_negoce]').val());
        
        if (isNaN(id_pdt_negoce)){ id_pdt_negoce = 0; }

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
        var quantite = parseFloat($('#produitsNewBl input[name=quantite]').val());
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

}); // FIN ready



// Chargement select adresses du client sélectionné
function updAdressesClt() {
    "use strict";

    var id_clt = $('#id_tiers_livraison').val();

    $.fn.ajax({
        'script_execute': 'fct_clients.php',
        'arguments': 'mode=adressesCltSelect&id_clt='+id_clt,
        'return_id' : 'selectAdresses',
        'done' : function (url_fichier) {
            $('#selectAdresses').selectpicker('refresh');
        } // FIN callBack
    }); // FIN ajax

} // FIN Fonction


// Affichage des noms des produits dans la langue sélectionnée
function updNomsProduits() {
    "use strict";

    return false;
/*
    var id_langue = $('#id_langue').val();

    $('.nom_trads').hide();
    $('.nom_trad_'+id_langue).show();*/

} // FIN fonction




// Génère puis télécharge le BL
function genereEtTelechargeBl() {
    "use strict";

    var objetDomBtnExport = $('#btnGenereBl');
    var ifabtn =  objetDomBtnExport.find('i').hasClass('fa-download') ? 'fa-download' : 'fa-check';
    objetDomBtnExport.find('i').removeClass(ifabtn).addClass('fa-spin fa-spinner');
    objetDomBtnExport.attr('disabled', 'disabled');

    $('#formBl input[name=mode]').val('generePdfBl'); // On remet comme c'était

    $.fn.ajax({
        'script_execute': 'fct_bl.php',
        'form_id': 'formBl',
        'return_id' : 'telechargeBl',
        'callBack' : function (retour) {
            retour+='';

            var id_bl = parseInt($('#formBl input[name=id]').val());
            if (isNaN(id_bl)) { id_bl = 0; }
            if (id_bl === 0) { alert('ERREUR !\r\nIdentification du BL impossible...'); return false; }

            $.fn.ajax({
                'script_execute': 'fct_bl.php',
                'arguments': 'mode=getUrlBlPdf&id_bl='+id_bl,
                'callBack' : function (url_fichier) {
                    url_fichier+='';
                    // On attends 1 seconde car parfois c'est trop rapide et le fichier n'est pas encore écrit sur le disque = 404
                    setTimeout(function(){
                        objetDomBtnExport.find('i').removeClass('fa-spin fa-spinner').addClass(ifabtn);
                        objetDomBtnExport.prop('disabled', false);
                        $('#lienPdfBl').attr('href', url_fichier);
                        $('#lienPdfBl')[0].click();

                    }, 1000);


                } // FIN callBack
            }); // FIN ajax


        } // FIN callBack

    }); // FIN ajax

} // FIN fonction

/*// Génère le PDF du BL
function genereBl(copies) {
    "use strict";

    if (isNaN(copies)) { copies = 1; }

    var objetDomBtnExport = $('#btnGenereBl');
    objetDomBtnExport.find('i').removeClass('fa-file-invoice').addClass('fa-spin fa-spinner');
    objetDomBtnExport.attr('disabled', 'disabled');

    $.fn.ajax({
        'script_execute': 'fct_bl.php',
        'form_id': 'formBl',
        'return_id' : 'telechargeBl',
        'callBack' : function (retour) {
            retour+='';

            objetDomBtnExport.find('i').removeClass('fa-spin fa-spinner').addClass('fa-file-invoice');
            objetDomBtnExport.prop('disabled', false);

            // Si on enregistre
            if (retour.substr(0, 4) === 'http') {

                $('#lienPdfBl').attr('href', retour);
                $('#lienPdfBl')[0].click();

            // Si on imprime
            } else {

                var doc = document.getElementById('blFrame').contentWindow.document;
                doc.open();
                for (var copie = 0; copie < copies; copie++) {
                    var page = 1;
                    doc.write(retour);
                }
                doc.close();

                window.frames["imprimerBl"].focus();
                window.frames["imprimerBl"].print();

            } // FIN test impression/enregistrement


        } // FIN callBack

    }); // FIN ajax



} // FIN fonction*/

// Recalcule les totaux
function recalculeTotaux() {
    "use strict";

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
            var paramBo = $('body').hasClass('bobl') ? '&bo' : '';
            $(location).attr('href',"bl-addupd.php?idbl="+id_bl+paramBo);


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
    calculePoidsBruts();
} // FIN fonction

function savePoidsLigne(objDom) {
    "use strict";
    var poids = parseFloat(objDom.val());
    var id_ligne_bl = parseInt(objDom.parents('tr').data('id-ligne'));
    if (isNaN(poids)) { poids = 0; }
    if (isNaN(id_ligne_bl)) { id_ligne_bl = 0; }
    if (id_ligne_bl === 0) { alert("ERREUR !\r\nIdentification de la ligne impossible !"); return false; }

    // Si on a mis zéro... suppression
    if (poids === 0) {

        if (!confirm("ATTENTION !\r\nPoids nul : souhaitez-vous supprimer cette ligne du BL ?")) { return false; }
        var ligneDom = objDom.parents('tr');
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
    return true;
} // FIN fonction


function savePuLigne(objDom) {
    "use strict";

    var pu = parseFloat(objDom.val());
    var id_ligne_bl = parseInt(objDom.parents('tr').data('id-ligne'));
    if (isNaN(id_ligne_bl)) { id_ligne_bl = 0; }

    if (objDom.hasClass('rougeTemp') && pu > 0) {
        objDom.removeClass('rougeTemp');
    } else if (!objDom.hasClass('rougeTemp') && pu === 0) {
        objDom.addClass('rougeTemp');
    }

    var id_produit = parseInt(objDom.parents('tr').find('select.selectProduitBl').val());
    if (isNaN(id_produit)) { id_produit = 0; }

    // Si différent de l'ancienne valeur
    var oldDom = objDom.parents('tr').find('.puLigneOld');
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


    if (old !== pu && pu > 0) {

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
                    if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de la mise à jour du tarif client."); return false; }

                    // On met à jour ce prix pour toutes les lignes du même produit (sans avoir à refaire F5)
                    $('.lignePdt'+id_produit).find('input.puLigne').val(pu);
                    recalculeTotaux();

                } // FIN callBack
            }); // FIN ajax

        } // FIN confirmation

    } // FIN test PU différent du tarif client
    return true;
} // FIN fonction



function saveNumLotLigne(objDom) {
    "use strict";
    var numlot = objDom.val().trim();
    var id_ligne_bl = parseInt(objDom.parents('tr').data('id-ligne'));
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
    return true;
} // FIN fonction

function saveNumPaletteSelect(objDom) {
    "use strict";
    var id_bl = parseInt($('#formBl input[name=id]').val());
    if (isNaN(id_bl)) { id_bl = 0; }
    if (id_bl === 0) { alert('ERREUR !\r\nIdentification du BL impossible...'); return false; }
    var infos_palette = objDom.val().split('-');
    var id_palette = infos_palette[0];
    var old_id_palette = infos_palette[1];

    if (id_palette === old_id_palette) { return false; }

    if (isNaN(id_palette)) { id_palette = 0; }
    if (isNaN(old_id_palette)) { id_palette = 0; }
    if (id_palette === 0) { alert("ERREUR !\r\nIdentification de la palette source impossible !"); return false; }
    $.fn.ajax({
        'script_execute': 'fct_bl.php',
        'arguments': 'mode=changeNumPaletteLigneBySelect&id_bl='+id_bl+'&id_palette='+id_palette+'&old_id_palette='+old_id_palette,
        'callBack' : function (retour) {
            retour+='';
            if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de l'enregistrement des modifications."); return false; }
            $('body').css('cursor', 'wait');
            window.location.href = 'bl-addupd.php?i='+btoa(id_bl);
        } // FIN callBack
    }); // FIN ajax
    return true;
}

function saveNumPalette(objDom) {
    "use strict";
    var id_bl = parseInt($('#formBl input[name=id]').val());
    if (isNaN(id_bl)) { id_bl = 0; }
    if (id_bl === 0) { alert('ERREUR !\r\nIdentification du BL impossible...'); return false; }
    var num_palette = objDom.val().trim();
    var id_palette = parseInt(objDom.parents('tr').data('id'));
    if (isNaN(id_palette)) { id_palette = 0; }
    if (id_palette === 0) { alert("ERREUR !\r\nIdentification de la palette source impossible !"); return false; }
    $.fn.ajax({
        'script_execute': 'fct_bl.php',
        'arguments': 'mode=changeNumPaletteLigne&id_bl='+id_bl+'&id_palette='+id_palette+'&num_palette='+num_palette,
        'callBack' : function (retour) {
            retour+='';
            if (parseInt(retour) !== 1) { alert("ERREUR !\r\nEchec lors de l'enregistrement des modifications.");}
            $('body').css('cursor', 'wait');
            window.location.href = 'bl-addupd.php?i='+btoa(id_bl);
        } // FIN callBack
    }); // FIN ajax
    return true;
} // FIN fonction


function saveNbColisLigne(objDom) {
    "use strict";
    var nb_colis = parseInt(objDom.val());
    var id_ligne_bl = parseInt(objDom.parents('tr').data('id-ligne'));
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
            } // FIN callBack
        }); // FIN ajax
    //} // FIN test > 0
    return true;
} // FIN fonction

function saveQteLigne(objDom) {
    "use strict";
    var qte = parseInt(objDom.val());
    var id_ligne_bl = parseInt(objDom.parents('tr').data('id-ligne'));
    if (isNaN(qte)) { qte = ''; }
    if (qte === '') { return false; }
    if (isNaN(id_ligne_bl)) { id_ligne_bl = 0; }
    if (id_ligne_bl === 0) { alert("ERREUR !\r\nIdentification de la ligne impossible !"); return false; }

    // Si on a mis zéro... suppression
    if (qte === 0) {

        if (!confirm("ATTENTION !\r\nQuantité nulle : souhaitez-vous supprimer cette ligne du BL ?")) { return false; }
        var ligneDom = objDom.parents('tr');
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
    return true;
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
    var colis_web = parseInt($('#modalAddProduitBl input[name=colis_web]').val());
    var poids_web = parseFloat($('#modalAddProduitBl input[name=poids_web]').val());  
    var id_pdt_negoce = parseInt($('#modalAddProduitBl input[name=id_pdt_negoce]').val());  
    $('#produitsNewBl input[name=nb_colis]').val(colis_web);
    $('#produitsNewBl input[name=poids]').val(poids_web);
    $('#btnAddPdtBl').show();

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

function addLigneProduitBl() {
    "use strict";


    var id_bl = parseInt($('#formBl input[name=id]').val());
    var id_pdt_negoce = $("#id_pdt_negoce").data('id-negoce');

    //var id_pdt_negoce = parseInt($('#modalAddProduitBlBody input[name=id_pdt_negoce]').val(id_pdt_negoce));           
    if (isNaN(id_bl)) { id_bl = 0; }
    if (id_bl === 0) { alert("ID BL non identifé !"); return false; }
    var id_client = parseInt($('#formBl select[name=id_t_fact] option:selected').val());
    if (isNaN(id_client)) { id_client = 0; }
    $('#modalAddProduitBlBody input[name=id_bl]').val(id_bl);
    $('#modalAddProduitBlBody input[name=id_bl]').val(id_bl);
    $('#modalAddProduitBlBody input[name=id_pdt_negoce]').val(id_pdt_negoce);
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


// Charge la liste des produits du BL
function chargeProduitsBl() {
    "use strict";

    $('#formBl input[name=mode]').val('miseEnAttenteBl');

    $.fn.ajax({
        script_execute: 'fct_bl.php',
        form_id : 'formBl',
        arguments: '&init_attente=1',
        done: function() {

            // Ici il faut s'assurer qu'on ait bien l'id du bl et pas les id_compo dans le GET si on viens du stock, sinon on va créer un nouveau BL...
            // Bref il faut pas bl-addupd.php?c=xxxx mais bl-addupd.php?i=xxxx
            var id_bl = parseInt($('#formBl input[name=id]').val());
            if (isNaN(id_bl) || id_bl === 0) { alert('Erreur identification BL !'); return false; }
            window.location.href = 'bl-addupd.php?i='+btoa(id_bl);
            //location.reload();
        }
    }); // FIN ajax


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

// Calcul et recalcul des poids bruts par ligne
function calculePoidsBruts() {
    "use strict";

    var id_bl = parseInt($('#formBl input[name=id]').val());
    if (isNaN(id_bl)) { id_bl = 0; }

    

    $.fn.ajax({
        script_execute: 'fct_bl.php',
        arguments: 'mode=getPoidsBrutsByPalettes&id_bl='+id_bl,
        callBack : function (retour) {
            retour+='';

            // On affiche le poids net par défaut en attendant de calculer, ça réinit sur les cases où on aurait pas d'info afin d'avoir le poids brut = poids net
            $('.poids-brut').each(function () {
                var poids_net = parseFloat($(this).parents('.ligneBl').find('.poidsLigne').val().replace(/\s/g, ''));
                if (isNaN(poids_net)) { poids_net = 0; }
                $(this).html(poids_net.toFixed(3));
            });
            if (retour === '') { return false; }            
            var poids_palette = parseFloat(retour);
            $.each(poids_palette, function(id_palette, poids) {
                var total_colis_palette = 0;
                $('.lignePalette'+id_palette).each(function() {
                    var nbc = parseInt($(this).find('.nbColisLigne').val());
                    if (isNaN(nbc) || nbc === 0) { nbc = 1; }
                    total_colis_palette+=nbc;
                });
                $('.lignePalette'+id_palette).each(function() {
                    var poids_net = parseFloat($(this).find('.poidsLigne').val().replace(/\s/g, ''));
                    var colis = parseInt($(this).find('.nbColisLigne').val());
                    if (isNaN(poids_net)) { poids_net = 0.0; }
                    if (isNaN(colis) || colis === 0) { colis = 1; }
                    var poids_brut = poids_net + ((colis*poids)/total_colis_palette);
                    if (isNaN(poids_brut)) { poids_brut = 0; }
                    $(this).find('.poids-brut').html(poids_brut.toFixed(3));
                });
            });
        } // FIN callBack
    }); // FIN ajax
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
                var id_bl = parseInt($('#formBl input[name=id]').val());
                if (isNaN(id_bl)) { id_bl = 0; }
                $('body').css('cursor', 'wait');
                var paramBo = $('body').hasClass('bobl') ? '&bo' : '';
                $(location).attr('href',"bl-addupd.php?idbl="+id_bl+paramBo);
            } // FIN callBack
        }); // FIN ajax
    });


} // FIN fonction