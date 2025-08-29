<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2020 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet BonTransfert
Généré par CBO FrameWork le 29/09/2020 à 17:21:04
------------------------------------------------------*/
class BonTransfert {

	protected
		$id,
		$id_tiers,
		$nom_tiers,
		$date,
		$date_add,
		$num_bon_transfert,
		$nb_palettes,
		$nb_produits,
		$poids,
		$supprime,
		$lignes; // Array d'objets BonTransfertLigne

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

	public function getId_tiers() {
		return $this->id_tiers;
	}

	public function getNom_tiers() {
		return $this->nom_tiers;
	}

	public function getDate() {
		return $this->date;
	}

	public function getDate_add() {
		return $this->date_add;
	}

	public function getNum_bon_transfert() {
		return $this->num_bon_transfert;
	}

	public function getSupprime() {
		return $this->supprime;
	}

	public function getLignes() {
		return is_array($this->lignes) ? $this->lignes : [];
	}

	public function getNb_produits() {
		return $this->nb_produits;
	}

	public function getNb_palettes() {
		return $this->nb_palettes;
	}

	public function getPoids() {
		return $this->poids;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setId_tiers($id_tiers) {
		$this->id_tiers = (int)$id_tiers;
		Outils::setAttributs('id_tiers',$this);
	}

	public function setNom_tiers($nom_tiers) {
		$this->nom_tiers = (string)$nom_tiers;
	}

	public function setDate($date) {
		$this->date = (string)$date;
		Outils::setAttributs('date',$this);
	}

	public function setDate_add($date_add) {
		$this->date_add = (string)$date_add;
		Outils::setAttributs('date_add',$this);
	}

	public function setNum_bon_transfert($num_bon_transfert) {
		$this->num_bon_transfert = (string)$num_bon_transfert;
		Outils::setAttributs('num_bon_transfert',$this);
	}

	public function setSupprime($supprime) {
		$this->supprime = (int)$supprime;
		Outils::setAttributs('supprime',$this);
	}

	public function setLignes($lignes) {
		if (!is_array($lignes)) { $lignes = []; }
		$this->lignes = $lignes;
	}

	public function setNb_produits($valeur) {
		$this->nb_produits = (int)$valeur;
	}

	public function setNb_palettes($valeur) {
		$this->nb_palettes = (int)$valeur;
	}

	public function setPoids($valeur) {
		$this->poids = (float)$valeur;
	}

} // FIN classe