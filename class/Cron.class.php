<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2021 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet Cron
------------------------------------------------------*/
class Cron {

	protected    $id,
		$fichier,
		$chemin,
		$description,
		$minute,
		$heure,
		$jour_mois,
		$mois,
		$jour_sem,
		$actif,
		$info,
		$execution;

	public       $attributs = array();

	public function __construct(array $donnees)	{
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

	public function getFichier() {
		return $this->fichier;
	}

	public function getChemin() {
		return $this->chemin;
	}

	public function getDescription() {
		return $this->description;
	}

	public function getActif() {
		return $this->actif;
	}

	public function isActif() {
		return (int)$this->actif == 1;
	}

	public function getInfo() {
		return $this->info;
	}

	public function getMinute() {
		return $this->minute >= 0 ? $this->minute : '*';
	}

	public function getHeure() {
		return $this->heure  >= 0 ? $this->heure : '*';
	}

	public function getJour_mois() {
		return $this->jour_mois >= 0 ? $this->jour_mois : '*';
	}

	public function getMois() {
		return $this->mois >= 0 ? $this->mois : '*';
	}

	public function getJour_sem() {
		return $this->jour_sem >= 0 ? $this->jour_sem : '*';
	}

	public function getExecution() {
		return $this->execution;
	}

	public function getExecutionWithoutSeconds() {
		return $this->execution != '' ? substr($this->execution,0,-3): '';
	}


	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setFichier($valeur) {
		$this->fichier = (string)$valeur;
		Outils::setAttributs('fichier',$this);
	}

	public function setChemin($valeur) {
		$this->chemin = (string)$valeur;
		Outils::setAttributs('chemin',$this);
	}

	public function setDescription($valeur) {
		$this->description = (string)$valeur;
		Outils::setAttributs('description',$this);
	}

	public function setInfo($valeur) {
		$this->info = (string)$valeur;
		Outils::setAttributs('info',$this);
	}

	public function setMinute($valeur) {
		$this->minute = (int)$valeur;
		Outils::setAttributs('minute',$this);
	}

	public function setHeure($valeur) {
		$this->heure = (int)$valeur;
		Outils::setAttributs('heure',$this);
	}

	public function setMois($valeur) {
		$this->mois = (int)$valeur;
		Outils::setAttributs('mois',$this);
	}

	public function setJour_sem($valeur) {
		$this->jour_sem = (int)$valeur;
		Outils::setAttributs('jour_sem',$this);
	}

	public function setJour_mois($valeur) {
		$this->jour_mois = (int)$valeur;
		Outils::setAttributs('jour_mois',$this);
	}

	public function setActif($valeur) {
		$this->actif = (int)$valeur;
		Outils::setAttributs('actif',$this);
	}

	public function setExecution($valeur) {
		$this->execution = (string)$valeur;
		Outils::setAttributs('execution',$this);
	}


} // FIN classe