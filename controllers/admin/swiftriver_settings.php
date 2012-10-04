<?php defined('SYSPATH') or die('No direct script access.');
/**
 * SwiftRiver settings controller
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author     Ushahidi Team <team@ushahidi.com> 
 * @package    Ushahidi - https://github.com/ushahidi/Ushahidi_Web
 * @category   Controllers
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
 */
class Swiftriver_Settings_Controller extends Admin_Controller {
	
	public function index()
	{
		$this->template->set("this_page", "addons")
		    ->bind("content", $content);

		// Form fields for a client record
		$form = array(
			'id' => '',
			'client_id' => '',
			'client_secret' => '',
			'client_name' => '',
			'client_url' => ''
		);

		// Content view
		$content = View::factory("admin/addons/plugin_settings")
		   ->set("title", "SwiftRiver Clients")
		   ->bind("settings_form", $settings_form);		

		// View for adding/removing SwiftRiver deployments
		$settings_form = View::factory('admin/swiftriver/settings')
		    ->bind("clients", $clients)
		    ->set("action_url", url::site('admin/swiftriver_settings/manage'));
		
		$clients = json_encode(Swiftriver_Client_Model::get_user_clients_array($this->user));
		
		// When a client is aded/edited/deleted
		if ($_POST)
		{
			$validation = Validation::factory($_POST)
			    ->pre_filter('trim')
			    ->add_rules('client_url', 'required', 'url')
			    ->add_rules('client_name', 'required');
			
			if ($validation->validate())
			{
				Swiftriver_Client_Model::add_client($this->user->id, $validation);
			}
			else
			{
				// TODO: Show the errors
			}
			
		}
	}
	
	/**
	 * REST endpoint for mananging the clients listing
	 */
	public function manage()
	{
		$this->template = '';
		$this->auto_render = FALSE;
		
		switch (request::method())
		{
			case "post":
			break;
			
			case "put":
			break;
			
			case "delete":
			break;
		}
	}
	
}