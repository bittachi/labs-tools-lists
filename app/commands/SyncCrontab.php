<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @author Fabio Alessandro Locati <fabiolocati@gmail.com>
 * @copyright Fabio Alessandro Locati 2013
 * @license AGPL-3.0 http://www.gnu.org/licenses/agpl-3.0.html
 */
class SyncCrontab extends Command {

	/**
	 * The console command name.
	 */
	protected $name = 'crontab:sync';

	/**
	 * The console command description.
	 */
	protected $description = 'Sync db with cnf file';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$directory = base_path() . "/query/";
		$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
		$files = array();
		while($it->valid()) {
		    if (!$it->isDot() AND preg_match("/.*\.cnf/i", $it->getSubPathName()))
		        $files[] = substr($it->getSubPathName(), 0, -4);
		    $it->next();
		}

		foreach ($files as $file) {
			$filepath = base_path() . "/query/" . $file;
			$query = file_get_contents($filepath . ".sql");
			$config = parse_ini_file($filepath . ".cnf");
			$fullconfig = parse_ini_file($filepath . ".cnf", TRUE);
			if (array_key_exists('query', $fullconfig)
				OR array_key_exists('mysql', $fullconfig))
				$kind = 'mysql';
			if (array_key_exists('python', $fullconfig))
				$kind = 'python';
			if ($config['frequency'] == "default")
				$frequency = "daily";
			else
				$frequency = $config['frequency'];
			if (!DB::table('queries')->where('name', $file)->count())
				$obj = Query::create(array(
					'name' => $file,
					'frequency' => $frequency,
					'kind' => $kind
				));
			else
				if (DB::table('queries')->where('name', $file)->pluck('frequency') != $frequency)
					DB::table('queries')->where('name', $file)->update(array('frequency' => $frequency));
				if (DB::table('queries')->where('name', $file)->pluck('kind') != $kind)
					DB::table('queries')->where('name', $file)->update(array('kind' => $kind));
		}
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array();
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array();
	}

}
