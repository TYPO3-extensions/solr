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
class Tx_Solr_ViewHelpers_UsedFacetsViewHelper extends Tx_Solr_ViewHelpers_AbstractFacetViewHelper {


	/**
	 * Render the gravatar image
	 *
	 * @return string The rendered image tag
	 */
	public function render() {
		$facetsInUse = array();
		
		if ($this->controllerContext->getRequest()->hasArgument('filter')) {
			
			$filterParameters = explode(',', $this->controllerContext->getRequest()->getArgument('filter'));
			foreach ($filterParameters as $filter) {
				list($filterName, $filterValue) = explode(':', $filter);
	
				$facetText = $this->renderFacetOption($filterName, $filterValue);
	
				$removeFacetText = strtr(
					$this->settings['search']['faceting']['removeFacetLinkText'],
					array(
						'@facetValue' => $filterValue,
						'@facetName'  => $filterName,
						'@facetText'  => $facetText
					)
				);

				$removeFacetUrl = $this->buildRemoveFacetUrl($this->search->getQuery(), $filter, $filterParameters);
	
				$facetToRemove = array(
					'removalUrl'  => $removeFacetUrl,
					'text' => $removeFacetText,
					'name' => $filterValue
				);
	
				$facetsInUse[] = $facetToRemove;
			}
		}
		return $facetsInUse;
	}
	
	protected function buildRemoveFacetUrl($query, $facetToRemove, $filterParameters) {
		$filterParameters = array_unique($filterParameters);
		$indexToRemove = array_search($facetToRemove, $filterParameters);

		if ($indexToRemove !== false) {
			unset($filterParameters[$indexToRemove]);
		}

		return $query->getQueryUrl(
			array('filter' => implode(',', $filterParameters))
		);
	}
}
?>