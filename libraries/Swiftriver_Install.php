<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Swiftriver plugin installer
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author     Ushahidi Team <team@ushahidi.com> 
 * @package    Ushahidi - https://github.com/ushahidi/Ushahidi_Web
 * @category   Libraries
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
 */
class Swiftriver_Install {

	/**
	 * Database table prefix
	 * @var string
	 */
	private $table_prefix;
	
	/**
	 * @var Database
	 */
	private $db;
	
	public function __construct()
	{
		$this->table_prefix = Kohana::config('database.default.table_prefix');
		$this->db = Database::instance();
	}
	
	/**
	 * Runs the SQL script to create the schema objects used
	 * by this plugin
	 */
	public function run_install()
	{
		$this->_execute_sql_script('swiftriver_install');
	}
	
	/**
	 * Runs the SQL script to drop the schema objects used by this plugin
	 */
	public function uninstall()
	{
		$this->_execute_sql_script('swiftriver_uninstall');
	}

	/**
	 * Loads and executes the SQL in the specified file. The file must be located
	 * in the sql/ directory of this plugin
	 * 
	 * @param string $filename Name of the file
	 */
	private function _execute_sql_script($filename)
	{
		// Load the SQL script
		if (($script = Kohana::find_file('sql', $filename, FALSE, 'sql')) !== FALSE)
		{
			// Get the contents of the file
			$script_ddl = file_get_contents(realpath($script));

			try
			{
				// Independently execute the DDL for each schema object
				foreach (explode(";", preg_replace('/\--\s.*/i', '', $script_ddl)) as $ddl)
				{
					$ddl = trim($ddl);
					if ( ! empty($ddl))
					{
						$this->db->query($ddl);
					}
				}

				Kohana::log('info', "SQL script successfully executed");
			}
			catch (Kohana_Database_Exception $e)
			{
				Kohana::log('error', $e->getMessage());
			}
		}
		else
		{
			Kohana::log('error', "The SQL script file could not be found.");
		}
		
	}
}