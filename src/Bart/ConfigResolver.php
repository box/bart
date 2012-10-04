<?php
namespace Bart;

/**
 * Resolves configurations from DSL
 */
class ConfigResolver
{
	/** @var array Specificity of each environment */
	private $envSpecificity;

	/**
	 * @param array $envs Ordered list of active environments. From least to most specific.
	 */
	public function __construct(array $envs = array())
	{
		// The default empty environment is the least specific to active environment,
		// ...but in some cases, it is also the most specific (i.e. the only env)
		$this->envSpecificity = array('' => 0);

		$this->resolveEnvPriroties($envs);
	}

	/**
	 * Configure the respective specificity of each environment
	 */
	private function resolveEnvPriroties(array $envs)
	{
		// Assign a specificity to each environment
		// The last one is the most specific
		foreach ($envs as $imp => $env) {
			if (trim($env)) {
				$this->envSpecificity[trim($env)] = $imp + 1;
			}
		}
	}

	/**
	 * Resolve active configurations based on active environment
	 * @param array $configDsl Section => configName => env => value configuration values
	 * @return array Section => configName => value configuration values
	 */
	public function resolve(array $configDsl)
	{
		// Will hold the final configuration, with respect to active environment
		$final = array();

		// Break out each configuration section
		foreach ($configDsl as $sectionName => $namedGroups) {
			$final[$sectionName] = array();

			// Break out each key-value pair
			// ...and percolate based on env specificity
			foreach ($namedGroups as $configName => $envValues) {
				foreach ($envValues as $envName => $configValue) {
					$final = $this->updateFinalArray($final, $sectionName, $configName, $envName, $configValue);
				}
			}

			// No config values for active environment
			if (empty($final[$sectionName])) {
				unset($final[$sectionName]);
			}
		}

		$final = $this->extractIntoKeyValues($final);

		return $this->resolveReferences($final);
	}

	/**
	 * Update $final array with $configValue if $envName is more specific than current value
	 * @param array $final
	 * @param string $sectionName
	 * @param string $configName
	 * @param string $envName
	 * @param string $configValue
	 */
	private function updateFinalArray(array $final, $sectionName, $configName, $envName, $configValue)
	{
		// Do we care about this env?
		if (!isset($this->envSpecificity[$envName])) return $final;

		// Is there already a value defined?
		if (isset($final[$sectionName][$configName])) {
			// Is new value more specific to active environment?
			if ($this->envSpecificity[$envName] > $final[$sectionName][$configName]['specificity']) {
				$final[$sectionName][$configName]['value'] = $configValue;
				$final[$sectionName][$configName]['specificity'] = $this->envSpecificity[$envName];
			}
		}
		else {
			$final[$sectionName][$configName] = array(
				'value' => $configValue,
				'specificity' => $this->envSpecificity[$envName],
			);
		}

		return $final;
	}

	/**
	 * Remove the specificity values from $final array
	 * @param array $final
	 */
	private function extractIntoKeyValues(array $final)
	{
		// Prune out the specificity values from the array
		foreach ($final as $sectionName => $namedGroup) {
			foreach ($namedGroup as $configName => $valueAndSpecifity) {
				$final[$sectionName][$configName] = $valueAndSpecifity['value'];
			}
		}

		return $final;
	}

	/**
	 * Expand any back references to other section configs to their values
	 * @param array $final
	 */
	private function resolveReferences($final)
	{
		foreach ($final as $sectionName => $nameValues) {
			foreach ($nameValues as $configName => $originalValue) {
				// So, if you want to use an array in your conf, then you can't use back references
				if (is_array($originalValue)) continue;

				$matches = array();
				while (preg_match('/<<([\w\d]+)>>/', $final[$sectionName][$configName], $matches)
					&& $nameValues[$matches[1]])
				{
					$final[$sectionName][$configName] =
						preg_replace(
							'/<<[\w\d]+>>/',
							$nameValues[$matches[1]],
							$final[$sectionName][$configName],
							1);
				}
			}
		}

		return $final;
	}
}
