<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Sebastian Kurfürst <sebastian@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * The solr results controller
 *
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Tx_Solr_Controller_ResultsController extends Tx_Extbase_MVC_Controller_ActionController {

	/**
	 *
	 * @var Tx_Solr_Search
	 */
	protected $search;

	/**
	 *
	 * @var boolean
	 */
	protected $solrAvailable;


	public function initializeAction() {
		$this->search = t3lib_div::makeInstance('Tx_Solr_Search', $this->settings['solr']['host'],$this->settings['solr']['port'],$this->settings['solr']['path']);
		$this->solrAvailable = $this->search->ping();
	}

	/**
	 *
	 * @param string $q
	 */
	public function indexAction($q = '') {
		$this->initializeSearch();
		$this->view->assign('hasSearched', $this->search->hasSearched());
		if ($this->search->hasSearched()) {
			$this->view->assign('numberOfResults', $this->search->getNumberOfResults());
			$searchResponse = $this->search->getResponse();
			$this->view->assign('searchResponse', $searchResponse);
		}
		$this->view->assign('q', $q);
		$this->view->assign('targetPageId', $this->settings['search']['targetPage']);

		$this->view->assign('acceptCharset', $GLOBALS['TSFE']->metaCharset);
	}
	
	protected function initializeSearch() {
			// TODO provide the option in TS, too
		//$emptyQuery = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'emptyQuery', 'sQuery');
		$emptyQuery = FALSE;

		if ($this->solrAvailable && $this->request->hasArgument('q') || $emptyQuery) {
			$query = $this->request->getArgument('q');

			if ($emptyQuery) {
					// TODO set rows to retrieve when searching to 0
			}

			if ($this->settings['logging']['query']['searchWords']) {
				t3lib_div::devLog('received search query', 'tx_solr', 0, array($query));
			}

			$query = t3lib_div::makeInstance('tx_solr_Query', $query);

			if ($this->settings['search']['highlighting']['enabled']) {
				$query->setHighlighting(true, $this->settings['search']['highlighting']['fragmentSize']);
			}

			if ($this->settings['search']['spellchecking']['enabled']) {
				$query->setSpellchecking();
			}

			if ($this->settings['search']['faceting']['enabled']) {
				$query->setFaceting();
				// TODO $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['modifySearchQuery']['faceting'] = 'EXT:solr/classes/querymodifier/class.tx_solr_querymodifier_faceting.php:tx_solr_querymodifier_Faceting';
			}

			$query->setUserAccessGroups(explode(',', $GLOBALS['TSFE']->gr_list));
			$query->setSiteHash(Tx_Solr_Util::getSiteHash());

			$language = 0;
			if ($GLOBALS['TSFE']->sys_language_uid) {
				$language = $GLOBALS['TSFE']->sys_language_uid;
			}
			$query->addFilter('language:' . $language);

			$additionalFilters = $this->settings['search']['filter'];
			/*if (!empty($additionalFilters)) {
				$additionalFilters = explode('|', $additionalFilters);
				foreach($additionalFilters as $additionalFilter) {
					$query->addFilter($additionalFilter);
				}
			}*/

			// TODO PAGINGif ($)
			//$currentPage    = max(0, intval($this->request['page']));
			$currentPage = 0;
			$resultsPerPage = $this->getNumberOfResultsPerPage();
			$offSet         = $currentPage * $resultsPerPage;

				// ignore page browser?
			$ignorePageBrowser = (boolean) $this->conf['search']['results']['ignorePageBrowser'];
			if ($ignorePageBrowser) {
				$offSet = 0;
			}

				// sorting
			if ($this->settings['searchResultsViewComponents']['sorting']) {
				$query->setSorting();
			}

			/*$flexformSorting = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'sortBy', 'sQuery');
			if (!empty($flexformSorting)) {
				$query->addQueryParameter('sort', $flexformSorting);
			}*/

			$query = $this->modifyQuery($query);

			$response = $this->search->search($query, $offSet, $resultsPerPage);
		}
	}

	protected function getNumberOfResultsPerPage() {
		$resultsPerPageSwitchOptions = t3lib_div::intExplode(',', $this->settings['search']['results']['resultsPerPageSwitchOptions']);

		if ($this->request->hasArgument('resultsPerPage') && in_array($this->request->getArgument('resultsPerPage'), $resultsPerPageSwitchOptions)) {
			$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_solr_resultsPerPage', intval($this->request->getArgument('resultsPerPage')));
		}

		$defaultNumberOfResultsShown = $this->settings['search']['results']['resultsPerPage'];
		$userSetNumberOfResultsShown = $GLOBALS['TSFE']->fe_user->getKey('ses', 'tx_solr_resultsPerPage');

		$currentNumberOfResultsShown = $defaultNumberOfResultsShown;
		if (!is_null($userSetNumberOfResultsShown) && in_array($userSetNumberOfResultsShown, $resultsPerPageSwitchOptions)) {
			$currentNumberOfResultsShown = (int) $userSetNumberOfResultsShown;
		}

		return $currentNumberOfResultsShown;
	}

	protected function modifyQuery($query) {
			// hook to modify the search query
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['modifySearchQuery'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['modifySearchQuery'] as $classReference) {
				$queryModifier = t3lib_div::getUserObj($classReference);

				if ($queryModifier instanceof tx_solr_QueryModifier) {
					$query = $queryModifier->modifyQuery($query);
				}
			}
		}

		return $query;
	}


}

?>
