<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @author Fabio Alessandro Locati <fabiolocati@gmail.com>
 * @copyright Fabio Alessandro Locati 2013
 * @license AGPL-3.0 http://www.gnu.org/licenses/agpl-3.0.html
 */
class ExecCrontab extends Command {

	/**
	 * The console command name.
	 */
	protected $name = 'crontab:exec';

	/**
	 * The console command description.
	 */
	protected $description = 'Perform a crontabbed query';

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
		$file = $this->argument('query');
		$filepath = base_path() . "/query/" . $file;
		$query = file_get_contents($filepath . ".sql");
		$config = parse_ini_file($filepath . ".cnf");
		if (is_object(Query::where('name', $file)->first()))
			$db = Query::where('name', $file)->get()->first();
		else {
			$this->error('Query not present in db');
			return 1;
		}
		if (!is_dir(base_path() . "/output/" . $file))
			if (!mkdir(base_path() . "/output/" . $file, 0777, TRUE)) {
				$this->error('Impossible to create the output folder');
				return 1;
			}
		if ($db->kind == 'mysql')
			return mysql($file);
	}

	public function mysql($file)
	{
		$db = Query::where('name', $file)->get()->first();
		$date = date('Y-m-d H:i:s');
		$outpath = base_path() . "/output/" . $file . "/" . Execution::getSafeDate($date);
		$c = "mysql --defaults-file=~/replica.my.cnf -h {$config['project']}.analytics.db.svc.eqiad.wmflabs -BN ";
		$c.= "< {$filepath}.sql > {$outpath}.out";
		$before = microtime(true);
		$output = shell_exec($c);
		$after = microtime(true);
		$time = round(($after - $before) * 1000);
		$lines = explode(' ',trim(shell_exec("wc -l {$outpath}.out")));
		$c = "mysql --defaults-file=~/replica.my.cnf -h tools.db.svc.eqiad.wmflabs -e \"insert into executions (query_id, time, duration, results) values ($db->id, '$date', $time, $lines[0])\" s51223_db";
		shell_exec($c);
		$c = "mysql --defaults-file=~/replica.my.cnf -h tools.db.svc.eqiad.wmflabs -e \"update queries set times = times + 1, last_execution_at = '$date', updated_at = '$date', last_execution_results = $lines[0] where id = $db->id\" s51223_db";
		shell_exec($c);
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('query', InputArgument::REQUIRED, 'Query full path'),
		);
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
