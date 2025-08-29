<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Classe Pagination
------------------------------------------------------*/
class Pagination {

	protected	$nb_results_page 	= 10,
				$nb_results 		= 0,
				$page 				= 1,
				$nb_avant 			= 1,
				$nb_apres 			= 1,
				$start 				= 0,
				$verbose_pagination = 0,
				$verbose_position 	= 'top',
				$url 				= '',
				$page_param 		= 'page',
				$nature_resultats 	= 'résultat',
				$paginationHtml		= '',
				$commentaires 		= '',
				$card_btn_classes	= '',
				$ajax_function 		= 0;

	public function __construct($page = 1) {
		$this->setPage($page < 1 ? 1 : $page);
	}

	/*---------------------------------------
		Getters
	---------------------------------------*/


	public function getNb_results_page() {
		return $this->nb_results_page;
	}

	public function getNb_results() {
		return $this->nb_results;
	}

	public function getUrl() {
		return $this->url;
	}

	public function getPage() {
		return $this->page;
	}

	public function getPage_param() {
		return $this->page_param;
	}

	public function getNb_avant() {
		return $this->nb_avant;
	}

	public function getNb_apres() {
		return $this->nb_apres;
	}

	public function getVerbose_pagination() {
		return $this->verbose_pagination;
	}

	public function getVerbose_position() {
		return $this->verbose_position;
	}

	public function getNature_resultats() {
		return $this->nature_resultats;
	}

	public function getStart() {
		$start = ($this->page - 1) * $this->nb_results_page;
		return $start < 0 ? 0 : $start;
	}

	public function getAjax_function() {
		return $this->ajax_function;
	}

	public function getCommentaires() {
		return $this->commentaires;
	}

	public function getCardFooterButtonClasses() {
		return $this->card_btn_classes;
	}


	/*---------------------------------------
		Setters
	---------------------------------------*/

	public function setNb_results_page($nb_results_page) {
		$this->nb_results_page = (int) $nb_results_page;
	}

	public function setNb_results($nb_results) {
		$this->nb_results = (int) $nb_results;
	}

	public function setUrl($url) {
		$this->url = (string) $url;
	}

	public function setPage($page) {
		$this->page = (int) $page;
	}

	public function setPage_param($page_param) {
		$this->page_param = (string) $page_param;
	}

	public function setNb_avant($nb_avant) {
		$this->nb_avant = (int) $nb_avant;
	}

	public function setNb_apres($nb_apres) {
		$this->nb_apres = (int) $nb_apres;
	}

	public function setVerbose_pagination($verbose_pagination) {
		$this->verbose_pagination = (int) $verbose_pagination;
	}

	public function setVerbose_position($verbose_position) {
		$this->verbose_position = (string) $verbose_position;
	}

	public function setNature_resultats($nature_resultats) {
		$this->nature_resultats = (string) $nature_resultats;
	}

	public function setAjax_function($ajax_function) {
		$this->ajax_function = (string) $ajax_function;
	}

	public function setCommentaires($commentaires) {
		$this->commentaires = (string) $commentaires;
	}

	public function setCardFooterButtonClasses($classes) {
		$this->card_btn_classes = (string)$classes;
	}


	/*---------------------------------------
		Méthodes
	---------------------------------------*/

	// Retourne la pagination en HTML
	public function getPaginationHtml($fontAwesome = true, $taille = '') {
		$this->paginationHtml = '';
		if ($this->nb_results == 0) {
			return $this->paginationHtml;
		}
		$courant	= $this->page;
		$nb_pages	= ceil($this->nb_results / $this->nb_results_page);
		$avant		= $courant > ($this->nb_avant + 1) ? $this->nb_avant : $courant - 1;
		$apres		= $courant <= $nb_pages - $this->nb_apres ? $this->nb_apres : $nb_pages - $courant;
		$pluriel_nature	= ($this->nb_results > 1 && substr($this->nature_resultats,-1) != 's')  ? 's' : '';
		$pluriel = $this->nb_results > 1 ? 's' : '';

		$feminin = substr($this->nature_resultats, -1, 1) == 'e' ? 'e' : '';

		if (strtolower(trim($taille)) == 'lg') {
			$taille = ' pagination-lg';
		} else if (strtolower(trim($taille)) == 'sm') {
			$taille = ' pagination-sm';
		} else {
			$taille = '';
		}

		if ($nb_pages <1 ) {
			return $this->paginationHtml;
		}

		$url = $this->url == '' ? '?'.$this->page_param : $this->url . '&'.$this->page_param;

		$this->paginationHtml.='
		<nav class="pagination-container" aria-label="Pagination">';

		if ($this->verbose_pagination == 1) {

			$this->paginationHtml.='

				<div class="pagination-verbose verbose-'.$this->verbose_position.'">

					Page '. $courant.'/'. $nb_pages.' '

					. '<span>('. number_format($this->nb_results,0,'',' ').' '. $this->nature_resultats.$pluriel_nature .' trouvé'. $feminin.$pluriel.')</span>';

			$this->paginationHtml.= $this->commentaires != '' ? $this->commentaires : '';
			$this->paginationHtml.='</div>';
		}
		$this->paginationHtml.='
			<ul class="pagination'.$taille.'">';

		// première page
		if ($courant > 1) {

			$this->paginationHtml.='
			<li class="page-item">
				<a '.($this->getAjax_function()== true ? 'data-url="'.$url.'=1'.'" href="#"' : 'href="'.$url.'=1"').' data-page="1" aria-label="Première page" title="Première page" class="page-link">
					<span aria-hidden="true" class="color-pagination">';
						$this->paginationHtml.= $fontAwesome ? '<i class="fa fa-step-backward color-pagination"></i>' : '<<';
					$this->paginationHtml.= '</span>
				</a>
			</li>';

		}     

		// page précédente
		if ($courant > 1) {
			$page_prec = $courant -1;
			$this->paginationHtml.='
			<li class="page-item">
				<a '.($this->getAjax_function()== true ? 'data-url="'.$url.'='.$page_prec.'" href="#"' : 'href="'.$url.'=' . $page_prec  . '"').' aria-label="Page précédente" title="Page précédente" class="page-link" data-page="'.$page_prec.'">
					<span aria-hidden="true" class="color-pagination">';
						$this->paginationHtml.= $fontAwesome ? '<i class="fa fa-arrow-left color-pagination"></i>' : '<';
					$this->paginationHtml.= '</span>
				</a>
			</li>';
		}

		// affichage des numeros de page
		for ($e = $courant - $avant; $e <= $courant + $apres; $e++) {
			if ($e == $courant && $nb_pages > 1 && $e>0 && $e<=$nb_pages) {
				$this->paginationHtml.='
				<li class="page-item active"><a href="#" onclick="return false" class="page-link">'. $e .'<span class="sr-only">(current)</span></a></li>';
			} else if ($nb_pages > 1 && $e>0 && $e<=$nb_pages) {
				$this->paginationHtml.='
				<li class="page-item"><a '.($this->getAjax_function() == true ? 'data-url="'.$url.'='.$e.'" href="#"' : 'href="'.$url.'='. $e .'"').' class="page-link" data-page="'.$e.'">'. $e.' </a></li>';
			}
		}

		// page suivante
		if ($courant < $nb_pages) {
			$page_suiv = $courant +1;
				$this->paginationHtml.='
				<li class="page-item">
					<a '.($this->getAjax_function() == true ? 'data-url="'.$url.'='.$page_suiv.'" href="#"' : 'href="'.$url.'='. $page_suiv .'"').' aria-label="Suivant" title="Page suivante" class="page-link" data-page="'.$page_suiv.'">
						<span aria-hidden="true" class="color-pagination">';
							$this->paginationHtml.= $fontAwesome ? '<i class="fa fa-arrow-right color-pagination"></i>' : '>';
						$this->paginationHtml.= '</span>
					</a>
				</li>';
		}    

		if ($courant < $nb_pages) {
			$this->paginationHtml.='
				<li class="page-item">
					<a '.($this->getAjax_function()== true ? 'data-url="'.$url.'='.$nb_pages.'" href="#"' : 'href="'.$url.'='. $nb_pages .'"').' aria-label="Dernière page" title="Dernière page" class="page-link" data-page="'.$nb_pages.'">
						<span aria-hidden="true" class="color-pagination">';
							$this->paginationHtml.= $fontAwesome ? '<i class="fa fa-step-forward color-pagination"></i>' : '>>';
						$this->paginationHtml.= '</span>
					</a>
				</li>';
	}
	$this->paginationHtml.='
	</ul>
</nav>';
	return $this->paginationHtml;

	} // FIN méthode

	// Retourne la pagination en blocs (PROFIL EXPORT PRODUITS VUE ATELIER)
	public function getPaginationBlocs() {

		$this->paginationHtml = '';
		if ($this->nb_results == 0) {
			return $this->paginationHtml;
		}

		$courant	= $this->page;
		$nb_pages	= ceil($this->nb_results / $this->nb_results_page);
		$avant		= $courant > ($this->nb_avant + 1) ? $this->nb_avant : $courant - 1;
		$apres		= $courant <= $nb_pages - $this->nb_apres ? $this->nb_apres : $nb_pages - $courant;
		$pluriel_nature	= ($this->nb_results > 1 && substr($this->nature_resultats,-1) != 's')  ? 's' : '';
		$pluriel = $this->nb_results > 1 ? 's' : '';
		$feminin = substr($this->nature_resultats, -1, 1) == 'e' ? 'e' : '';

		if ($nb_pages <1 ) {
			return $this->paginationHtml;
		}

		$url = $this->url == '' ? '?'.$this->page_param : $this->url . '&'.$this->page_param;
		$this->paginationHtml.='';

		$balisesBtnFooterO = $this->card_btn_classes != '' ? '<div class="row"><div class="col"><button class="btn '.$this->card_btn_classes.'">' : '';
		$balisesBtnFooterF = $this->card_btn_classes != '' ? '</button></div></div>' : '';

		// page précédente
		if ($courant > 1) {
			$page_prec = $courant -1;
			$this->paginationHtml.='
			<div class="col-2 mb-3">
				<div class="card bg-secondary text-white pointeur carte-pdt-pagination" data-url="'.$url.'='.$page_prec.'">
					<div class="card-header">
						Page '.$courant.' sur '.$nb_pages.'
					</div>
					<div class="card-body">
						<h4 class="card-title mb-0 mt-3"><i class="fa fa-2x fa-chevron-circle-left"></i></h4>
					</div>
					<div class="card-footer">'.$balisesBtnFooterO.'
					Page précédente
					'.$balisesBtnFooterF.'</div>
				</div>
			</div>';
		}

		// page suivante
		if ($courant < $nb_pages) {
			$page_suiv = $courant +1;
			$this->paginationHtml.='
			<div class="col-2 mb-3">
				<div class="card bg-secondary text-white pointeur carte-pdt-pagination" data-url="'.$url.'='.$page_suiv.'">
					<div class="card-header">
						Page '.$courant.' sur '.$nb_pages.'
					</div>
					<div class="card-body">
						<h4 class="card-title mb-0 mt-3"><i class="fa fa-2x fa-chevron-circle-right"></i></h4>
					</div>
					<div class="card-footer">'.$balisesBtnFooterO.'
					Page suivante
					'.$balisesBtnFooterF.'</div>
				</div>
			</div>';
		}
		return $this->paginationHtml;
	} // FIN méthode


} // FIN classe