<?php
/**
------------------------------------------------------------------------
PAGE - ADMIN - Paramètres GESCOM

Copyright (C) 2020 Intersed
http://www.intersed.fr/
------------------------------------------------------------------------

@author    Cédric Bouillon
@copyright Copyright (c) 2020 Intersed
@version   1.0
@since     2020

------------------------------------------------------------------------
 */
require_once 'scripts/php/config.php';
require_once 'scripts/php/check_admin.php';

$h1     = 'Paramètres Gescom';
$h1fa   = 'fa-fw fa-cogs';

// HEADER
include('includes/header.php');

$languesManager = new LanguesManager($cnx);
$tiersManager = new TiersManager($cnx);

$web_url = $configManager->getConfig('web_url');
if (!$web_url instanceof Config) {
	$web_url = createConfig('web_url', 'URL de la boutique en ligne', 'https://dev2.steakapapa.com/');
}

$web_api = $configManager->getConfig('web_api');
if (!$web_api instanceof Config) {
	$web_api = createConfig('web_api', 'Clef API du webservice Prestashop', 'F8CEBKS5KXZTPKYZ72JCNL351HWSEPCX');
}

$tva_interbev = $configManager->getConfig('tva_interbev');
if (!$tva_interbev instanceof Config) {
	$tva_interbev = createConfig('tva_interbev', 'ID taxe de TVA Inteberv', '3');
}

$interbev = $configManager->getConfig('interbev');
if (!$interbev instanceof Config) {
	$interbev = createConfig('interbev', 'Inteberv activée', '0');
}

$bt_clt = $configManager->getConfig('bt_clt');
if (!$bt_clt instanceof Config) {
	$bt_clt = createConfig('bt_clt', 'Client destinataire des bons de transfert', '0');
}

$pdf_top_nett = $configManager->getConfig('pdf_top_nett');
if (!$pdf_top_nett instanceof Config) {
	$pdf_top_nett = createConfig('pdf_top_nett', 'Hauteur en mm de la hauteur des en-têtes de planning de nettoyage', '120');
}

$pdf_top_bt = $configManager->getConfig('pdf_top_bt');
if (!$pdf_top_bt instanceof Config) {
	$pdf_top_bt = createConfig('pdf_top_bt', 'Hauteur en mm de la hauteur des en-têtes de bons de transferts PDF', '70');
}

$pdf_top_interbev = $configManager->getConfig('pdf_top_interbev');
if (!$pdf_top_interbev instanceof Config) {
	$pdf_top_interbev = createConfig('pdf_top_interbev', 'Hauteur en mm de la hauteur des en-têtes d\'états Interbev PDF', '25');
}

$pdf_top_factures = $configManager->getConfig('pdf_top_factures');
if (!$pdf_top_factures instanceof Config) {
	$pdf_top_factures = createConfig('pdf_top_factures', 'Hauteur en mm de la hauteur des en-têtes de factures PDF', '100');
}

$pdf_top_bl = $configManager->getConfig('pdf_top_bl');
if (!$pdf_top_bl instanceof Config) {
	$pdf_top_bl = createConfig('pdf_top_bl', 'Hauteur en mm de la hauteur des en-têtes des BL PDF', '70');
}

$pdf_top_pl = $configManager->getConfig('pdf_top_pl');
if (!$pdf_top_pl instanceof Config) {
	$pdf_top_pl = createConfig('pdf_top_pl', 'Hauteur en mm de la hauteur des en-têtes de packing lists PDF', '100');
}

$interbev_gros = $configManager->getConfig('interbev_gros');
if (!$interbev_gros instanceof Config) {
	$interbev_gros = createConfig('interbev_gros', 'Montant de la taxe Interbev sur les morceaux de gros', '0.012');
}

$interbev_autres = $configManager->getConfig('interbev_autres');
if (!$interbev_autres instanceof Config) {
	$interbev_autres = createConfig('interbev_autres', 'Montant de la taxe Interbev sur les autres morceaux', '0.018');
}

$i_raison_sociale = $configManager->getConfig('i_raison_sociale');
if (!$i_raison_sociale instanceof Config) {
    $i_raison_sociale = createConfig('i_raison_sociale', 'Nom de la société imprimée sur les documents', 'PROFIL EXPORT');
}

$i_capital = $configManager->getConfig('i_capital');
if (!$i_capital instanceof Config) {
	$i_capital = createConfig('i_capital', 'Capital social de la société imprimé sur les documents', 'SAS au capital de 210 000 euros');
}

$i_num_agr = $configManager->getConfig('i_num_agr');
if (!$i_num_agr instanceof Config) {
	$i_num_agr = createConfig('i_num_agr', "Numéro d'agrément Profil Export", 'FR-38-085-003-CE');
}

$i_siret = $configManager->getConfig('i_siret');
if (!$i_siret instanceof Config) {
	$i_siret = createConfig('i_siret', "Siret Profil Export", '528 415 961 00010');
}

$i_num_tva = $configManager->getConfig('i_num_tva');
if (!$i_num_tva instanceof Config) {
	$i_num_tva = createConfig('i_num_tva', "Numéro de TVA intracommunautaire", 'FR 78528415961');
}

$i_ifs = $configManager->getConfig('i_ifs');
if (!$i_ifs instanceof Config) {
	$i_ifs = createConfig('i_ifs', "Numéro de certification IFS", 'FQA4001055');
}

$i_email = $configManager->getConfig('i_email');
if (!$i_email instanceof Config) {
	$i_email = createConfig('i_email', "Adresse e-mail sur factures", 'profil.lyon@wanadoo.fr');
}

$i_adresse_1 = $configManager->getConfig('i_adresse_1');
if (!$i_adresse_1 instanceof Config) {
	$i_adresse_1 = createConfig('i_adresse_1', "Adresse postale siège, ligne 1", 'ZI. Montbertrand - 24 rue du Claret');
}

$i_adresse_2 = $configManager->getConfig('i_adresse_2');
if (!$i_adresse_2 instanceof Config) {
	$i_adresse_2 = createConfig('i_adresse_2', "Adresse postale siège, ligne 2 (CP, ville)", '38230 CHARVIEU CHAVAGNEUX');
}

$i_tel = $configManager->getConfig('i_tel');
if (!$i_tel instanceof Config) {
	$i_tel = createConfig('i_tel', "Téléphone siège indiqué sur documents GesCom", '04.74.80.61.28');
}

$i_fax = $configManager->getConfig('i_fax');
if (!$i_fax instanceof Config) {
	$i_fax = createConfig('i_fax', "Fax siège indiqué sur documents GesCom", '04.74.80.61.29');
}

$i_info_1 = $configManager->getConfig('i_info_1');
if (!$i_info_1 instanceof Config) {
	$i_info_1 = createConfig('i_info_1', "Ligne d'info sur documents GesCom - 1", 'Claude ROBERT 06.07.38.05.67');
}

$i_info_2 = $configManager->getConfig('i_info_2');
if (!$i_info_2 instanceof Config) {
	$i_info_2 = createConfig('i_info_2', "Ligne d'info sur documents GesCom - 2", 'John ROBERT 06.89.30.04.65');
}

$i_web = $configManager->getConfig('i_web');
if (!$i_web instanceof Config) {
	$i_web = createConfig('i_web', "URL site Internet", 'www.profilexport.com');
}

$i_sec_adresse_1 = $configManager->getConfig('i_sec_adresse_1');
if (!$i_sec_adresse_1 instanceof Config) {
	$i_sec_adresse_1 = createConfig('i_sec_adresse_1', "Adresse postale secondaire, ligne 1", '83 ch. de la Chataigneraie - Mianges');
}

$i_sec_adresse_2 = $configManager->getConfig('i_sec_adresse_2');
if (!$i_sec_adresse_2 instanceof Config) {
	$i_sec_adresse_2 = createConfig('i_sec_adresse_2', "Adresse postale secondaire, ligne 2 (CP, ville)", '38460 CHAMAGNIEU');
}

$i_sec_tel = $configManager->getConfig('i_sec_tel');
if (!$i_sec_tel instanceof Config) {
	$i_sec_tel = createConfig('i_sec_tel', "Téléphone secondaire indiqué sur documents GesCom", '04.37.65.99.57');
}

$i_sec_fax = $configManager->getConfig('i_sec_fax');
if (!$i_sec_fax instanceof Config) {
	$i_sec_fax = createConfig('i_sec_fax', "Fax secondaire indiqué sur documents GesCom", '04.72.23.03.18');
}

$i_sec_info_1 = $configManager->getConfig('i_sec_info_1');
if (!$i_sec_info_1 instanceof Config) {
	$i_sec_info_1 = createConfig('i_sec_info_1', "Ligne d'info sur documents GesCom (Service compta) - 1", 'Claude ROBERT 06.07.38.05.67');
}

$i_sec_mail = $configManager->getConfig('i_sec_mail');
if (!$i_sec_mail instanceof Config) {
	$i_sec_mail = createConfig('i_sec_mail', "Adresse e-mail du service comptabilité", 'isabelle.robert@profilexport.fr');
}

$cc_mails = $configManager->getConfig('cc_mails');
if (!$cc_mails instanceof Config) {
	$cc_mails = createConfig('cc_mails', "Adresses e-mail en copie des envois", 'patrice.pactol.ext@koesio.com');
}

$incoterms = $configManager->getConfig('incoterms');
if (!$incoterms instanceof Config) {
	$incoterms = createConfig('incoterms', "Incoterms Packing List", 'Incoterms');
}

$logsManager = new LogManager($cnx);

// Si mise à jour d'un champ de config
if (isset($_REQUEST['gope'])) {

	$maj = null;

	// Activation des langues
    $languesActivation = isset($_REQUEST['acti_langue']) ? $_REQUEST['acti_langue'] : [];
    $languesActives = [];
    foreach ($languesActivation as $id_langue => $on) { $languesActives[] = $id_langue; }

	$languesManager->activeLangues($languesActives);

	$web_api_send           = isset($_REQUEST['web_api'])           ? trim($_REQUEST['web_api']) : '';
	$web_url_send           = isset($_REQUEST['web_url'])           ? trim($_REQUEST['web_url']) : '';
	$interbev_send          = isset($_REQUEST['interbev'])          ? 1 : 0;
	$tva_interbev_send      = isset($_REQUEST['tva_interbev'])      ? intval($_REQUEST['tva_interbev'])      : 0;
	$bt_clt_send            = isset($_REQUEST['bt_clt'])            ? intval($_REQUEST['bt_clt'])            : 0;
	$pdf_top_nett_send      = isset($_REQUEST['pdf_top_nett'])      ? intval($_REQUEST['pdf_top_nett'])      : 0;
	$pdf_top_bt_send        = isset($_REQUEST['pdf_top_bt'])        ? intval($_REQUEST['pdf_top_bt'])        : 0;
	$pdf_top_interbev_send  = isset($_REQUEST['pdf_top_interbev'])  ? intval($_REQUEST['pdf_top_interbev'])  : 0;
	$pdf_top_bl_send        = isset($_REQUEST['pdf_top_bl'])        ? intval($_REQUEST['pdf_top_bl'])        : 0;
	$pdf_top_pl_send        = isset($_REQUEST['pdf_top_pl'])        ? intval($_REQUEST['pdf_top_pl'])        : 0;
	$pdf_top_factures_send  = isset($_REQUEST['pdf_top_factures'])  ? intval($_REQUEST['pdf_top_factures'])  : 0;
	$interbev_gros_send     = isset($_REQUEST['interbev_gros'])     ? floatval($_REQUEST['interbev_gros'])   : 0.0;
	$interbev_autres_send   = isset($_REQUEST['interbev_autres'])   ? floatval($_REQUEST['interbev_autres']) : 0.0;
	$i_raison_sociale_send  = isset($_REQUEST['i_raison_sociale'])  ? trim($_REQUEST['i_raison_sociale'])    : '';
	$i_capital_send         = isset($_REQUEST['i_capital'])         ? trim($_REQUEST['i_capital'])           : '';
	$i_num_agr_send         = isset($_REQUEST['i_num_agrement'])    ? trim($_REQUEST['i_num_agrement'])      : '';
	$i_siret_send           = isset($_REQUEST['i_siret'])           ? trim($_REQUEST['i_siret'])             : '';
	$i_num_tva_send         = isset($_REQUEST['i_num_tva'])         ? trim($_REQUEST['i_num_tva'])           : '';
	$i_ifs_send             = isset($_REQUEST['i_ifs'])             ? trim($_REQUEST['i_ifs'])               : '';
	$i_email_send           = isset($_REQUEST['i_email'])           ? trim($_REQUEST['i_email'])             : '';
	$i_adresse_1_send       = isset($_REQUEST['i_adresse_1'])       ? trim($_REQUEST['i_adresse_1'])         : '';
	$i_adresse_2_send       = isset($_REQUEST['i_adresse_2'])       ? trim($_REQUEST['i_adresse_2'])         : '';
	$i_tel_send             = isset($_REQUEST['i_tel'])             ? trim($_REQUEST['i_tel'])               : '';
	$i_fax_send             = isset($_REQUEST['i_fax'])             ? trim($_REQUEST['i_fax'])               : '';
	$i_info_1_send          = isset($_REQUEST['i_info_1'])          ? trim($_REQUEST['i_info_1'])            : '';
	$i_info_2_send          = isset($_REQUEST['i_info_2'])          ? trim($_REQUEST['i_info_2'])            : '';
	$i_web_send             = isset($_REQUEST['i_web'])             ? trim($_REQUEST['i_web'])               : '';
	$i_sec_adresse_1_send   = isset($_REQUEST['i_sec_adresse_1'])   ? trim($_REQUEST['i_sec_adresse_1'])     : '';
	$i_sec_adresse_2_send   = isset($_REQUEST['i_sec_adresse_2'])   ? trim($_REQUEST['i_sec_adresse_2'])     : '';
	$i_sec_tel_send         = isset($_REQUEST['i_sec_tel'])         ? trim($_REQUEST['i_sec_tel'])           : '';
	$i_sec_fax_send         = isset($_REQUEST['i_sec_fax'])         ? trim($_REQUEST['i_sec_fax'])           : '';
	$i_sec_info_1_send      = isset($_REQUEST['i_sec_info_1'])      ? trim($_REQUEST['i_sec_info_1'])        : '';
	$i_sec_mail_send        = isset($_REQUEST['i_sec_mail'])        ? trim($_REQUEST['i_sec_mail'])          : '';
	$cc_mails_send          = isset($_REQUEST['cc_mails'])          ? trim($_REQUEST['cc_mails'])            : '';
	$incoterms_send         = isset($_REQUEST['incoterms'])         ? trim($_REQUEST['incoterms'])           : '';

    if ($web_api_send != $web_api->getValeur()) {
        $web_api->setValeur($web_api_send);
		$maj = $configManager->saveConfig($web_api);
		$log = new Log([]);
		$log->setLog_texte("Modification de la clef d'API pour la liaison Prestashop");
		$logsManager->saveLog($log);
	}

	if ($web_url_send != $web_url->getValeur()) {
		$web_url->setValeur($web_url_send);
		$maj = $configManager->saveConfig($web_url);
		$log = new Log([]);
		$log->setLog_texte("Modification de l'adresse du site web Prestashop");
		$logsManager->saveLog($log);
	}

	if ($tva_interbev_send != intval($tva_interbev->getValeur())) {
		$tva_interbev->setValeur($tva_interbev_send);
		$maj = $configManager->saveConfig($tva_interbev);
		$log = new Log([]);
		$log->setLog_texte("Modification de la taxe TVA associée a l'Interbev");
		$logsManager->saveLog($log);
	}

	if ($interbev_send != $interbev->getValeur()) {
		$interbev->setValeur($interbev_send);
		$maj = $configManager->saveConfig($interbev);
		$log = new Log([]);
		$logtxt = $interbev_send == 0 ? 'Désactivaton' : 'Activation';
		$log->setLog_texte($logtxt." de l'Interbev en params gescom");
		$logsManager->saveLog($log);
	}

	if ($bt_clt_send != $bt_clt->getValeur()) {
		$bt_clt->setValeur($bt_clt_send);
		$maj = $configManager->saveConfig($bt_clt);
		$log = new Log([]);
		$log->setLog_texte("Modification du client destinataire des Bons de Transfert");
		$logsManager->saveLog($log);
    }

	if ($pdf_top_nett_send != $pdf_top_nett->getValeur()) {
		$pdf_top_nett->setValeur($pdf_top_nett_send);
		$maj = $configManager->saveConfig($pdf_top_nett);
		$log = new Log([]);
		$log->setLog_texte("Modification de la marge des PDF des plannings de nettoyage");
		$logsManager->saveLog($log);
	}

	if ($pdf_top_bt_send != $pdf_top_bt->getValeur()) {
		$pdf_top_bt->setValeur($pdf_top_bt_send);
		$maj = $configManager->saveConfig($pdf_top_bt);
		$log = new Log([]);
		$log->setLog_texte("Modification de la marge des PDF des bons de transfert");
		$logsManager->saveLog($log);
	}

	if ($pdf_top_interbev_send != $pdf_top_interbev->getValeur()) {
		$pdf_top_interbev->setValeur($pdf_top_interbev_send);
		$maj = $configManager->saveConfig($pdf_top_interbev);
		$log = new Log([]);
		$log->setLog_texte("Modification de la marge des PDF des états Interbev");
		$logsManager->saveLog($log);
	}

	if ($pdf_top_bl_send != $pdf_top_bl->getValeur()) {
		$pdf_top_bl->setValeur($pdf_top_bl_send);
		$maj = $configManager->saveConfig($pdf_top_bl);
		$log = new Log([]);
		$log->setLog_texte("Modification de la marge des PDF des BLs");
		$logsManager->saveLog($log);
	}

	if ($pdf_top_pl_send != $pdf_top_pl->getValeur()) {
		$pdf_top_pl->setValeur($pdf_top_pl_send);
		$maj = $configManager->saveConfig($pdf_top_pl);
		$log = new Log([]);
		$log->setLog_texte("Modification de la marge des PDF des packing lists");
		$logsManager->saveLog($log);
	}

	if ($pdf_top_factures_send != $pdf_top_factures->getValeur()) {
		$pdf_top_factures->setValeur($pdf_top_factures_send);
		$maj = $configManager->saveConfig($pdf_top_factures);
		$log = new Log([]);
		$log->setLog_texte("Modification de la marge des PDF de factures");
		$logsManager->saveLog($log);
	}

	if ($interbev_gros_send != $interbev_gros->getValeur()) {
		$interbev_gros->setValeur($interbev_gros_send);
		$maj = $configManager->saveConfig($interbev_gros);
		$log = new Log([]);
		$log->setLog_texte("Modification du montant de la taxe INTERBEV (gros)");
		$logsManager->saveLog($log);
	}


	if ($interbev_autres_send != $interbev_autres->getValeur()) {
		$interbev_autres->setValeur($interbev_autres_send);
		$maj = $configManager->saveConfig($interbev_autres);
		$log = new Log([]);
		$log->setLog_texte("Modification du montant de la taxe INTERBEV (autres)");
		$logsManager->saveLog($log);
	}

	if ($i_raison_sociale_send != $i_raison_sociale->getValeur()) {
		$i_raison_sociale->setValeur($i_raison_sociale_send);
		$maj = $configManager->saveConfig($i_raison_sociale);
		$log = new Log([]);
		$log->setLog_texte("Modification de la raison sociale");
		$logsManager->saveLog($log);
	}

	if ($i_capital_send != $i_capital->getValeur()) {
		$i_capital->setValeur($i_capital_send);
		$maj = $configManager->saveConfig($i_capital);
		$log = new Log([]);
		$log->setLog_texte("Modification du capital social");
		$logsManager->saveLog($log);
	}

	if ($i_num_agr_send != $i_num_agr->getValeur()) {
		$i_num_agr->setValeur($i_num_agr_send);
		$maj = $configManager->saveConfig($i_num_agr);
		$log = new Log([]);
		$log->setLog_texte("Modification du numéro d'agrément");
		$logsManager->saveLog($log);
	}

	if ($i_num_tva_send != $i_num_tva->getValeur()) {
		$i_num_tva->setValeur($i_num_tva_send);
		$maj = $configManager->saveConfig($i_num_tva);
		$log = new Log([]);
		$log->setLog_texte("Modification du numéro de TVA intra");
		$logsManager->saveLog($log);
	}

	if ($i_siret_send != $i_siret->getValeur()) {
		$i_siret->setValeur($i_siret_send);
		$maj = $configManager->saveConfig($i_siret);
		$log = new Log([]);
		$log->setLog_texte("Modification du  SIRET");
		$logsManager->saveLog($log);
	}

	if ($i_ifs_send != $i_ifs->getValeur()) {
		$i_ifs->setValeur($i_ifs_send);
		$maj = $configManager->saveConfig($i_ifs);
		$log = new Log([]);
		$log->setLog_texte("Modification du numéro certification IFS");
		$logsManager->saveLog($log);
	}

	if ($i_email_send != $i_email->getValeur()) {
		$i_email->setValeur($i_email_send);
		$maj = $configManager->saveConfig($i_email);
		$log = new Log([]);
		$log->setLog_texte("Modification de l'adresse e-mail pour les documents");
		$logsManager->saveLog($log);
	}

	if ($i_adresse_1_send != $i_adresse_1->getValeur()) {
		$i_adresse_1->setValeur($i_adresse_1_send);
		$maj = $configManager->saveConfig($i_adresse_1);
		$log = new Log([]);
		$log->setLog_texte("Modification de la première ligne d'adresse du siège sur les documents");
		$logsManager->saveLog($log);
	}

	if ($i_adresse_2_send != $i_adresse_2->getValeur()) {
		$i_adresse_2->setValeur($i_adresse_2_send);
		$maj = $configManager->saveConfig($i_adresse_2);
		$log = new Log([]);
		$log->setLog_texte("Modification de la seconde ligne d'adresse du siège sur les documents");
		$logsManager->saveLog($log);
	}

	if ($i_tel_send != $i_tel->getValeur()) {
		$i_tel->setValeur($i_tel_send);
		$maj = $configManager->saveConfig($i_tel);
		$log = new Log([]);
		$log->setLog_texte("Modification du téléphone du siège sur les documents");
		$logsManager->saveLog($log);
	}

	if ($i_fax_send != $i_fax->getValeur()) {
		$i_fax->setValeur($i_fax_send);
		$maj = $configManager->saveConfig($i_fax);
		$log = new Log([]);
		$log->setLog_texte("Modification du fax du siège sur les documents");
		$logsManager->saveLog($log);
	}

	if ($i_info_1_send != $i_info_1->getValeur()) {
		$i_info_1->setValeur($i_info_1_send);
		$maj = $configManager->saveConfig($i_info_1);
		$log = new Log([]);
		$log->setLog_texte("Modification de la première ligne d'info sur les documents");
		$logsManager->saveLog($log);
	}

	if ($i_info_2_send != $i_info_2->getValeur()) {
		$i_info_2->setValeur($i_info_2_send);
		$maj = $configManager->saveConfig($i_info_2);
		$log = new Log([]);
		$log->setLog_texte("Modification de la seconde ligne d'info sur les documents");
		$logsManager->saveLog($log);
	}

	if ($i_web_send != $i_web->getValeur()) {
		$i_web->setValeur($i_web_send);
		$maj = $configManager->saveConfig($i_web);
		$log = new Log([]);
		$log->setLog_texte("Modification de l'URL de Profil Export sur les documents");
		$logsManager->saveLog($log);
	}

	if ($i_sec_adresse_1_send != $i_sec_adresse_1->getValeur()) {
		$i_sec_adresse_1->setValeur($i_sec_adresse_1_send);
		$maj = $configManager->saveConfig($i_sec_adresse_1);
		$log = new Log([]);
		$log->setLog_texte("Modification de la première ligne de l'adresse secondaire sur les documents");
		$logsManager->saveLog($log);
	}

	if ($i_sec_adresse_2_send != $i_sec_adresse_2->getValeur()) {
		$i_sec_adresse_2->setValeur($i_sec_adresse_2_send);
		$maj = $configManager->saveConfig($i_sec_adresse_2);
		$log = new Log([]);
		$log->setLog_texte("Modification de la seconde ligne de l'adresse secondaire sur les documents");
		$logsManager->saveLog($log);
	}

	if ($i_sec_tel_send != $i_sec_tel->getValeur()) {
		$i_sec_tel->setValeur($i_sec_tel_send);
		$maj = $configManager->saveConfig($i_sec_tel);
		$log = new Log([]);
		$log->setLog_texte("Modification du téléphone secondaire sur les documents");
		$logsManager->saveLog($log);
	}

	if ($i_sec_fax_send != $i_sec_fax->getValeur()) {
		$i_sec_fax->setValeur($i_sec_fax_send);
		$maj = $configManager->saveConfig($i_sec_fax);
		$log = new Log([]);
		$log->setLog_texte("Modification du fax secondaire sur les documents");
		$logsManager->saveLog($log);
	}

	if ($i_sec_info_1_send != $i_sec_info_1->getValeur()) {
		$i_sec_info_1->setValeur($i_sec_info_1_send);
		$maj = $configManager->saveConfig($i_sec_info_1);
		$log = new Log([]);
		$log->setLog_texte("Modification de la ligne d'info libre sur les documents (Service comptabilité)");
		$logsManager->saveLog($log);
	}

	if ($i_sec_mail_send != $i_sec_mail->getValeur()) {
		$i_sec_mail->setValeur($i_sec_mail_send);
		$maj = $configManager->saveConfig($i_sec_mail);
		$log = new Log([]);
		$log->setLog_texte("Modification de l'adresse e-mail du service comptabilité)");
		$logsManager->saveLog($log);
	}

	if ($cc_mails_send != $cc_mails->getValeur()) {
		$cc_mails->setValeur($cc_mails_send);
		$maj = $configManager->saveConfig($cc_mails);
		$log = new Log([]);
		$log->setLog_texte("Modification des adresse e-mails en copie des envois");
		$logsManager->saveLog($log);
	}

	if ($incoterms_send != $incoterms->getValeur()) {
		$incoterms->setValeur($incoterms_send);
		$maj = $configManager->saveConfig($incoterms);
		$log = new Log([]);
		$log->setLog_texte("Modification des incoterms (config gescom)");
		$logsManager->saveLog($log);
	}

	if ($maj) { ?>
        <div class="alert alert-success" style="margin-top:-8px;">
            <i class="fa fa-check-circle mr-1"></i>Paramètres mis à jour.
        </div>

		<?php
	} else if ($maj === false) { ?>
        <div class="alert alert-danger" style="margin-top:-8px;">
            <i class="fa fa-exclamation-circle mr-1"></i>Une erreur est survenue, mise à jour des données impossibles ! Code erreur : Z302RWD3/GC
        </div>
		<?php
		// FIN test retour sur MAJ
	}

} // FIN test formulaire posté

?>
<form id="formPeConfigGc" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
    <div class="container-fluid">
        <div class="row justify-content-md-center">

        <div class="col-md-6">
                <div class="alert alert-secondary">
                    <div class="row">
                        <div class="col text-center mb-2">
                            <span class="badge badge-dark text-20 w-100"><i class="fa fa-language mr-2"></i> Multilingue</span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col">
                        <table class="admin table table-v-middle">
                            <thead>
                            <tr>
                                <th colspan="2">Langue</th>
                                <th>ISO</th>
                                <th class="t-actions">Activation</th>
                            </tr>
                            </thead>
                            <tbody>
							<?php
							foreach ($languesManager->getListeLangues() as $langue) { ?>
                                <tr>
                                    <td class="w-50px"><img src="<?php echo $langue->getDrapeau(); ?>"/></td>
                                    <td><?php echo $langue->getNom(); ?></td>
                                    <td><?php echo strtoupper($langue->getIso()); ?></td>
                                    <td class="w-150px text-right">
                                        <input type="checkbox"
                                               class="togglemaster <?php echo strtolower($langue->getIso()) == 'fr' ? 'disabled' : ''; ?>"
                                               <?php echo strtolower($langue->getIso()) == 'fr' ? 'disabled' : ''; ?>
                                               <?php echo $langue->getActif() == 1 || strtolower($langue->getIso()) == 'fr' ? 'checked' : ''; ?>
                                               data-toggle="toggle"
                                               data-on="Active"
                                               data-off="Désactivée"
                                               data-onstyle="success"
                                               data-offstyle="secondary"
                                               data-size="small"
                                               name="acti_langue[<?php echo $langue->getId(); ?>]"
                                        />
                                    </td>
                                </tr>
							<?php }
							?>
                            </tbody>
                        </table>
                            <div class="alert alert-info text-12 nomargin"><i class="fa fa-info-circle mr-1"></i>
                                Faites appel au support applicatif pour tout besoin de langue supplémentaire. Cliquez sur « Enregistrer » pour prendre en compte les changements d'activation.
                            </div>
                        </div>

                    </div>
                </div>

            <div class="alert alert-secondary mt-4">
                <div class="row">
                    <div class="col text-center mb-2">
                        <span class="badge badge-dark text-20 w-100"><i class="fa fa-landmark mr-2"></i> INTERBEV </span>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-5 padding-top-10">
                       Calcul de l'Interbev dans les factures
                    </div>
                    <div class="col-md-7 text-right">
                        <div class="input-group input-group-lg">
                            <input type="checkbox"
                                   class="togglemaster"
								    <?php echo intval($interbev->getValeur()) == 1 ? 'checked' : ''; ?>
                                   data-toggle="toggle"
                                   data-on="Active"
                                   data-off="Désactivée"
                                   data-onstyle="success"
                                   data-offstyle="secondary"
                                   data-size="small"
                                   name="interbev"
                            />
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-md-5 padding-top-10">
                        <span class="badge badge-warning">INTERBEV</span> Tarif C.I.E. <span class="badge badge-secondary badge-pill text-14">1</span> <span class="texte-fin text-12">(morceaux de gros)</span>
                    </div>
                    <div class="col-md-7">
                        <div class="input-group input-group-lg">
                            <input type="text" class="form-control text-center" placeholder="Non reseigné !" name="interbev_gros" value="<?php
							echo $interbev_gros->getValeur();
							?>"/>
                            <div class="input-group-append">
                                <span class="input-group-text">€ / kg</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-md-5 padding-top-10">
                        <span class="badge badge-warning">INTERBEV</span> Tarif C.I.E. <span class="badge badge-secondary badge-pill text-14">2</span> <span class="texte-fin text-12">(autres morceaux y compris hachée)</span>
                    </div>
                    <div class="col-md-7">
                        <div class="input-group input-group-lg">
                            <input type="text" class="form-control text-center" placeholder="Non reseigné !" name="interbev_autres" value="<?php
							echo $interbev_autres->getValeur();
							?>"/>
                            <div class="input-group-append">
                                <span class="input-group-text">€ / kg</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-5 padding-top-10">
                        TVA Interbev
                    </div>
                    <div class="col-md-7 text-right">
                        <div class="input-group input-group-lg">
                           <select class="selectpicker form-control" name="tva_interbev">
                                <?php
                                $taxesManager = new TaxesManager($cnx);
                                $taxes = $taxesManager->getListeTaxes();
                                foreach ($taxes as $taxe) { ?>
                                    <option value="<?php echo $taxe->getId();?>" <?php echo (int)$taxe->getId() == (int)$tva_interbev->getValeur() ? 'selected' : ''; ?> data-subtext="<?php echo $taxe->getTaux(); ?>%"><?php echo $taxe->getnom(); ?></option>
                                <?php }
                                ?>
                           </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-secondary mt-4">

                <div class="row">
                    <div class="col text-center mb-2">
                        <span class="badge badge-dark text-20 w-100"><i class="fa fa-landmark mr-2"></i> Edition </span>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-5 padding-top-10">
                        Hauteur de l'en-tête des factures (PDF)
                    </div>
                    <div class="col-md-7">
                        <div class="input-group input-group-lg">
                            <input type="text" class="form-control text-center" placeholder="Non reseigné !" name="pdf_top_factures" value="<?php
							echo $pdf_top_factures->getValeur();
							?>"/>
                            <div class="input-group-append">
                                <span class="input-group-text">mm</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-5 padding-top-10">
                        Hauteur de l'en-tête des BL (PDF)
                    </div>
                    <div class="col-md-7">
                        <div class="input-group input-group-lg">
                            <input type="text" class="form-control text-center" placeholder="Non reseigné !" name="pdf_top_bl" value="<?php
							echo $pdf_top_bl->getValeur();
							?>"/>
                            <div class="input-group-append">
                                <span class="input-group-text">mm</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-5 padding-top-10">
                        Hauteur de l'en-tête des packing list (PDF)
                    </div>
                    <div class="col-md-7">
                        <div class="input-group input-group-lg">
                            <input type="text" class="form-control text-center" placeholder="Non reseigné !" name="pdf_top_pl" value="<?php
							echo $pdf_top_pl->getValeur();
							?>"/>
                            <div class="input-group-append">
                                <span class="input-group-text">mm</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-5 padding-top-10">
                        Hauteur de l'en-tête des bons de transfert (PDF)
                    </div>
                    <div class="col-md-7">
                        <div class="input-group input-group-lg">
                            <input type="text" class="form-control text-center" placeholder="Non reseigné !" name="pdf_top_bt" value="<?php
							echo $pdf_top_bt->getValeur();
							?>"/>
                            <div class="input-group-append">
                                <span class="input-group-text">mm</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-5 padding-top-10">
                        Hauteur de l'en-tête des états Interbev (PDF)
                    </div>
                    <div class="col-md-7">
                        <div class="input-group input-group-lg">
                            <input type="text" class="form-control text-center" placeholder="Non reseigné !" name="pdf_top_interbev" value="<?php
							echo $pdf_top_interbev->getValeur();
							?>"/>
                            <div class="input-group-append">
                                <span class="input-group-text">mm</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-5 padding-top-10">
                        Hauteur de l'en-tête des plannings de nettoyage (PDF)
                    </div>
                    <div class="col-md-7">
                        <div class="input-group input-group-lg">
                            <input type="text" class="form-control text-center" placeholder="Non reseigné !" name="pdf_top_nett" value="<?php
							echo $pdf_top_nett->getValeur();
							?>"/>
                            <div class="input-group-append">
                                <span class="input-group-text">mm</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="alert alert-secondary">
                <div class="row">
                    <div class="col-12 text-center mb-2">
                        <span class="badge badge-dark text-20 w-100"><i class="fa fa-id-card mr-2"></i> Identité</span>
                    </div>
                </div>
                <div class="form-group row mb-1">
                    <div class="col-md-6 col-lg-2 padding-top-5 pl-1">
                        <span class="pl-3">Raison Sociale</span>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <input type="text" class="form-control" name="i_raison_sociale" placeholder="Raison Sociale" value="<?php echo $i_raison_sociale->getValeur(); ?>"/>
                    </div>
                    <div class="col-md-6 col-lg-2 padding-top-5 pl-1">
                        <span class="pl-3">N° d'agrément</span>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <input type="text" class="form-control" name="i_num_agrement" placeholder="Numéro d'agrément" value="<?php echo $i_num_agr->getValeur(); ?>"/>
                    </div>
                </div>
                <div class="form-group row mb-1">
                    <div class="col-md-6 col-lg-2 padding-top-5 pl-1">
                        <span class="pl-3">Capital</span>
                    </div>
                    <div class="col-md-6 col-lg-10">
                        <input type="text" class="form-control" name="i_capital" placeholder="Capital social" value="<?php echo $i_capital->getValeur(); ?>"/>
                    </div>
                </div>

                <div class="form-group row mb-1">
                    <div class="col-md-6 col-lg-2 padding-top-5 pl-1">
                        <span class="pl-3">Siret</span>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <input type="text" class="form-control" name="i_siret" placeholder="Siret" value="<?php echo $i_siret->getValeur(); ?>"/>
                    </div>
                    <div class="col-md-6 col-lg-2 padding-top-5 pl-1">
                        <span class="pl-3">N° TVA</span>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <input type="text" class="form-control" name="i_num_tva" placeholder="TVA Intra" value="<?php echo $i_num_tva->getValeur(); ?>"/>
                    </div>
                </div>

                <div class="form-group row mb-1">
                    <div class="col-md-6 col-lg-2 padding-top-5 pl-1">
                        <span class="pl-3">Certification IFS</span>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <input type="text" class="form-control" name="i_ifs" placeholder="Certification IFS" value="<?php echo $i_ifs->getValeur(); ?>"/>
                    </div>
                    <div class="col-md-6 col-lg-2 padding-top-5 pl-1">
                        <span class="pl-3">E-mail</span>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <input type="text" class="form-control" name="i_email" placeholder="Adresse e-mail" value="<?php echo $i_email->getValeur(); ?>"/>
                    </div>
                </div>

                <div class="form-group row mb-1">
                    <div class="col-md-6 col-lg-2 padding-top-5 pl-1">
                        <span class="pl-3">Adresse</span>
                    </div>
                    <div class="col-md-6 col-lg-10">
                        <input type="text" class="form-control" name="i_adresse_1" placeholder="Adresse postale" value="<?php echo $i_adresse_1->getValeur(); ?>"/>
                    </div>
                </div>

                <div class="form-group row mb-1">
                    <div class="col-md-6 col-lg-2 padding-top-5 pl-1">
                        <span class="pl-3">Ligne 2</span>
                    </div>
                    <div class="col-md-6 col-lg-10">
                        <input type="text" class="form-control" name="i_adresse_2" placeholder="Code postal et ville" value="<?php echo $i_adresse_2->getValeur(); ?>"/>
                    </div>
                </div>

                <div class="form-group row mb-1">
                    <div class="col-md-6 col-lg-2 padding-top-5 pl-1">
                        <span class="pl-3">Télephone</span>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <input type="text" class="form-control" name="i_tel" placeholder="Télephone" value="<?php echo $i_tel->getValeur(); ?>"/>
                    </div>
                    <div class="col-md-6 col-lg-2 padding-top-5 pl-1">
                        <span class="pl-3">Fax</span>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <input type="text" class="form-control" name="i_fax" placeholder="Fax" value="<?php echo $i_fax->getValeur(); ?>"/>
                    </div>
                </div>

                <div class="form-group row mb-1">
                    <div class="col-md-6 col-lg-2 padding-top-5 pl-1">
                        <span class="pl-3">Info 1</span>
                    </div>
                    <div class="col-md-6 col-lg-10">
                        <input type="text" class="form-control" name="i_info_1" placeholder="Info 1" value="<?php echo $i_info_1->getValeur(); ?>"/>
                    </div>
                </div>

                <div class="form-group row mb-1">
                    <div class="col-md-6 col-lg-2 padding-top-5 pl-1">
                        <span class="pl-3">Info 2</span>
                    </div>
                    <div class="col-md-6 col-lg-10">
                        <input type="text" class="form-control" name="i_info_2" placeholder="Info 2" value="<?php echo $i_info_2->getValeur(); ?>"/>
                    </div>
                </div>

                <div class="form-group row mb-1">
                    <div class="col-md-6 col-lg-2 padding-top-5 pl-1">
                        <span class="pl-3">Site Web</span>
                    </div>
                    <div class="col-md-6 col-lg-10">
                        <input type="text" class="form-control" name="i_web" placeholder="www.url.com" value="<?php echo $i_web->getValeur(); ?>"/>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 text-center mb-2">
                        <span class="badge badge-secondary text-20 w-100">Service comptabilité</span>
                    </div>
                </div>

                <div class="form-group row mb-1">
                    <div class="col-md-6 col-lg-2 padding-top-5 pl-1">
                        <span class="pl-3">Adresse</span>
                    </div>
                    <div class="col-md-6 col-lg-10">
                        <input type="text" class="form-control" name="i_sec_adresse_1" placeholder="Adresse postale" value="<?php echo $i_sec_adresse_1->getValeur(); ?>"/>
                    </div>
                </div>

                <div class="form-group row mb-1">
                    <div class="col-md-6 col-lg-2 padding-top-5 pl-1">
                        <span class="pl-3">Ligne 2</span>
                    </div>
                    <div class="col-md-6 col-lg-10">
                        <input type="text" class="form-control" name="i_sec_adresse_2" placeholder="Code postal et ville" value="<?php echo $i_sec_adresse_2->getValeur(); ?>"/>
                    </div>
                </div>

                <div class="form-group row mb-1">
                    <div class="col-md-6 col-lg-2 padding-top-5 pl-1">
                        <span class="pl-3">Télephone</span>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <input type="text" class="form-control" name="i_sec_tel" placeholder="Télephone" value="<?php echo $i_sec_tel->getValeur(); ?>"/>
                    </div>
                    <div class="col-md-6 col-lg-2 padding-top-5 pl-1">
                        <span class="pl-3">Fax</span>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <input type="text" class="form-control" name="i_sec_fax" placeholder="Fax" value="<?php echo $i_sec_fax->getValeur(); ?>"/>
                    </div>
                </div>

                <div class="form-group row mb-1">
                    <div class="col-md-6 col-lg-2 padding-top-5 pl-1">
                        <span class="pl-3">Info libre</span>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <input type="text" class="form-control" name="i_sec_info_1" placeholder="Info libre" value="<?php echo $i_sec_info_1->getValeur(); ?>"/>
                    </div>
                    <div class="col-md-6 col-lg-2 padding-top-5 pl-1">
                        <span class="pl-3">E-mail</span>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <input type="text" class="form-control" name="i_sec_mail" placeholder="E-mail comptabilité" value="<?php echo $i_sec_mail->getValeur(); ?>"/>
                    </div>
                </div>

                <div class="form-group row mb-1">
                    <div class="col-md-6 col-lg-2 padding-top-5 pl-1">
                        <span class="pl-3">Incoterms</span>
                    </div>
                    <div class="col-md-6 col-lg-10">
                        <input type="text" class="form-control" name="incoterms" placeholder="Incoterms" value="<?php echo $incoterms->getValeur(); ?>"/>
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-12 text-center">
                        <div class="alert alert-info text-12 nomargin"><i class="fa fa-info-circle mr-1"></i>
                            Informations utilisés dans la génération des documents éditables (BL, factures)...
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 text-center mb-2">
                        <span class="badge badge-secondary text-20 w-100">Préférences d'envoi</span>
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-12 padding-top-5 pl-1">
                        <span class="pl-3">Adresses e-mail en copie des envois depuis IPREX. Plusieurs possibles : séparées par des points-virgules (;)</span>
                    </div>
                    <div class="col-12">
                        <input type="text" class="form-control" name="cc_mails" placeholder="exemple@domaine.com" value="<?php echo $cc_mails->getValeur(); ?>"/>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 text-center mb-2">
                        <span class="badge badge-secondary text-20 w-100">Bons de transfert</span>
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-6 col-lg-8 padding-top-5 pl-1">
                        <span class="pl-3">Dépot (client paramétré Stock/dépot) destinataire des BT</span>
                    </div>
                    <div class="col-6 col-lg-4">
                        <select class="selectpicker form-control show-tick" name="bt_clt" title="Sélectionnez">
                            <?php
							foreach ($tiersManager->getListeClients(['stk_type' => 2]) as $clt_stk) { ?>
                                <option value="<?php echo $clt_stk->getID(); ?>" <?php echo $clt_stk->getId() == (int)$bt_clt->getValeur() ? 'selected' : ''; ?>><?php echo $clt_stk->getNom(); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 text-center mb-2">
                        <span class="badge badge-secondary text-20 w-100">Connecteur Prestashop commandes Web</span>
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-lg-4 padding-top-5 pl-1">
                        <span class="pl-3">URL du site</span>
                    </div>
                    <div class="col-lg-8">
                        <input type="text" class="form-control" name="web_url" placeholder="https://" value="<?php echo $web_url->getValeur(); ?>"/>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-lg-4 padding-top-5 pl-1">
                        <span class="pl-3">Clef API</span>
                    </div>
                    <div class="col-lg-8">
                        <input type="text" class="form-control" name="web_api" placeholder="Webservice API KEY" value="<?php echo $web_api->getValeur(); ?>"/>
                    </div>
                </div>
            </div>
            <div class="alert alert-success">
                <div class="form-group row">
                    <div class="col-9 mt-2">
                     <i class="fa fa-info-circle mr-1"></i> Pensez à cliquer sur <b>Enregistrer</b> pour sauvegarder vos modifications <i class="fa fa-arrow-right ml-1"></i>
                    </div>
                    <div class="col-3 mt-1">
                        <button type="submit" name="gope" class="btn btn-success form-control"><i class="fa fa-check mr-1"></i> Enregistrer </button>
                    </div>
                </div>

            </div>
            <div class="alert alert-info text-11">
                <p class="nomargin">Format des numéros de Bons de livraisons, Bons de transferts, Factures et Avoirs :</p>
                <ul>
                    <li class="texte-fin mb-1"><kbd>BL</kbd> / <kbd>BT</kbd> / <kbd>FA</kbd> / <kbd>AV</kbd>  (Type de document)</li>
                    <li class="texte-fin mb-1"><kbd>Année</kbd> (du jour du document, sur deux chiffres)</li>
                    <li class="texte-fin mb-1"><kbd>Mois</kbd> (du jour du document, sur deux chiffres)</li>
                    <li class="texte-fin mb-2"><kbd>Ordre</kbd> (incrément du type de document dans le mois sur 3 chiffres)</li>
                    <li class="texte-fin">Exemple : <code>BL2110054</code> est le <u>54</u><sup>ème</sup> <u>BL</u> généré du <u>10</u><sup>è</sup> mois de l'année 20<u>21</u></li>
                </ul>
            </div>
        </div>
    </div>
</form>
<?php
include('includes/footer.php');


function createConfig($clef, $description, $valeur) {

    global $configManager;

	$confTmp = new Config([]);
	$confTmp->setClef($clef);
	$confTmp->setDescription($description);
	$confTmp->setValeur($valeur);
	$confTmp->setDate_maj(date('Y-m-d H:i:s'));
	$configManager->saveConfig($confTmp);

	return $confTmp;

}