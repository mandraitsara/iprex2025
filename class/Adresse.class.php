<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet Adresse
Généré par CBO FrameWork le 04/03/2020 à 09:24:51
------------------------------------------------------*/
class Adresse {

	const TYPES = [
		0 => 'Facturation',
		1 => 'Livraison'
	];

	protected
		$id,
		$id_tiers,
		$nom,
		$adresse_1,
		$adresse_2,
		$cp,
		$ville,
		$id_pays,
		$nom_pays,
		$type,
		$supprime;

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

	public function getNom() {
		return $this->nom;
	}

	public function getAdresse_1() {
		return $this->adresse_1;
	}

	public function getAdresse_2() {
		return $this->adresse_2;
	}

	public function getCp() {
		return $this->cp;
	}

	public function getVille() {
		return $this->ville;
	}

	public function getId_pays() {
		return $this->id_pays;
	}

	public function getNom_pays() {
		return $this->nom_pays;
	}

	public function getType() {
		return $this->type;
	}

	public function getSupprime() {
		return $this->supprime;
	}

	public function getAdresse_ligne() {
		$retour = $this->adresse_1 . ' ';
		$retour.= $this->adresse_2 != '' ? $this->adresse_2 .  ' ' : '';
		$retour.= $this->cp != '' ? $this->cp . ' ' : '';
		$retour.= $this->ville != '' ? strtoupper($this->ville) . ' ' : '';
		$retour.= $this->nom_pays != '' ? strtoupper($this->nom_pays) . ' ' : '';
		return $retour;
	}

	public function getAdresse($nom_client = '') {

		$retour = '';
		$retour.= $this->nom != '' ? $this->nom . '<br>' : '';

		$retour.= $this->adresse_1 . '<br>';
		$retour.= $this->adresse_2 != '' ? $this->adresse_2 .  '<br>' : '';
		$retour.= $this->cp != '' ? $this->cp . ' ' : '';
		$retour.= $this->ville != '' ? strtoupper($this->ville) . '<br>' : '';
		$retour.= $this->nom_pays != '' ? strtoupper($this->nom_pays) : '';

		return $retour;

	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setId_tiers($id_tiers) {
		$this->id_tiers = (int)$id_tiers;
		Outils::setAttributs('id_tiers',$this);
	}

	public function setNom($nom) {
		$this->nom = (string)$nom;
		Outils::setAttributs('nom', $this);
	}

		public function setAdresse_1($adresse_1) {
		$this->adresse_1 = (string)$adresse_1;
		Outils::setAttributs('adresse_1',$this);
	}

	public function setAdresse_2($adresse_2) {
		$this->adresse_2 = (string)$adresse_2;
		Outils::setAttributs('adresse_2',$this);
	}

	public function setCp($cp) {
		$this->cp = (string)$cp;
		Outils::setAttributs('cp',$this);
	}

	public function setVille($ville) {
		$this->ville = (string)$ville;
		Outils::setAttributs('ville',$this);
	}

	public function setId_pays($id_pays) {
		$this->id_pays = (int)$id_pays;
		Outils::setAttributs('id_pays',$this);
	}

	public function setNom_pays($pays) {
		$this->nom_pays = (string)$pays;
	}

	public function setType($type) {
		$this->type = (int)$type;
		Outils::setAttributs('type',$this);
	}

	public function setSupprime($type) {
		$this->supprime = (int)$type;
		Outils::setAttributs('supprime',$this);
	}

} // FIN classe