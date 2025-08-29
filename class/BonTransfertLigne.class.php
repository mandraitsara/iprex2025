<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2020 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet BonTransfertLigne
Généré par CBO FrameWork le 29/09/2020 à 17:29:35
------------------------------------------------------*/
class BonTransfertLigne {

	protected
		$id,
		$id_bon_transfert,
		$id_compo,
		$id_produit,
		$id_palette,
		$poids,
		$date_add,
		$supprime,
		$num_palette,
		$nom_produit;

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

	public function getId_bon_transfert() {
		return $this->id_bon_transfert;
	}

	public function getId_compo() {
		return $this->id_compo;
	}

	public function getId_produit() {
		return $this->id_produit;
	}

	public function getId_palette() {
		return $this->id_palette;
	}

	public function getPoids() {
		return $this->poids;
	}

	public function getDate_add() {
		return $this->date_add;
	}

	public function getSupprime() {
		return $this->supprime;
	}

	public function getNum_palette() {
		return $this->num_palette;
	}

	public function getNom_produit() {
		return $this->nom_produit;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setId_bon_transfert($id_bon_transfert) {
		$this->id_bon_transfert = (int)$id_bon_transfert;
		Outils::setAttributs('id_bon_transfert',$this);
	}

	public function setId_compo($id_compo) {
		$this->id_compo = (int)$id_compo;
		Outils::setAttributs('id_compo',$this);
	}

	public function setId_produit($id_produit) {
		$this->id_produit = (int)$id_produit;
		Outils::setAttributs('id_produit',$this);
	}

	public function setId_palette($id_palette) {
		$this->id_palette = (int)$id_palette;
		Outils::setAttributs('id_palette',$this);
	}

	public function setPoids($poids) {
		$this->poids = (float)$poids;
		Outils::setAttributs('poids',$this);
	}

	public function setDate_add($date_add) {
		$this->date_add = (string)$date_add;
		Outils::setAttributs('date_add',$this);
	}

	public function setSupprime($supprime) {
		$this->supprime = (int)$supprime;
		Outils::setAttributs('supprime',$this);
	}

	public function setNum_palette($num_palette) {
		$this->num_palette = (string)$num_palette;
	}

	public function setNom_produit($nom_produit) {
		$this->nom_produit = (string)$nom_produit;
	}

} // FIN classe