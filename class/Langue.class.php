<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet Langue
Généré par CBO FrameWork le 06/03/2020 à 15:32:54
------------------------------------------------------*/
class Langue {

	protected    $id,
		$nom,
		$iso,
		$ordre,
		$actif,
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
		return strtolower($this->iso);
	}

	public function getOrdre() {
		return $this->ordre;
	}

	public function getActif() {
		return $this->actif;
	}

	public function getSupprime() {
		return $this->supprime;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setNom($nom) {
		$this->nom = (string)$nom;
		Outils::setAttributs('nom',$this);
	}

	public function setIso($iso) {
		$this->iso = (string)$iso;
		Outils::setAttributs('iso',$this);
	}

	public function setOrdre($iso) {
		$this->ordre = (int)$iso;
		Outils::setAttributs('ordre',$this);
	}

	public function setActif($actif) {
		$this->actif = (int)$actif;
		Outils::setAttributs('actif',$this);
	}

	public function setSupprime($supprime) {
		$this->supprime = (int)$supprime;
		Outils::setAttributs('supprime',$this);
	}


	public function getDrapeau() {
		return __CBO_IMG_URL__.'/flags/' . strtolower($this->iso) . '.png';
	}

} // FIN classe