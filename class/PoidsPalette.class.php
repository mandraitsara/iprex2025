<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet PoidsPalette
Généré par CBO FrameWork le 18/06/2020 à 10:20:32
------------------------------------------------------*/
class PoidsPalette {

	protected    $id,
		$nom,
		$poids,
		$type;

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

    public function getPoids() {
		return $this->poids;
	}

    public function getType() {
		return $this->type;
	}

    /* ----------------- SETTERS ----------------- */

    public function setId($id) {
		$this->id = (int)$id;
	}

    public function setNom($nom) {
		$this->nom = (string)$nom;
		Outils::setAttributs('nom',$this);
	}

    public function setPoids($poids) {
		$this->poids = (float)$poids;
		Outils::setAttributs('poids',$this);
	}

    public function setType($type) {
		$this->type = (int)$type;
		Outils::setAttributs('type',$this);
	}

} // FIN classe