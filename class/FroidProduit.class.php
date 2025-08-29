<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Objet FroidProduit
------------------------------------------------------*/
class FroidProduit {

	protected	$id_lot_pdt_froid,
				$id_lot,
				$id_pdt,
				$id_froid,
				$numlot,
				$id_palette,
				$quantite,
				$id_compo,
				$numero_palette,
				$nb_colis,
				$poids,
				$quantieme,
				$etiquetage,
				$loma,
				$date_add,
				$user_add,
				$date_maj,
				$user_maj,
				$user_debut,
				$user_fin,
				$type_froid_nom,
				$froid_code,
				$attente,
				$nom_traitement,
				$code_traitement,
				$is_froid,
				$froid,			// Objet Froid
				$produit;		// Objet Produit

	public		$attributs = [];

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
	public function getId_lot_pdt_froid() {
		return $this->id_lot_pdt_froid;
	}

	public function getId_lot() {
		return $this->id_lot;
	}

	public function getId_pdt() {
		return $this->id_pdt;
	}

	public function getId_froid() {
		return $this->id_froid;
	}

	public function getNb_colis() {
		return $this->nb_colis;
	}

	public function getType_froid_nom() {
		return $this->type_froid_nom;
	}

	public function getLoma() {
		return $this->loma;
	}

	public function getId_palette() {
		return $this->id_palette;
	}

	public function getId_compo() {
		return $this->id_compo;
	}

	public function getNumero_palette() {
		return $this->numero_palette;
	}

	public function getPoids() {
		return $this->poids;
	}
	public function getQuantite() {
		return $this->quantite;
	}

	public function getQuantieme() {
		return $this->quantieme;
	}

	public function getCode_traitement() {
		return strtoupper($this->code_traitement);
	}

	public function getNom_traitement() {
		return $this->nom_traitement;
	}

	public function getDate_add() {
		return $this->date_add;
	}

	public function getDate_maj() {
		return $this->date_maj;
	}

	public function getUser_add() {
		return $this->user_add;
	}

	public function getUser_maj() {
		return $this->user_maj;
	}

	public function getProduit() {
		return $this->produit;
	}

	public function getFroid() {
		return $this->froid;
	}

	public function getNumlot() {
		return $this->numlot;
	}

	public function getEtiquetage() {
		return $this->etiquetage;
	}

	public function getUser_debut() {
		return $this->user_debut;
	}

	public function getUser_fin() {
		return $this->user_fin;
	}

	public function getAttente() {
		return $this->attente;
	}

	public function getIs_froid() {
		return $this->is_froid;
	}

	public function getFroidCode(){
		return $this->froid_code;
	}

	/*##### SETTERS #####*/


	public function setId_lot_pdt_froid($valeur) {
		$this->id_lot_pdt_froid = (int)$valeur;
	}

	public function setId_lot($valeur) {
		$this->id_lot = (int)$valeur;
		Outils::setAttributs('id_lot',$this);
	}

	public function setId_pdt($valeur) {
		$this->id_pdt = (int)$valeur;
		Outils::setAttributs('id_pdt',$this);
	}

	public function setId_froid($valeur) {
		$this->id_froid= (int)$valeur;
		Outils::setAttributs('id_froid',$this);
	}

	public function setNb_colis($valeur) {
		$this->nb_colis = (int)$valeur;
		Outils::setAttributs('nb_colis',$this);
	}

	public function setId_palette($valeur) {
		$this->id_palette = (int)$valeur;
		Outils::setAttributs('id_palette',$this);
	}

	public function setLoma($valeur) {
		$this->loma = (int)$valeur;
		Outils::setAttributs('loma',$this);
	}

	public function setId_compo($valeur) {
		$this->id_compo = (int)$valeur;
	}

	public function setNumero_palette($valeur) {
		$this->numero_palette = (int)$valeur;
	}

	public function setCode_traitement($valeur) {
		$this->code_traitement = (string)$valeur;
	}

	public function setNom_traitement($valeur) {
		$this->nom_traitement = (string)$valeur;
	}

	public function setPoids($valeur) {
		$this->poids = (float)$valeur;
		Outils::setAttributs('poids',$this);
	}
	public function setQuantite($quantite) {
		$this->quantite = (float)$quantite;
		Outils::setAttributs('quantite',$this);
		
	}

	public function setQuantieme($valeur) {
		if ($valeur != '<br') {
			$this->quantieme = (string)$valeur;
			Outils::setAttributs('quantieme',$this);
		}
	}

	public function setDate_maj($valeur) {
		$this->date_maj = (string)$valeur;
		Outils::setAttributs('date_maj',$this);
	}

	public function setDate_add($valeur) {
		$this->date_add = (string)$valeur;
		Outils::setAttributs('date_add',$this);
	}

	public function setUser_maj($valeur) {
		$this->user_maj = (int)$valeur;
		Outils::setAttributs('user_maj',$this);
	}

	public function setUser_add($valeur) {
		$this->user_add = (int)$valeur;
		Outils::setAttributs('user_add',$this);
	}

	public function setEtiquetage($valeur) {
		$this->etiquetage = (int)$valeur;
		Outils::setAttributs('etiquetage',$this);
	}

	public function setAttente($valeur) {
		$this->attente = (int)$valeur;
		Outils::setAttributs('attente',$this);
	}

	public function setProduit($objet) {
		$this->produit = $objet;
	}

	public function setFroid($objet) {
		$this->froid = $objet;
	}

	public function setNumlot($valeur) {
		$this->numlot = (string)$valeur;
	}

	public function setType_froid_nom($valeur) {
		$this->type_froid_nom = (string)$valeur;
	}

	public function setFroid_code($valeur) {
		$this->froid_code = strtoupper($valeur);
	}

	public function setUser_debut($valeur) {
		$this->user_debut = (string)$valeur;
	}

	public function setUser_fin($valeur) {
		$this->user_fin = (string)$valeur;
	}

	public function setIs_froid($valeur) {
		$this->is_froid = (int)$valeur;
	}


	/*##### MTHODES PROPRES A LA CLASSE #####*/


	public function getOpFroid() {
		return trim($this->froid_code) != '' && (int)$this->id_froid > 0 ?
			$this->froid_code.sprintf("%04d", $this->id_froid)
			: '';
	}

} // FIN classe