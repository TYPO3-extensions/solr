<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Ingo Renner <ingo@typo3.org>
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
 * Modifies a query to add faceting parameters
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage solr
 */
class Tx_Solr_QueryModifier_Faceting implements Tx_Solr_QueryModifierInterface {

	/**
	 * @var Tx_Extbase_MVC_RequestInterface
	 */
	protected $request;
	
	/**
	 * @var array
	 */
	protected $settings;

	public function setRequest(Tx_Extbase_MVC_RequestInterface $request) {
		$this->request = $request;
	}
	public function setSettings(array $settings) {
		$this->settings = $settings;
	}
	

	/**
	 * Modifies the given query and adds the parameters necessary for faceted
	 * search
	 *
	 * @param	tx_solr_Query	The query to modify
	 * @return	tx_solr_Query	The modified query with faceting parameters
	 */
	public function modifyQuery(tx_solr_Query $query) {
		$facetingParameters = $this->buildFacetingParameters();
		$facetQueryFilters = $this->addFacetQueryFilters();


		foreach ($facetingParameters as $facetParameter => $value) {
			$query->addQueryParameter($facetParameter, $value);
		}

		foreach ($facetQueryFilters as $filter) {
			$query->addFilter($filter);
		}

		return $query;
	}

	/**
	 * Builds faceting parameters. This tells Solr what facets exist.
	 *
	 * @return	array	An array of query parameters
	 */
	protected function buildFacetingParameters() {
		$facetingParameters = array();
		$configuredFacets = $this->settings['search']['faceting']['facets'];

		foreach ($configuredFacets as $facetName => $facetConfiguration) {

			if (empty($facetConfiguration['field'])) {
					// TODO later check for query and date, too
				continue;
			}

			// very simple for now, may add overrides f.<field_name>.facet.* later
			$facetingParameters['facet.field'][] = $facetConfiguration['field'];
		}
		return $facetingParameters;
	}

	/**
	 * Adds filters specified through HTTP GET as filter query parameters to
	 * the Solr query.
	 * This is the place where facet choices are evaluated.
	 *
	 * @return void
	 */
	protected function addFacetQueryFilters() {
		$facetFilters = array();
			// format for filter URL parameter:
			// tx_solr[filter]=$facetName0:$facetValue0,$facetName1:$facetValue1,$facetName2:$facetValue2
		if ($this->request->hasArgument('filter')) {
			$filters = explode(',', $this->request->getArgument('filter'));
			$configuredFacets = $this->getConfiguredFacets();

			foreach ($filters as $filter) {
				list($filterName, $filterValue) = explode(':', $filter);

				if (in_array($filterName, $configuredFacets)) {
						// TODO support query and date facets
					$facetFilters[] = $this->settings['search']['faceting']['facets'][$filterName]['field']
						. ':"' . $filterValue . '"';
				}
			}
		}
		return $facetFilters;
	}

	/**
	 * Gets the facets as configured through TypoScript
	 *
	 * @return	array	An array of facet names as specified in TypoScript
	 */
	protected function getConfiguredFacets() {
		$configuredFacets = $this->settings['search']['faceting']['facets'];
		$facets = array();

		foreach ($configuredFacets as $facetName => $facetConfiguration) {
			if (empty($facetConfiguration['field'])) {
					// TODO later check for query and date, too
				continue;
			}

			$facets[] = $facetName;
		}

		return $facets;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/querymodifier/class.tx_solr_querymodifier_faceting.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/querymodifier/class.tx_solr_querymodifier_faceting.php']);
}

?>