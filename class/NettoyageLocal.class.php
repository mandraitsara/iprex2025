<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2020 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet NettoyageLocal
Généré par CBO FrameWork le 27/11/2020 à 11:49:08
------------------------------------------------------*/
class NettoyageLocal {

	protected
		$id,
		$id_local,
		$id_local_zone,
		$id_freq_protection,
		$id_freq_degrossi,
		$id_freq_demontage,
		$id_freq_vidage,
		$nettoyage_id_fam_conso,
		$nettoyage_temps,
		$desinfection_id_fam_conso,
		$desinfection_temps,
		$id_acteur_nett,
		$id_freq_prelavage,
		$id_freq_deterg_mal,
		$id_freq_deterg_mac,
		$id_freq_rincage_1,
		$id_freq_rincage_2,
		$id_freq_desinfection,
		$numero,
		$surface,
		$nom_local,
		$nom_zone,
		$nom_desinfection_pdt,
		$nom_nettoyage_pdt,
		$contexte,
		$vues,
		$users, // Array simple id/trigramme/nom complet
		$alerteVerbose;

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

	public function getId_local() {
		return $this->id_local;
	}

	public function getId_local_zone() {
		return $this->id_local_zone;
	}

	public function getId_freq_protection() {
		return $this->id_freq_protection;
	}

	public function getId_freq_degrossi() {
		return $this->id_freq_degrossi;
	}

	public function getId_freq_demontage() {
		return $this->id_freq_demontage;
	}

	public function getId_freq_vidage() {
		return $this->id_freq_vidage;
	}

	public function getNettoyage_id_fam_conso() {
		return $this->nettoyage_id_fam_conso;
	}

	public function getNettoyage_temps() {
		return $this->nettoyage_temps;
	}

	public function getDesinfection_id_fam_conso() {
		return $this->desinfection_id_fam_conso;
	}

	public function getDesinfection_temps() {
		return $this->desinfection_temps;
	}

	public function getId_acteur_nett() {
		return $this->id_acteur_nett;
	}

	public function getId_freq_prelavage() {
		return $this->id_freq_prelavage;
	}

	public function getId_freq_deterg_mal() {
		return $this->id_freq_deterg_mal;
	}

	public function getId_freq_deterg_mac() {
		return $this->id_freq_deterg_mac;
	}

	public function getId_freq_rincage_1() {
		return $this->id_freq_rincage_1;
	}

	public function getId_freq_rincage_2() {
		return $this->id_freq_rincage_2;
	}

	public function getId_freq_desinfection() {
		return $this->id_freq_desinfection;
	}

	public function getNumero() {
		return (int)$this->numero;
	}

	public function getSurface() {
		return (float)$this->surface;
	}

	public function getNom_local() {
		return $this->nom_local;
	}

	public function getNom_zone() {
		return $this->nom_zone;
	}

	public function getNom_desinfection_pdt() {
		return $this->nom_desinfection_pdt;
	}

	public function getNom_nettoyage_pdt() {
		return $this->nom_nettoyage_pdt;
	}

	public function getContexte() {
		return $this->contexte;
	}

	public function getAlerteVerbose() {
		return $this->alerteVerbose;
	}

	public function getVues() {
		return $this->vues;
	}

	public function getUsers() {
		return $this->users;
	}


	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setId_local($id_local) {
		$this->id_local = (int)$id_local;
		Outils::setAttributs('id_local',$this);
	}

	public function setId_local_zone($id_local_zone) {
		$this->id_local_zone = (int)$id_local_zone;
		Outils::setAttributs('id_local_zone',$this);
	}

	public function setId_freq_protection($id_freq_protection) {
		$this->id_freq_protection = (int)$id_freq_protection;
		Outils::setAttributs('id_freq_protection',$this);
	}

	public function setId_freq_degrossi($id_freq_degrossi) {
		$this->id_freq_degrossi = (int)$id_freq_degrossi;
		Outils::setAttributs('id_freq_degrossi',$this);
	}

	public function setId_freq_demontage($id_freq_demontage) {
		$this->id_freq_demontage = (int)$id_freq_demontage;
		Outils::setAttributs('id_freq_demontage',$this);
	}

	public function setId_freq_vidage($id_freq_vidage) {
		$this->id_freq_vidage = (int)$id_freq_vidage;
		Outils::setAttributs('id_freq_vidage',$this);
	}

	public function setNettoyage_id_fam_conso($nettoyage_id_fam_conso) {
		$this->nettoyage_id_fam_conso = (int)$nettoyage_id_fam_conso;
		Outils::setAttributs('nettoyage_id_fam_conso',$this);
	}

	public function setNettoyage_temps($nettoyage_temps) {
		$this->nettoyage_temps = (int)$nettoyage_temps;
		Outils::setAttributs('nettoyage_temps',$this);
	}

	public function setDesinfection_id_fam_conso($desinfection_id_fam_conso) {
		$this->desinfection_id_fam_conso = (int)$desinfection_id_fam_conso;
		Outils::setAttributs('desinfection_id_fam_conso',$this);
	}

	public function setDesinfection_temps($desinfection_temps) {
		$this->desinfection_temps = (int)$desinfection_temps;
		Outils::setAttributs('desinfection_temps',$this);
	}

	public function setId_acteur_nett($id_acteur_nett) {
		$this->id_acteur_nett = (int)$id_acteur_nett;
		Outils::setAttributs('id_acteur_nett',$this);
	}

	public function setId_freq_prelavage($id_freq_prelavage) {
		$this->id_freq_prelavage = (int)$id_freq_prelavage;
		Outils::setAttributs('id_freq_prelavage',$this);
	}

	public function setId_freq_deterg_mal($id_freq_deterg_mal) {
		$this->id_freq_deterg_mal = (int)$id_freq_deterg_mal;
		Outils::setAttributs('id_freq_deterg_mal',$this);
	}

	public function setId_freq_deterg_mac($id_freq_deterg_mac) {
		$this->id_freq_deterg_mac = (int)$id_freq_deterg_mac;
		Outils::setAttributs('id_freq_deterg_mac',$this);
	}

	public function setId_freq_rincage_1($id_freq_rincage_1) {
		$this->id_freq_rincage_1 = (int)$id_freq_rincage_1;
		Outils::setAttributs('id_freq_rincage_1',$this);
	}

	public function setId_freq_rincage_2($id_freq_rincage_2) {
		$this->id_freq_rincage_2 = (int)$id_freq_rincage_2;
		Outils::setAttributs('id_freq_rincage_2',$this);
	}

	public function setId_freq_desinfection($id_freq_desinfection) {
		$this->id_freq_desinfection = (int)$id_freq_desinfection;
		Outils::setAttributs('id_freq_desinfection',$this);
	}

	public function setNumero($valeur) {
		$this->numero = (int)$valeur;
		Outils::setAttributs('numero',$this);
	}

	public function setSurface($valeur) {
		$this->surface = (float)$valeur;
		Outils::setAttributs('surface',$this);
	}

	public function setNom_local($valeur) {
		$this->nom_local = $valeur;
		Outils::setAttributs('nom_local',$this);
	}

	public function setNom_zone($valeur) {
		$this->nom_zone = $valeur;
		Outils::setAttributs('nom_zone',$this);
	}

	public function setContexte($valeur) {
		$this->contexte = (int)$valeur;
		Outils::setAttributs('contexte',$this);
	}

	public function setNom_desinfection_pdt($valeur) {
		$this->nom_desinfection_pdt = $valeur;
	}

	public function setNom_nettoyage_pdt($valeur) {
		$this->nom_nettoyage_pdt = $valeur;
	}

	public function setAlerteVerbose($valeur) {
		$this->alerteVerbose = $valeur;
	}

	public function setVues($valeur) {
		$this->vues = $valeur;
	}

	public function setUsers($valeur) {
		if (!is_array($valeur)) { $valeur = []; }
		$this->users = $valeur;
	}

	public function getUsersId() {
		$ids = [];
		if (!is_array($this->users) || empty($this->users)) { return []; }
		foreach ($this->users as $u) {
			$ids[] = (int)$u['id'];
		}
		return $ids;
	}

} // FIN classe