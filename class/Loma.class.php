<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Objet Loma
------------------------------------------------------*/
class Loma {

	protected
		$id,
		$id_lot_pdt_froid,
		$id_client,
		$id_froid,
		$cond_debut,
		$cond_fin,
		$date_test,
		$test_pdt,
		$commentaire,
		$id_user_visa,
		$nom_user,
		$nom_produit,
		$code_froid;

	public $attributs = array();

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

	public function getId_lot_pdt_froid() {
		return $this->id_lot_pdt_froid;
	}

	public function getId_client() {
		return $this->id_client;
	}

	public function getId_froid() {
		return $this->id_froid;
	}

	public function getCond_fin() {
		return $this->cond_fin;
	}

	public function getCond_debut() {
		return $this->cond_debut;
	}

	public function getDate_test() {
		return $this->date_test;
	}

	public function getTest_pdt() {
		return $this->test_pdt;
	}

	public function getCommentaire() {
		return $this->commentaire;
	}

	public function getId_user_visa() {
		return $this->id_user_visa;
	}

	public function getNom_user() {
		return $this->nom_user;
	}

	public function getNom_produit() {
		return $this->nom_produit;
	}

	public function getCode_froid() {
		return $this->code_froid;
	}


	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}


	public function setId_lot_pdt_froid($valeur) {
		$this->id_lot_pdt_froid = (int)$valeur;
		Outils::setAttributs('id_lot_pdt_froid',$this);
	}

	public function setId_client($valeur) {
		$this->id_client = (int)$valeur;
		Outils::setAttributs('id_client',$this);
	}

	public function setId_froid($valeur) {
		$this->id_froid= (int)$valeur;
		Outils::setAttributs('id_froid',$this);
	}

	public function setCond_debut($valeur) {
		$this->cond_debut = (string)$valeur;
		Outils::setAttributs('cond_debut',$this);
	}

	public function setCond_fin($valeur) {
		$this->cond_fin = (string)$valeur;
		Outils::setAttributs('cond_fin',$this);
	}

	public function setDate_test($valeur) {
		$this->date_test = (string)$valeur;
		Outils::setAttributs('date_test',$this);
	}

	public function setTest_pdt($valeur) {
		$this->test_pdt= (int)$valeur;
		Outils::setAttributs('test_pdt',$this);
	}

	public function setCommentaire($valeur) {
		$this->commentaire = (string)$valeur;
		Outils::setAttributs('commentaire',$this);
	}

	public function setId_user_visa($valeur) {
		$this->id_user_visa = (int)$valeur;
		Outils::setAttributs('id_user_visa',$this);
	}

	public function setNom_user($valeur) {
		$this->nom_user = (string)$valeur;
	}

	public function setNom_produit($valeur) {
		$this->nom_produit = (string)$valeur;
	}

	public function setCode_froid($valeur) {
		$this->code_froid = (string)$valeur;
	}


} // FIN classe