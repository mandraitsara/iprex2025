<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet FactureLigne
Généré par CBO FrameWork le 06/03/2020 à 14:56:08
------------------------------------------------------*/
class FactureLigne {

	protected    $id,
		$id_facture,
		$id_facture_avoir,
		$id_frs,
		$nom_frs,
		$num_facture,
		$date_add,
		$id_produit,
		$id_pays,		// ID de l'origine
		$produit, 		// Objet Produit pour les traductions
		$code,
		$designation, // Désignation personnalisé à la compo ou en upd
		$numlot,
		$origine,
		$poids,
		$nb_colis,
		$qte,
		$pu_ht,
		$pa_ht,
		$total,
		$tva,
		$taux_tva,
		$tarif_interbev,
		$montant_interbev,
		$vendu_piece,
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

	public function getId_facture() {
		return $this->id_facture;
	}

	public function getId_frs() {
		return $this->id_frs;
	}

	public function getNom_frs() {
		return $this->nom_frs;
	}

	public function getNum_facture() {
		return $this->num_facture;
	}

	public function getId_facture_avoir() {
		return $this->id_facture_avoir;
	}

	public function getDate_add() {
		return $this->date_add;
	}

	public function getId_produit() {
		return $this->id_produit;
	}

	public function getNumlot() {
		return $this->numlot;
	}

	public function getPoids() {
		return $this->poids;
	}

	public function getNb_colis() {
		return $this->nb_colis;
	}

	public function getQte() {
		return $this->qte;
	}

	public function getPu_ht() {
		return $this->pu_ht;
	}

	public function getPa_ht() {
		return $this->pa_ht;
	}

	public function getTva() {
		return $this->tva;
	}

	public function getTarif_interbev() {
		return $this->tarif_interbev;
	}

	public function getMontant_interbev() {
		return $this->montant_interbev;
	}

	public function getCode() {
		return $this->code;
	}

	public function getDesignation() {
		return $this->designation != null ? $this->designation : '';
	}

	public function getProduit() {
		return $this->produit;
	}

	public function getOrigine() {
		return $this->origine;
	}

	public function getId_pays() {
		return $this->id_pays;
	}

	public function getTaux_tva() {
		return $this->taux_tva;
	}

	public function getMontant_tva() {
		return $this->total * ($this->taux_tva / 100);
	}

	public function getTotal() {
		return $this->total;
	}

	public function getInterbev() {
		return floatval($this->tarif_interbev) * floatval($this->poids);
	}

	public function getVendu_piece() {
		return (int)$this->vendu_piece;
	}

	public function isVendu_piece() {
		return (int)$this->vendu_piece == 1;
	}

	public function getSupprime() {
		return (int)$this->supprime;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setNum_facture($num_facture) {
		$this->num_facture = (string)$num_facture;
	}

	public function setId_facture($id_facture) {
		$this->id_facture = (int)$id_facture;
		Outils::setAttributs('id_facture',$this);
	}

	public function setId_facture_avoir($id_facture) {
		$this->id_facture_avoir = (int)$id_facture;
		Outils::setAttributs('id_facture_avoir',$this);
	}

	public function setDate_add($date_add) {
		$this->date_add = (string)$date_add;
		Outils::setAttributs('date_add',$this);
	}

	public function setId_produit($id_produit) {
		$this->id_produit = (int)$id_produit;
		Outils::setAttributs('id_produit',$this);
	}

	public function setId_frs($id_frs) {
		$this->id_frs = (int)$id_frs;
		Outils::setAttributs('id_frs',$this);
	}

	public function setNom_frs($frs) {
		$this->nom_frs = $frs;
	}

	public function setNumlot($numlot) {
		$this->numlot = (string)$numlot;
		Outils::setAttributs('numlot',$this);
	}

	public function setPoids($poids) {
		$this->poids = (float)$poids;
		Outils::setAttributs('poids',$this);
	}

	public function setNb_colis($nb_colis) {
		$this->nb_colis = (int)$nb_colis;
		Outils::setAttributs('nb_colis',$this);
	}

	public function setQte($qte) {
		$this->qte = (int)$qte;
		Outils::setAttributs('qte',$this);
	}

	public function setPu_ht($pu_ht) {
		$this->pu_ht = (float)$pu_ht;
		Outils::setAttributs('pu_ht',$this);
	}

	public function setPa_ht($pa_ht) {
		$this->pa_ht = (float)$pa_ht;
		Outils::setAttributs('pa_ht',$this);
	}

	public function setTva($tva) {
		$this->tva = (float)$tva;
		Outils::setAttributs('tva',$this);
	}

	public function setId_pays($id_pays) {
		$this->id_pays = (int)$id_pays;
		Outils::setAttributs('id_pays',$this);
	}

	public function setTarif_interbev($tva) {
		$this->tarif_interbev = (float)$tva;
		Outils::setAttributs('tarif_interbev',$this);
	}

	public function setMontant_interbev($tva) {
		$this->montant_interbev = (float)$tva;
		Outils::setAttributs('montant_interbev',$this);
	}

	public function setTotal($valeur) {
		$this->total = (float)$valeur;
	}

	public function setCode($valeur) {
		$this->code = (int)$valeur;
	}

	public function setTaux_tva($valeur) {
		$this->taux_tva = (float)$valeur;
	}

	public function setDesignation($valeur) {
		$this->designation = (string)$valeur;
		Outils::setAttributs('designation',$this);
	}

	public function setOrigine($valeur) {
		$this->origine = (string)$valeur;
	}

	public function setProduit($valeur) {
		$this->produit = $valeur;
	}

	public function setVendu_piece($valeur) {
		$this->vendu_piece = (int)$valeur;
		Outils::setAttributs('vendu_piece',$this);
	}

	public function setSupprime($valeur) {
		$this->supprime = (int)$valeur;
		Outils::setAttributs('supprime',$this);
	}

} // FIN classe