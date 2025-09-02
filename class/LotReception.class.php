<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Objet LotReception
------------------------------------------------------*/
class LotReception {
	
	protected	$id,
				$id_lot,
				$id_lot_negoce,
				$temp,
				$temp_d,
				$temp_m,
				$temp_f,
				$etat_visuel,
				$conformite,
				$observations,
				$crochets_recus,
				$crochets_rendus,
				$id_transporteur,
				$nom_transporteur,
				$id_user,
				$user_nom,
				$date_confirmation,
				$validation,
				$validateur_nom,
				$validation_date;

	public		$attributs = array();
	
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
	
	/*##### GETTERS #####*/

	public function getId() {
		return $this->id;
	}

	public function getId_lot_negoce() {
		return $this->id_lot_negoce;
	}
	
	public function getId_lot() {
		return $this->id_lot;
	}

	public function getTemp() {
		return $this->temp;
	}

	public function getTemp_d() {
		return $this->temp_d;
	}

	public function getTemp_m() {
		return $this->temp_m;
	}

	public function getTemp_f() {
		return $this->temp_f;
	}

	public function getEtat_visuel() {
		return $this->etat_visuel;
	}

	public function getConformite() {
		return $this->conformite;
	}

	public function getObservations() {
		return $this->observations;
	}

	public function getId_user() {
		return $this->id_user;
	}

	public function getUser_nom() {
		return $this->user_nom;
	}

	public function getDate_confirmation() {
		return $this->date_confirmation;
	}

	public function getValidation() {
		return $this->validation;
	}

	public function getValidateur_nom() {
		return $this->validateur_nom;
	}

	public function getValidation_date() {
		return $this->validation_date;
	}

	public function getCrochets_recus() {
		return (int)$this->crochets_recus;
	}

	public function getCrochets_rendus() {
		return (int)$this->crochets_rendus;
	}

	public function getId_transporteur() {
		return (int)$this->id_transporteur;
	}

	public function getNom_transporteur() {
		return (string)$this->nom_transporteur;
	}


	/*##### SETTERS #####*/

	public function setId($valeur) {
		$this->id = (int)$valeur;
	}

	public function setId_lot($valeur) {
		$this->id_lot = (int)$valeur;
		Outils::setAttributs('id_lot',$this);
	}

	public function setId_lot_negoce($valeur) {
		$this->id_lot_negoce = (int)$valeur;
		Outils::setAttributs('id_lot_negoce',$this);
	}

	public function setTemp($valeur) {
		$this->temp = (float)$valeur;
		Outils::setAttributs('temp',$this);
	}

	public function setTemp_d($valeur) {
		$this->temp_d = (float)$valeur;
		Outils::setAttributs('temp_d',$this);
	}

	public function setTemp_m($valeur) {
		$this->temp_m = (float)$valeur;
		Outils::setAttributs('temp_m',$this);
	}

	public function setTemp_f($valeur) {
		$this->temp_f = (float)$valeur;
		Outils::setAttributs('temp_f',$this);
	}

	public function setEtat_visuel($valeur) {
		$this->etat_visuel = (int)$valeur;
		Outils::setAttributs('etat_visuel',$this);
	}

	public function setConformite($valeur) {
		$this->conformite = (int)$valeur;
		Outils::setAttributs('conformite',$this);
	}

	public function setObservations($valeur) {
		$this->observations = (string)$valeur;
		Outils::setAttributs('observations',$this);
	}

	public function setId_user($valeur) {
		$this->id_user = (int)$valeur;
		Outils::setAttributs('id_user',$this);
	}

	public function setUser_nom($valeur) {
		$this->user_nom = (string)$valeur;
	}

	public function setDate_confirmation($valeur) {
		$this->date_confirmation= (string)$valeur;
		Outils::setAttributs('date_confirmation',$this);
	}

	public function setCrochets_recus($valeur) {
		$this->crochets_recus= (int)$valeur;
		Outils::setAttributs('crochets_recus',$this);
	}

	public function setCrochets_rendus($valeur) {
		$this->crochets_rendus= (int)$valeur;
		Outils::setAttributs('crochets_rendus',$this);
	}

	public function setId_transporteur($valeur) {
		$this->id_transporteur = (int)$valeur;
		Outils::setAttributs('id_transporteur',$this);
	}

	public function setNom_transporteur($valeur) {
		$this->nom_transporteur = (string)$valeur;
	}

	public function setValidation($valeur) {
		$this->validation = (int)$valeur;
	}

	public function setValidateur_nom($valeur) {
		$this->validateur_nom = (string)$valeur;
	}

	public function setValidation_date($valeur) {
		$this->validation_date = (string)$valeur;
	}


	/*##### MTHODES PROPRES A LA CLASSE #####*/

	public function getEtat_visuel_verbose() {

		if ((int)$this->etat_visuel < 0 ) { return 'N/A'; }
		return (int)$this->etat_visuel == 1 ? 'Satisfaisant' : 'Contestable';
	}

	public function getConformite_verbose() {

		if ((int)$this->conformite < 0 ) { return 'N/A'; }
		return (int)$this->conformite == 1 ? 'Conforme' : 'Non conforme';
	}

	public function isValidee() {
			return (int)$this->validation > 0;
	}

	public function isConfirmee() {
		return $this->date_confirmation != '' &&  $this->date_confirmation != '0000-00-00 00:00:00' && (int)$this->getId_user() > 0;
	}

} // FIN classe