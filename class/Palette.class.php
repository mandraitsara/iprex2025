<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet Palette
Généré par CBO FrameWork le 31/07/2019 à 15:26:15
------------------------------------------------------*/
class Palette {

	const STATUTS = [
		0 => "En préparation",
		1 => "Capacité atteinte",
		2 => "Clôturée",
		3 => "Expédiée"
	];

	protected
		$id,
		$numero,
		$date,
		$id_user,
		$id_client,
		$nom_client,
		$nom_user,		// Nom de l'utilisateur ayant créé la palette
		$statut,
		$id_poids_palette,
		$poids_palette_type,
		$poids_palette,
		$quantite,
		$supprime,
		$scan_frais, 	// 0 = Palette traitement / 1 = Plaette frais en scan / 2 = Palette frais scan terminé
		$composition;	// Array d'objets PaletteComposition

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


	public function getQuantite(){
		return $this->quantite;
	}

	public function getId_client() {
		return $this->id_client;
	}

	public function getNumero() {
		return $this->numero;
	}

	public function getDate() {
		return $this->date;
	}

	public function getId_user() {
		return $this->id_user;
	}

	public function getNom_user() {
		return $this->nom_user;
	}

	public function getNom_client() {
		return $this->nom_client;
	}

	public function getStatut() {
		return $this->statut;
	}

	public function isEn_cours() {
		return intval($this->statut) == 0;
	}

	public function isComplete() {
		return intval($this->statut) == 1;
	}

	public function getSupprime() {
		return $this->supprime;
	}

	public function isSupprimee() {
		return intval($this->supprime) == 1;
	}

	public function getComposition() {
		return $this->composition;
	}

	public function getId_poids_palette() {
		return $this->id_poids_palette;
	}

	public function getPoids_palette() {
		return $this->poids_palette;
	}

	public function getPoids_palette_type() {
		return $this->poids_palette_type;
	}

	public function getScan_frais() {
		return $this->scan_frais;
	}



	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setNumero($valeur) {
		$this->numero = (int)$valeur;
		Outils::setAttributs('numero',$this);
	}

	public function setDate($valeur) {
		$this->date = (string)$valeur;
		Outils::setAttributs('date',$this);
	}

	public function setId_user($valeur) {
		$this->id_user = (int)$valeur;
		Outils::setAttributs('id_user',$this);
	}

	public function setNom_user($valeur) {
		$this->nom_user = (string)$valeur;
	}

	public function setStatut($valeur) {
		$this->statut = (int)$valeur;
		Outils::setAttributs('statut',$this);
	}

	public function setSupprime($valeur) {
		$this->supprime = (int)$valeur;
		Outils::setAttributs('supprime',$this);
	}

	public function setId_poids_palette($valeur) {
		$this->id_poids_palette = (int)$valeur;
		Outils::setAttributs('id_poids_palette',$this);
	}

	public function setScan_frais($valeur) {
		$this->scan_frais = (int)$valeur;
		Outils::setAttributs('scan_frais',$this);
	}

	public function setId_client($valeur) {
		$this->id_client = (int)$valeur;
		Outils::setAttributs('id_client',$this);
	}

	public function setPoids_palette($valeur) {
		$this->poids_palette = (float)$valeur;
	}

	public function setNom_client($valeur) {
		$this->nom_client = (string)$valeur;
	}

	public function setQuantite($quantite) {
		$this->quantite = (string)$quantite;
	}

	public function setPoids_palette_type($valeur) {
		$this->poids_palette_type = (string)$valeur;
	}

	public function setComposition($valeur) {
		if (!is_array($valeur)) { return false; }
		$this->composition = $valeur;
	}

	public function getStatut_verbose($na = 'N/A') {
		return  $this::STATUTS[$this->statut] != null
			? $this::STATUTS[$this->statut]
			: $na;
	}

} // FIN classe