<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet ProduitStock
Généré par CBO FrameWork le 02/12/2019 à 10:22:51
------------------------------------------------------*/
class ProduitStock {

	protected
		$id_produit,
		$id_client,
		$id_palette,
		$id_compo,
		$id_bl,
		$num_bl,
		$nums_bls,
		$id_facture,
		$num_facture,
		$nom_produit,
		$noms_produit, 	// Traduction
		$code_produit,
		$nom_client,
		$numero_palette,
		$nb_colis,
		$poids,
		$poids_frais,
		$poids_froid,
		$poids_total_lot,
		$designation,
		$id_lot,
		$numlot,
		$quantieme,
		$palette_suiv,
		$date_dlc,
		$id_froid,
		$id_type_froid,
		$code_froid,
		$date_froid,
		$date,
		$nb_clients_pdt,
		$nb_bls_pdt,
		$id_lot_regroupement,
		$numlot_regroupement;

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

	public function getId_produit() {
		return $this->id_produit;
	}

	public function getId_client() {
		return $this->id_client;
	}

	public function getId_palette() {
		return $this->id_palette;
	}

	public function getId_bl() {
		return $this->id_bl;
	}

	public function getNum_bl() {
		return $this->num_bl;
	}

	public function getId_facture() {
		return $this->id_facture;
	}

	public function getNum_facture() {
		return $this->num_facture;
	}

	public function getNom_produit() {
		return $this->nom_produit;
	}

	public function getNoms_produit() {
		return $this->noms_produit;
	}

	public function getCode_produit() {
		return sprintf('%05d', $this->code_produit);
	}

	public function getNom_client() {
		return $this->nom_client;
	}

	public function getNumero_palette() {
		return $this->numero_palette;
	}

	public function getNb_colis() {
		return $this->nb_colis;
	}

	public function getPoids() {
		return $this->poids;
	}

	public function getPoids_frais() {
		return floatval($this->poids_frais);
	}

	public function getPoids_froid() {
		return floatval($this->poids_froid);
	}

	public function getPoids_total_lot() {
		return floatval($this->poids_total_lot);
	}

	public function getId_compo() {
		return $this->id_compo;
	}

	public function getNumlot() {
		return $this->numlot;
	}

	public function getId_lot() {
		return $this->id_lot;
	}

	public function getDate_froid() {
		return $this->date_froid;
	}

	public function getPalette_suiv() {
		return $this->palette_suiv;
	}

	public function getQuantieme() {

		if (strlen($this->quantieme) == 1) {
			return '00'.$this->quantieme;
		} else if (strlen($this->quantieme) == 2) {
			return '0'.$this->quantieme;
		} else if ((int)$this->quantieme == 0) {
			return '';
		} else {
			return $this->quantieme;
		}

	}

	public function getDate() {
		return $this->date;
	}

	public function getDate_dlc() {
		return $this->date_dlc;
	}

	public function getId_froid() {
		return $this->id_froid;
	}

	public function getId_type_froid() {
		return $this->id_type_froid;
	}

	public function getCode_froid() {
		return $this->code_froid;
	}

	public function getId_lot_regroupement() {
		return $this->id_lot_regroupement;
	}

	public function getNumlot_regroupement() {
		return $this->numlot_regroupement;
	}

	public function getDesignation() {
		return $this->designation;
	}

	public function getNums_bls() {
		return $this->nums_bls;
	}

	public function getNb_clients_pdt() {
		return $this->nb_clients_pdt;
	}

	public function getNb_bls_pdt() {
		return $this->nb_bls_pdt;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId_produit($id_produit) {
		$this->id_produit = (int)$id_produit;
		Outils::setAttributs('id_produit',$this);
	}

	public function setId_client($id_client) {
		$this->id_client = (int)$id_client;
		Outils::setAttributs('id_client',$this);
	}

	public function setId_palette($id_palette) {
		$this->id_palette = (int)$id_palette;
		Outils::setAttributs('id_palette',$this);
	}

	public function setNom_produit($nom_produit) {
		$this->nom_produit = (string)$nom_produit;
		Outils::setAttributs('nom_produit',$this);
	}

	public function setNoms_produit($noms_produit) {
		$this->noms_produit = $noms_produit;
	}

	public function setCode_produit($code_produit) {
		$this->code_produit = (string)$code_produit;
	}

	public function setNom_client($nom_client) {
		$this->nom_client = (string)$nom_client;
		Outils::setAttributs('nom_client',$this);
	}

	public function setNumero_palette($numero_palette) {
		$this->numero_palette = (int)$numero_palette;
		Outils::setAttributs('numero_palette',$this);
	}

	public function setId_compo($id_compo) {
		$this->id_compo = (int)$id_compo;
		Outils::setAttributs('id_compo',$this);
	}

	public function setNb_colis($nb_colis) {
		$this->nb_colis = (int)$nb_colis;
		Outils::setAttributs('nb_colis',$this);
	}

	public function setPoids($poids) {
		$this->poids = (float)$poids;
		Outils::setAttributs('poids',$this);
	}

	public function setId_lot($id_lot) {
		$this->id_lot = (int)$id_lot;
		Outils::setAttributs('id_lot',$this);
	}

	public function setNumlot($numlot) {
		$this->numlot = (string)$numlot;
		Outils::setAttributs('numlot',$this);
	}

	public function setQuantieme($quantieme) {
		$this->quantieme = (int)$quantieme;
		Outils::setAttributs('quantieme',$this);
	}

	public function setDate_dlc($date_dlc) {
		$this->date_dlc = (string)$date_dlc;
		Outils::setAttributs('date_dlc',$this);
	}

	public function setId_froid($id_froid) {
		$this->id_froid = (int)$id_froid;
		Outils::setAttributs('id_froid',$this);
	}

	public function setId_type_froid($id_type_froid) {
		$this->id_type_froid = (int)$id_type_froid;
		Outils::setAttributs('id_type_froid',$this);
	}

	public function setCode_froid($code_froid) {
		$this->code_froid = (string)$code_froid;
		Outils::setAttributs('code_froid',$this);
	}

	public function setId_lot_regroupement($valeur) {
		$this->id_lot_regroupement = (int)$valeur;
	}

	public function setNumlot_regroupement($valeur) {
		$this->numlot_regroupement = (string)$valeur;
	}

	public function setDate($valeur) {
		$this->date = (string)$valeur;
	}

	public function setDate_froid($valeur) {
		$this->date_froid = (string)$valeur;
	}

	public function setDesignation($valeur) {
		$this->designation = (string)$valeur;
		Outils::setAttributs('designation',$this);
	}

	public function setPalette_suiv($valeur) {
		$this->palette_suiv = (int)$valeur;
		Outils::setAttributs('palette_suiv',$this);
	}

	public function setId_bl($id_bl) {
		$this->id_bl = (int)$id_bl;
	}

	public function setNum_bl($num_bl) {
		$this->num_bl = $num_bl;
	}

	public function setId_facture($id_facture) {
		$this->id_facture = (int)$id_facture;
	}

	public function setNum_facture($num_facture) {
		$this->num_facture = $num_facture;
	}

	public function setPoids_frais($poids_frais) {
		$this->poids_frais = $poids_frais;
	}

	public function setPoids_froid($poids_froid) {
		$this->poids_froid = $poids_froid;
	}

	public function setPoids_total_lot($poids_total_lot) {
		$this->poids_total_lot = $poids_total_lot;
	}

	public function setNums_bls($nums_bls) {
		$this->nums_bls = $nums_bls;
	}

	public function setNb_clients_pdt($valeur) {
		$this->nb_clients_pdt = $valeur;
	}

	public function setNb_bls_pdt($valeur) {
		$this->nb_bls_pdt = $valeur;
	}

	public function getPoids_restant() {
		return (float)$this->poids_total_lot - ((float)$this->poids_frais + (float)$this->poids_froid);
	}

} // FIN classe