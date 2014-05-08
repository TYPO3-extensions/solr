<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Dimitri Ebert <dimitri.ebert@dkd.de>
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
 * Last searches view command to display a user's last searches or the last
 * searches of all users.
 *
 * @author	Dimitri Ebert <dimitri.ebert@dkd.de>
 * @author	Ingo Renner <ingo.renner@dkd.de>
 * @package	TYPO3
 * @subpackage	solr
 */
class Tx_Solr_PiResults_LastSearchesCommand implements Tx_Solr_PluginCommand {

	/**
	 * Parent plugin
	 *
	 * @var Tx_Solr_PiResults_Results
	 */
	protected $parentPlugin;

	/**
	 * Configuration
	 *
	 * @var	array
	 */
	protected $configuration;

	/**
	 * Constructor.
	 *
	 * @param Tx_Solr_PluginBase_CommandPluginBase Parent plugin object.
	 */
	public function __construct(Tx_Solr_PluginBase_CommandPluginBase $parentPlugin) {
		$this->parentPlugin  = $parentPlugin;
		$this->configuration = $parentPlugin->conf;
	}

	/**
	 * Provides the values for the markers for the last search links
	 *
	 * @return array	an array containing values for markers for the last search links template
	 */
	public function execute() {
		if ($this->configuration['search.']['lastSearches'] == 0) {
				// command is not activated, intended early return
			return NULL;
		}

		$lastSearches = $this->getLastSearches();
		if(empty($lastSearches)) {
			return NULL;
		}
		
		$marker = array(
			'loop_lastsearches|lastsearch' => $lastSearches
		);

		return $marker;
	}

	/**
	 * Prepares the content for the last search markers
	 *
	 * @return	array	An array with content for the last search markers
	 */
	protected function getLastSearches() {
		$lastSearchesKeywords = array();
		switch ($this->configuration['search.']['lastSearches.']['mode']) {
			case 'user':
				$lastSearchesKeywords = $this->getLastSearchesFromSession();
				break;
			case 'global':
				$lastSearchesKeywords = $this->getLastSearchesFromDatabase($this->configuration['search.']['lastSearches.']['limit']);
				break;
		}
			// fill array for output
		$i = 0;
		$lastSearches = array();
		foreach ($lastSearchesKeywords as $keywords) {
			if (++$i > $this->configuration['search.']['lastSearches.']['limit']) {
				break;
			}

			$keywords       = stripslashes($keywords);
			$lastSearches[] = array(
				'q'          => Tx_Solr_Template::escapeMarkers($keywords),
				'parameters' => '&q=' . html_entity_decode($keywords, ENT_NOQUOTES, 'UTF-8'),
				'pid'        => $this->parentPlugin->getLinkTargetPageId()
			);
		}

		return $lastSearches;
	}

	/**
	 * Gets the last searched keywords from the user's session
	 *
	 * @return	array	An array containing the last searches of the current user
	 */
	protected function getLastSearchesFromSession() {
		$lastSearches = $GLOBALS['TSFE']->fe_user->getKey(
			'ses',
			$this->parentPlugin->prefixId . '_lastSearches'
		);

		if (!is_array($lastSearches)) {
			$lastSearches = array();
		}

		$lastSearches = array_reverse(array_unique($lastSearches));

		return $lastSearches;
	}

	/**
	 * Gets the last searched keywords from the database
	 *
	 * @return	array	An array containing the last searches of the current user
	 */
	protected function getLastSearchesFromDatabase($limit = FALSE) {
		$limit = $limit ? intval($limit) : FALSE;
		$lastSearchesRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'DISTINCT keywords',
			'tx_solr_last_searches',
			'',
			'',
			'tstamp DESC',
			$limit
		);

		$lastSearches = array();
		foreach ($lastSearchesRows as $row) {
			$lastSearches[] = $row['keywords'];
		}

		return $lastSearches;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/solr/PiResults/LastSearchesCommand.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/solr/PiResults/LastSearchesCommand.php']);
}

?>
