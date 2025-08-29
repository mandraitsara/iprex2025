<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet Frais
Généré par CBO FrameWork le 27/02/2020 à 16:21:17
------------------------------------------------------*/
class Frais {

	protected
		$id,
		$id_produit,
		$id_palette,
		$num_palette,
		$nom_client,
		$nom_produit,
		$code_produit,
		$id_lot,
		$id_lot_negoce,
		$quantieme,
		$numlot,
		$poids,
		$dlc,
		$envoye,
		$date_scan,
		$compo;

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

	public function getId_compo() {
		return $this->id_compo;
	}

	public function getId_lot() {
		return $this->id_lot;
	}

	public function getId_produit() {
		return $this->id_produit;
	}

	public function getId_palette() {
		return $this->id_palette;
	}

	public function getQuantieme() {
		return $this->quantieme;
	}

	public function getDlc() {
		return $this->dlc;
	}

	public function getNumlot() {
		return $this->numlot;
	}

	public function getPoids() {
		return $this->poids;
	}

	public function getEnvoye() {
		return $this->envoye;
	}

	public function isEnvoye() {
		return (int)$this->envoye == 1;
	}

	public function getDate_scan() {
		return $this->date_scan;
	}

	public function getNum_palette() {
		return $this->num_palette;
	}

	public function getNom_client() {
		return $this->nom_client;
	}

	public function getCompo() {
		return $this->compo;
	}

	public function getNom_produit() {
		return $this->nom_produit;
	}

	public function getCode_produit() {
		return $this->code_produit;
	}

	public function getId_lot_negoce() {
		return $this->id_lot_negoce;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setId_compo($id_compo) {
		$this->id_compo = (int)$id_compo;
	}

	public function setId_lot($id_lot) {
		$this->id_lot = (int)$id_lot;
		Outils::setAttributs('id_lot',$this);
	}

	public function setId_lot_negoce($id_lot) {
		$this->id_lot_negoce = (int)$id_lot;
		Outils::setAttributs('id_lot_negoce',$this);
	}

	public function setId_palette($id_palette) {
		$this->id_palette = (int)$id_palette;
	}

	public function setId_produit($id_produit) {
		$this->id_produit = (int)$id_produit;
	}

	public function setQuantieme($quantieme) {
		$this->quantieme = (string)$quantieme;
		Outils::setAttributs('quantieme',$this);
	}

	public function setDlc($dlc) {
		$this->dlc = (string)$dlc;
		Outils::setAttributs('dlc',$this);
	}

	public function setDate_scan($date_scan) {
		$this->date_scan = (string)$date_scan;
		Outils::setAttributs('date_scan',$this);
	}

	public function setPoids($poids) {
		$this->poids = (float)$poids;
	}

	public function setNumlot($numlot) {
		$this->numlot = (string)$numlot;
	}

	public function setNum_palette($numlot) {
		$this->num_palette = (string)$numlot;
	}

	public function setNom_client($numlot) {
		$this->nom_client = (string)$numlot;
	}

	public function setCompo($compo) {
		$this->compo = $compo;
	}

	public function setNom_produit($valeur) {
		$this->nom_produit = (string)$valeur;
	}

	public function setCode_produit($valeur) {
		$this->code_produit = (string)$valeur;
	}

	public function setEnvoye($envoye) {
		$this->envoye = (int)$envoye;
		Outils::setAttributs('envoye',$this);
	}

} // FIN classe