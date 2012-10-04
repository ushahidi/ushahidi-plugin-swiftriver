<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Model for the swiftriver_drop_incident table
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
class Swiftriver_Drop_Incident_Model extends ORM {

	/**
	 * One-to-one relationship definition
	 * @var array
	 */
	protected $has_one = array('incident');

	/**
	 * Database table name
	 * @var string
	 */
	protected $table_name = 'swiftriver_drop_incident';

	/**
	 * Given the id of an incident, retrives the drop it was created
	 * created from
	 *
	 * @param  int  $incident_id ID of the incident
	 * @return mixed Swiftriver_Drop_Incident_Model on success, FALSE otherwise
	 */
	public static function get_by_incident_id($incident_id)
	{
		$drop_orm = ORM::factory('swiftriver_drop_incident')
		    ->where('incident_id', $incident_id)
		    ->find();
		
		return $drop_orm->loaded ? $drop_orm : FALSE;
	}
}