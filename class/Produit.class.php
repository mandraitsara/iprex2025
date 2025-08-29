<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Objet Produit
------------------------------------------------------*/
class Produit {

	protected	$id,
				$nom,			// Nom en français
				$noms,			// Traductions (array)
				$nom_court,
				$code,
				$ean13,
				$ean14,
				$nomenclature,
				$id_espece,
				$categories,
				$nom_espece,
				$vrai_poids,
				$poids,
				$nb_colis,
				$nb_jours_dlc,
				$mixte,
				$palette_suiv,
				$actif,
				$supprime,
				$date_add,
				$date_maj,
				$froids,
				$ean7,
				$ean7_type,	// 0 = Prix / 1 = Poids
				$id_client,
				$vrac,
				$vendu_piece,				
				$id_taxe,
				$pdt_gros,
				$stats,
				$id_pdt_emballage,
				$id_poids_palette,
				$pcb,
				$poids_unitaire,
				$ids_familles_emballages,
				$id_lot_pdt_froid,
				$vendu_negoce;

	public		$attributs = array();

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

	/*##### GETTERS #####*/

	public function getId() {
		return $this->id;
	}

	public function getNom() {
		return $this->nom;
	}

	public function getNoms() {
		return $this->noms;
	}

	public function getNom_court() {
		return $this->nom_court;
	}

	public function getCode() {
		return $this->code;
		//return sprintf('%05d', $this->code);
	}

	public function getEan13() {
		return $this->ean13;
	}

	public function getEan14() {
		return $this->ean14;
	}

	public function getId_espece() {
		return $this->id_espece;
	}

	public function getCategories() {
		return $this->categories;
	}

	public function getPoids() {
		return $this->poids;
	}

	public function getVrai_poids() {
		return $this->vrai_poids;
	}

	public function getNb_colis() {
		return $this->nb_colis;
	}

	public function getNb_jours_dlc() {
		return $this->nb_jours_dlc;
	}

	public function getMixte() {
		return $this->mixte;
	}

	public function isMixte() {
		return (int)$this->mixte == 1;
	}

	public function getNom_espece() {
		return $this->nom_espece;
	}

	public function getActif() {
		return $this->actif;
	}

	public function getSupprime() {
		return $this->supprime;
	}

	public function getDate_maj() {
		return $this->date_maj;
	}

	public function getDate_add() {
		return $this->date_add;
	}

	public function getFroids() {
		return $this->froids;
	}

	public function getPalette_suiv() {
		return $this->palette_suiv;
	}

	public function getId_taxe() {
		return $this->id_taxe;
	}

	public function getPdt_gros() {
		return $this->pdt_gros;
	}

	public function isPdt_gros() {
		return (int)$this->pdt_gros == 1;
	}

	public function getStats() {
		return $this->stats;
	}

	public function getId_pdt_emballage() {
		return $this->id_pdt_emballage;
	}

	public function getPcb() {
		return $this->pcb;
	}

	public function getPoids_unitaire() {
		return $this->poids_unitaire;
	}

	public function getVrac() {
		return $this->vrac;
	}

	public function isVrac() {
		return intval($this->vrac) == 1;
	}

	public function getId_client() {
		return $this->id_client;
	}

	public function getEan7() {
		return $this->ean7;
	}

	public function getEan7_type() {
		return $this->ean7_type;
	}

	public function getVendu_piece() {
		return $this->vendu_piece;
	}

	public function isVendu_piece() {
		return (int)$this->vendu_piece == 1;
	}

	public function getId_poids_palette() {
		return $this->id_poids_palette;
	}

	public function getIds_familles_emballages() {
		return $this->ids_familles_emballages;
	}

	public function getId_lot_pdt_froid() {
		return $this->id_lot_pdt_froid;
	}

	public function getNomenclature() {
		return $this->nomenclature;
	}

	public function getVendu_negoce(){
		return $this->vendu_negoce;
	}
	/*##### SETTERS #####*/

	public function setId($valeur) {
		$this->id = (int)$valeur;
	}

	public function setNom($valeur) {
		$this->nom = (string)$valeur;
	}

	public function setNoms($valeurs) {
		$this->noms = $valeurs;
	}

	public function setNom_court($valeur) {
		$this->nom_court = (string)$valeur;
		Outils::setAttributs('nom_court',$this);
	}

	public function setCode($valeur) {
		$this->code = (string)$valeur;
		Outils::setAttributs('code',$this);
	}

	public function setEan13($valeur) {
		$this->ean13 = (string)$valeur;
		Outils::setAttributs('ean13',$this);
	}

	public function setEan14($valeur) {
		$this->ean14 = (string)$valeur;
		Outils::setAttributs('ean14',$this);
	}

	public function setId_espece($valeur) {
		$this->id_espece = (int)$valeur;
		Outils::setAttributs('id_espece',$this);
	}

	public function setPoids($valeur) {
		$this->poids = (float)$valeur;
		Outils::setAttributs('poids',$this);
	}

	public function setVrai_poids($valeur) {
		$this->vrai_poids = (float)$valeur;
	}

	public function setNb_colis($valeur) {
		$this->nb_colis = (int)$valeur;
		Outils::setAttributs('nb_colis',$this);
	}

	public function setNb_jours_dlc($valeur) {
		$this->nb_jours_dlc = (int)$valeur;
		Outils::setAttributs('nb_jours_dlc',$this);
	}

	public function setMixte($valeur) {
		$this->mixte = (int)$valeur;
		Outils::setAttributs('mixte',$this);
	}

	public function setNom_espece($valeur) {
		$this->nom_espece = (string)$valeur;
	}

	public function setCategories($valeur) {
		$this->categories = $valeur;
	}

	public function setActif($valeur) {
		$this->actif = (int)$valeur;
		Outils::setAttributs('actif',$this);
	}

	public function setSupprime($valeur) {
		$this->supprime = (int)$valeur;
		Outils::setAttributs('supprime',$this);
	}

	public function setDate_add($valeur) {
		$this->date_add = (string)$valeur;
		Outils::setAttributs('date_add',$this);
	}

	public function setDate_maj($valeur) {
		$this->date_maj = (string)$valeur;
		Outils::setAttributs('date_maj',$this);
	}

	public function setFroids($valeur) {
		$this->froids = is_array($valeur) ? $valeur : [$valeur];
	}

	public function setPalette_suiv($valeur) {
		$this->palette_suiv = (int)$valeur;
		Outils::setAttributs('palette_suiv',$this);
	}

	public function setId_taxe($valeur) {
		$this->id_taxe = (int)$valeur;
		Outils::setAttributs('id_taxe',$this);
	}

	public function setPdt_gros($valeur) {
		$this->pdt_gros = (int)$valeur;
		Outils::setAttributs('pdt_gros',$this);
	}

	public function setPcb($valeur) {
		$this->pcb = (float)$valeur;
		Outils::setAttributs('pcb',$this);
	}

	public function setStats($valeur) {
		$this->stats = (int)$valeur;
		Outils::setAttributs('stats',$this);
	}

	public function setId_pdt_emballage($valeur) {
		$this->id_pdt_emballage = (int)$valeur;
		Outils::setAttributs('id_pdt_emballage',$this);
	}

	public function setPoids_unitaire($valeur) {
		$this->poids_unitaire = (float)$valeur;
		Outils::setAttributs('poids_unitaire',$this);
	}

	public function setVrac($valeur) {
		$this->vrac = (int)$valeur;
		Outils::setAttributs('vrac',$this);
	}

	public function setId_client($valeur) {
		$this->id_client = (int)$valeur;
		Outils::setAttributs('id_client',$this);
	}

	public function setEan7($valeur) {
		$this->ean7 = (string)$valeur;
		Outils::setAttributs('ean7',$this);
	}

	public function setEan7_type($valeur) {
		$this->ean7_type = (int)$valeur;
		Outils::setAttributs('ean7_type',$this);
	}

	public function setVendu_piece($valeur) {
		$this->vendu_piece = (int)$valeur;
		Outils::setAttributs('vendu_piece',$this);
	}

	public function setVendu_negoce($vendu_negoce){

		$this->vendu_negoce = (int)$vendu_negoce;
		Outils::setAttributs('vendu_negoce', $this);
	}
	public function setId_poids_palette($valeur) {
		$this->id_poids_palette = (int)$valeur;
		Outils::setAttributs('id_poids_palette',$this);
	}

	public function setIds_familles_emballages($valeur) {
		if (!is_array($valeur)) { $valeur = []; }
		$this->ids_familles_emballages = $valeur;
	}

	public function setNomenclature($valeur) {
		$this->nomenclature = (string)$valeur;
		Outils::setAttributs('nomenclature',$this);
	}

	public function setId_lot_pdt_froid($id_lot_pdt_froid) {
		$this->id_lot_pdt_froid = (integer)$id_lot_pdt_froid;
		Outils::setAttributs('id_lot_pdt_froid',$this);
	}



	/*##### MTHODES PROPRES A LA CLASSE #####*/

	public function isActif() {
		return (int)$this->actif == 1 && (int)$this->supprime == 0;
	}

	public function isSupprime() {
		return (int)$this->supprime == 1;
	}

	public function isAbats() {
		return (int)$this->is_abats == 1;
	}

	// Retourne le code EAN formaté avec les espaces -- DEPRECIE --
	public function getEan_13() {
		return '';
	}

	public function getCategories_ids() {
		$liste = [];
		foreach ($this->categories as $cate) {
			if (!$cate instanceof ProduitCategorie) { continue; }
			$liste[] = $cate->getId();
		}
		return $liste;
	}

} // FIN classe