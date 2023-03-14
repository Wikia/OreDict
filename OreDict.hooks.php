<?php

use MediaWiki\Hook\EditPage__showEditForm_initialHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

/**
 * OreDict hooks file
 * Defines entry points to the extension
 *
 * @file
 * @ingroup Extensions
 * @version 1.0.1
 * @author Jinbobo <paullee05149745@gmail.com>
 * @license
 */

class OreDictHooks implements
	ParserFirstCallInitHook,
	EditPage__showEditForm_initialHook,
	LoadExtensionSchemaUpdatesHook
{

	/**
	 * @param DatabaseUpdater $updater
	 * @return bool
	 */
	public function onLoadExtensionSchemaUpdates( $updater ): bool {
		$extDir = __DIR__;
		$updater->addExtensionUpdate(['addTable', 'ext_oredict_items', "{$extDir}/install/sql/ext_oredict_items.sql", true]);
		$updater->addExtensionUpdate(['dropField', 'ext_oredict_items', 'flags', "{$extDir}/upgrade/sql/remove_flags.sql", true]);
		return true;
	}

	/**
	 * Entry point for parser functions.
	 *
	 * @param Parser $parser
	 * @return bool
	 */
	public function onParserFirstCallInit( $parser ): bool {
		$parser->setFunctionHook('dict', 'OreDictHooks::RenderParser');
		$parser->setFunctionHook('grid_foreach', 'OreDictHooks::RenderMultiple');
		return true;
	}

	/**
	 * Generate grids from a string.
	 *
	 * @param Parser $parser
	 * @return array|string
	 */
	public static function RenderMultiple(Parser &$parser): array|string {
		$opts = array();
		for ($i = 1; $i < func_num_args(); $i++) {
			$opts[] = func_get_arg($i);
		}

		// Check if input is in the correct format
		foreach ($opts as $opt) {
			if ( str_contains( "{{", $opt ) || str_contains( "}}", $opt ) ) {
				OreDictError::error(wfMessage('oredict-grid_foreach-format-error')->text());
				return "";
			}
		}

		// Check if separated by commas
		if ( str_contains( $opts[0], ',' ) ) {
			$opts = explode(',', $opts[0]);
		}

		// Check for global parameters
		$gParams = array();
		foreach ($opts as $option) {
			$pair = explode('=>', $option);
			if (count($pair) == 2) {
				$gParams[trim($pair[0])] = trim($pair[1]);
			}
		}

		// Prepare items
		$items = array();
		foreach ($opts as $option) {
			if ( !str_contains( $option, '=>' ) ) {
				// Pre-load global params
				$items[] = $gParams;
				end($items);
				$iKey = key($items);

				// Parse string
				$gridOptions = explode('!', $option);
				foreach ($gridOptions as $key => $gridOption) {
					$pair = explode('=', $gridOption);
					if (count($pair) == 2) {
						$gridOptions[trim($pair[0])] = trim($pair[1]);
						$items[$iKey][trim($pair[0])] = trim($pair[1]);
					} else {
						$items[$iKey][$key + 1] = trim($gridOption);
					}
				}
			}
		}

		// Create grids
		$outs = array();
		foreach ($items as $options) {
			// Set mod
			$mod = '';
			if (isset($options['mod'])) {
				$mod = $options['mod'];
			}

			// Call OreDict
			$dict = new OreDict($options[1], $mod);
			$dict->exec(isset($options['tag']), isset($options['no-fallback']));
			$outs[] = $dict->runHooks(self::BuildParamString($options));
		}

		$ret = "";
		foreach ($outs as $out) {
			if (!isset($out[0])) {
				continue;
			}
			$ret .= $out[0];
		}

		// Return output
		return array($ret, 'noparse' => false, 'isHTML' => false);
	}

	/**
	 * Query OreDict and return output.
	 *
	 * @param Parser $parser
	 * @return array
	 */
	public static function RenderParser(Parser &$parser): array {
		$opts = array();
		for ($i = 1; $i < func_num_args(); $i++) {
			$opts[] = func_get_arg($i);
		}
		$options = OreDictHooks::ExtractOptions($opts);

		// Set mod
		$mod = '';
		if (isset($options['mod'])) {
			$mod = $options['mod'];
		}
		// Call OreDict
		$dict = new OreDict($options[1], $mod);
		$dict->exec(isset($options['tag']), isset($options['no-fallback']));
		return $dict->runHooks(self::BuildParamString($options));
	}

	/**
	 * Helper function to extract options from raw parser function input.
	 *
	 * @param array $opts
	 * @return array|bool
	 */
	public static function ExtractOptions( array $opts): bool|array {
		if (count($opts) == 0) return array();
		foreach ($opts as $key => $option) {
			$pair = explode('=', $option);
			if (count($pair) == 2) {
				$name = trim($pair[0]);
				$value = trim($pair[1]);
				$results[$name] = $value;
			} else {
				$results[$key + 1] = trim($option);
			}
		}

		return $results ?? false;
	}

	/**
	 * Helper function to split a parameter string into an array.
	 *
	 * @param string $params
	 * @return array
	 */
	public static function ParseParamString( string $params ): bool|array {
		if ($params === "") {
			return [];
		}
		return OreDictHooks::ExtractOptions(explode('|', $params));
	}

	/**
	 * Helper function to rebuild a parameter string from an array.
	 *
	 * @param array $params
	 * @return string
	 */

	public static function BuildParamString( array $params ): string {
		foreach ($params as $key => $value) {
			$pairs[] = "$key=$value";
		}
		if (!isset($pairs)) {
			return "";
		}
		return implode("|", $pairs);
	}

	/**
	 * Entry point for the EditPage::showEditForm:initial hook, allows the oredict extension to modify the edit form. Displays errors on preview.
	 *
	 * @param EditPage $editor
	 * @param OutputPage $out
	 * @return bool
	 */
	public function onEditPage__showEditForm_initial( $editor, $out ): bool {
		global $wgOreDictDebug;

		// Output errors
		$errors = new OreDictError( $wgOreDictDebug );
		$editor->editFormTextAfterWarn .= $errors->output();

		return true;
	}
}
