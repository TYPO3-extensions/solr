<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Ingo Renner <ingo@typo3.org>
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
 * Results per page switchview command
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package	TYPO3
 * @subpackage	solr
 */
class tx_solr_pi_results_ResultsPerPageSwitchCommand implements tx_solr_PluginCommand {

	/**
	 * Parent plugin
	 *
	 * @var tx_solr_pi_results
	 */
	protected $parentPlugin;

	/**
	 * Configuration
	 *
	 * @var array
	 */
	protected $configuration;


	/**
	 * Constructor.
	 *
	 * @param tx_solr_pluginbase_CommandPluginBase Parent plugin object.
	 */
	public function __construct(tx_solr_pluginbase_CommandPluginBase $parentPlugin) {
		$this->parentPlugin  = $parentPlugin;
		$this->configuration = $parentPlugin->conf;
	}

	public function execute() {
		$markers = array();

		$selectOptions = $this->getResultsPerPageOptions();
		if ($selectOptions) {
			$queryLinkBuilder = t3lib_div::makeInstance('tx_solr_query_LinkBuilder', $this->parentPlugin->getSearch()->getQuery());
			$queryLinkBuilder->setLinkTargetPageId($this->parentPlugin->getLinkTargetPageId());
			$form = array(
				'action' => $queryLinkBuilder->getQueryUrl()
			);

			$markers['loop_options|option'] = $selectOptions;
			$markers['form'] = $form;
		} else {
			$markers = NULL;
		}

		return $markers;
	}

	/**
	 * Generates the options for the results per page switch.
	 *
	 * @return array Array of results per page switch options.
	 */
	public function getResultsPerPageOptions() {
		$resultsPerPageOptions = array();

		$resultsPerPageSwitchOptions = t3lib_div::intExplode(',', $this->configuration['search.']['results.']['resultsPerPageSwitchOptions'], TRUE);
		$currentNumberOfResultsShown = $this->parentPlugin->getNumberOfResultsPerPage();

		$queryLinkBuilder = t3lib_div::makeInstance('tx_solr_query_LinkBuilder', $this->parentPlugin->getSearch()->getQuery());
		$queryLinkBuilder->removeUnwantedUrlParameter('resultsPerPage');
		$queryLinkBuilder->setLinkTargetPageId($this->parentPlugin->getLinkTargetPageId());

		foreach ($resultsPerPageSwitchOptions as $option) {
			$selected      = '';
			$selectedClass = '';

			if ($option == $currentNumberOfResultsShown) {
				$selected      = ' selected="selected"';
				$selectedClass = ' class="currentNumberOfResults"';
			}

			$resultsPerPageOptions[] = array(
				'value'         => $option,
				'selected'      => $selected,
				'selectedClass' => $selectedClass,
				'url'           => $queryLinkBuilder->getQueryUrl(array('resultsPerPage' => $option)),
			);
		}

		return $resultsPerPageOptions;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/solr/pi_results/class.tx_solr_pi_results_resultsperpageswitchcommand.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/solr/pi_results/class.tx_solr_pi_results_resultsperpageswitchcommand.php']);
}

?>