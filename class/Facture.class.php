<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet Facture
Généré par CBO FrameWork le 06/03/2020 à 14:50:20
------------------------------------------------------*/
class Facture {

	protected    $id,
		$num_facture,
		$id_tiers_livraison,
		$id_tiers_facturation,
		$id_tiers_transporteur,
		$id_adresse_facturation,
		$id_adresse_livraison,
		$montant_ht,
		$montant_tva,
		$total_ttc,
		$montant_interbev,
		$remise_ht,
		$remise_ttc,
		$date,
		$date_add,
		$date_compta,
		$date_envoi,
		$num_cmd,
		$nom_client,
		$supprime,
		$bls,					// Arrays d'objets BL
		$lignes;				// Arrays d'objets FactureLigne


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

	public function getNum_facture() {
		return $this->num_facture;
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

	public function getDate() {
		return $this->date;
	}

	public function getDate_add() {
		return $this->date_add;
	}

	public function getDate_envoi() {
		return $this->date_envoi;
	}

	public function getNum_cmd() {
		return $this->num_cmd;
	}

	public function getBls() {
		return $this->bls;
	}

	public function getLignes() {
		return $this->lignes;
	}

	public function getMontant_ht() {
		return $this->montant_ht;
	}

	public function getMontant_tva() {
		return $this->montant_tva;
	}

	public function getMontant_interbev() {
		return $this->montant_interbev;
	}

	public function getFichier() {
		return $this->getNum_facture().'.pdf';
	}

	public function getDate_compta() {
		return $this->date_compta;
	}

	public function getSupprime() {
		return $this->supprime;
	}

	public function isSupprime() {
		return (int)$this->supprime == 1;
	}

	public function getMontant_ttc() {
		return round($this->montant_ht,2) + round($this->montant_tva,2) + round($this->montant_interbev,2);
	}

	public function getTotal_ttc() {
		return $this->total_ttc;
	}

	public function getRemise_ht() {
		return $this->remise_ht;
	}

	public function getRemise_ttc() {
		return $this->remise_ttc;
	}


	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setNum_facture($code) {
		$this->num_facture = (string)$code;
		Outils::setAttributs('num_facture',$this);
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

	public function setDate($date) {
		$this->date = (string)$date;
		Outils::setAttributs('date',$this);
	}

	public function setDate_envoi($date_envoi) {
		$this->date_envoi = (string)$date_envoi;
		Outils::setAttributs('date_envoi',$this);
	}

	public function setDate_add($date_add) {
		$this->date_add = (string)$date_add;
		Outils::setAttributs('date_add',$this);
	}

	public function setNum_cmd($num_cmd) {
		$this->num_cmd = (string)$num_cmd;
		Outils::setAttributs('num_cmd',$this);
	}

	public function setMontant_ht($valeur) {
		$this->montant_ht = (float)$valeur;
		Outils::setAttributs('montant_ht',$this);
	}

	public function setMontant_interbev($valeur) {
		$this->montant_interbev = (float)$valeur;
		Outils::setAttributs('montant_interbev',$this);
	}

	public function setMontant_tva($valeur) {
		$this->montant_tva = (float)$valeur;
	}

	public function setDate_compta($valeur) {
		$this->date_compta = (string)$valeur;
		Outils::setAttributs('date_compta',$this);
	}

	public function setSupprime($supprime) {
		$this->supprime = (int)$supprime;
		Outils::setAttributs('supprime',$this);
	}

	public function setTotal_ttc($total) {
		$this->total_ttc = (float)$total;
		Outils::setAttributs('total_ttc',$this);
	}

	public function setRemise_ht($valeur) {
		$this->remise_ht = (float)$valeur;
		Outils::setAttributs('remise_ht',$this);
	}

	public function setRemise_ttc($valeur) {
		$this->remise_ttc = (float)$valeur;
		Outils::setAttributs('remise_ttc',$this);
	}

	public function setBls($bls) {
		$this->bls = $bls;
	}

	public function setLignes($lignes) {
		$this->lignes = $lignes;
	}

	public function envoyeeCompta() {
		return $this->date_compta != '' && $this->date_compta != '0000-00-00 00:00:00';
	}

	public function getMois() {
		$dd = explode('-', $this->date);
		return isset($dd[1]) ? $dd[1] : 0;
	}

	public function getAnnee() {
		$dd = explode('-', $this->date);
		return isset($dd[0]) ? $dd[0] : 0;
	}

} // FIN classe