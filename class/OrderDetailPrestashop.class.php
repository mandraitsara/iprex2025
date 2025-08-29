<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2020 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet OrderDetailPrestashop
Généré par CBO FrameWork le 21/05/2021 à 16:43:47
------------------------------------------------------*/
class OrderDetailPrestashop {

	protected    $id,
		$id_order,
		$nom,
		$ref,
		$qte,
		$pu_ht,
		$pu_ttc,
		$id_bl_ligne,
		$id_bl,
		$num_bl,
		$nom_client,
		$reference_order;

	public       $attributs = array();

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

	/* ----------------- GETTERS ----------------- */

	public function getId() {
		return $this->id;
	}

	public function getId_order() {
		return $this->id_order;
	}

	public function getNom() {
		return $this->nom;
	}

	public function getRef() {
		return $this->ref;
	}

	public function getQte() {
		return $this->qte;
	}

	public function getPu_ht() {
		return $this->pu_ht;
	}

	public function getPu_ttc() {
		return $this->pu_ttc;
	}

	public function getId_bl_ligne() {
		return $this->id_bl_ligne;
	}

	public function getId_bl() {
		return $this->id_bl;
	}

	public function getNum_bl() {
		return $this->num_bl;
	}

	public function isTraitee() {
		return (int)$this->id_bl_ligne > 0;
	}

	public function getReference_order() {
		return $this->reference_order;
	}

	public function getNom_client() {
		return $this->nom_client;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setId_order($id_order) {
		$this->id_order = (int)$id_order;
		Outils::setAttributs('id_order',$this);
	}

	public function setNom($nom) {
		$this->nom = (string)$nom;
		Outils::setAttributs('nom',$this);
	}

	public function setRef($ref) {
		$this->ref = (string)$ref;
		Outils::setAttributs('ref',$this);
	}

	public function setQte($qte) {
		$this->qte = (int)$qte;
		Outils::setAttributs('qte',$this);
	}

	public function setPu_ht($pu_ht) {
		$this->pu_ht = (float)$pu_ht;
		Outils::setAttributs('pu_ht',$this);
	}

	public function setPu_ttc($pu_ttc) {
		$this->pu_ttc = (float)$pu_ttc;
		Outils::setAttributs('pu_ttc',$this);
	}

	public function setId_bl_ligne($id_bl_ligne) {
		$this->id_bl_ligne = (int)$id_bl_ligne;
		Outils::setAttributs('id_bl_ligne',$this);
	}

	public function setTraitee($traitee) {
		$this->traitee = (int)$traitee;
		Outils::setAttributs('traitee',$this);
	}

	public function setId_bl($valeur) {
		$this->id_bl = (int)$valeur;
	}

	public function setNum_bl($valeur) {
		$this->num_bl = $valeur;
	}

	public function setReference_order($valeur) {
		$this->reference_order = $valeur;
	}

	public function setNom_client($valeur) {
		$this->nom_client = $valeur;
	}

} // FIN classe