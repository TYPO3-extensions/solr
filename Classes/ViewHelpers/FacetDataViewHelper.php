<?php
/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * View Helper to fetch facet data from the current search.
 *
 * @version $Id: GravatarViewHelper.php 1356 2009-09-23 21:22:38Z bwaidelich $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Solr_ViewHelpers_FacetDataViewHelper extends Tx_Solr_ViewHelpers_AbstractFacetViewHelper {

	/**
	 * View Helper to fetch facet data from the current search.
	 *
	 * Builds up two arrays whose names are specified in $facetOptionArrayName and
	 * $facetRootlineArrayName.
	 *
	 * $facetOptionArrayName is an array which consists of the following elements:
	 * array(
	 *   'url' => // URL of the link to to add the query restriction
	 *   'name' => // Name of the option
	 *   'count' => // count of the option
	 *   'hiddenCssClass' => is either empty or 'tx-solr-facet-hidden' in case more than settings.search.faceting.limit facet options are needed.
	 * )
	 *
	 * $facetRootlineArrayName is an array which consists of the following elements:
	 * array(
	 *   'url' => // URL of the link to to add the query restriction
	 *   'name' => // Name of the option
	 * )
	 * It consists of the _rootline_ in case of a hierarchy, and links to jump back to upper levels.
	 * 
	 *
	 * @param string $name
	 * @param array $configuration
	 * @param string $facetOptionArrayName
	 * @param string $facetRootlineArrayName
	 * @return string The rendered image tag
	 */
	public function render($name, $configuration, $facetOptionArrayName, $facetRootlineArrayName) {
		$facetCounts = $this->search->getFacetCounts();
		$facetField = $configuration['field'];

		$facetOptions = array();
		if (get_object_vars($facetCounts->facet_fields->$facetField)) {
			$i = 0;
			foreach ($facetCounts->facet_fields->$facetField as $facetOption => $facetOptionResultCount) {
				if ($facetOption == '_empty_') {
						// TODO - for now we don't handle facet missing.
					continue;
				}

				if ($configuration['hierarchical'] == 1) {
					list(, $facetOption) = explode('-', $facetOption);
				}
				
				$facetHidden = '';
				if (++$i > $this->settings['search']['faceting']['limit']) {
					$facetHidden = 'tx-solr-facet-hidden';
				}
	
				$facetOptions[] = array(
					'url' => $this->buildAddFacetUrl($name, $facetOption),
					'name' =>  $this->renderFacetOption($name, $facetOption),
					'count' => $facetOptionResultCount,
					'hiddenCssClass' => $facetHidden
				);
			}
			if ($i > $this->settings['search']['faceting']['limit']) {
				$this->addHeadJavascript();
			}
		}

		$facetRootline = $this->getFacetRootline($name, $configuration);
		$this->templateVariableContainer->add($facetOptionArrayName, $facetOptions);
		$this->templateVariableContainer->add($facetRootlineArrayName, $facetRootline);
		$output = $this->renderChildren();
		$this->templateVariableContainer->remove($facetOptionArrayName);
		$this->templateVariableContainer->remove($facetRootlineArrayName);
		return $output;
	}

	/**
	 * Get the rootline for the current facet which should be rendered.
	 *
	 * @param string $facetName
	 * @param array $configuration
	 * @return array
	 */
	protected function getFacetRootline($facetName, array $configuration) {
		$facetRootline = array();

		if ($this->controllerContext->getRequest()->hasArgument('facetSelection')) {
			$facetSelection = $this->controllerContext->getRequest()->getArgument('facetSelection');
			if (isset($facetSelection[$facetName])) {
				$facetRootline[] = array(
					'url' => $this->buildAddFacetUrl($facetName, NULL),
					'name' => 'Remove'
				);
				if ($configuration['hierarchical'] == 1) {
					$rootline = explode('/', $facetSelection[$facetName]);
					for ($i=1; $i <= count($rootline); $i++) {
						$facetOption = implode('/', array_slice($rootline, 0, $i));
						$facetRootline[] = array(
							'url' => $this->buildAddFacetUrl($facetName, $facetOption),
							'name' =>  $this->renderFacetOption($facetName, $facetOption),
						);
					}
					
				}
			}
		}
		return $facetRootline;
	}

	/**
	 * Add JS to expand/collapse facets in the header.
	 * @todo: Localization
	 */
	protected function addHeadJavascript() {
		$jsFilePath = t3lib_extMgm::siteRelPath('solr') . 'Resources/Public/JavaScript';
		 // $this->parentPlugin->pi_getLL('faceting_showMore')
		  // $this->parentPlugin->pi_getLL('faceting_showFewer')
		$this->controllerContext->getResponse()->addAdditionalHeaderData('
			<script type="text/javascript">
			/*<![CDATA[*/

			var tx_solr_facetLabels = {
				\'showMore\' : \'' . 'Show more' . '\',
				\'showFewer\' : \'' . 'Show fewer' . '\'
			};

			/*]]>*/
			</script>
			');

		if ($this->settings['addDefaultJs']) {
			$this->controllerContext->getResponse()->addAdditionalHeaderData(
				'<script type="text/javascript" src="' . $jsFilePath . '/jquery-1.3.2.min.js"></script>' .
				'<script type="text/javascript" src="' . $jsFilePath . '/results.js"></script>');
		}
	}

	/**
	 * Builds a URL to add a facet.
	 * If NULL, removes the facet from the link.
	 *
	 * @param string $name
	 * @param string $facetOption
	 * @return string
	 */
	protected function buildAddFacetUrl($name, $facetOption) {
		$facetSelection = array();
		if ($this->controllerContext->getRequest()->hasArgument('facetSelection')) {
			$facetSelection = $this->controllerContext->getRequest()->getArgument('facetSelection');
		}
		if ($facetOption === NULL) {
			unset($facetSelection[$name]);
		} else {
			$facetSelection[$name] = $facetOption;
		}
		return $this->search->getQuery()->getQueryUrl(
			array('facetSelection' => $facetSelection)
		);
	}
}
?>