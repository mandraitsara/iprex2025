<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet PvisuApresPoints
Généré par CBO FrameWork le 03/09/2020 à 11:13:53
------------------------------------------------------*/
class PvisuApresPoints {

	protected
		$id,
		$nom,
		$id_pvisu_apres,
		$id_point_controle,
		$etat,
		$id_pvisu_action,
		$nom_action,
		$fiche_nc,
		$id_user,
		$date;

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


	public function getId_pvisu_apres() {
		return $this->id_pvisu_apres;
	}

	public function getId_point_controle() {
		return $this->id_point_controle;
	}

	public function getEtat() {
		return $this->etat;
	}

	public function getId_pvisu_action() {
		return $this->id_pvisu_action;
	}

	public function getNom_action() {
		return $this->nom_action;
	}

	public function getFiche_nc() {
		return $this->fiche_nc;
	}

	public function getId_user() {
		return $this->id_user;
	}

	public function getDate() {
		return $this->date;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setNom($nom) {
		$this->nom = (string)$nom;
	}

	public function setId_pvisu_apres($id_pvisu_apres) {
		$this->id_pvisu_apres = (int)$id_pvisu_apres;
		Outils::setAttributs('id_pvisu_apres',$this);
	}

	public function setId_point_controle($id_point_controle) {
		$this->id_point_controle = (int)$id_point_controle;
		Outils::setAttributs('id_point_controle',$this);
	}

	public function setEtat($etat) {
		$this->etat = (int)$etat;
		Outils::setAttributs('etat',$this);
	}

	public function setId_pvisu_action($id_pvisu_action) {
		$this->id_pvisu_action = (int)$id_pvisu_action;
		Outils::setAttributs('id_pvisu_action',$this);
	}

	public function setNom_action($nom_action) {
		$this->nom_action = (string)$nom_action;
	}

	public function setFiche_nc($fiche_nc) {
		$this->fiche_nc = (int)$fiche_nc;
		Outils::setAttributs('fiche_nc',$this);
	}

	public function setId_user($id_user) {
		$this->id_user = (int)$id_user;
		Outils::setAttributs('id_user',$this);
	}

	public function setDate($date) {
		$this->date = (string)$date;
		Outils::setAttributs('date',$this);
	}

} // FIN classe