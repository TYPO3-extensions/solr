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
 * View helper for rendering gravatar images.
 * See http://www.gravatar.com
 *
 * = Examples =
 *
 * <code>
 * <blog:gravatar emailAddress="foo@bar.com" size="40" defaultImageUri="someDefaultImage" />
 * </code>
 *
 * <output>
 * <img src="http://www.gravatar.com/avatar/4a28b782cade3dbcd6e306fa4757849d?d=someDefaultImage&s=40" />
 * </output>
 *
 * @version $Id: GravatarViewHelper.php 1356 2009-09-23 21:22:38Z bwaidelich $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Solr_ViewHelpers_FacetDataViewHelper extends Tx_Solr_ViewHelpers_AbstractFacetViewHelper {

	/**
	 * Render the gravatar image
	 *
	 * @param string $name
	 * @param array $configuration
	 * @return string The rendered image tag
	 */
	public function render($name, $configuration) {
		$facetCounts = $this->search->getFacetCounts();
		$facetField = $configuration['field'];

		$facetData = array();
		if (get_object_vars($facetCounts->facet_fields->$facetField)) {
			$i = 0;
			foreach ($facetCounts->facet_fields->$facetField as $facetOption => $facetOptionResultCount) {
				if ($facetOption == '_empty_') {
						// TODO - for now we don't handle facet missing.
					continue;
				}
				
				
				$facetHidden = '';
				if (++$i > $this->settings['search']['faceting']['limit']) {
					$facetHidden = 'tx-solr-facet-hidden';
				}
	
				$facetData[] = array(
					'url' => $this->buildAddFacetUrl($name . ':' . $facetOption),
					'name' =>  $this->renderFacetOption($name, $facetOption),
					'count' => $facetOptionResultCount,
					'hiddenCssClass' => $facetHidden
				);
			}
			if ($i > $this->settings['search']['faceting']['limit']) {
				$this->addHeadJavascript();
			}
		}
		return $facetData;
	}
	
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
	protected function buildAddFacetUrl($facetToAdd) {
		$filterParameters = array();
		if ($this->controllerContext->getRequest()->hasArgument('filter')) {
			$filterParameters = explode(',', $this->controllerContext->getRequest()->getArgument('filter'));
		}

		$filterParameters[] = $facetToAdd;
		$filterParameters = array_unique($filterParameters);

		return $this->search->getQuery()->getQueryUrl(
			array('filter' => implode(',', $filterParameters))
		);
	}
}
?>