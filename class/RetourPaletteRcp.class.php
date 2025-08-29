<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2020 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet RetourPaletteRcp
Généré par CBO FrameWork le 24/11/2020 à 11:54:07
------------------------------------------------------*/
class RetourPaletteRcp {

	protected    $id,
		$id_transporteur,
		$date_retour,
		$palettes_recues,
		$palettes_rendues,
		$id_user,
		$date_add;

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

	public function getId_transporteur() {
		return $this->id_transporteur;
	}

	public function getDate_retour() {
		return $this->date_retour;
	}

	public function getPalettes_recues() {
		return $this->palettes_recues;
	}

	public function getPalettes_rendues() {
		return $this->palettes_rendues;
	}

	public function getId_user() {
		return $this->id_user;
	}

	public function getDate_add() {
		return $this->date_add;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setId_transporteur($id_transporteur) {
		$this->id_transporteur = (int)$id_transporteur;
		Outils::setAttributs('id_transporteur',$this);
	}

	public function setDate_retour($date_retour) {
		$this->date_retour = (string)$date_retour;
		Outils::setAttributs('date_retour',$this);
	}

	public function setPalettes_recues($palettes_recues) {
		$this->palettes_recues = (int)$palettes_recues;
		Outils::setAttributs('palettes_recues',$this);
	}

	public function setPalettes_rendues($palettes_rendues) {
		$this->palettes_rendues = (int)$palettes_rendues;
		Outils::setAttributs('palettes_rendues',$this);
	}

	public function setId_user($id_user) {
		$this->id_user = (int)$id_user;
		Outils::setAttributs('id_user',$this);
	}

	public function setDate_add($date_add) {
		$this->date_add = (string)$date_add;
		Outils::setAttributs('date_add',$this);
	}

} // FIN classe