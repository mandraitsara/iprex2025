<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet Traduction
Généré par CBO FrameWork le 06/03/2020 à 15:35:37
------------------------------------------------------*/
class Traduction {

	protected
		$id,
		$scope,
		$zone,
		$id_langue,
		$iso,
		$texte;

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

	public function getScope() {
		return $this->scope;
	}

	public function getZone() {
		return $this->zone;
	}

	public function getId_langue() {
		return $this->id_langue;
	}

	public function getIso() {
		return strtolower($this->iso);
	}

	public function getTexte() {
		return $this->texte;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setScope($zone) {
		$this->scope = (string)$zone;
		Outils::setAttributs('scope',$this);
	}

	public function setZone($zone) {
		$this->zone = (string)$zone;
		Outils::setAttributs('zone',$this);
	}

	public function setId_langue($id_langue) {
		$this->id_langue = (int)$id_langue;
		Outils::setAttributs('id_langue',$this);
	}

	public function setIso($iso) {
		$this->iso = (string)$iso;
		Outils::setAttributs('iso',$this);
	}

	public function setTexte($texte) {
		$this->texte = (string)$texte;
		Outils::setAttributs('texte',$this);
	}

} // FIN classe