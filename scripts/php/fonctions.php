<?php
function vd($variable) {
	return Outils::vd($variable);
}

function envoiMail($destinataires, $from, $titre, $corp, $accuse = 0, $copie_cache =[], $pieces_jointes = []) {

	require_once dirname( __FILE__ ).'/../../class/PHPMailer.php';
	require_once dirname( __FILE__ ).'/../../class/SMTP.php';
	$mail = new PHPMailer(true);



	try {


		foreach ($destinataires as $desti) {

			if (trim($desti) != '')	{
				if ($mail->ValidateAddress($desti))	{
					$mail->AddAddress($desti, '');
				}
			}
		}

		$mail->SetFrom($from);
		$mail->AddReplyTo($from);
		$mail->Subject = $titre;
		$mail->AltBody = 'Pour voir ce message votre client de messagerie doit accepter les mails HTML';
		$mail->MsgHTML($corp);

		if (!empty($copie_cache)) {
			foreach ($copie_cache as $bcc) {
				if (trim($bcc) != '') {
					$mail->AddBCC($bcc,"");
				}
			}
		}

		if ($accuse == 1) {
			$mail->ConfirmReadingTo=$from;
		}

		if (!empty($pieces_jointes)) {

			foreach ($pieces_jointes as $piece) {
				$mail->AddAttachment($piece);
			}
		}

		$mail->Send();

		return true;

	} catch (phpmailerException $e) {

		//vd($e);
		return false;
	} catch (Exception $e) {
		//vd($e);
		return false;
	}
}

/**
* Affichage et ou log d'une variable avec ou non un die
*
* @access public
* @param mixed 
*		$param			=>	Précise le mode de log (Chaîne de caractère comprenant ou non les codes suivants)
*                           - 'S'   => Echo (Screen)
*                           - 'D'   => Die
*                           - 'F'   => Fichier de log
*                           - 'J'   => JS (console.log)
* 		$variable		=>	Variable à afficher ou à logguer 	
*		$log_purge		=>	Précise si purge : 
*                           0 => Non, 
*                           -1 => suppression fichier avant, 
*                           sinon nbre de jours de log conservés 
*		$libelle		=>	Libellé à afficher sur l'écran ou sur le log
*		$log_file		=>	Chemin et nom du fichier de log
*		$start_time		=>	Précise si on renvoie une durée d'exécution 
* @return Néant
*/
function bvlog($param = 'S', $variable = Null, $log_purge = Null, $libelle = Null, $log_file = Null, $start_time = Null){

	$param  = strtoupper($param);
	$choix  = str_split($param);

	if(!$log_file){
		$log_file = './log/' . date('ymd') . '.log';
	}

	if($start_time){
		$end_time       = microtime(true);
		$executionTime  = $end_time - $start_time;
	}
	else {
		$executionTime  = 0;
	}

	if (in_array('S',$choix)){
		if($variable){
			echo '--------------------------------------------------------<br />';
			echo $libelle . ' ('. date("Y-m-d H:i:s") . ') : ' . '<pre>';
			print_r($variable);
			echo '</pre>';
			echo '<br />';    
		} else {
			echo '--------------------------------------------------------<br />';
		}
	}
	if (in_array('J',$choix)){
		if($variable){
			// $output = $variable;
			if (is_array($variable))
				$variable = implode(',', $variable);

			echo "<script>console.log('" . $libelle . ": " . $variable . "' );</script>";
		} else {
			echo "<script>console.log('--------------------------------------------------------' );</script>";
		}
	}
	if (in_array('F',$choix)){
		try {
			if($log_purge == -1){
				$tmp_log = fopen($log_file, "w+");
			}
			else {
				$tmp_log = fopen($log_file, "a+");
			}
			fputs($tmp_log, date('H:i:s') . " : " . $libelle . " (". number_format($executionTime,3,'.','') . ' sec' . ")\n");
			//fputs($tmp_log, var_export($variable));
			fclose($tmp_log);
		} catch (\Throwable $th) {
			$th = 'Erreur création de log.';
		}
	}
	if (in_array('D',$choix)){
		die($libelle);
	}
}