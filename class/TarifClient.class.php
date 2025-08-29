<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet TarifClient
Généré par CBO FrameWork le 06/03/2020 à 14:34:30
------------------------------------------------------*/
class TarifClient {

	protected    $id,
		$id_tiers,
		$id_tiers_groupe,
		$id_produit,
		$nom_client,
		$nom_produit,
		$ean13,
		$code_produit,
		$nom_groupe,
		$prix;

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

	public function getId_clt() {
		return $this->id_tiers;
	}

	public function getId_client() {
		return $this->id_tiers;
	}

	public function getId_tiers_groupe() {
		return $this->id_tiers_groupe;
	}

	public function getPrix() {
		return $this->prix;
	}

	public function getEan13() {
		return $this->ean13;
	}

	public function getId_produit() {
		return $this->id_produit;
	}

	public function getNom_clt() {
		return $this->nom_client;
	}

	public function getNom_client() {
		return $this->nom_client;
	}

	public function getNom_produit() {
		return $this->nom_produit;
	}

	public function getCode_produit() {
		return $this->code_produit;
	}

	public function getCode_pdt() {
		return sprintf('%05d', $this->code_produit);
	}

	public function getNom_groupe() {
		return $this->nom_groupe;
	}

	public function getNom_grp() {
		return $this->nom_groupe;
	}

	public function getNom_pdt() {
		return $this->nom_produit;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setId_tiers($id_tiers) {
		$this->id_tiers = (int)$id_tiers;
		Outils::setAttributs('id_tiers',$this);
	}

	public function setId_tiers_groupe($id_tiers_groupe) {
		$this->id_tiers_groupe = (int)$id_tiers_groupe;
		Outils::setAttributs('id_tiers_groupe',$this);
	}

	public function setPrix($prix) {
		$this->prix = (float)$prix;
		Outils::setAttributs('prix',$this);
	}

	public function setId_produit($id_produit) {
		$this->id_produit = (int)$id_produit;
		Outils::setAttributs('id_produit',$this);
	}

	public function setCode_produit($code) {
		$this->code_produit = (int)$code;
	}

	public function setEan13($valeur) {
		$this->ean13 = (string)$valeur;
	}

	public function setNom_client($nom) {
		$this->nom_client = (string)$nom;
	}

	public function setNom_groupe($nom) {
		$this->nom_groupe = (string)$nom;
	}

	public function setNom_produit($nom) {
		$this->nom_produit = (string)$nom;
	}

} // FIN classe