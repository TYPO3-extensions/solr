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
class Tx_Solr_ViewHelpers_OutputViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	protected function getSearch() {
		return $this->templateVariableContainer->get('search');
	}
	/**
	 * Render the gravatar image
	 *
	 * @param mixed $document
	 * @return string The rendered image tag
	 */
	public function render($document) {
		$variableName = $this->renderChildren();
		$processedField = $this->processDocumentField($document, $variableName);
		
			// TODO check whether highlighting is enabled in TS at all
			// TODO: Bug. If you post-process this with processDocumentField, you get a problem.
		if ($variableName === 'content') {
			$highlightedContent = $this->getSearch()->getHighlightedContent();
			if ($highlightedContent->{$document->id}->content[0]) {
				$processedField = $this->utf8Decode(
					$highlightedContent->{$resultDocument->id}->content[0]
				);
			}
		}
		// TODO add a hook to further modify the search result document

			//$resultDocuments[] = $this->renderDocumentFields($temporaryResult);
		
		return $processedField;

	}
	
		/**
	 * takes a search result document and processes its fields according to the
	 * instructions configured in TS. Currently available instructions are
	 * 	* timestamp - converts a date field into a unix timestamp
	 * 	* utf8Decode - decodes utf8
	 * 	* skip - skips the whole field so that it is not available in the result, usefull for the spell field f.e.
	 * The default is to do nothing and just add the document's field to the
	 * resulting array.
	 *
	 * @param	Apache_Solr_Document	$document the Apache_Solr_Document result document
	 * @return	array	An array with field values processed like defined in TS
	 */
	protected function processDocumentField(Apache_Solr_Document $document, $fieldName) {
		$processingInstructions = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_solr.']['settings.']['search.']['results.']['fieldProcessingInstructions.'];

		if (isset($processingInstructions[$fieldName])) {
			// TODO allow to have multiple (commaseparated) instructions for each field
			switch ($processingInstructions[$fieldName]) {
				case 'timestamp':
					$parsedTime = strptime($document->{$fieldName}, '%Y-%m-%dT%TZ');
					$processedFieldValue = mktime(
						$parsedTime['tm_hour'],
						$parsedTime['tm_min'],
						$parsedTime['tm_sec'],
						$parsedTime['tm_mon'],
						$parsedTime['tm_mday'],
						$parsedTime['tm_year']
					);
					break;
				case 'utf8Decode':
					$processedFieldValue = $this->utf8Decode($document->{$fieldName});
					break;
				case 'skip':
					$processedFieldValue = '';
				default:
					$processedFieldValue = $document->{$fieldName};
			}
		} else {
			$processedFieldValue = $document->{$fieldName};
		}

		return $processedFieldValue;
	}

	/* TODO */
	protected function renderDocumentFields(array $document) {
		$renderingInstructions = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_solr.']['search.']['results.']['fieldRenderingInstructions.'];
		$cObj = t3lib_div::makeInstance('tslib_cObj');
		$cObj->start($document);

		foreach ($renderingInstructions as $renderingInstructionName => $renderingInstruction) {
			if (!is_array($renderingInstruction)) {
				$renderedField = $cObj->cObjGetSingle(
					$renderingInstructions[$renderingInstructionName],
					$renderingInstructions[$renderingInstructionName . '.']
				);

				$document[$renderingInstructionName] = $renderedField;
			}
		}

		return $document;
	}
	
	protected function utf8Decode($string) {
		if ($GLOBALS['TSFE']->metaCharset !== 'utf-8') {
			$string = $GLOBALS['TSFE']->csConvObj->utf8_decode($string, $GLOBALS['TSFE']->renderCharset);
		}

		return $string;
	}
}


?>
