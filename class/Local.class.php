<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2020 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet Local
Généré par CBO FrameWork le 01/12/2020 à 09:26:07
------------------------------------------------------*/
class Local {

	protected
		$id,
		$nom,
		$numero,
		$vues,
		$surface;

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

	public function getNumero() {
		return $this->numero;
	}

	public function getSurface() {
		return $this->surface;
	}

	public function getVues() {
		return $this->vues;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setNom($nom) {
		$this->nom = (string)$nom;
		Outils::setAttributs('nom',$this);
	}

	public function setNumero($numero) {
		$this->numero = (int)$numero;
		Outils::setAttributs('numero',$this);
	}

	public function setSurface($surface) {
		$this->surface = (float)$surface;
		Outils::setAttributs('surface',$this);
	}

	public function setVues($vues) {
		$this->vues = $vues;
		Outils::setAttributs('vues',$this);
	}

} // FIN classe