<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet Bl
Généré par CBO FrameWork le 06/03/2020 à 14:50:20
------------------------------------------------------*/
class Bl {

	CONST STATUTS = [
		0 => 'En cours',
		1 => 'Mis en attente',
		2 => 'Généré'
	];

	protected    $id,
		$num_bl,
		$bt,
		$id_tiers_livraison,
		$id_tiers_facturation,
		$id_tiers_transporteur,
		$id_adresse_facturation,
		$id_adresse_livraison,
		$id_lot_negoce,
		$nom_client,
		$date,
		$date_add,
		$date_envoi,
		$date_livraison,
		$num_cmd,
		$statut,					// 0 = En cours / 1 = Mis en attente / 2 = Généré
		$supprime,
		$regroupement,
		$id_langue,					// Pour PDF
		$chiffrage,					// Pour PDF
		$factures,					// Arrays d'objet Facture
		$lignes,					// Arrays d'objets BlLigne
		$nb_palettes,
		$nb_produits,
		$nb_colis,
		$poids,
		$total,
		$id_packing_list;

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

	public function getCode() {
		return $this->num_bl;
	}

	public function getNum_bl() {
		return $this->num_bl;
	}

	public function getBt() {
		return (int)$this->bt;
	}

	public function isBt() {
		return (int)$this->bt == 1;
	}

	public function getId_tiers_livraison() {
		return $this->id_tiers_livraison;
	}

	public function getId_tiers_facturation() {
		return $this->id_tiers_facturation;
	}

	public function getId_tiers_transporteur() {
		return $this->id_tiers_transporteur;
	}

	public function getId_adresse_facturation() {
		return $this->id_adresse_facturation;
	}

	public function getId_adresse_livraison() {
		return $this->id_adresse_livraison;
	}

	public function getId_lot_negoce() {
		return $this->id_lot_negoce;
	}

	public function getDate() {
		return $this->date;
	}

	public function getDate_envoi() {
		return $this->date_envoi;
	}

	public function getDate_add() {
		return $this->date_add;
	}

	public function getDate_livraison() {
		return $this->date_livraison;
	}

	public function getNum_cmd() {
		return $this->num_cmd;
	}

	public function getSupprime() {
		return $this->supprime;
	}

	public function getFactures() {
		return $this->factures;
	}

	public function getLignes() {
		return $this->lignes;
	}

	public function getId_langue() {
		return $this->id_langue;
	}

	public function getChiffrage() {
		return $this->chiffrage;
	}

	public function getNom_client() {
		return $this->nom_client;
	}

	public function getStatut() {
		return $this->statut;
	}
	public function getStatut_verbose() {
		return $this::STATUTS[(int)$this->statut];
	}

	public function isChiffre() {
		return (int)$this->chiffrage == 1;
	}

	public function getRegroupement() {
		return $this->regroupement;
	}

	public function getNb_palettes() {
		return $this->nb_palettes;
	}

	public function getNb_produits() {
		return $this->nb_produits;
	}

	public function getNb_colis() {
		return $this->nb_colis;
	}

	public function getPoids() {
		return $this->poids;
	}

	public function getTotal() {
		return $this->total;
	}

	public function getId_packing_list() {
		return $this->id_packing_list;
	}

	public function getNum_packing_list() {
		return 'PL'. sprintf("%04d",$this->id_packing_list);
	}


	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setNum_bl($num_bl) {
		$this->num_bl = (string)$num_bl;
		Outils::setAttributs('num_bl',$this);
	}

	public function setBt($bt) {
		$this->bt = (int)$bt;
		Outils::setAttributs('bt',$this);
	}

	public function setId_tiers_livraison($id_tiers_livraison) {
		$this->id_tiers_livraison = (int)$id_tiers_livraison;
		Outils::setAttributs('id_tiers_livraison',$this);
	}

	public function setId_tiers_facturation($id_tiers_facturation) {
		$this->id_tiers_facturation = (int)$id_tiers_facturation;
		Outils::setAttributs('id_tiers_facturation',$this);
	}

	public function setId_tiers_transporteur($id_tiers_transporteur) {
		$this->id_tiers_transporteur = (int)$id_tiers_transporteur;
		Outils::setAttributs('id_tiers_transporteur',$this);
	}

	public function setId_adresse_facturation($id_adresse_facturation) {
		$this->id_adresse_facturation = (int)$id_adresse_facturation;
		Outils::setAttributs('id_adresse_facturation',$this);
	}

	public function setId_adresse_livraison($id_adresse_livraison) {
		$this->id_adresse_livraison = (int)$id_adresse_livraison;
		Outils::setAttributs('id_adresse_livraison',$this);
	}

	public function setId_lot_negoce($id_lot_negoce) {
		$this->id_lot_negoce = (int)$id_lot_negoce;
		Outils::setAttributs('id_lot_negoce',$this);
	}

	public function setDate($date) {
		$this->date = (string)$date;
		Outils::setAttributs('date',$this);
	}

	public function setDate_add($date_add) {
		$this->date_add = (string)$date_add;
		Outils::setAttributs('date_add',$this);
	}

	public function setDate_envoi($date_envoi) {
		$this->date_envoi = (string)$date_envoi;
		Outils::setAttributs('date_envoi',$this);
	}

	public function setDate_livraison($date_livraison) {
		$this->date_livraison = (string)$date_livraison;
		Outils::setAttributs('date_livraison',$this);
	}

	public function setNum_cmd($num_cmd) {
		$this->num_cmd = (string)$num_cmd;
		Outils::setAttributs('num_cmd',$this);
	}

	public function setNom_client($nom) {
		$this->nom_client = (string)$nom;
		Outils::setAttributs('nom_client',$this);
	}

	public function setId_packing_list($valeur) {
		$this->id_packing_list = (string)$valeur;
		Outils::setAttributs('id_packing_list',$this);
	}

	public function setSupprime($supprime) {
		$this->supprime = (int)$supprime;
		Outils::setAttributs('supprime',$this);
	}

	public function setStatut($statut) {
		$this->statut = (int)$statut;
		Outils::setAttributs('statut',$this);
	}

	public function setRegroupement($valeur) {
		$this->regroupement = (int)$valeur;
		Outils::setAttributs('regroupement',$this);
	}

	public function setFactures($factures) {
		$this->factures = $factures;
	}

	public function setLignes($lignes) {
		$this->lignes = $lignes;
	}

	public function setId_langue($valeur) {
		$this->id_langue = (int)$valeur;
	}

	public function setChiffrage($valeur) {
		$this->chiffrage = (int)$valeur;
		Outils::setAttributs('chiffrage',$this);
	}

	public function getFichier() {

		$num_bl = $this->num_bl != '' ? $this->num_bl : $this->getCode();

		if ($this->isBt()) {
			return str_replace('BL', 'BT',$num_bl).'.pdf';
		} else {
			return $num_bl.'.pdf';
		}
	}

	public function setNb_palettes($valeur) {
		$this->nb_palettes = (int)$valeur;
	}

	public function setNb_produits($valeur) {
		$this->nb_produits = (int)$valeur;
	}

	public function setNb_colis($valeur) {
		$this->nb_colis = (int)$valeur;
	}

	public function setPoids($valeur) {
		$this->poids = (float)$valeur;
	}

	public function setTotal($valeur) {
		$this->total = (float)$valeur;
	}

} // FIN classe