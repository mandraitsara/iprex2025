<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet Contact
Généré par CBO FrameWork le 04/03/2020 à 09:22:59
------------------------------------------------------*/
class Contact {

	protected    $id,
		$id_tiers,
		$nom,
		$prenom,
		$telephone,
		$mobile,
		$fax,
		$email,
		$supprime,
		$date_add,
		$date_maj;

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

	public function getId_tiers() {
		return $this->id_tiers;
	}

	public function getNom() {
		return $this->nom;
	}

	public function getPrenom() {
		return $this->prenom;
	}

	public function getTelephone() {
		return $this->telephone;
	}

	public function getMobile() {
		return $this->mobile;
	}

	public function getFax() {
		return $this->fax;
	}

	public function getEmail() {
		return strtolower($this->email);
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

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setId_tiers($id_tiers) {
		$this->id_tiers = (int)$id_tiers;
		Outils::setAttributs('id_tiers',$this);
	}

	public function setNom($nom) {
		$this->nom = (string)$nom;
		Outils::setAttributs('nom',$this);
	}

	public function setPrenom($prenom) {
		$this->prenom = (string)$prenom;
		Outils::setAttributs('prenom',$this);
	}

	public function setTelephone($telephone) {
		$this->telephone = (string)$telephone;
		Outils::setAttributs('telephone',$this);
	}

	public function setMobile($mobile) {
		$this->mobile = (string)$mobile;
		Outils::setAttributs('mobile',$this);
	}

	public function setFax($fax) {
		$this->fax = (string)$fax;
		Outils::setAttributs('fax',$this);
	}

	public function setEmail($email) {
		$this->email = (string)$email;
		Outils::setAttributs('email',$this);
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

	public function getNom_complet() {
		return ucwords(strtolower($this->prenom)) . ' ' . strtoupper($this->nom);
	}

} // FIN classe