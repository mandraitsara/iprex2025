<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet PackingList
Généré par CBO FrameWork le 16/07/2020 à 10:43:28
------------------------------------------------------*/
class PackingList {

	protected    $id,
		$date_envoi,
		$date;

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

	public function getDate() {
		return $this->date;
	}

	public function getDate_envoi() {
		return $this->date_envoi;
	}

	public function getNum_packing_list() {
		return 'PL'. sprintf("%04d",$this->id);
	}

	public function getFichier() {
		return $this->getNum_packing_list().'.pdf';
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setDate($date) {
		$this->date = (string)$date;
		Outils::setAttributs('date',$this);
	}

	public function setDate_envoi($date) {
		$this->date_envoi = (string)$date;
		Outils::setAttributs('date_envoi',$this);
	}

} // FIN classe