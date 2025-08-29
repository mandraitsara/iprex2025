<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet Zone
Généré par CBO FrameWork le 10/03/2020 à 14:35:04
------------------------------------------------------*/
class Zone {

	protected
		$id,
		$code,
		$large,
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

	public function getCode() {
		return $this->code;
	}

	public function getLarge() {
		return $this->large;
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

	public function setCode($zone) {
		$this->code = (string)$zone;
		Outils::setAttributs('code',$this);
	}

	public function setActif($actif) {
		$this->actif = (int)$actif;
		Outils::setAttributs('actif',$this);
	}

	public function setLarge($large) {
		$this->large = (int)$large;
		Outils::setAttributs('large',$this);
	}

	public function setSupprime($supprime) {
		$this->supprime = (int)$supprime;
		Outils::setAttributs('supprime',$this);
	}

} // FIN classe