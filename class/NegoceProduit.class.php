<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Objet NegoceProduit
Généré par CBO FrameWork le 13/01/2020 à 17:18:09
------------------------------------------------------*/
class NegoceProduit {

	protected$id_lot_pdt_negoce,
		$id_lot_negoce,
		$id_bl, // ID du BL sortant associé
		$num_lot, // Référence du BL sortant associé
		$id_pdt,
		$numagr,
		$id_palette,
		$dlc,		
		$nom_produit,
		$nb_cartons,
		$fournisseur,
		$poids,
		$traite,
		$quantite,
		$date_add,
		$id_facture,
		$date_reception,
		$user_add,
		$date_maj,
		$user_maj,
		$supprime,
		$num_bl,
		$num_facture,
		$poids_clients,
		$nom_client,
		$numero_bl,
		$status,
		$numero_palette;

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

	public function getId_lot_pdt_negoce() {
		return $this->id_lot_pdt_negoce;
	}

	public function getStatus(){
		return $this->status;
	}

	public function getNum_facture() {
		return $this->num_facture;
	}
	
	public function getId_facture() {
		return $this->id_facture;
	}

	public function getnom_client() {
		return $this->nom_client;
	}
	

	public function getDlc() {
		return $this->dlc;
	}

	public function getNum_bl(){
		return $this->num_bl;
	}

	public function getFournisseur(){
		return $this->fournisseur;
	}
	public function getId_bl() {
		return $this->id_bl;
	}

	public function getId_palette() {
		return $this->id_palette;
	}

	

	public function getId_lot_negoce() {
		return $this->id_lot_negoce;
	}

	public function getId_pdt() {
		return $this->id_pdt;
	}

	public function getNum_lot(){
		return $this->num_lot;
	}

	public function getDate_reception(){
		return $this->date_reception;
	}

	public function getNb_cartons() {
		return $this->nb_cartons;
	}

	public function getPoids() {
		return $this->poids;
	}

	public function getTraite() {
		return $this->traite;
	}

	public function getQuantite() {
		return $this->quantite;
	}

	public function getDate_add() {
		return $this->date_add;
	}

	public function getUser_add() {
		return $this->user_add;
	}

	public function getDate_maj() {
		return $this->date_maj;
	}

	public function getNumagr(){
		return $this->numagr;
	}

	public function getUser_maj() {
		return $this->user_maj;
	}

	public function getSupprime() {
		return $this->supprime;
	}

	public function getNom_produit() {
		return $this->nom_produit;
	}

	public function getNumero_palette() {
		return $this->numero_palette;
	}

	public function getPoids_clients() {
		return $this->poids_clients;
	}

	public function getNumero_bl(){
		return $this->numero_bl;
	}
	/* ----------------- SETTERS ----------------- */

	public function setId_lot_pdt_negoce($id_lot_pdt_negoce) {
		$this->id_lot_pdt_negoce = (int)$id_lot_pdt_negoce;
	}

	public function setId_bl($id_bl) {
		$this->id_bl = (int)$id_bl;
	}

	public function setNumagr($numagr) {
		$this->numagr = (string)$numagr;
	}

	public function setStatus($status){
		$this->status = (int)$status;
	}

	public function setNum_facture($num_facture){
		$this->num_facture = (string)$num_facture;
		Outils::setAttributs('num_facture',$this);
	}

	public function setId_facture($id_facture){
		$this->id_facture = (string)$id_facture;
		Outils::setAttributs('id_facture',$this);
	}

	public function setId_palette($id_palette) {
		$this->id_palette = (int)$id_palette;
		Outils::setAttributs('id_palette',$this);
	}

	public function setNum_lot($num_lot) {
		$this->num_lot = (string)$num_lot;
		Outils::setAttributs('num_lot',$this);
	}

	public function setFournisseur($fournisseur) {
		$this->fournisseur = (string)$fournisseur;
		Outils::setAttributs('fournisseur',$this);
	}

	public function setDate_reception($date_reception) {
		$this->date_reception = (string)$date_reception;
		Outils::setAttributs('date_reception',$this);
	}

	public function setId_lot_negoce($id_lot_negoce) {
		$this->id_lot_negoce = (int)$id_lot_negoce;
		Outils::setAttributs('id_lot_negoce',$this);
	}
	

	public function setDlc($dlc) {
		$this->dlc = (string)$dlc;
		Outils::setAttributs('dlc',$this);
	}

	public function setNum_bl($num_bl) {
		$this->num_bl = (string)$num_bl;
		Outils::setAttributs('num_bl',$this);
	}

	public function setNom_client($nom_client){
		$this->nom_client = (string)$nom_client;
		Outils::setAttributs('nom_client',$this);
	}

	public function setId_pdt($id_pdt) {
		$this->id_pdt = (int)$id_pdt;
		Outils::setAttributs('id_pdt',$this);
	}

	public function setNb_cartons($nb_cartons) {
		$this->nb_cartons = (int)$nb_cartons;
		Outils::setAttributs('nb_cartons',$this);
	}

	public function setPoids($poids) {
		$this->poids = (float)$poids;
		Outils::setAttributs('poids',$this);
	}

	public function setTraite($traite) {
		$this->traite = (int)$traite;
		Outils::setAttributs('traite',$this);
	}

	public function setQuantite($quantite) {
		$this->quantite = (int)$quantite;
		Outils::setAttributs('quantite',$this);
	}

	public function setDate_add($date_add) {
		$this->date_add = (string)$date_add;
		Outils::setAttributs('date_add',$this);
	}

	public function setUser_add($user_add) {
		$this->user_add = (int)$user_add;
		Outils::setAttributs('user_add',$this);
	}

	public function setDate_maj($date_maj) {
		$this->date_maj = (string)$date_maj;
		Outils::setAttributs('date_maj',$this);
	}

	public function setUser_maj($user_maj) {
		$this->user_maj = (int)$user_maj;
		Outils::setAttributs('user_maj',$this);
	}

	public function setSupprime($supprime) {
		$this->supprime = (int)$supprime;
		Outils::setAttributs('supprime',$this);
	}

	public function setNom_produit($nom_produit) {
		$this->nom_produit = (string)$nom_produit;
		Outils::setAttributs('nom_produit',$this);
	}

	public function setPoids_clients($poids_clients) {
		$this->poids_clients = (string)$poids_clients;
		Outils::setAttributs('poids_clients',$this);
	}

	public function setNumero_bl($numero_bl) {
		$this->numero_bl = (string)$numero_bl;
		Outils::setAttributs('numero_bl',$this);
	}

	public function setNumero_palette($numero_palette) {
		$this->numero_palette = $numero_palette;		
	}

} // FIN classe