<?php
ini_set('display_errors',1);
$version = '7.0';

/**
 * Ce script permet de nettoyer le dossier contenant les sauvegardes du site internet en fonction des différents noms d'archives
 * - Garde toutes les archives des dernières 24H
 * - Garde 1 archive par jour du dernier mois
 * - Garde 1 archive par semaine du début du 2ème mois à la fin du 4ème mois
 * - Garde 1 sauvegarde par mois du début du 5ème mois à la fin du 12ème mois
 * - Garde 1 sauvegarde par an au delà de 12 mois
 **/

//  --------------------------------- PARAMETRES -------------------------------------
// Chemin relatif depuis ce script vers le répertoire contenant toutes les sauvegardes
$rep_archives = dirname(__FILE__).'/../../saves';
// --------------------------------- FIN PARAMETRES ----------------------------------

$archives = array();

$siteName = 'iprex';

if ($handle = opendir($rep_archives)) {

	while (false !== ($file = readdir($handle))) {

		if ($file != "." && $file != ".." && is_file($rep_archives.'/'.$file)) {

			$date_file = filemtime($rep_archives.'/'.$file);



			if($date_file < time() - 60 * 60 * 24 * 365) {
				$annee_file = date('Y', $date_file);

				// On stock le fichier dans un tableau en fonction du nom du site
				$archives[$siteName]['p12'][$annee_file][] = $file;

			} else if($date_file < time() - 60 * 60 * 24 * (365 / 2)) {

				$mois_file = date('m', $date_file);

				// On stock le fichier dans un tableau en fonction du nom du site et du mois
				$archives[$siteName]['p4'][$mois_file][] = $file;

			} else if($date_file < time() - 60 * 60 * 24 * 31) {

				$semaine_file = date('W', $date_file);

				// On stock le fichier dans un tableau en fonction du nom du site et de la semaine
				$archives[$siteName]['p1'][$semaine_file][] = $file;

				// Si la date du fichier est inférieure à J-1 : une sauvegarde par jour
			} else if($date_file < time() - 60 * 60 * 24) {

				$moisjour_file = date('d', $date_file);

				// On stock le fichier dans un tableau en fonction du nom du site et du jour
				$archives[$siteName]['m1'][$moisjour_file][] = $file;
			}
		}
	}
	closedir($handle);

	$compt = 0;


	foreach ($archives as $siteName => $tabIndicesFile) {

		foreach($tabIndicesFile as $indice => $TabCleMoisSemJourFile) {


			if ($indice == 'p12') {

				//Trie le tableau contenant les fichiers par indice par ordre décroissant.
				//Ainsi la date la plus récente se trouve dans la variable $TabCleMoisSemJourFile[0]
				rsort($TabCleMoisSemJourFile);

				foreach ($TabCleMoisSemJourFile as $cle => $file) {

					if ($cle > 0) {
						echo $file.'<br/>';
						unlink($rep_archives.'/'.$file);
					}
				}
			} else if ($indice == 'p4') {

				foreach ($TabCleMoisSemJourFile as $mois => $files)	{

					// Trie le tableau contenant les fichiers par mois par ordre décroissant.
					// Ainsi la date la plus récente se trouve dans la variable $files[0]
					rsort($files);

					foreach ($files as $cle => $file) {

						if ($cle > 0) {

							echo $file.'<br/>';
							unlink($rep_archives.'/'.$file);
						}
					}
				}

			} else if($indice == 'p1') {

				foreach ($TabCleMoisSemJourFile as $sem => $files) {

					// Trie le tableau contenant les fichiers par semaine par ordre décroissant.
					// Ainsi la date la plus récente se trouve dans la variable $files[0]

					rsort($files);

					foreach ($files as $cle => $file) {

						if ($cle > 0) {

							echo $file.'<br/>';
							unlink($rep_archives.'/'.$file);
						}
					}
				}

			} else if($indice == 'm1') {

				foreach ($TabCleMoisSemJourFile as $moisjour => $files) {

					//Trie le tableau contenant les fichiers par jour par ordre décroissant.
					//Ainsi la date la plus récente se trouve dans la variable $files[0]
					rsort($files);

					foreach ($files as $cle => $file) {

						if ($cle > 0) {

							echo $file.'<br/>';
							unlink($rep_archives.'/'.$file);
						}
					}
				}
			}
		}
	}
}