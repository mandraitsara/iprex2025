<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet PointControle
Généré par CBO FrameWork le 03/09/2020 à 11:08:50
------------------------------------------------------*/
class PointControle {

	protected    $id,
		$nom,
		$id_parent,
		$position,
		$doc,
		$activation,
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

	public function getNom() {
		return $this->nom;
	}

	public function getId_parent() {
		return $this->id_parent;
	}

	public function getPosition() {
		return $this->position;
	}

	public function getDoc() {
		return $this->doc;
	}

	public function getActivation() {
		return $this->activation;
	}

	public function getSupprime() {
		return $this->supprime;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setNom($nom) {
		$this->nom = (string)$nom;
		Outils::setAttributs('nom',$this);
	}

	public function setId_parent($id_parent) {
		$this->id_parent = (int)$id_parent;
		Outils::setAttributs('id_parent',$this);
	}

	public function setPosition($position) {
		$this->position = (int)$position;
		Outils::setAttributs('position',$this);
	}

	public function setDoc($doc) {
		$this->doc = (int)$doc;
		Outils::setAttributs('doc',$this);
	}

	public function setActivation($activation) {
		$this->activation = (int)$activation;
		Outils::setAttributs('activation',$this);
	}

	public function setSupprime($supprime) {
		$this->supprime = (int)$supprime;
		Outils::setAttributs('supprime',$this);
	}

} // FIN classe