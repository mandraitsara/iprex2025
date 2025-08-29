<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2021 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet Gesdoc
------------------------------------------------------*/
class Gesdoc {

	protected
		$id,
		$ref,
		$date,
		$date_envoi,
		$id_client,
		$nom_client,
		$type_code,
		$type_texte,
		$total,
		$statut,
		$associes,
		$envoi;

	public       $attributs = [];

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
		$this->attributs = [];
	}

	/* ----------------- GETTERS ----------------- */

	public function getId() {
		return $this->id;
	}

	public function getRef() {
		return $this->ref;
	}

	public function getDate() {
		return $this->date;
	}

	public function getDate_envoi() {
		return $this->date_envoi;
	}

	public function getId_client() {
		return $this->id_client;
	}

	public function getNom_client() {
		return $this->nom_client;
	}

	public function getType_code() {
		return $this->type_code;
	}

	public function getType_texte() {
		return $this->type_texte;
	}

	public function getStatut() {
		return $this->statut;
	}

	public function getEnvoi() {
		return $this->envoi;
	}

	public function getTotal() {
		return $this->total;
	}

	public function getAssocies() {
		return $this->associes;
	}


	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setRef($val) {
		$this->ref = $val;
	}

	public function setDate($val) {
		$this->date = $val;
	}

	public function setDate_envoi($val) {
		$this->date_envoi = $val;
	}

	public function setId_client($val) {
		$this->id_client = (int)$val;
	}

	public function setNom_client($val) {
		$this->nom_client = $val;
	}

	public function setType_code($val) {
		$this->type_code = $val;
	}

	public function setType_texte($val) {
		$this->type_texte = $val;
	}

	public function setStatut($val) {
		$this->statut = (int)$val;
	}

	public function setEnvoi($val) {
		$this->envoi = (int)$val;
	}

	public function setTotal($val) {
		$this->total = (float)$val;
	}

	public function setAssocies($val) {
		$this->associes = is_array($val) ? $val : [];
	}

} // FIN classe