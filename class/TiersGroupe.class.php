<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet TiersGroupe
Généré par CBO FrameWork le 06/03/2020 à 14:36:38
------------------------------------------------------*/
class TiersGroupe {

	protected
		$id,
		$nom,
		$actif,
		$supprime,
		$date_add,
		$date_maj,
		$tiers;		// Array d'objets tiers

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

	public function getActif() {
		return $this->actif;
	}

	public function getSupprime() {
		return $this->supprime;
	}

	public function getDate_add() {
		return $this->date_add;
	}

	public function getDate_maj() {
		return $this->date_maj;
	}

	public function getTiers() {
		return $this->tiers;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setNom($nom) {
		$this->nom = (string)$nom;
		Outils::setAttributs('nom',$this);
	}

	public function setActif($actif) {
		$this->actif = (int)$actif;
		Outils::setAttributs('actif',$this);
	}

	public function setSupprime($supprime) {
		$this->supprime = (int)$supprime;
		Outils::setAttributs('supprime',$this);
	}

	public function setDate_add($date_add) {
		$this->date_add = (string)$date_add;
		Outils::setAttributs('date_add',$this);
	}

	public function setDate_maj($date_maj) {
		$this->date_maj = (string)$date_maj;
		Outils::setAttributs('date_maj',$this);
	}

	public function setTiers($tableau) {
		$this->tiers = (array)$tableau;
	}

} // FIN classe