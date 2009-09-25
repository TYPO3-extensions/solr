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
class Tx_Solr_ViewHelpers_AbstractFacetViewHelper extends Tx_Solr_ViewHelpers_AbstractViewHelper {

	/**
	 * Renders a single facet option according to the rendering instructions
	 * that may be given.
	 *
	 * @param	string	The facet this option belongs to, used to determine the rendering instructions
	 * @param	string	The facet option's raw string value.
	 * @return	string	The facet option rendered according to rendering instructions if available
	 */
	protected function renderFacetOption($facetName, $facetOption) {
		$renderedFacetOption = $facetOption;

		if (isset($this->settings['search']['faceting']['facets'][$facetName]['renderingInstruction'])) {
			$facetConfiguration = Tx_Extbase_Utility_TypoScript::convertPlainArrayToTypoScriptArray($this->settings['search']['faceting']['facets'][$facetName]);
			$cObj = t3lib_div::makeInstance('tslib_cObj');
			$cObj->start(array('optionValue' => $facetOption));
			$renderedFacetOption = $cObj->cObjGetSingle(
				$facetConfiguration['renderingInstruction'],
				$facetConfiguration['renderingInstruction.']
			);
		}
		return $renderedFacetOption;
	}
}


?>
