<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2020 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet NettoyageSignature
Généré par CBO FrameWork le 18/03/2021 à 11:07:33
------------------------------------------------------*/
class NettoyageSignature {

	protected
		$id,
		$id_user,
		$date,
		$nom_user,
		$id_validateur,
		$nom_validateur,
		$date_visa;
	
	public
		$date_only,
		$heure_only;

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

	public function getId_user() {
		return $this->id_user;
	}

	public function getDate() {
		return $this->date;
	}

	public function getNom_user() {
		return $this->nom_user;
	}

	public function getHeure() {
		return $this->heure_only;
	}

	public function getId_validateur()
	{
		return $this->id_validateur;
	}

	public function getDate_visa(){
		return $this->date_visa;
	}

	public function getNom_validateur(){
		return $this->nom_validateur;
	}
	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setId_user($id_user) {
		$this->id_user = (int)$id_user;
		Outils::setAttributs('id_user',$this);
	}

	public function setDate($date) {
		$this->date = (string)$date;
		$this->date_only = date('Y-m-d', strtotime(($date)));
		$this->heure_only = date('H:i', strtotime(($date)));
		Outils::setAttributs('date',$this);
	}

	public function setNom_user($user_nom) {
		$this->nom_user = (string)$user_nom;
	}
	
	public function setId_validateur($id_validateur){
		$this->id_validateur = $id_validateur;
		Outils::setAttributs('id_validateur', $this);
	}
	
	public function setDate_visa($date_visa){
		$this->date_visa = $date_visa;
		Outils::setAttributs('date_visa', $this);
	}

	public function setNom_validateur($nom_validateur){
		$this->nom_validateur = $nom_validateur;
		Outils::setAttributs('nom_validateur', $this);
	}
} // FIN classe