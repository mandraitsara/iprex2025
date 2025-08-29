<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet FactureFrais
Généré par CBO FrameWork le 15/07/2020 à 17:12:38
------------------------------------------------------*/
class FactureFrais {

	protected    $id,
		$nom,
		$type,
		$id_taxe,
		$taxe_nom,
		$taxe_taux,
		$valeur,
		$id_facture;

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

	public function getType() {
		return $this->type;
	}

	public function getId_taxe() {
		return $this->id_taxe;
	}

	public function getTaxe_nom() {
		return $this->taxe_nom;
	}

	public function getTaxe_taux() {
		return $this->taxe_taux;
	}

	public function getValeur() {
		return $this->valeur;
	}

	public function getId_facture() {
		return $this->id_facture;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setNom($nom) {
		$this->nom = (string)$nom;
		Outils::setAttributs('nom',$this);
	}

	public function setType($type) {
		$this->type = (int)$type;
		Outils::setAttributs('type',$this);
	}

	public function setId_taxe($id_taxe) {
		$this->id_taxe = (int)$id_taxe;
		Outils::setAttributs('id_taxe',$this);
	}

	public function setTaxe_nom($taxe_nom) {
		$this->taxe_nom = $taxe_nom;
	}

	public function setTaxe_taux($taxe_taux) {
		$this->taxe_taux = floatval($taxe_taux);
	}

	public function setValeur($valeur) {
		$this->valeur = (float)$valeur;
		Outils::setAttributs('valeur',$this);
	}

	public function setId_facture($id_facture) {
		$this->id_facture = (int)$id_facture;
		Outils::setAttributs('id_facture',$this);
	}

} // FIN classe