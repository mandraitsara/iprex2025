<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2020 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet PrpOp
Généré par CBO FrameWork le 06/10/2020 à 15:25:12
------------------------------------------------------*/
class PrpOp {

	protected
		$id,
		$bls,		// Array d'objets BL
		$id_transporteur,
		$nom_transporteur,
		$date,
		$cmd_conforme,
		$t_surface,
		$t_surface_conforme,
		$t_camion,
		$t_camion_conforme,
		$emballage_conforme,
		$palettes_poids,
		$palettes_recues,
		$palettes_rendues,
		$crochets_recus,
		$crochets_rendus,
		$id_user,
		$nom_user,
		$id_validateur,
		$nom_validateur,
		$date_add,
		$date_visa;

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

	public function getBls() {
		return $this->bls;
	}

	public function getId_transporteur() {
		return $this->id_transporteur;
	}

	public function getNom_transporteur() {
		return $this->nom_transporteur;
	}

	public function getDate() {
		return $this->date;
	}

	public function getCmd_conforme() {
		return (int)$this->cmd_conforme;
	}

	public function getT_surface() {
		return $this->t_surface;
	}

	public function getT_surface_conforme() {
		return $this->t_surface_conforme;
	}

	public function getT_camion() {
		return $this->t_camion;
	}

	public function getT_camion_conforme() {
		return $this->t_camion_conforme;
	}

	public function getEmballage_conforme() {
		return $this->emballage_conforme;
	}

	public function getId_user() {
		return $this->id_user;
	}

	public function getNom_user() {
		return $this->nom_user;
	}

	public function getNom_validateur() {
		return $this->nom_validateur;
	}

	public function getId_validateur() {
		return $this->id_validateur;
	}

	public function getDate_visa() {
		return $this->date_visa;
	}

	public function getDate_add() {
		return $this->date_add;
	}

	public function getPalettes_poids() {
		return (float)$this->palettes_poids;
	}

	public function getPalettes_recues() {
		return (int)$this->palettes_recues;
	}

	public function getPalettes_rendues() {
		return (int)$this->palettes_rendues;
	}

	public function getCrochets_recus() {
		return (int)$this->crochets_recus;
	}

	public function getCrochets_rendus() {
		return (int)$this->crochets_rendus;
	}

	/* ----------------- SETTERS ----------------- */

	public function setId($id) {
		$this->id = (int)$id;
	}

	public function setBls($bls) {
		$this->bls = $bls;
	}

	public function setId_transporteur($id_trans) {
		$this->id_transporteur = (int)$id_trans;
		Outils::setAttributs('id_transporteur',$this);
	}

	public function setNom_transporteur($nom_client) {
		$this->nom_transporteur = (string)$nom_client;
	}

	public function setDate($date) {
		$this->date = (string)$date;
		Outils::setAttributs('date',$this);
	}

	public function setCmd_conforme($cmd_conforme) {
		$this->cmd_conforme = (int)$cmd_conforme;
		Outils::setAttributs('cmd_conforme',$this);
	}

	public function setT_surface($t_surface) {
		$this->t_surface = (float)$t_surface;
		Outils::setAttributs('t_surface',$this);
	}

	public function setT_surface_conforme($t_surface_conforme) {
		$this->t_surface_conforme = (int)$t_surface_conforme;
		Outils::setAttributs('t_surface_conforme',$this);
	}

	public function setT_camion($t_camion) {
		$this->t_camion = (float)$t_camion;
		Outils::setAttributs('t_camion',$this);
	}

	public function setT_camion_conforme($t_camion_conforme) {
		$this->t_camion_conforme = (int)$t_camion_conforme;
		Outils::setAttributs('t_camion_conforme',$this);
	}

	public function setEmballage_conforme($emballage_conforme) {
		$this->emballage_conforme = (int)$emballage_conforme;
		Outils::setAttributs('emballage_conforme',$this);
	}

	public function setId_user($id_user) {
		$this->id_user = (int)$id_user;
		Outils::setAttributs('id_user',$this);
	}

	public function setNom_user($nom_user) {
		$this->nom_user = (string)$nom_user;
	}

	public function setNom_validateur($nom_validateur) {
		$this->nom_validateur = (string)$nom_validateur;
	}

	public function setPalettes_poids($valeur) {
		$this->palettes_poids = (float)$valeur;
		Outils::setAttributs('palettes_poids',$this);
	}

	public function setPalettes_recues($valeur) {
		$this->palettes_recues = (int)$valeur;
		Outils::setAttributs('palettes_recues',$this);
	}

	public function setPalettes_rendues($valeur) {
		$this->palettes_rendues = (int)$valeur;
		Outils::setAttributs('palettes_rendues',$this);
	}

	public function setCrochets_recus($valeur) {
		$this->crochets_recus = (int)$valeur;
		Outils::setAttributs('crochets_recus',$this);
	}

	public function setCrochets_rendus($valeur) {
		$this->crochets_rendus = (int)$valeur;
		Outils::setAttributs('crochets_rendus',$this);
	}

	public function setId_validateur($id_validateur) {
		$this->id_validateur = (int)$id_validateur;
		Outils::setAttributs('id_validateur',$this);
	}

	public function setDate_visa($date_visa) {
		$this->date_visa = (string)$date_visa;
		Outils::setAttributs('date_visa',$this);
	}

	public function setDate_add($date_add) {
		$this->date_add = (string)$date_add;
		Outils::setAttributs('date_add',$this);
	}

	public function getIds_clients($implode = false) {

		$ids_clients = [];
		foreach ($this->bls as $bl) {
			if ((int)$bl->getId_tiers_livraison() == 0) { continue; }
			$ids_clients[] = $bl->getId_tiers_livraison();
		}
		return $implode ? implode(',',$ids_clients) : $ids_clients;
	}

	public function getNoms_clients($sep = '<br>') {

		$noms_clients = [];
		foreach ($this->bls as $bl) {
			if ($bl->getNom_client() == '') { continue; }
			$noms_clients[$bl->getId_tiers_livraison()] = $bl->getNom_client();
		}
		return implode($sep, $noms_clients);
	}

	public function getNums_bls($sep = '<br>') {
		$nums_bls = [];
		foreach ($this->bls as $bl) {
			if ($bl->getNum_bl() == '') { continue; }
			$nums_bls[] = $bl->getNum_bl();
		}
		return implode($sep, $nums_bls);
	}

	public function getIds_bls() {
		$ids_bls = [];
		foreach ($this->bls as $bl) {
			if ((int)$bl->getId() == 0) { continue; }
			$ids_bls[] = $bl->getId();
		}
		return $ids_bls;
	}

} // FIN classe