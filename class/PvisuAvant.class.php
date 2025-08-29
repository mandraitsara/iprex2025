<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet PvisuAvant
Généré par CBO FrameWork le 03/09/2020 à 11:11:31
------------------------------------------------------*/
class PvisuAvant {

	protected
		$id,
		$id_lot,
		$numlot,
		$date,
		$commentaires,
		$id_user,
		$id_user_validation,
		$date_validation,
		$nom_user,
		$nom_validateur,
		$points_controles;	// Array d'objets PvisuAvantPoints

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

	public function getId_lot() {
		return $this->id_lot;
	}

	public function getNumlot() {
		return $this->numlot;
	}


	public function getDate() {
		return $this->date;
	}

	public function getId_user() {
		return $this->id_user;
	}

	public function getNom_user() {
		return $this->nom_user;
	}

	public function getCommentaires() {
		return $this->commentaires;
	}

	public function getId_user_validation() {
		return $this->id_user_validation;
	}

	public function getDate_validation() {
		return $this->date_validation;
	}

	public function getNom_validateur() {
		return $this->nom_validateur;
	}

	public function getPoints_controles() {
		return $this->points_controles;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setId_lot($id_lot) {
		$this->id_lot = (int)$id_lot;
		Outils::setAttributs('id_lot',$this);
	}

	public function setnumlot($numlot) {
		$this->numlot = $numlot;
	}

	public function setDate($date) {
		$this->date = (string)$date;
		Outils::setAttributs('date',$this);
	}

	public function setId_user($id_user) {
		$this->id_user = (int)$id_user;
		Outils::setAttributs('id_user',$this);
	}

	public function setId_user_validation($id_user_validation) {
		$this->id_user_validation = (int)$id_user_validation;
		Outils::setAttributs('id_user_validation',$this);
	}

	public function setDate_validation($date_validation) {
		$this->date_validation = (string)$date_validation;
		Outils::setAttributs('date_validation',$this);
	}

	public function setNom_user($user) {
		$this->nom_user = (string)$user;
	}

	public function setNom_validateur($validateur) {
		$this->nom_validateur = (string)$validateur;
	}

	public function setCommentaires($commentaires) {
		$this->commentaires = (string)$commentaires;
		Outils::setAttributs('commentaires',$this);
	}


	public function setPoints_controles($pc) {
		$this->points_controles = $pc;
	}

} // FIN classe