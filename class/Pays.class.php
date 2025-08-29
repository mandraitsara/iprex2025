<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet Pays
Généré par CBO FrameWork le 04/03/2020 à 09:25:59
------------------------------------------------------*/
class Pays {

	protected
		$id,
		$nom,			// Traduction française récupérée par défaut
		$noms,			// Traductions (array)
		$iso,
		$code,
		$export,
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
		return $this->id;
	}

	public function getNom() {
		return $this->nom;
	}

	public function getIso() {
		return $this->iso;
	}

	public function getCode() {
		return $this->code;
	}

	public function getSupprime() {
		return $this->supprime;
	}

	public function getNoms() {
		return $this->noms;
	}

	public function getExport() {
		return $this->export;
	}

	public function isExport() {
		return intval($this->export) == 1;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setNoms($valeurs) {
		$this->noms = $valeurs;
	}

	public function setNom($nom) {
		$this->nom = (string)$nom;
	}

	public function setIso($iso) {
		$this->iso = (string)$iso;
		Outils::setAttributs('iso',$this);
	}

	public function setCode($code) {
		$this->code = (string)$code;
		Outils::setAttributs('code',$this);
	}

	public function setSupprime($valeur) {
		$this->supprime = (int)$valeur;
		Outils::setAttributs('supprime',$this);
	}

	public function setExport($valeur) {
		$this->export = (int)$valeur;
		Outils::setAttributs('export',$this);
	}

} // FIN classe