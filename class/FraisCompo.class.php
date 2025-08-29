<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet FraisCompo
Généré par CBO FrameWork le 27/02/2020 à 16:21:17
------------------------------------------------------*/
class FraisCompo {

	protected
		$id_compo,
		$id_lot,
		$quantieme,
		$dlc;

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

	public function getId_compo() {
		return $this->id_compo;
	}

	public function getId_lot() {
		return $this->id_lot;
	}

	public function getQuantieme() {
		return $this->quantieme;
	}

	public function getDlc() {
		return $this->dlc;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setId_compo($id_compo) {
		$this->id_compo = (int)$id_compo;
		Outils::setAttributs('id_compo',$this);
	}

	public function setId_lot($id_lot) {
		$this->id_lot = (int)$id_lot;
		Outils::setAttributs('id_lot',$this);
	}

	public function setQuantieme($quantieme) {
		$this->quantieme = (string)$quantieme;
		Outils::setAttributs('quantieme',$this);
	}

	public function setDlc($dlc) {
		$this->dlc = (string)$dlc;
		Outils::setAttributs('dlc',$this);
	}

} // FIN classe