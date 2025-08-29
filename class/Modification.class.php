<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet Modification
------------------------------------------------------*/
class Modification {

	protected
		$id,
		$user_id,
		$nom_user,
		$date,
		$id_froid,
		$id_lot_pdt_froid,
		$champ,
		$valeur_old,
		$valeur_new;

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

	public function getUser_id() {
		return $this->user_id;
	}

	public function getNom_user() {
		return $this->nom_user;
	}

	public function getDate() {
		return $this->date;
	}

	public function getId_froid() {
		return $this->id_froid;
	}

	public function getId_lot_pdt_froid() {
		return $this->id_lot_pdt_froid;
	}

	public function getChamp() {
		return $this->champ;
	}

	public function getValeur_old() {
		return $this->valeur_old;
	}

	public function getValeur_new() {
		return $this->valeur_new;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setUser_id($user_id) {
		$this->user_id = (int)$user_id;
		Outils::setAttributs('user_id',$this);
	}

	public function setNom_user($nom_user) {
		$this->nom_user = (string)$nom_user;
	}

	public function setDate($date) {
		$this->date = (string)$date;
		Outils::setAttributs('date',$this);
	}

	public function setId_froid($id_froid) {
		$this->id_froid = (int)$id_froid;
		Outils::setAttributs('id_froid',$this);
	}

	public function setId_lot_pdt_froid($id_lot_pdt_froid) {
		$this->id_lot_pdt_froid = (int)$id_lot_pdt_froid;
		Outils::setAttributs('id_lot_pdt_froid',$this);
	}

	public function setChamp($champ) {
		$this->champ = (string)$champ;
		Outils::setAttributs('champ',$this);
	}

	public function setValeur_old($valeur_old) {
		$this->valeur_old = (string)$valeur_old;
		Outils::setAttributs('valeur_old',$this);
	}

	public function setValeur_new($valeur_new) {
		$this->valeur_new = (string)$valeur_new;
		Outils::setAttributs('valeur_new',$this);
	}

} // FIN classe