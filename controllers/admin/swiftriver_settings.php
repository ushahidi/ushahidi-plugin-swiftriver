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

		// Tracks the form status
		$form_saved = FALSE;

		// When a client is aded/edited/deleted
		if ($_POST)
		{
			$post = Validation::factory($_POST)
			    ->pre_filter('trim')
			    ->add_rules('client_url', 'required', 'url')
			    ->add_rules('client_name', 'required');
			
			// Check if the client id has been specified
			$client_orm = ( ! empty($_POST['id']))
			    ? ORM::factory('swiftriver_client', $_POST['id'])
			    : new Swiftriver_Client_Model();
			
			if ($post->validate())
			{
				$client_orm->client_url = $post->client_url;
				$client_orm->client_name = $post->client_name;
				$client_orm->user_id = $this->user->id;
				$client_orm->save();
				
				$form_saved = TRUE;
			}
			else
			{
				$errors = $post->errors('swiftriver_client');
			}
		}

		// Content view
		$content = View::factory("admin/addons/plugin_settings")
		   ->set("title", "SwiftRiver Clients")
		   ->bind("settings_form", $settings_form);		

		// View for adding/removing SwiftRiver deployments
		$settings_form = View::factory('admin/swiftriver/settings')
		    ->bind("clients", $clients)
		    ->bind("errors", $errors)
		    ->bind("form_saved", $form_saved)
		    ->set("action_url", url::site('admin/swiftriver_settings/manage'));

		$clients = json_encode(Swiftriver_Client_Model::get_user_clients_array($this->user));
	}
	
	/**
	 * REST endpoint for deleting swifriver clients
	 */
	public function manage($client_id)
	{
		$this->template = '';
		$this->auto_render = FALSE;
		
		switch (request::method())
		{
			case "delete":
			ORM::factory("swiftriver_client", $client_id)->delete();
			break;
		}
	}
}