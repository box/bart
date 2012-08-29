<?php
namespace Bart;

class ConfigResovlerTest extends BaseTestCase
{
	public function test_parseDefaults()
	{
		$resolver = new ConfigResolver();
		$configs = $resolver->resolve(array(
			'sectionName1' => array(
				'configName1' => array(
					'' => 'the default value',
					'env1' => 'The value for env1',
				),
			),
		));

		$this->assertEquals('the default value', $configs['sectionName1']['configName1'], 'Config Name 1');
	}

	public function test_resolveArrays()
	{
		$resolver = new ConfigResolver();
		$configs = $resolver->resolve(array(
			's' => array(
				'c' => array(
					'' => array('fullName' => 'john braynard')
				),
			),
		));

		$this->assertEquals('john braynard', $configs['s']['c']['fullName'], 'Full name');
	}

	public function test_resolveBackreferences()
	{
		$resolver = new ConfigResolver();
		$configs = $resolver->resolve(array(
			's' => array(
				'nameserver' => array(
					'' => 'ns.<<origin>>',
				),
				'origin' => array(
					'' => 'example.com.',
				),
			),
		));

		$this->assertEquals('ns.example.com.', $configs['s']['nameserver'], 'Name server');
	}

	public function test_multipleConfigSections()
	{
		$resolver = new ConfigResolver();
		$configs = $resolver->resolve(array(
			'dns' => array(
				'nameserver' => array(
					'' => 'ns.<<origin>>',
				),
				'origin' => array(
					'' => 'example.com.',
				),
			),
			'favorites' => array(
				'color' => array(
					'' => 'green',
				),
				'food' => array(
					'' => 'turkey',
				),
			),
		));

		$this->assertEquals('ns.example.com.', $configs['dns']['nameserver'], 'DNS Nameserver');
		$this->assertEquals('example.com.', $configs['dns']['origin'], 'DNS origin');
		$this->assertEquals('green', $configs['favorites']['color'], 'favorite color');
		$this->assertEquals('turkey', $configs['favorites']['food'], 'favorite food');
	}

	public function test_envOverrides()
	{
		// Override default with jbraynard, and override either with test
		$resolver = new ConfigResolver(array('jbraynard', 'test'));
		$configs = $resolver->resolve(array(
			'favorites' => array(
				'color' => array(
					'jbraynard' => 'pink',
				),
				'food' => array(
					'' => 'turkey',
					'test' => 'small portions',
					'jbraynard' => 'tofu',
				),
				'failure' => array(
					'jbraynard' => 'things on youtube',
					'test' => 'stack overflow',
				),
				'music' => array(
					'' => 'jazz',
				)
			),
		));

		$favs = $configs['favorites'];
		$this->assertEquals('pink', $favs['color'], 'color');
		$this->assertEquals('small portions', $favs['food'], 'food');
		$this->assertEquals('stack overflow', $favs['failure'], 'failure');
		$this->assertEquals('jazz', $favs['music'], 'music');
	}
}
