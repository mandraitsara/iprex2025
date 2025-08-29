<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2020 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet FraisFonctionnement
Généré par CBO FrameWork le 28/10/2020 à 10:32:11
------------------------------------------------------*/
class FraisFonctionnement {

	const PERIODICITE = [
		0 => 'annuel',
		1 => 'semestriel',
		2 => 'trimestriel',
		3 => 'mensuel',
		4 => 'hebdomadaire',
		5 => 'quotidien'
	];

	const PERIODICITE_JOURS = [
		0 => 365,
		1 => 365/2,
		2 => 365/4,
		3 => 365/12,
		4 => 365/52,
		5 => 1
	];

	protected
		$id,
		$montant,
		$periodicite,
		$nom,
		$activation,
		$supprime;

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
		return (int)$this->id;
	}

	public function getMontant() {
		return (float)$this->montant;
	}

	public function getPeriodicite() {
		return (int)$this->periodicite;
	}

	public function getNom() {
		return $this->nom;
	}

	public function getActivation() {
		return (int)$this->activation;
	}

	public function getSupprime() {
		return (int)$this->supprime;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setMontant($montant) {
		$this->montant = (float)$montant;
		Outils::setAttributs('montant',$this);
	}

	public function setPeriodicite($periodicite) {
		$this->periodicite = (int)$periodicite;
		Outils::setAttributs('periodicite',$this);
	}

	public function setNom($nom) {
		$this->nom = (string)$nom;
		Outils::setAttributs('nom',$this);
	}

	public function setActivation($activation) {
		$this->activation = (int)$activation;
		Outils::setAttributs('activation',$this);
	}

	public function setSupprime($supprime) {
		$this->supprime = (int)$supprime;
		Outils::setAttributs('supprime',$this);
	}

	/* ------------- METHODES --------------*/
	public function isActif() {
		return (int)$this->activation == 1;
	}

} // FIN classe