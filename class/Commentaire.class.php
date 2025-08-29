<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet Commentaire
Généré par CBO FrameWork le 30/07/2019 à 17:41:17
------------------------------------------------------*/
class Commentaire {

	protected    $id,
		$id_lot,
		$id_froid,
		$negoce,
		$commentaire,
		$incident,
		$date,
		$id_user;

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

	public function getId_lot() {
		return $this->id_lot;
	}

	public function getId_froid() {
		return $this->id_froid;
	}

	public function getIncident() {
		return $this->incident;
	}

	public function getCommentaire() {
		return $this->commentaire;
	}

	public function getDate() {
		return $this->date;
	}

	public function getId_user() {
		return $this->id_user;
	}

	public function getNegoce() {
		return $this->negoce;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setId_lot($id_lot) {
		$this->id_lot = (int)$id_lot;
		Outils::setAttributs('id_lot',$this);
	}

	public function setId_froid($id_froid) {
		$this->id_froid = (int)$id_froid;
		Outils::setAttributs('id_froid',$this);
	}

	public function setIncident($incident) {
		$this->incident = (int)$incident;
		Outils::setAttributs('incident',$this);
	}

	public function setCommentaire($commentaire) {
		$this->commentaire = (string)$commentaire;
		Outils::setAttributs('commentaire',$this);
	}

	public function setDate($date) {
		$this->date = (string)$date;
		Outils::setAttributs('date',$this);
	}

	public function setId_user($id_user) {
		$this->id_user = (int)$id_user;
		Outils::setAttributs('id_user',$this);
	}

	public function setNegoce($valeur) {
		$this->negoce = (int)$valeur;
		Outils::setAttributs('negoce',$this);
	}

} // FIN classe