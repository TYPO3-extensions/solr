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
	 * @param integer $page
	 */
	public function indexAction($q = '', $page = 0) {
		
		if ($this->settings['addDefaultCss']) {
			// Search CSS
			$pathToCssFile = $GLOBALS['TSFE']->config['config']['absRefPrefix']
			. t3lib_extMgm::siteRelPath(Tx_Extbase_Utility_Extension::convertCamelCaseToLowerCaseUnderscored($this->request->getControllerExtensionName()))
				. 'Resources/Public/CSS/results.css';
			$this->response->addAdditionalHeaderData('<link href="' . $pathToCssFile . '" rel="stylesheet" type="text/css" />');
			
			// Page Browser CSS
			$pathToCssFile = $GLOBALS['TSFE']->config['config']['absRefPrefix']
			. t3lib_extMgm::siteRelPath('pagebrowse')
				. 'res/styles_min.css';
			$this->response->addAdditionalHeaderData('<link href="' . $pathToCssFile . '" rel="stylesheet" type="text/css" />');
		}
		
		$this->initializeSearch($q, $page);
		$this->view->assign('hasSearched', $this->search->hasSearched());
		if ($this->search->hasSearched()) {
			$this->view->assign('search', $this->search);
		}
		$this->view->assign('q', $q);
		$this->view->assign('targetPageId', $this->settings['search']['targetPage']);

		$this->view->assign('acceptCharset', $GLOBALS['TSFE']->metaCharset);
	}
	
	protected function initializeSearch($query, $page) {
		if ($this->solrAvailable && $query || $this->settings['search']['emptyQuery']) {

			if (!$query) {
				// This is an empty query
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
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['modifySearchQuery']['faceting'] = 'EXT:solr/Classes/querymodifier/Faceting.php:tx_solr_querymodifier_Faceting';
			}

			$query->setUserAccessGroups(explode(',', $GLOBALS['TSFE']->gr_list));
			$query->setSiteHash(Tx_Solr_Util::getSiteHash());

			$language = 0;
			if ($GLOBALS['TSFE']->sys_language_uid) {
				$language = $GLOBALS['TSFE']->sys_language_uid;
			}
			$query->addFilter('language:' . $language);

			$additionalFilters = $this->settings['search']['filter'];
			if (!empty($additionalFilters)) {
				$additionalFilters = explode('|', $additionalFilters);
				foreach($additionalFilters as $additionalFilter) {
					$query->addFilter($additionalFilter);
				}
			}

			$resultsPerPage = $this->getNumberOfResultsPerPage();
			$offSet         = $page * $resultsPerPage;

				// ignore page browser?
			$ignorePageBrowser = (boolean) $this->conf['search']['results']['ignorePageBrowser'];
			if ($ignorePageBrowser) {
				$offSet = 0;
			}

				// sorting
			if ($this->settings['searchResultsViewComponents']['sorting']) {
				$query->setSorting();
			}

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

				if ($queryModifier instanceof Tx_Solr_QueryModifierInterface) {
					$queryModifier->setSettings($this->settings);
					$queryModifier->setRequest($this->request);
					$query = $queryModifier->modifyQuery($query);
				}
			}
		}

		return $query;
	}


}

?>
