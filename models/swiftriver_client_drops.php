<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Model for the swiftriver_client_drops table
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
class Swiftriver_Client_Drops_Model extends ORM {
	
	/**
	 * Belongs-to relationship definition
	 * @var array
	 */
	protected $belongs_to = array('swiftriver_client');
	
	/**
	 * Database table name
	 * @var string
	 */
	protected $table_name = 'swiftriver_client_drops';
}