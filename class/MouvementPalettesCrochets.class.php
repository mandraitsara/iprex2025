<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2020 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet MouvementPalettesCrochets
Généré par CBO FrameWork le 24/11/2020 à 16:41:48
------------------------------------------------------*/
class MouvementPalettesCrochets {

	protected
		$date,
		$nom_transporteur,
		$crochets_recus,
		$crochets_rendus,
		$palettes_recues,
		$palettes_rendues,
		$poste,
		$signature,
		$nom_user;

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

	public function getDate() {
		return $this->date;
	}

	public function getNom_transporteur() {
		return $this->nom_transporteur;
	}

	public function getCrochets_recus() {
		return $this->crochets_recus;
	}

	public function getCrochets_rendus() {
		return $this->crochets_rendus;
	}

	public function getPalettes_recues() {
		return $this->palettes_recues;
	}

	public function getPalettes_rendues() {
		return $this->palettes_rendues;
	}

	public function getPoste() {
		return $this->poste;
	}

	public function getNom_user() {
		return $this->nom_user;
	}
	public function getSignature() {
		return $this->signature;
	}

	/* ----------------- SETTERS ----------------- */


	public function setDate($date) {
		$this->date = (string)$date;
	}

	public function setNom_transporteur($nom_transporteur) {
		$this->nom_transporteur = (string)$nom_transporteur;
	}

	public function setCrochets_recus($crochets_recus) {
		$this->crochets_recus = (int)$crochets_recus;
	}

	public function setCrochets_rendus($crochets_rendus) {
		$this->crochets_rendus = (int)$crochets_rendus;
	}

	public function setPalettes_recues($palettes_recues) {
		$this->palettes_recues = (int)$palettes_recues;
	}

	public function setPalettes_rendues($palettes_rendues) {
		$this->palettes_rendues = (int)$palettes_rendues;
	}

	public function setPoste($poste) {
		$this->poste = (string)$poste;
	}

	public function setSignature($signature) {
		$this->signature = (string)$signature;
	}

	public function setNom_user($nom_user) {
		$this->nom_user = (string)$nom_user;
	}

} // FIN classe