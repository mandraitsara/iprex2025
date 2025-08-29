<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2020 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet OrdersPrestashop
Généré par CBO FrameWork le 21/05/2021 à 16:40:19
------------------------------------------------------*/
class OrderPrestashop {

	protected
		$id,
		$id_order,
		$reference,
		$total_ht,
		$total_ttc,
		$total_produits_ht,
		$total_produits_ttc,
		$reductions_ht,
		$reductions_ttc,
		$livraison_ht,
		$livraison_ttc,
		$id_adresse,
		$id_client,
		$id_transporteur,
		$transporteur,
		$adresse,
		$nom_client,
		$date_facture,
		$date_import,
		$traitee,
		$order_details; // Array d'objets

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

	public function getId_order() {
		return $this->id_order;
	}

	public function getReference() {
		return $this->reference;
	}

	public function getTotal_ht() {
		return $this->total_ht;
	}

	public function getTotal_ttc() {
		return $this->total_ttc;
	}

	public function getTotal_produits_ht() {
		return $this->total_produits_ht;
	}

	public function getTotal_produits_ttc() {
		return $this->total_produits_ttc;
	}

	public function getReductions_ht() {
		return $this->reductions_ht;
	}

	public function getReductions_ttc() {
		return $this->reductions_ttc;
	}

	public function getLivraison_ht() {
		return $this->livraison_ht;
	}

	public function getLivraison_ttc() {
		return $this->livraison_ttc;
	}

	public function getId_adresse() {
		return $this->id_adresse;
	}

	public function getId_client() {
		return $this->id_client;
	}

	public function getId_transporteur() {
		return $this->id_transporteur;
	}

	public function getTransporteur() {
		return $this->transporteur;
	}

	public function getAdresse() {
		return $this->adresse;
	}

	public function getNom_client() {
		return $this->nom_client;
	}

	public function getDate_facture() {
		return $this->date_facture;
	}

	public function getDate_import() {
		return $this->date_import;
	}

	public function getOrder_details() {
		return $this->order_details;
	}

	public function getTraitee() {
		return $this->traitee;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setId_order($id_order) {
		$this->id_order = (int)$id_order;
		Outils::setAttributs('id_order',$this);
	}

	public function setReference($reference) {
		$this->reference = (string)$reference;
		Outils::setAttributs('reference',$this);
	}

	public function setTotal_ht($total_ht) {
		$this->total_ht = (float)$total_ht;
		Outils::setAttributs('total_ht',$this);
	}

	public function setTotal_ttc($total_ttc) {
		$this->total_ttc = (float)$total_ttc;
		Outils::setAttributs('total_ttc',$this);
	}

	public function setTotal_produits_ht($total_produits_ht) {
		$this->total_produits_ht = (float)$total_produits_ht;
		Outils::setAttributs('total_produits_ht',$this);
	}

	public function setTotal_produits_ttc($total_produits_ttc) {
		$this->total_produits_ttc = (float)$total_produits_ttc;
		Outils::setAttributs('total_produits_ttc',$this);
	}

	public function setReductions_ht($reductions_ht) {
		$this->reductions_ht = (float)$reductions_ht;
		Outils::setAttributs('reductions_ht',$this);
	}

	public function setReductions_ttc($reductions_ttc) {
		$this->reductions_ttc = (float)$reductions_ttc;
		Outils::setAttributs('reductions_ttc',$this);
	}

	public function setLivraison_ht($livraison_ht) {
		$this->livraison_ht = (float)$livraison_ht;
		Outils::setAttributs('livraison_ht',$this);
	}

	public function setLivraison_ttc($livraison_ttc) {
		$this->livraison_ttc = (float)$livraison_ttc;
		Outils::setAttributs('livraison_ttc',$this);
	}

	public function setId_adresse($id_adresse) {
		$this->id_adresse = (int)$id_adresse;
		Outils::setAttributs('id_adresse',$this);
	}

	public function setId_client($id_client) {
		$this->id_client = (int)$id_client;
		Outils::setAttributs('id_client',$this);
	}

	public function setId_transporteur($id_transporteur) {
		$this->id_transporteur = (int)$id_transporteur;
		Outils::setAttributs('id_transporteur',$this);
	}

	public function setTransporteur($transporteur) {
		$this->transporteur = (string)$transporteur;
		Outils::setAttributs('transporteur',$this);
	}

	public function setAdresse($adresse) {
		$this->adresse = (string)$adresse;
		Outils::setAttributs('adresse',$this);
	}

	public function setNom_client($nom_client) {
		$this->nom_client = (string)$nom_client;
		Outils::setAttributs('nom_client',$this);
	}

	public function setDate_facture($date_facture) {
		$this->date_facture = (string)$date_facture;
		Outils::setAttributs('date_facture',$this);
	}

	public function setDate_import($date_import) {
		$this->date_import = (string)$date_import;
		Outils::setAttributs('date_import',$this);
	}

	public function setTraitee($traitee) {
		$this->traitee = (int)$traitee;
		Outils::setAttributs('traitee',$this);
	}

	public function setOrder_details($liste) {
		if (!is_array($liste)) { $liste = []; }
		$this->order_details = $liste;
	}

} // FIN classe