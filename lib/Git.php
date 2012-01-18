<?php
/**
 * Interact with Git
 */
class Git
{
	private static $blame_pattern = '/^(\S*)\s.*?\((.*?)\s(\d{4}-\d{2}-\d{2}).*?\s\d+\)\s(.*)$/';
	private $git;
	private $origin;

	/**
	 * @param git_dir The git directory of interest
	 * @param origin Upstream origin name
	 * @param bin The git executable
	 */
	public function __construct($dir = '.git', $origin = 'origin', Diesel $di = null)
	{
		$this->git = "git --git-dir=$dir";
		$this->origin = $origin;

		$di = $di ?: new Diesel();

		$this->shell = $di->create($this, 'Shell');
	}

	/**
	 * Configure dependency injection
	 * Called by Diesel
	 */
	public static function dieselify($me)
	{
		Diesel::register_global($me, 'Shell', function($params) {
			return new Shell();
		});
	}

	/**
	 * Get the list of files changed by a commit
	 */
	public function get_file_list($hash)
	{
		$files = shell_exec("{$this->git} show --pretty='format:' --name-only $hash");
		return explode(PHP_EOL, trim($files));
	}

	/**
	 * Get the list files staged for commit
	 */
	public function get_staged_files()
	{
		$files = shell_exec($this->git . ' diff-index --cached HEAD --name-only');
		return explode(PHP_EOL, trim($files));
	}

	/**
	 * @returns array('author', 'subject', 'message')
	 */
	public function get_pretty_email($hash)
	{
		$show = shell_exec($this->git . ' show -s --pretty="email" ' . $hash);
		$show_lines = explode(PHP_EOL, trim($show));

		list($trash, $author) = explode(': ', $show_lines[1]);
		list($trash, $subject) = explode('[PATCH] ', $show_lines[3]);

		$message_lines = array_slice($show_lines, 5);
		$message = implode(PHP_EOL, $message_lines);

		return array(
			'author' => $author,
			'subject' => $subject,
			'message' => $message,
		);
	}

	/**
	 * @returns 2d array for each line of git blame info of form
	 * ...array(commit, author, date, code)
	 */
	public function blame($file, $start, $end)
	{
		$blame_blob = shell_exec("{$this->git} blame -L $start,$end $file");
		$blame_lines = explode(PHP_EOL, trim($blame_blob));

		$blame_info = array();
		foreach ($blame_lines as $blame_line)
		{
			$matches = array();
			if (!preg_match(self::$blame_pattern, $blame_line, $matches))
			{
				continue;
			}

			$blame_info []= array(
				'commit' => $matches[1],
				'author' => $matches[2],
				'date' => $matches[3],
				'loc' => $matches[4],
			);
		}

		return $blame_info;
	}

	/**
	 * Does a fetch using $refs if available.
	 *
	 * @return {Boolean} Did the fetch succeed?
	 */
	public function fetch($refs = '')
	{
		if ($refs) $refs = escapeshellarg($refs);

		$cmd = trim("{$this->git} fetch {$this->origin} $refs");
		$this->shell->exec($cmd, $output, $exit_status);

		if ($exit_status !== 0)
		{
			throw new Git_Exception('Error in fetch: ' . print_r($output, true));
		}
	}

	/**
	 * Does a checkout on $branch with $options
	 *
	 * @return {Boolean} Did the checkout succeed?
	 */
	public function checkout($branch, $options = array())
	{
		$cmd = trim("{$this->git} checkout");
		foreach ($options as $o)
		{
			$cmd .= ' ' . escapeshellarg($o);
		}

		$branch = escapeshellarg($branch);
		$cmd .= " $branch";

		exec($cmd, $output, $exit_status);

		if ($exit_status !== 0)
		{
			throw new Git_Exception('Error in checkout: ' . print_r($output, true));
		}
	}

	/**
	 * Checkout branch and reset hard to upstream HEAD
	 */
	public function reset($branch, $hard = false)
	{
		if (!$branch)
		{
			throw new Git_Exception(
					"Error in Git reset: Must specify branch name for origin: $this->origin");
		}

		$branch = escapeshellarg($branch);
		$hard = $hard ? '--hard' : '';
		$reset_cmd = $this->chain_commands(array(
			"fetch $this->origin",
			'stash',
			"checkout -f $branch",
			"reset $hard {$this->origin}/$branch",
		));

		exec($reset_cmd, $output, $exit_status);

		if ($exit_status !== 0)
		{
			throw new Git_Exception("Error in reset $hard: " . print_r($output, true));
		}
	}

	/**
	 * Update server info
	 */
	public function update_server_info()
	{
		$cmd = "{$this->git} update-server-info -f";

		exec($cmd, $output, $exit_status);
		if ($exit_status !== 0)
		{
			throw new Git_Exception('Unable to update server info.'
				. "\nCmd: $cmd\nRet: $ret\nOutput: " . implode(PHP_EOL, $output));
		}
	}

	/*
	 * Prepend each command with :git
	 * and chain together with bash logical &&
	 */
	private function chain_commands(array $cmds)
	{
		return $this->git . ' ' . implode(" && {$this->git} ", $cmds);
	}
}

class Git_Exception extends Exception
{
}
