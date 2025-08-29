<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Objet Froid
------------------------------------------------------*/
class Froid {

	protected
		$id,
		$id_type,
		$date_entree,
		$date_sortie,
		$temp_debut,
		$temp_fin,
		$nuit,
		$code,
		$conformite,
		$test_avant_fe,
		$test_avant_nfe,
		$test_avant_inox,
		$test_apres_fe,
		$test_apres_nfe,
		$test_apres_inox,
		$id_visa_controleur,
		$date_controle,
		$id_user_debut,
		$id_user_fin,
		$id_user_maj,
		$statut,
		$type_nom,
		$nb_produits,
		$lots,
		$produits,
		$supprime,
		$id_lot_pdt_froid;

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

	public function getId_type() {
		return $this->id_type;
	}

	public function getDate_entree() {
		return $this->date_entree;
	}

	public function getDate_sortie() {
		return $this->date_sortie;
	}

	public function getTemp_debut() {
		return $this->temp_debut;
	}

	public function getTemp_fin() {
		return $this->temp_fin;
	}

	public function getNuit() {
		return $this->nuit;
	}

	public function getConformite() {
		return $this->conformite;
	}

	public function getId_visa_controleur() {
		return $this->id_visa_controleur;
	}

	public function getDate_controle() {
		return $this->date_controle;
	}

	public function getId_user_maj() {
		return $this->id_user_maj;
	}

	public function getId_user_debut() {
		return $this->id_user_debut;
	}

	public function getId_user_fin() {
		return $this->id_user_fin;
	}

	public function getProduits() {
		return $this->produits;
	}

	public function getLots() {
		return $this->lots;
	}

	public function getNb_produits() {
		return $this->nb_produits;
	}

	public function getStatut() {
		return $this->statut;
	}

	public function getCode() {
		return $this->code;
	}

	public function getType_nom() {
		return $this->type_nom;
	}

	public function getSupprime() {
		return $this->supprime;
	}

	public function isSupprime() {
		return intval($this->supprime) == 1;
	}

	public function getTest_avant_fe() {
		return (int)$this->test_avant_fe;
	}

	public function getTest_avant_nfe() {
		return (int)$this->test_avant_nfe;
	}

	public function getTest_avant_inox() {
		return (int)$this->test_avant_inox;
	}
	
	public function getTest_apres_fe() {
		return (int)$this->test_apres_fe;
	}

	public function getTest_apres_nfe() {
		return (int)$this->test_apres_nfe;
	}

	public function getTest_apres_inox() {
		return (int)$this->test_apres_inox;
	}

	public function getId_lot_pdt_froid(){
		return (int) $this->id_lot_pdt_froid;
	}
	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setId_type($valeur) {
		$this->id_type = (int)$valeur;
		Outils::setAttributs('id_type',$this);
	}

	public function setId_lot_pdt_froid($id_lot_pdt_froid){

		$this->id_lot_pdt_froid = (int)$id_lot_pdt_froid;
		Outils::setAttributs('id_lot_pdt_froid', $this);
	}

	public function setDate_entree($valeur) {
		$this->date_entree = (string)$valeur;
		Outils::setAttributs('date_entree',$this);
	}

	public function setDate_sortie($valeur) {
		$this->date_sortie = (string)$valeur;
		Outils::setAttributs('date_sortie',$this);
	}

	public function setTemp_debut($valeur) {
		$this->temp_debut = (string)$valeur;
		Outils::setAttributs('temp_debut',$this);
	}

	public function setTemp_fin($valeur) {
		$this->temp_fin = (string)$valeur;
		Outils::setAttributs('temp_fin',$this);
	}

	public function setNuit($valeur) {
		$this->nuit = (int)$valeur;
		Outils::setAttributs('nuit',$this);
	}

	public function setConformite($valeur) {
		$this->conformite = (int)$valeur;
		Outils::setAttributs('conformite',$this);
	}

	public function setId_visa_controleur($valeur) {
		$this->id_visa_controleur = (int)$valeur;
		Outils::setAttributs('id_visa_controleur',$this);
	}

	public function setDate_controle($valeur) {
		$this->date_controle = (string)$valeur;
		Outils::setAttributs('date_controle',$this);
	}

	public function setId_user_maj($valeur) {
		$this->id_user_maj = (int)$valeur;
		Outils::setAttributs('id_user_maj',$this);
	}

	public function setId_user_debut($valeur) {
		$this->id_user_debut = (int)$valeur;
		Outils::setAttributs('id_user_debut',$this);
	}

	public function setId_user_fin($valeur) {
		$this->id_user_fin = (int)$valeur;
		Outils::setAttributs('id_user_fin',$this);
	}

	public function setStatut($valeur) {
		$this->statut = (int)$valeur;
		Outils::setAttributs('statut',$this);
	}

	public function setSupprime($valeur) {
		$this->supprime = (int)$valeur;
		Outils::setAttributs('supprime',$this);
	}

	public function setTest_avant_fe($valeur) {
		$this->test_avant_fe = (int) $valeur;
		Outils::setAttributs('test_avant_fe',$this);
	}

	public function setTest_avant_nfe($valeur) {
		$this->test_avant_nfe = (int) $valeur;
		Outils::setAttributs('test_avant_nfe',$this);
	}

	public function setTest_avant_inox($valeur) {
		$this->test_avant_inox = (int) $valeur;
		Outils::setAttributs('test_avant_inox',$this);
	}

	public function setTest_apres_fe($valeur) {
		$this->test_apres_fe = (int) $valeur;
		Outils::setAttributs('test_apres_fe',$this);
	}
	
	public function setTest_apres_nfe($valeur) {
		$this->test_apres_nfe = (int) $valeur;
		Outils::setAttributs('test_apres_nfe',$this);
	}

	public function setTest_apres_inox($valeur) {
		$this->test_apres_inox = (int) $valeur;
		Outils::setAttributs('test_apres_inox',$this);
	}

	public function setCode($valeur) {
		$this->code = (string)$valeur;
	}

	public function setProduits($listeObjets) {
		$this->produits = $listeObjets;
	}

	public function setLots($listeObjets) {
		$this->lots = $listeObjets;
	}

	public function setNb_produits($valeur) {
		$this->nb_produits = (int)$valeur;
	}

	public function setType_nom($valeur) {
		$this->type_nom = (string)$valeur;
	}


	/* ----------------- METHODES ----------------- */

	public function isConforme() {
		return (int)$this->conforme == 1;
	}

	public function getTempsFroid($enMinutes = false) {
		$datetime_entree = new DateTime(substr($this->date_entree,0,-3)); // On retire les secondes pour éviter les problèmes d'arrondis
		$datetime_sortie = new DateTime(substr($this->date_sortie,0,-3));
		$intervale = $datetime_entree->diff($datetime_sortie);

		if (intval($intervale->format('%d')) > 0) {
			$retour = $intervale->format('%d j. %H h %I min.');
		} else {
			$retour = $intervale->format('%H:%I');
		}

		return $enMinutes
			? abs($datetime_entree->getTimestamp() - $datetime_sortie->getTimestamp()) / 60
			: $retour;

	}

	public function isEnCours() {
		return $this->date_entree != '' && $this->date_entree != '0000-00-00 00:00:00' && $this->date_entree != null;
	}

	public function isSortie() {
		return $this->date_sortie != '' && $this->date_sortie != '0000-00-00 00:00:00' && $this->date_sortie != null;
	}


} // FIN classe