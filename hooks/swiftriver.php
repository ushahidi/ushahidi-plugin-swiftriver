<?php defined('SYSPATH') or die('No direct script access');
/**
 * Hook for the swiftriver plugin. This hook registers the callbacks
 * the events that this plugin hooks into
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author     Ushahidi Team <team@ushahidi.com> 
 * @package    Ushahidi - https://github.com/ushahidi/Ushahidi_Web
 * @category   Hooks
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
 */
class swiftriver {
	
	/**
	 * Singleton instance for this hook
	 * @var swifriver
	 */
	private static $instance;

	/**
	 * Initializes this hook
	 */
	public static function init()
	{
		empty(self::$instance) and new swiftriver;
	}
	
	public function __construct()
	{
		// Create a singleton instance
		empty(self::$instance) and self::$instance = $this;
				
		// Register this plugin's event registrar
		Event::add('system.pre_controller', array($this, 'register_events'));
	}
	
	/**
	 * Subscribes this plugin to events and registers their respective callbacks
	 */
	public function register_events()
	{
		Kohana::log('info', "Registering swiftriver event callbacks");

		// When a report (incident) is displayed on the frontend
		Event::add('ushahidi_action.report_meta', array($this, 'display_drop_metadata'));
		
		// Styling for this plugin's settings pages
		if (Router::$controller === 'swiftriver_settings')
		{
			Event::add('ushahidi_action.header_scripts_admin', array($this, 'swiftriver_settings_assets'));
		}
	}
	
	/**
	 * Executed when a report is displayed on the frontend
	 */
	public function display_drop_metadata()
	{
		// 	Get the incident id
		$incident_id = Event::$data;
		
		// Check if the incident is mapped to a drop
		if (($drop = Swiftriver_Drop_Incident_Model::get_by_incident_id($incident_id)) !== FALSE)
		{
			// Load the view
			$metadata_view  = View::factory('reports/drop_metadata')
			   ->bind("metadata", $metadata);

			// Sanitize the the payload - Remove double newline characters
			$sanitized = preg_replace("/(\\n)+/m", "\\n", $drop->metadata);

			// Get the metadata
			$metadata = json_decode($sanitized, TRUE);

			// Render the view
			$metadata_view->render(TRUE);
		}
	}
	
	/**
	 * Styling for the SwiftRiver plugin settings page
	 */
	public function swiftriver_settings_assets()
	{
		 // Backbone JS (+underscore)
		echo html::script(array(
		    'plugins/swiftriver/media/js/underscore-min',
		    'plugins/swiftriver/media/js/backbone-min'
		));

		View::factory('admin/swiftriver/settings_css')
		    ->render(TRUE);
	}
	
}


// 
// Initiliaze the hook
// 
swiftriver::init();