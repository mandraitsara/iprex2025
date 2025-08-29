<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Objet Tiers
------------------------------------------------------*/
class Tiers {
	
	protected	$id,
				$type,
				$id_famille,
				$famille,
				$nom,
				$code,
				$adresses,
				$contacts,
				$tva_intra,
				$visibilite_palettes,
				$palette_suiv,
				$id_groupe,
				$id_transporteur,
				$nom_groupe,
				$id_langue,
				$bl_chiffre,
				$langue_iso,
				$nb_ex_bl,
				$nb_ex_fact,
				$code_comptable,
				$ean,
				$echeance,
				$stk_type,
				$tva,
				$message,
				$actif,
				$supprime,
				$date_add,
				$date_maj,
				$regroupement,
				$solde_crochets,
				$numagr,
				$solde_palettes;

	public		$attributs = array();

	const TYPES = [
		1 => 'Client',
		2 => 'Fournisseur'
	];
	
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
	
	/*##### GETTERS #####*/

	public function getId() {
		return $this->id;
	}
	
	public function getNom() {
		return $this->nom;
	}

	public function getNumagr(){

		return $this->numagr;
	}

	public function getCode() {
		return $this->code;
	}

	public function getType() {
		return $this->type;
	}

	public function getType_nom() {
		return $this->TYPES[$this->type];
	}

	public function getId_famille() {
		return $this->id_famille;
	}

	public function getFamille() {
		return $this->famille;
	}

	public function getAdresses() {
		return $this->adresses;
	}

	public function getContacts() {
		return $this->contacts;
	}

	public function getTva_intra() {
		return $this->tva_intra;
	}

	public function getVisibilite_palettes() {
		return $this->visibilite_palettes;
	}

	public function getPalette_suiv() {
		return $this->palette_suiv;
	}

	public function getActif() {
		return $this->actif;
	}

	public function getSupprime() {
		return $this->supprime;
	}

	public function getDate_maj() {
		return $this->date_maj;
	}

	public function getDate_add() {
		return $this->date_add;
	}

	public function getId_groupe() {
		return $this->id_groupe;
	}

	public function getNom_groupe() {
		return $this->nom_groupe;
	}

	public function getId_langue() {
		return $this->id_langue;
	}

	public function getId_transporteur() {
		return $this->id_transporteur;
	}

	public function getLangue_iso() {
		return $this->langue_iso;
	}

	public function getBl_chiffre() {
		return $this->bl_chiffre;
	}

	public function getNb_ex_bl() {
		return $this->nb_ex_bl;
	}

	public function getNb_ex_fact() {
		return $this->nb_ex_fact;
	}

	public function getCode_comptable() {
		return $this->code_comptable;
	}

	public function getEan() {
		return $this->ean;
	}

	public function getEcheance() {
		return $this->echeance;
	}

	public function getMessage() {
		return $this->message;
	}

	public function getTva() {
		return $this->tva;
	}

	public function getStk_type() {
		return $this->stk_type;
	}

	public function getRegroupement() {
		return $this->regroupement;
	}

	public function hasRegroupement() {
		return (int)$this->regroupement == 1;
	}

	public function getSolde_crochets() {
		return (int)$this->solde_crochets;
	}

	public function getSolde_palettes() {
		return (int)$this->solde_palettes;
	}
			
	/*##### SETTERS #####*/

	public function setId($valeur) {
		$this->id = (int)$valeur;
	}

	public function setType($valeur) {
		$this->type = (int)$valeur;
	}

	public function setId_famille($valeur) {
		$this->id_famille = (int)$valeur;
	}

	public function setFamille($valeur) {
		$this->famille = (string)$valeur;
	}

	public function setNom($valeur) {
		$this->nom = (string)$valeur;
		Outils::setAttributs('nom',$this);
	}
public function setNumagr($numagr){

	$this->numagr = (string)$numagr;
	Outils::setAttributs('numagr', $this);

}
	public function setAdresses($valeur) {
		$this->adresses = $valeur;
	}

	public function setContacts($valeur) {
		$this->contacts = $valeur;
	}

	public function setTva_intra($valeur) {
		$this->tva_intra = (string)$valeur;
		Outils::setAttributs('tva_intra',$this);
	}

	public function setVisibilite_palettes($valeur) {
		$this->visibilite_palettes = (int)$valeur;
		Outils::setAttributs('visibilite_palettes',$this);
	}

	public function setPalette_suiv($valeur) {
		$this->palette_suiv = (int)$valeur;
		Outils::setAttributs('palette_suiv',$this);
	}

	public function setActif($valeur) {
		$this->actif = (int)$valeur;
		Outils::setAttributs('actif',$this);
	}

	public function setSupprime($valeur) {
		$this->supprime = (int)$valeur;
		Outils::setAttributs('supprime',$this);
	}

	public function setDate_add($valeur) {
		$this->date_add = (string)$valeur;
		Outils::setAttributs('date_add',$this);
	}

	public function setDate_maj($valeur) {
		$this->date_maj = (string)$valeur;
		Outils::setAttributs('date_maj',$this);
	}

	public function setId_groupe($valeur) {
		$this->id_groupe = (int)$valeur;
		Outils::setAttributs('id_groupe',$this);
	}

	public function setId_transporteur($valeur) {
		$this->id_transporteur = (int)$valeur;
		Outils::setAttributs('id_transporteur',$this);
	}

	public function setBl_chiffre($valeur) {
		$this->bl_chiffre = (int)$valeur;
		Outils::setAttributs('bl_chiffre',$this);
	}

	public function setNom_groupe($valeur) {
		$this->nom_groupe = (string)$valeur;
	}

	public function setNb_ex_bl($valeur) {
		$this->nb_ex_bl = (int)$valeur;
		Outils::setAttributs('nb_ex_bl',$this);
	}

	public function setNb_ex_fact($valeur) {
		$this->nb_ex_fact = (int)$valeur;
		Outils::setAttributs('nb_ex_fact',$this);
	}

	public function setEcheance($valeur) {
		$this->echeance = (int)$valeur;
		Outils::setAttributs('echeance',$this);
	}

	public function setCode_comptable($valeur) {
		$this->code_comptable = (string)$valeur;
		Outils::setAttributs('code_comptable',$this);
	}

	public function setEan($valeur) {
		$this->ean = (string)$valeur;
		Outils::setAttributs('ean',$this);
	}

	public function setMessage($valeur) {
		$this->message = (string)$valeur;
		Outils::setAttributs('message',$this);
	}

	public function setCode($valeur) {
		$this->code = (string)$valeur;
		Outils::setAttributs('code',$this);
	}

	public function setTva($valeur) {
		$this->tva = (int)$valeur;
		Outils::setAttributs('tva',$this);
	}

	public function setStk_type($valeur) {
		$this->stk_type = (int)$valeur;
		Outils::setAttributs('stk_type',$this);
	}

	public function setRegroupement($valeur) {
		$this->regroupement = (int)$valeur;
		Outils::setAttributs('regroupement',$this);
	}

	public function setSolde_crochets($valeur) {
		$this->solde_crochets = (int)$valeur;
		Outils::setAttributs('solde_crochets',$this);
	}

	public function setSolde_palettes($valeur) {
		$this->solde_palettes = (int)$valeur;
		Outils::setAttributs('solde_palettes',$this);
	}

	/*##### MTHODES PROPRES A LA CLASSE #####*/

	public function isActif() {
		return (int)$this->actif == 1 && (int)$this->supprime == 0;
	}

	public function isSupprime() {
		return (int)$this->supprime == 1;
	}

	public function setId_langue($valeur) {
		$this->id_langue = (int)$valeur;
		Outils::setAttributs('id_langue',$this);
	}

	public function setLangue_iso($valeur) {
		$this->langue_iso = (string)$valeur;
	}

	// Retourne la liste des pays d'un tiers
	public function getPays($separateur = ', ') {

		// Si il n'a pas d'adresse, on ne retourne rien
		if (empty($this->getAdresses())) { return ''; }

		// Variable de retour et IDs pays déjà trouvés pour éviter les doublons
		$pays 		= '';
		$ids_pays 	= [];

		// On boucle sur les adresses du client
		foreach ($this->getAdresses() as $adresse) {

			// Si l'adresse est mal instanciée, on passe
			if (!$adresse instanceof Adresse) { continue; }

			// Si on a déjà mentionné ce pays, on passe
			if (in_array($adresse->getId_pays(), $ids_pays)) { continue; }

			// On rajoute ce pays aux pays déjà mentionnés
			$ids_pays[] = $adresse->getId_pays();

			// On intègre ce pays dans le retour avec le séparateur
			$pays.= $adresse->getNom_pays() . $separateur;

		} // FIN boucle

		// Si on a des résultats dans le retour
		if (strlen($pays) > 2) {
			// On retire le dernier séparateur
			$pays = substr($pays,0,strlen($separateur)*-1);
		}

		// Retour HTML
		return $pays;

	} // FIN méthode

	// Retourne la liste des noms de contacts d'un tiers pour affichage
	public function getContact($separateur = ', ') {

		// Si il n'a pas de contact, on ne retourne rien
		if (empty($this->getContacts())) { return ''; }

		// Variable de retour
		$contacts = '';

		// On boucle sur les adresses du client
		foreach ($this->getContacts() as $contact) {

			// Si le contact est mal instancié, on passe
			if (!$contact instanceof Contact) { continue; }

			// On intègre ce pays dans le retour avec le séparateur
			$contacts.= $contact->getNom_complet() . $separateur;

		} // FIN boucle

		// Si on a des résultats dans le retour
		if (strlen($contacts) > 2) {
			// On retire le dernier séparateur
			$contacts = substr($contacts,0,strlen($separateur)*-1);
		}

		// Retour HTML
		return $contacts;

	} // FIN méthode

} // FIN classe