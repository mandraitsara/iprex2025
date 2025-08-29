<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet LotNegoce
Généré par CBO FrameWork le 13/01/2020 à 11:30:07
------------------------------------------------------*/
class LotNegoce {

	protected    $id,
		$num_bl,	
		$bls,	
		$date_add,
		$date_maj,
		$date_entree,
		$date_out,
		$id_espece,
		$id_fournisseur,				
		$temp,		
		$id_user_maj,
		$visible,
		$supprime,
		$nom_espece,
		$nom_fournisseur,
		$numagr,					
		$id_pdt,			
		$dlc,
		$nb_produits,
		$nb_produits_traites,
		$produits,
		$nom_produit,	
		$couleur,
		$poids,
		$reception,
		$vues,
		$bizerba,
		$test_tracabilite,
		$date_reception;
		

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

	public function getPoids(){
		return $this->poids;
	}

	public function getDate_reception(){

		return $this->date_reception;
	}

	public function getReception() {
		return $this->reception;
	}

	public function getNum_bl() {
		return $this->num_bl;
	}


	public function getTest_tracabilite() {
		return $this->test_tracabilite != '' &&  $this->test_tracabilite != '0000-00-00 00:00:00' ? $this->test_tracabilite : null;
	}

	
	
	public function getDate_add() {
		return $this->date_add;
	}

	public function getDate_maj() {
		return $this->date_maj;
	}

	public function getDate_entree() {
		return $this->date_entree;
	}

	public function getDate_out() {
		return $this->date_out;
	}

	
	public function getId_fournisseur() {
		return $this->id_fournisseur;
	}

	public function getId_user_maj() {
		return $this->id_user_maj;
	}

	public function getVisible() {
		return $this->visible;
	}

	public function getSupprime() {
		return $this->supprime;
	}

	public function getNom_fournisseur($defaut = '-') {
		return $this->nom_fournisseur != '' ? $this->nom_fournisseur : $defaut;
	}
	

	public function getTemp() {
		return $this->temp;
	}

	public function getNom_produit() {
		return $this->nom_produit;
	}

	public function getNumagr() {
		return $this->numagr;
	}
	
	public function getBls() {
		return $this->bls;
	}

	public function getCouleur() {
		return $this->couleur != '' ? $this->couleur : '#777';
	}

	public function getProduits() {
		return $this->produits;
	}

	public function getNb_produits() {
		return $this->nb_produits;
	}

	
	public function getVues() {
		return $this->vues;
	}

	public function getBizerba() {
		return $this->bizerba != '' &&  $this->bizerba != '0000-00-00 00:00:00' ? $this->bizerba : null;
	}


	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setBizerba($valeur) {
		$this->bizerba = (string)$valeur;
		Outils::setAttributs('bizerba',$this);
	}

	public function setPoids($poids){
		$this->poids = $poids;
		Outils::setAttributs('poids', $this);
	}

	public function setTest_tracabilite($valeur) {
		$this->test_tracabilite = (string)$valeur;
		Outils::setAttributs('test_tracabilite',$this);
	}

	public function setReception(LotReception $objet) {
		$this->reception = $objet;
	}

	public function setId_pdt($id_pdt){
		$this->id_pdt = (int)$id_pdt;
		Outils::setAttributs('id_pdt', $this);
	}

	public function setVues($lotVues) {
		$this->vues = $lotVues;
	}

	public function setDate_reception($date_reception){

		$this->date_reception = $date_reception;
		Outils::setAttributs('date_reception', $this);
	}

	public function setNum_bl($num_bl) {
		$this->num_bl = (string)$num_bl;
		Outils::setAttributs('num_bl',$this);
	}

	public function setDate_add($date_add) {
		$this->date_add = (string)$date_add;
		Outils::setAttributs('date_add',$this);
	}

	public function setDate_maj($date_maj) {
		$this->date_maj = (string)$date_maj;
		Outils::setAttributs('date_maj',$this);
	}

	public function setDate_entree($date_entree) {
		$this->date_entree = (string)$date_entree;
		Outils::setAttributs('date_entree',$this);
	}


	

	public function setDate_out($date_out) {
		$this->date_out = (string)$date_out;
		Outils::setAttributs('date_out',$this);
	}

	public function setId_espece($id_espece) {
		$this->id_espece = (int)$id_espece;
		Outils::setAttributs('id_espece',$this);
	}

	public function setId_fournisseur($id_fournisseur) {
		$this->id_fournisseur = (int)$id_fournisseur;
		Outils::setAttributs('id_fournisseur',$this);
	}

	public function setNumgar($numagr) {
		$this->numagr = (string)$numagr;
		Outils::setAttributs('numagr',$this);
	}


	public function setId_user_maj($id_user_maj) {
		$this->id_user_maj = (int)$id_user_maj;
		Outils::setAttributs('id_user_maj',$this);
	}

	public function setVisible($visible) {
		$this->visible = (int)$visible;
		Outils::setAttributs('visible',$this);
	}

	public function setSupprime($supprime) {
		$this->supprime = (int)$supprime;
		Outils::setAttributs('supprime',$this);
	}

	public function setTemp($valeur) {
		$this->temp = (float)$valeur;
		Outils::setAttributs('temp',$this);
	}

	public function setNom_espece($valeur) {
		$this->nom_espece = (string)$valeur;
	}

	public function setNom_fournisseur($valeur) {
		$this->nom_fournisseur = (string)$valeur;
	}

	public function isSupprime() {
		return (int)$this->supprime == 1;
	}

	public function isVisible() {
		return (int)$this->visible == 1;
	}

	public function setProduits($produits) {
		$this->produits = $produits;
	}

	public function setNb_produits($nb) {
		$this->nb_produits = $nb;
	}

	public function setNom_produit($nom_produit) {
		$this->nom_produit = $nom_produit;
	}

	public function setNumagr($numagr) {
		$this->numagr = (string)$numagr;
	}

	public function setBls($bls) {
		$this->bls = $bls;
	}

	public function setCouleur($couleur) {
		$this->couleur = $couleur;
	}

	

	
} // FIN classe