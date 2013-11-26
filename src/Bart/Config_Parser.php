<?php
namespace Bart;

/**
 * Load ini style files giving preference to current environment
 */
class Config_Parser
{
	private $environments;

	/**
	 * Create basic instance
	 * @param $environments Array of environments to which the parser should pay attention
	 */
	public function __construct($environments = array())
	{
		$this->environments = array(
			'' => 0, // The default environment
		);

		$this->set_envs($environments);
	}

	/**
	 * Indicate the desired environments
	 *
	 * @param $environments - List of environment set names in order of least importance
	 */
	private function set_envs(array $environments)
	{
		// Assign a priority to each environment
		// The last one is the most important
		foreach ($environments as $imp => $env)
		{
			if (trim($env))
			{
				$this->environments[trim($env)] = $imp + 1;
			}
		}
	}

	/**
	 * Load the configuration file and return the array of name-value pairs
	 */
	public function parse_conf_file($file_location)
	{
		$conf = parse_ini_file($file_location, 1);

		if (!$conf)
		{
			throw new \Exception('Error parsing configuration file at ' . $file_location);
		}

		// Will hold the final configuration, with respect to current environment
		$conf_final = array();

		// Break out each configuration section
		foreach ($conf as $section => $params)
		{
			$conf_final[$section] = array();

			// Break out each key-value pair
			// ...and percolate based on env priority
			foreach ($params as $param_name => $value)
			{
				// Use default env, unless specified otherwise
				$env = '';

				// Extract env if specified in param name
				// e.g. on<live> = no --> $param_name = 'on' and $env = 'live'
				$matches = array();
				if (preg_match('/^(.*)<([^>]*)>$/', $param_name, $matches))
				{
					list($param_name, $env) = array($matches[1], $matches[2]);
				}

				// Do we care about this env?
				if (!isset($this->environments[$env])) continue;

				// Is there already a value defined?
				if (isset($conf_final[$section][$param_name]))
				{
					// Overwrite the configuration value if the env priority
					// ...is higher the current value
					if ($this->environments[$env] > $conf_final[$section][$param_name]['priority'])
					{
						$conf_final[$section][$param_name]['value'] = $value;
						$conf_final[$section][$param_name]['priority'] = $this->environments[$env];
					}
				}
				else
				{
					$conf_final[$section][$param_name] = array(
						'value' => $value,
						'priority' => $this->environments[$env],
					);
				}
			}

			// Get rid of unused sections
			if (empty($conf_final[$section]))
			{
				unset($conf_final[$section]);
			}
		}

		// Prune out the priority values from the array
		foreach ($conf_final as $section => $param)
		{
			foreach ($conf_final[$section] as $param_name => $val_prio_pair)
			{
				// Overwrite the value-priority pair with just the value
				$conf_final[$section][$param_name] = $val_prio_pair['value'];
			}
		}

		// Expand variable references
		foreach ($conf_final as $section => $param)
		{
			foreach ($param as $p => $v)
			{
				$matches = array();
				while (preg_match('/<<([\w\d]+)>>/', $conf_final[$section][$p], $matches))
				{
					if (!array_key_exists($matches[1], $param))
					{
						throw new \Exception("Reference by key '{$p}' to undefined key '{$matches[1]}' in section '{$section}'.");
					}

					$conf_final[$section][$p] = preg_replace('/<<[\w\d]+>>/', $param[$matches[1]], $conf_final[$section][$p], 1);
				}
			}
		}

		return $conf_final;
	}
}
