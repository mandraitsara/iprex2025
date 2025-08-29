<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet PaletteComposition
Généré par CBO FrameWork le 31/07/2019 à 15:26:15
------------------------------------------------------*/
class PaletteComposition {

	protected
		$id,
		$id_palette,
		$id_client,
		$nom_client,
		$id_produit,
		$nom_produit,
		$numero_palette,
		$num_lot,
		$id_lot_pdt_froid,
		$id_lot_pdt_negoce,
		$id_lot_regroupement,
		$num_lot_regroupement,
		$poids,
		$nb_colis,
		$designation,
		$date,
		$id_user,
		$id_frais,
		$nom_user,
		$id_lot_hors_stock,
		$archive,
		$quantite,
		$supprime;

	public       $attributs = array();

	public function __construct($donnees = []) {
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

	public function getId_palette() {
		return $this->id_palette;
	}

	public function getQuantite(){
		return $this->quantite;
	}
	public function getId_client() {
		return $this->id_client;
	}

	public function getNom_client() {
		return $this->nom_client;
	}

	public function getId_produit() {
		return $this->id_produit;
	}

	public function getId_lot_pdt_froid() {
		return $this->id_lot_pdt_froid;
	}

	public function getId_lot_pdt_negoce() {
		return $this->id_lot_pdt_negoce;
	}

	public function getId_lot_regroupement() {
		return (int)$this->id_lot_regroupement;
	}

	public function getNum_lot_regroupement() {
		return $this->num_lot_regroupement;
	}

	public function getNom_produit() {
		return $this->nom_produit;
	}

	public function getPoids() {
		return $this->poids;
	}

	public function getNb_colis() {
		return $this->nb_colis;
	}

	public function getDesignation() {
		return $this->designation;
	}

	public function getId_frais() {
		return $this->id_frais;
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

	public function getSupprime() {
		return $this->supprime;
	}

	public function isSupprimee() {
		return intval($this->supprime) == 1;
	}

	public function getNumero_palette() {
		return $this->numero_palette;
	}

	public function getNum_lot() {
		return $this->num_lot;
	}
	public function getArchive() {
		return (int)$this->archive;
	}

	public function getId_lot_hors_stock() {
		return (int)$this->id_lot_hors_stock;
	}

	public function isHors_stock() {
		return (int)$this->id_lot_hors_stock > 0;
	}


	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setId_palette($valeur) {
		$this->id_palette = (int)$valeur;
		Outils::setAttributs('id_palette',$this);
	}

	public function setId_client($valeur) {
		$this->id_client = (int)$valeur;
		Outils::setAttributs('id_client',$this);
	}

	public function setNom_client($valeur) {
		$this->nom_client = (string)$valeur;
	}

	public function setQuantite($quantite){
		$this->quantite = (string)$quantite;
	}

	public function setId_produit($valeur) {
		$this->id_produit = (int)$valeur;
		Outils::setAttributs('id_produit',$this);
	}

	public function setId_lot_pdt_froid($valeur) {
		$this->id_lot_pdt_froid = (int)$valeur;
		Outils::setAttributs('id_lot_pdt_froid',$this);
	}

	public function setId_lot_pdt_negoce($valeur) {
		$this->id_lot_pdt_negoce = (int)$valeur;
		Outils::setAttributs('id_lot_pdt_negoce',$this);
	}

	public function setId_lot_regroupement($valeur) {
		$this->id_lot_regroupement = (int)$valeur;
		Outils::setAttributs('id_lot_regroupement',$this);
	}

	public function setNum_lot_regroupement($valeur) {
		$this->num_lot_regroupement = $valeur;
	}

	public function setNom_produit($valeur) {
		$this->nom_produit = (string)$valeur;
	}

	public function setPoids($valeur) {
		$this->poids = (float)$valeur;
		Outils::setAttributs('poids',$this);
	}

	public function setNb_colis($valeur) {
		$this->nb_colis = (int)$valeur;
		Outils::setAttributs('nb_colis',$this);
	}

	public function setDesignation($valeur) {
		$this->designation = (string)$valeur;
		Outils::setAttributs('designation',$this);
	}

	public function setDate($valeur) {
		$this->date = (string)$valeur;
		Outils::setAttributs('date',$this);
	}

	public function setId_user($valeur) {
		$this->id_user = (int)$valeur;
		Outils::setAttributs('id_user',$this);
	}

	public function setNom_user($valeur) {
		$this->nom_user = (string)$valeur;
	}

	public function setSupprime($valeur) {
		$this->supprime = (int)$valeur;
		Outils::setAttributs('supprime',$this);
	}

	public function setId_frais($valeur) {
		$this->id_frais = (int)$valeur;
		Outils::setAttributs('id_frais',$this);
	}

	public function setArchive($archive) {
		$this->archive = (int)$archive;
		Outils::setAttributs('archive',$this);
	}

	public function setId_lot_hors_stock($id_lot_hors_stock) {
		$this->id_lot_hors_stock = (int)$id_lot_hors_stock;
		Outils::setAttributs('id_lot_hors_stock',$this);
	}

	public function setNumero_palette($valeur) {
		$this->numero_palette = (int)$valeur;
	}

	public function setNum_lot($valeur) {
		$this->num_lot = (string)$valeur;
	}


} // FIN classe