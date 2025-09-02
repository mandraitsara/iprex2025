<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Objet Lot
------------------------------------------------------*/
class Lot {
	
	const COMPOSITIONS = [
				1 => 'Viande',
				2 => 'Abats'
	];

	const COMPOSITIONS_VIANDE = [
		1 => 'Sous-vide',
		2 => 'Carcasse'
	];

	protected	$id,
				$numlot,
				$date_add,
				$date_atelier,
				$date_prod,
				$date_maj,
				$date_out,
				$date_reception,
				$date_abattage,
				$id_abattoir,
				$id_origine,
				$id_espece,
				$nom_espece,
				$couleur,
				$id_fournisseur,
				$nom_fournisseur,
				$poids_abattoir,
				$poids_reception,
				$composition,
				$composition_viande,
				$id_user_maj,
				$supprime,
				$test_tracabilite,
				$visible,
				$bizerba,
				$nom_abattoir,
				$numagr_abattoir,
				$nom_user_maj,
				$nom_origine,
				$date_controle, 	// Datetime controle froid 			 (validation)
				$froid_conformite,	// Confirmité sortie congélation/surgélation (validation)
				$loma_test,			// Résultat du test contrôle Loma 			 (validation)
				$id_table,			// ID de la table loma/froid à valider		 (validation)
				$detailsLoma,
				$detailsFroid,
				$vue_validation,	// code de la vue à valider
				$reception,			// Objet LotReception
				$numlot_frs,
				$nom_produit,
				$vues;				// Array d'objet LotVue
	public		$attributs = array();
	
	public function __construct($donnees = [])	{
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

	/*##### GETTERS #####*/
	public function getId() {
		return $this->id;
	}
	public function getId_table() {
		return $this->id_table;
}
	
	public function getNumlot() {
		return $this->numlot;
	}

	public function getNom_produit() {
		return $this->nom_produit;
	}
	public function getDate_add() {
		return $this->date_add;
	}
	public function getDate_prod() {
		return $this->date_prod;
	}
	public function getDate_atelier() {
		return $this->date_atelier;
	}
	public function getDate_maj() {
		return $this->date_maj;
	}
	public function getDate_out() {
		return $this->date_out;
	}
	public function getDate_reception() {
		return $this->date_reception;
	}
	public function getDate_abattage() {
		return $this->date_abattage;
	}
	public function getId_abattoir() {
		return $this->id_abattoir;
	}
	public function getId_espece() {
		return $this->id_espece;
	}
	public function getNom_espece($defaut = '&mdash;') {
		return $this->nom_espece != '' ? $this->nom_espece : $defaut;
	}
	public function getId_fournisseur() {
		return $this->id_fournisseur;
	}
	public function getNom_fournisseur($defaut = '&mdash;') {
		return $this->nom_fournisseur != '' ? $this->nom_fournisseur : $defaut;
	}
	public function getCouleur() {
		return $this->couleur;
	}
	public function getId_origine() {
		return $this->id_origine;
	}
	public function getPoids_abattoir() {
		return $this->poids_abattoir;
	}
	public function getPoids_reception() {
		return $this->poids_reception;
	}
	public function getComposition() {
		return $this->composition;
	}
	public function getComposition_viande() {
		return $this->composition_viande;
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
	public function getTest_tracabilite() {
		return $this->test_tracabilite != '' &&  $this->test_tracabilite != '0000-00-00 00:00:00' ? $this->test_tracabilite : null;
	}
	public function getBizerba() {
		return $this->bizerba != '' &&  $this->bizerba != '0000-00-00 00:00:00' ? $this->bizerba : null;
	}
	public function getNom_abattoir() {
		return $this->nom_abattoir;
	}
	public function getNumagr_abattoir() {
		return $this->numagr_abattoir;
	}
	public function getNom_origine() {
		return $this->nom_origine;
	}
	public function getNom_user_maj() {
		return $this->nom_user_maj;
	}
	public function getDate_controle() {
		return $this->date_controle;
	}
	public function getFroid_conformite() {
		return $this->froid_conformite;
	}
	public function getLoma_test() {
		return $this->loma_test;
	}
	public function getDetailsLoma() {
		return $this->detailsLoma;
	}
	public function getDetailsFroid() {
		return $this->detailsFroid;
	}
	public function getVue_validation() {
		return $this->vue_validation;
	}
	public function getReception() {
		return $this->reception;
	}
	public function getVues() {
		return $this->vues;
	}

	public function getNumlot_frs(){
		return $this->numlot_frs;
	}

	/*##### SETTERS #####*/
	public function setId($valeur) {
		$this->id = (int)$valeur;
	}
	public function setId_table($valeur) {
		$this->id_table = (int)$valeur;
	}
	public function setNumlot($valeur) {
		$this->numlot = (string)$valeur;
		Outils::setAttributs('numlot',$this);
	}
	public function setDate_add($valeur) {
		$this->date_add = (string)$valeur;
		Outils::setAttributs('date_add',$this);
	}
	public function setDate_atelier($valeur) {
		$this->date_atelier = (string)$valeur;
		Outils::setAttributs('date_atelier',$this);
	}
	public function setDate_prod($valeur) {
		$this->date_prod = (string)$valeur;
		Outils::setAttributs('date_prod',$this);
	}
	public function setDate_maj($valeur) {
		$this->date_maj = (string)$valeur;
		Outils::setAttributs('date_maj',$this);
	}
	public function setDate_out($valeur) {
		$this->date_out = (string)$valeur;
		Outils::setAttributs('date_out',$this);
	}
	public function setDate_reception($valeur) {
		$this->date_reception = (string)$valeur;
		Outils::setAttributs('date_reception',$this);
	}
	public function setDate_abattage($valeur) {
		$this->date_abattage = (string)$valeur;
		Outils::setAttributs('date_abattage',$this);
	}
	public function setBizerba($valeur) {
		$this->bizerba = (string)$valeur;
		Outils::setAttributs('bizerba',$this);
	}
	public function setId_abattoir($valeur) {
		$this->id_abattoir = (int)$valeur;
		Outils::setAttributs('id_abattoir',$this);
	}
	public function setId_espece($valeur) {
		$this->id_espece = (int)$valeur;
		Outils::setAttributs('id_espece',$this);
	}
	public function setNom_espece($valeur) {
		$this->nom_espece = (string)$valeur;
	}

	public function setNom_produit($nom_produit) {
		$this->nom_produit = (string)$nom_produit;
	}
	public function setId_fournisseur($valeur) {
		$this->id_fournisseur = (int)$valeur;
		Outils::setAttributs('id_fournisseur',$this);
	}
	public function setNom_fournisseur($valeur) {
		$this->nom_fournisseur = (string)$valeur;
	}
	public function setCouleur($valeur) {
		$this->couleur = (string)$valeur;
	}
	public function setId_origine($valeur) {
		$this->id_origine = (int)$valeur;
		Outils::setAttributs('id_origine',$this);
	}
	public function setPoids_abattoir($valeur) {
		$this->poids_abattoir = (float)$valeur;
		Outils::setAttributs('poids_abattoir',$this);
	}

	public function setPoids_reception($valeur) {
		$this->poids_reception = (float)$valeur;
		Outils::setAttributs('poids_reception',$this);
	}

	public function setComposition($valeur) {
		$this->composition = (int)$valeur;
		Outils::setAttributs('composition',$this);
	}

	public function setComposition_viande($valeur) {
		$this->composition_viande = (int)$valeur;
		Outils::setAttributs('composition_viande',$this);
	}

	public function setId_user_maj($valeur) {
		$this->id_user_maj = (int)$valeur;
		Outils::setAttributs('id_user_maj',$this);
	}

	public function setVisible($valeur) {
		$this->visible = (int)$valeur;
		Outils::setAttributs('visible',$this);
	}

	public function setTest_tracabilite($valeur) {
		$this->test_tracabilite = (string)$valeur;
		Outils::setAttributs('test_tracabilite',$this);
	}

	public function setSupprime($valeur) {
		$this->supprime = (int)$valeur;
		Outils::setAttributs('supprime',$this);
	}

	public function setNom_abattoir($valeur) {
		$this->nom_abattoir = (string)$valeur;
	}

	public function setNumagr_abattoir($valeur) {
		$this->numagr_abattoir = (string)$valeur;
	}

	public function setNom_origine($valeur) {
		$this->nom_origine = (string)$valeur;
	}

	public function setNom_user_maj($valeur) {
		$this->nom_user_maj = (string)$valeur;
	}

	public function setDate_controle($valeur) {
		$this->date_controle = (string)$valeur;
	}

	public function setFroid_conformite($valeur) {
		$this->froid_conformite = (int)$valeur;
	}

	public function setLoma_test($valeur) {
		$this->loma_test = (int)$valeur;
	}

	public function setDetailsLoma($objet) {
		$this->detailsLoma = $objet;
	}


	public function setNumlot_frs($numlot_frs){
		$this->numlot_frs = $numlot_frs;


	}

	public function setDetailsFroid($objet) {
		$this->detailsFroid = $objet;
	}

	public function setVue_validation($valeur) {
		$this->vue_validation = $valeur;
	}

	public function setReception(LotReception $objet) {
		$this->reception = $objet;
	}

	public function setVues($lotVues) {
		$this->vues = $lotVues;
	}


	/*##### METHODES PROPRES A LA CLASSE #####*/

	public function isSupprime() {
		return (int)$this->supprime == 1;
	}

	public function isVisible() {
		return (int)$this->visible == 1;
	}


	public function isEnCours() {
		return strlen(trim($this->date_out)) == 0 || $this->date_out == '0000-00-00' || $this->date_out == '0000-00-00 00:00:00';
	}

	public function isReady() {
		return (int)$this->id_abattoir > 0 && (int)$this->id_origine > 0 && $this->date_abattage != "" && $this->date_abattage != "0000-00-00";
	}

	public function getComposition_verbose($defaut = '-') {
		return  array_key_exists(intval($this->composition), Lot::COMPOSITIONS) ? Lot::COMPOSITIONS[intval($this->composition)] : $defaut;
	}


	public function getComposition_viande_verbose($entreParentheses = false) {
		$verbose = array_key_exists(intval($this->composition_viande), Lot::COMPOSITIONS_VIANDE) ? Lot::COMPOSITIONS_VIANDE[intval($this->composition_viande)] : '';
		if ($verbose == '') { return $verbose; }
		return $entreParentheses ? '('.strtolower($verbose).')' : $verbose;
	}

	public function getClefConfigCompositionTemp() {
		if ($this->composition == 2) {					return 'aba';
		} else if ($this->composition_viande == 2) {	return 'car';
		} else if ($this->composition_viande == 1) {	return 'via';
		} else {										return '';	}
	}

} // FIN classe