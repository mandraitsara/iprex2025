<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|

--------------------------------------------------------
Téléchargement de fichiers
------------------------------------------------------*/
require_once 'config.php';

$dossier = isset($_REQUEST['doc']) ? __CBO_ROOT_URL__.'docs' : __CBO_TEMP_URL__.'temp';
$dossier = isset($_REQUEST['pl']) ? __CBO_ROOT_URL__.'gescom/packing_list' : $dossier;
$dossier = isset($_REQUEST['bl']) ? __CBO_ROOT_URL__.'gescom/bl' : $dossier;
$dossier = isset($_REQUEST['fa']) ? __CBO_ROOT_URL__.'gescom/facture' : $dossier;


if (isset($_REQUEST["file"]) || isset($_REQUEST["f"])) {

	// Récupération des paramètres
	$file 		= isset($_REQUEST["f"]) ? urldecode(base64_decode($_REQUEST["f"])) : urldecode($_REQUEST["file"]); // Decode la chaine URL encodée

	$filepath 	= $dossier."/" . $file;

	// Process download
	if (file_exists(str_replace(__CBO_ROOT_URL__,__CBO_ROOT_PATH__.'/',$filepath))) {
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($filepath));
		flush(); // Flush system output buffer
		readfile($filepath);
		exit;
	} else {
		echo 'Fichier introuvable !';
	}
}
