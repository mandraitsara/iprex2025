<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet BlLigne
Généré par CBO FrameWork le 06/03/2020 à 14:54:34
------------------------------------------------------*/
class BlLigne {

	protected
		$id,
		$id_bl,
		$id_facture,
		$id_compo,
		$id_pdt_negoce,
		$numero_palette,
		$id_palette,
		$id_poids_palette,
		$poids_palette_type,
		$poids_palette,
		$id_produit,
		$id_pays,
		$produit, 		// Objet Produit pour les traductions
		$libelle,		
		$num_palette, 	// Numéro de palette forcé manuellement
		$origine,
		$code,
		$designation,	// Désignation personnalisée
		$colis,
		$qte,
		$poids,
		$total,
		$date_add,
		$supprime,
		$id_produit_bl,
		$id_lot,
		$numlot,
		$nb_colis,
		$pu_ht,
		$pa_ht,
		$id_frs,
		$tva,
		$type_dlc,	// Pour traduction Packing List
		$date_abattage,
		$date_traitement, // Frais (conditionnement) ou froid (traitement)
		$trad_traitement, // Pour traduction Packing List
		$dlc,
		$vendu_piece,
		$vendu_negoce,
		$is_frais,
		$num_facture,
		$num_bl,
		$hors_stock;

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

	public function getId_bl() {
		return $this->id_bl;
	}

	public function getVendu_negoce(){
		return $this->vendu_negoce;
	}

	public function getId_facture(){
		return $this->id_facture;
	}

	public function getNum_facture(){
		return $this->num_facture;
	}

	public function getNum_bl(){
		return $this->num_bl;
	}


	public function getId_compo() {
		return $this->id_compo;
	}

	public function getDate_add() {
		return $this->date_add;
	}

	public function getSupprime() {
		return $this->supprime;
	}

	public function getCode() {
		return $this->code;
	}

	public function getDesignation() {
		return $this->designation;
	}

	public function getColis() {
		return $this->colis;
	}

	public function getQte() {
		return $this->qte;
	}

	public function getPoids() {
		return $this->poids;
	}

	public function getTotal() {
		$qte = (int)$this->vendu_piece == 1 ? (int)$this->qte : (float)$this->poids;
		return floatval($this->pu_ht) * $qte;
	}

	public function getId_produit() {
		return $this->id_produit;
	}

	public function getLibelle() {
		return $this->libelle;
	}

	public function getId_pays() {
		return $this->id_pays;
	}

	public function getProduit() {
		return $this->produit;
	}

	public function getNumero_palette() {
		return $this->numero_palette;
	}

	public function getId_palette() {
		return $this->id_palette;
	}

	public function getOrigine() {
		return $this->origine;
	}

	public function getId_produit_bl() {
		return $this->id_produit_bl;
	}

	public function getId_lot() {
		return $this->id_lot;
	}

	public function getNumlot() {
		return $this->numlot;
	}

	public function getNb_colis() {
		return $this->nb_colis;
	}

	public function getPu_ht() {
		return $this->pu_ht;
	}

	public function getPa_ht() {
		return $this->pa_ht;
	}

	public function getId_frs() {
		return $this->id_frs;
	}

	public function getTva() {
		return $this->tva;
	}

	public function getNum_palette() {
		return $this->num_palette;
	}

	public function getId_poids_palette() {
		return $this->id_poids_palette;
	}

	public function getPoids_palette() {
		return $this->poids_palette;
	}

	public function getPoids_palette_type() {
		return $this->poids_palette_type;
	}

	public function getId_pdt_negoce() {
		return $this->id_pdt_negoce;
	}

	public function getDlc() {
		return $this->dlc;
	}

	public function getType_dlc() {
		return $this->type_dlc;
	}

	public function getDate_abattage() {
		return $this->date_abattage;
	}

	public function getDate_traitement() {
		return $this->date_traitement;
	}

	public function getTrad_traitement() {
		return $this->trad_traitement;
	}

	public function getVendu_piece() {
		return $this->vendu_piece;
	}

	public function getIs_frais() {
		return $this->is_frais;
	}

	public function isFrais() {
		return (int)$this->is_frais == 1;
	}

	public function getHors_stock() {
		return (int)$this->hors_stock;
	}


	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setId_facture($id_facture){

		$this->id_facture = (int)$id_facture;
	}
	public function setNum_facture($num_facture) {
		$this->num_facture = (string)$num_facture;
	}

	public function setNum_bl($num_bl) {
		$this->num_bl = (string)$num_bl;
	}

	public function setId_bl($id_bl) {
		$this->id_bl = (int)$id_bl;
		Outils::setAttributs('id_bl',$this);
	}

	public function setId_compo($id_compo) {
		$this->id_compo = (int)$id_compo;
		Outils::setAttributs('id_compo',$this);
	}

	public function setDate_add($date_add) {
		$this->date_add = (string)$date_add;
		Outils::setAttributs('date_add',$this);
	}

	public function setSupprime($supprime) {
		$this->supprime = (int)$supprime;
		Outils::setAttributs('supprime',$this);
	}

	public function setId_pays($id_pays) {
		$this->id_pays = (int)$id_pays;
		Outils::setAttributs('id_pays',$this);
	}

	public function setCode($valeur) {
		$this->code = (int)$valeur;
	}

	public function setDesignation($valeur) {
		$this->designation = (string)$valeur;
	}

	public function setColis($valeur) {
		$this->colis = (int)$valeur;
		Outils::setAttributs('colis',$this);
	}

	public function setId_frs($valeur) {
		$this->id_frs = (int)$valeur;
		Outils::setAttributs('id_frs',$this);
	}

	public function setQte($valeur) {
		$this->qte = (int)$valeur;
		Outils::setAttributs('qte',$this);
	}

	public function setPoids($valeur) {
		$this->poids = (float)$valeur;
		Outils::setAttributs('poids',$this);
	}

	public function setId_produit($valeur) {
		$this->id_produit = (int)$valeur;
		Outils::setAttributs('id_produit',$this);
	}

	public function setLibelle($valeur) {
		$this->libelle = $valeur;
		Outils::setAttributs('libelle',$this);
	}

	public function setProduit($valeur) {
		$this->produit = (object)$valeur;
	}

	public function setNumero_palette($valeur) {
		$this->numero_palette = (int)$valeur;
	}

	public function setId_palette($valeur) {
		$this->id_palette = (int)$valeur;
		Outils::setAttributs('id_palette',$this);
	}

	public function setOrigine($valeur) {
		$this->origine = (string)$valeur;
	}

	public function setId_produit_bl($id_produit_bl) {
		$this->id_produit_bl = (int)$id_produit_bl;
		Outils::setAttributs('id_produit_bl',$this);
	}

	public function setId_lot($id_lot) {
		$this->id_lot = (int)$id_lot;
		Outils::setAttributs('id_lot',$this);
	}

	public function setNumlot($numlot) {
		$this->numlot = (string)$numlot;
		Outils::setAttributs('numlot',$this);
	}

	public function setNum_palette($numpal) {
		$this->num_palette = (string)$numpal;
		Outils::setAttributs('num_palette',$this);
	}

	public function setNb_colis($nb_colis) {
		$this->nb_colis = (int)$nb_colis;
		Outils::setAttributs('nb_colis',$this);
	}

	public function setPu_ht($pu_ht) {
		$this->pu_ht = (float)$pu_ht;
		Outils::setAttributs('pu_ht',$this);
	}

	public function setTva($tva) {
		$this->tva = (float)$tva;
		Outils::setAttributs('tva',$this);
	}

	public function setId_pdt_negoce($id_pdt_negoce) {
		$this->id_pdt_negoce = (int)$id_pdt_negoce;
		Outils::setAttributs('id_pdt_negoce',$this);
	}

	public function setId_poids_palette($valeur) {
		$this->id_poids_palette = (int)$valeur;
	}

	public function setPoids_palette($valeur) {
		$this->poids_palette = (float)$valeur;
	}

	public function setPoids_palette_type($valeur) {
		$this->poids_palette_type = (string)$valeur;
	}

	public function setDlc($valeur) {
		$this->dlc = (string)$valeur;
	}

	public function setType_dlc($valeur) {
		$this->type_dlc = (string)$valeur;
	}

	public function setDate_abattage($valeur) {
		$this->date_abattage = (string)$valeur;
	}

	public function setDate_traitement($valeur) {
		$this->date_traitement = (string)$valeur;
	}

	public function setTrad_traitement($valeur) {
		$this->trad_traitement = (string)$valeur;
	}

	public function setVendu_piece($vendu_piece) {
		$this->vendu_piece = (int)$vendu_piece;
	}

	public function setVendu_negoce($vendu_negoce) {
		$this->vendu_negoce = (int)$vendu_negoce;
	}

	public function setIs_frais($is_frais) {
		$this->is_frais = (int)$is_frais;
	}

	public function setHors_stock($val) {
		$this->hors_stock = (int)$val;
	}


} // FIN classe