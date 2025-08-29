<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet FactureReglement
Généré par CBO FrameWork le 16/09/2020 à 16:17:25
------------------------------------------------------*/
class FactureReglement {

	protected    $id,
		$id_facture,
		$date,
		$montant,
		$id_mode,
		$nom_mode;

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

	public function getId_facture() {
		return $this->id_facture;
	}

	public function getDate() {
		return $this->date;
	}

	public function getMontant() {
		return $this->montant;
	}

	public function getId_mode() {
		return $this->id_mode;
	}

	public function getNom_mode() {
		return $this->nom_mode;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setId_facture($id_facture) {
		$this->id_facture = (int)$id_facture;
		Outils::setAttributs('id_facture',$this);
	}

	public function setDate($date) {
		$this->date = (string)$date;
		Outils::setAttributs('date',$this);
	}

	public function setMontant($montant) {
		$this->montant = (float)$montant;
		Outils::setAttributs('montant',$this);
	}

	public function setId_mode($id_mode) {
		$this->id_mode = (int)$id_mode;
		Outils::setAttributs('id_mode',$this);
	}

	public function setNom_mode($nom_mode) {
		$this->nom_mode = (string)$nom_mode;
	}

} // FIN classe