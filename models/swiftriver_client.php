<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Model for the swiftriver_clients table
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author     Ushahidi Team <team@ushahidi.com> 
 * @package    Ushahidi - https://github.com/ushahidi/Ushahidi_Web
 * @category   Models
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
 */
class Swiftriver_Client_Model extends ORM {

	/**
	 * "Belongs to" relationship definition
	 * @var array
	 */
	protected $belongs_to = array('user');
	
	/**
	 * One-to-many relationship definition
	 * @var array
	 */
	protected $has_many = array(
		'drops' => 'swifttiver_client_drops'
	);

	/**
	 * Database table name
	 * @var string
	 */
	protected $table_name = 'swiftriver_clients';
	
	/**
	 * Overrides the default save behaviour
	 */
	public function save()
	{
		if ( ! $this->loaded)
		{
			$this->client_id = text::random('alnum', 25);
			$this->client_secret = hash_hmac("sha256", text::random('alnum', 40), text::random('alnum', 20));
		}

		return parent::save();
	}

	/**
	 * Retrieves a client record using the client_id field
	 *
	 * @param   string  $client_id ID of the client
	 * @return  string The client secret if client exists, FALSE otherwise
	 */
	public static function get_by_client_id($client_id)
	{
		$client_orm = ORM::factory('swiftriver_client')
		    ->where('client_id', $client_id)
		    ->find();

		return $client_orm->loaded ? $client_orm : FALSE;		
	}

	/**
	 * Creates reports from drops
	 *
	 * @param  Swiftriver_Client_Model $client_orm Client record associated with the drops
	 * @param  array  $drops Drops to be used for creating reports
	 * @return bool
	 */
	public static function create_reports($client_orm, $drops)
	{
		// DB instance to run queries
		$db = Database::instance();

		// Get drop and location hashes - to be used for duplicity checks
		$drop_keys = array();
		$locations = array();

		foreach ($drops as $drop)
		{
			// If drop has no 'place_hash' property skip
			if ( ! array_key_exists('place_hash', $drop))
				continue;

			$drop_keys[$drop['droplet_hash']] = $drop;

			// Get the locations
			if ( ! array_key_exists($drop['place_hash'], $locations))
			{
				$locations[$drop['place_hash']] = array(
					'location_name' => $drop['place_name'],
					'latitude' => $drop['latitude'],
					'longitude' => $drop['longitude']
				);
			}
		}

		Kohana::log("info", sprintf("Found %d unique locations in the drop payload", count($locations)));

		// Get the drop hashes
		$drop_hashes = sprintf("(%s)", implode(",", self::quote_str(array_keys($drop_keys))));

		// Check for drops that already exist as incidents
		$existing_drops = array();
		foreach ($db->getwhere('swiftriver_drop_incident', array('drop_hash IN '.$drop_hashes)) as $drop)
		{
			$existing_drops[] = $drop->drop_hash;
		}

		// Log
		Kohana::log("debug", sprintf("%d existing drops found", count($existing_drops)));

		//
		// Obtain database lock
		//
		self::acquire_mutex();
		
		Kohana::log("debug", "Acquired database lock");
		
		// Increase the veracity for drops that already exist as incidents
		if (count($existing_drops))
		{
			Kohana::log("info", "Increasing the veracity of existing drops");

			$query = "UPDATE `swiftriver_drop_incident` SET `veracity` = `veracity` + 1 WHERE drop_hash IN (%s)";
			$db->query(sprintf($query, implode(",", self::quote_str($existing_drops))));
		}

		// Get the hashes for the new drops
		$new_drop_keys = array_diff(array_keys($drop_keys), $existing_drops);
		
		// When no new drops are found, abort
		if ( ! count($new_drop_keys))
		{
			Kohana::log("info", "No new drops found. Aborting.");
			self::release_mutex();

			return TRUE;
		}

		Kohana::log("info", sprintf("Found %d new drops", count($new_drop_keys)));
		
		// Get the locations that already exist
		$existing_locations = array();
		$location_hashes = implode(",", self::quote_str(array_keys($locations)));
		try
		{
			foreach ($db->getwhere('location', array('location_hash IN ('.$location_hashes.')')) as $existing)
			{
				$existing_locations[$existing->location_hash] = $existing->id;
			}
		}
		catch (Kohana_Database_Exception $e)
		{
			Kohana::log("error", $e->getMessage());
			return FALSE;
		}

		// Get the new locations
		$new_locations = array_diff(array_keys($locations), array_keys($existing_locations));
		Kohana::log("info", sprintf("Found %d new locations", count($new_locations)));

		// -----------------------------------------------------
		// Create the locations for the entries in $new_locations
		
		// Allocate a bunch of location ids to be used for the insert
		if (count($new_locations))
		{
			$location_ids =  self::get_ids('location', count($new_locations));

			// Build the values list for each of the locations
			$new_locations_hash_map = array();
			$locations_value_list = array();
			foreach ($location_ids as $id)
			{
				$hash = array_shift($new_locations);
				$new_locations_hash_map[$hash] = $id;
			
				$entry = $locations[$hash];
				$locations_value_list[] = sprintf("(%d, '%s', %s, %s, %s, '%s', '%s')", 
				    $id, $db->escape_str($entry['location_name']),
				    $entry['latitude'], $entry['longitude'], 1, gmdate("Y-m-d H:i:s"), $hash);
			}

			// Query template for creating the locations
			$location_query = "INSERT INTO `location` "
			    . "(`id`, `location_name`, `latitude`, `longitude`, `location_visible`, `location_date`, `location_hash`) "
			    . "VALUES %s";

			// Create the new locations
			$db->query(sprintf($location_query, implode(",", $locations_value_list)));
			
			// Add the newly created locations to the list of existing locations
			$existing_locations = array_merge($existing_locations, $new_locations_hash_map);
		}
	
	
		// ------------------------------------------------------
		// Create the incidents for the entries in $new_drop_keys
		
		// Allocate ids for the incidents
		$incident_ids = self::get_ids('incident', count($new_drop_keys));

		// Build the VALUES list for each of the drops
		$incidents_value_list = array();
		$new_drops_hash_map = array();
		$incident_category_value_list = array();

		foreach ($incident_ids as $incident_id)
		{
			$drop_hash = array_shift($new_drop_keys);
			$new_drops_hash_map[$drop_hash] = $incident_id;

			$drop = $drop_keys[$drop_hash];
			$location_id = $existing_locations[$drop['place_hash']];
			$user_id = $client_orm->user_id;

			// Incident values
			$entry = sprintf("(%d, %d, %d, '%s', '%s', '%s', %d, %d, '%s', '%s')",
			    $incident_id, $location_id, $user_id, $db->escape_str($drop['droplet_title']),
			    $db->escape_str($drop['droplet_content']), $drop['droplet_date_add'], 1, 0,
			    date('Y-m-d H:i:s'), gmdate('Y-m-d H:i:s')
			);

			$incidents_value_list[] = $entry;
			
			// Incident category values
			$incident_category_value_list[] = sprintf("(%d, %d)", $incident_id, $drop['category_id']);
		}

		// Log
		Kohana::log("info", "Creating incidents from the submitted drops");

		try
		{
			// Template query for creating the incidents
			$incidents_query = "INSERT INTO `incident` "
			    . "(`id`, `location_id`, `user_id`, `incident_title`, `incident_description`,"
			    . "`incident_date`, `incident_active`, `incident_verified`, `incident_dateadd`, "
			    . "`incident_dateadd_gmt`) "
			    . "VALUES %s";

			// Execute the query, providing the values to be inserted
			$db->query(sprintf($incidents_query, implode(",", $incidents_value_list)));
		}
		catch (Kohana_Database_Exception $e)
		{
			Kohana::log("error", $e->getMessage());
			return FALSE;
		}

		Kohana::log("info", "Assigning categories to the newly created incidents");

		try
		{
			// Create incident_category entries
			$incident_category_query = "INSERT INTO `incident_category` (`incident_id`, `category_id`) VALUES %s";
			$db->query(sprintf($incident_category_query, implode(",", $incident_category_value_list)));
		}
		catch (Kohana_Database_Exception $e)
		{
			Kohana::log("error", $e->getMessage());
			return FALSE;
		}

		// ----------------------------------
		// Update incident <---> drop mapping

		$incident_drop_values_map = array();
		foreach ($new_drops_hash_map as $hash => $incident_id)
		{
			$drop = $drop_keys[$hash];
			$metadata = array(
			    'places' => $drop['places'],
			    'tags' => $drop['tags'],
			    'links' => $drop['links'],
			    'media' => $drop['media']
			);
			$incident_drop_values_map[] = sprintf("('%s', %d, '%s')", $hash, $incident_id, json_encode($metadata));
		}

		// Log
		Kohana::log("info", "Mapping the incidents to the drops used to create them");

		try
		{
			// Template query for creating the incident-drop mapping
			$incident_drop_map_query = "INSERT INTO `swiftriver_drop_incident` "
			    . "(`drop_hash`, `incident_id`, `metadata`) VALUES %s";

			$db->query(sprintf($incident_drop_map_query, implode(",", $incident_drop_values_map)));
		}
		catch (Kohana_Database_Exception $e)
		{
			Kohana::log("error", $e->getMessage());
			return FALSE;
		}

		// -------------------------------------------------
		// Update list of drops for the client in $client_id

		$client_drops = ORM::factory('swiftriver_client_drops')
		   ->where('swiftriver_client_id', $client_orm->id)
		   ->where('drop_hash IN '.$drop_hashes)
		   ->find_all();

		$existing_client_drops = array();
		foreach ($client_drops as $drop)
		{
			$existing_client_drops[] = $drop->drop_hash;
		}

		// Get the new client drops
		$new_client_drops= array_diff(array_keys($drop_keys), $existing_client_drops);
		$client_drops_list = array();
		foreach ($new_client_drops as $hash)
		{
			$client_drops_list[] = sprintf("(%d, '%s')", $client_orm->id, $hash);
		}

		Kohana::log("info", sprintf("Updating the drop log for client %d", $client_orm->id));

		try
		{
			// Template query for creating entries in swiftriver_client_drops
			$client_drops_query = "INSERT INTO `swiftriver_client_drops` (`swiftriver_client_id`, `drop_hash`) VALUES %s";

			// Execute the query
			$db->query(sprintf($client_drops_query, implode(",", $client_drops_list)));
		}
		catch (Kohana_Database_Exception $e)
		{
			Kohana::log("error", $e->getMessage());
			return FALSE;
		}

		// Release the database lock
		self::release_mutex();

		Kohana::log("info", "Drops successfully posted and created as incidents");

		// Return the drop ids
		return TRUE;
	}
	
	/**
	 * Attempts to obtain a database level lock. This method is invoked
	 * by Swiftriver_Client_Model::create_reports so that the locations
	 * and reports can be created using batch INSERTS.
	 */
	private static function acquire_mutex()
	{
		$result = Database::instance()->query("SELECT GET_LOCK(?, ?) AS `status`", get_class(), 3600);
		if (intval($result[0]->status) != 1)
		{
			Kohana::log('error', "Unable to obtain database lock. Aborting...");
			throw new Kohana_Exception("Unable to obtain database lock");
		}
	}
	
	/**
	 * Releases the database level lock
	 */
	private static function release_mutex()
	{
		Database::instance()->query("SELECT RELEASE_LOCK(?)", get_class());
	}
	
	/**
	 * Given a table name - $table_name - and the number of IDs - $count - to allocate,
	 * computes the maximum value of the `id` column in the specified table and
	 * returns the values for the `id` column for the next $count rows
	 *
	 * @param  string $table_name Name of the table to allocate IDs for
	 * @param  int    $count No. of IDs to allocate
	 * @return array
	 */
	private static function get_ids($table_name, $count)
	{
		$result = Database::instance()->query("SELECT MAX(`id`) AS `max_id` FROM `$table_name`");
		$ids = array();
		for ($i = 1; $i <= $count; $i++)
		{
			$ids[] = $result[0]->max_id + $i;
		}

		// Log
		Kohana::log("info", sprintf("Allocated %d new ids for the %s table", $count, $table_name));

		return $ids;
	}
	
	/**
	 * Gets the clients list of the specified user
	 *
	 * @param  User_Model $user_orm
	 * @return array 
	 */
	public static function get_user_clients_array($user_orm)
	{
		$user_clients = array();

		// Build the where clause - If the specified user has the superadmin role,
		// get all the clients else only fetch those of the current user
		$where = $user_orm->has(ORM::factory('role')->where('name', 'superadmin')->find())
		    ? '1 = 1'
		    : array('user_id' => $user_orm->id);

		// Fetch the clients listing
		$clients = ORM::factory('swiftriver_client')
		    ->where($where)
		    ->find_all();

		foreach ($clients as $client)
		{
			$user_clients[] = $client->as_array();
		}

		return $user_clients;
	}
	
	/**
	 * Given a list of values, quotes them as strings
	 *
	 * @param  array $values
	 * @return array
	 */
	private static function quote_str($values)
	{
		$modified = array();
		$db = Database::instance();

		foreach ($values as $value)
		{
			$modified[] = "'".$db->escape_str($value)."'";
		}

		return $modified;
	}
}