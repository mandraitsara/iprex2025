<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2020 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet NettoyageLocalAlerte
Généré par CBO FrameWork le 02/04/2021 à 11:30:26
------------------------------------------------------*/
class NettoyageLocalAlerte {

	protected    $id,
		$id_nett_local,
		$mois,
		$semaine,
		$jour,
		$heure,
		$minute;

	public       $attributs = array();

	public function __construct($donnees = [])	{
		$this->hydrate($donnees);
	}

	public function hydrate(array $donnees)	{
		foreach ($donnees as $key => $value) {
			$method = 'set'.ucfirst(strtolower($key));
			if (method_exists($this,$method)) {
				$this->$method($value);
			}
		}
		$this->attributs = array();
	}

	/* ----------------- GETTERS ----------------- */

	public function getId() {
		return $this->id;
	}

	public function getId_nett_local() {
		return $this->id_nett_local;
	}

	public function getMois() {
		return $this->mois;
	}

	public function getSemaine() {
		return $this->semaine;
	}

	public function getJour() {
		return $this->jour;
	}

	public function getHeure() {
		return $this->heure;
	}

	public function getMinute() {
		return $this->minute;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setId_nett_local($id_nett_local) {
		$this->id_nett_local = (int)$id_nett_local;
		Outils::setAttributs('id_nett_local',$this);
	}

	public function setMois($mois) {
		$this->mois = $mois;
		Outils::setAttributs('mois',$this);
	}

	public function setSemaine($semaine) {
		$this->semaine = $semaine;
		Outils::setAttributs('semaine',$this);
	}

	public function setJour($jour) {
		$this->jour = $jour;
		Outils::setAttributs('jour',$this);
	}

	public function setHeure($heure) {
		$this->heure = (int)$heure;
		Outils::setAttributs('heure',$this);
	}

	public function setMinute($minute) {
		$this->minute = (int)$minute;
		Outils::setAttributs('minute',$this);
	}

	/* ---------------- METHODES -----------------*/

	public function getMoisArray() {
		if (trim($this->mois) == '') { return []; }
		return explode(',', $this->mois);
	}

	public function getSemainesArray() {
		if (trim($this->semaine) == '') { return []; }
		return explode(',', $this->semaine);
	}

	public function getJoursArray() {
		if (trim($this->jour) == '') { return []; }
		return explode(',', $this->jour);
	}


} // FIN classe