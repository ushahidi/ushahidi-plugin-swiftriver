<?php defined('SYSPATH') or die('No direct script access.');
/**
 * SwiftRiver API controller
 * Exposes REST endpoint for receiving drops from a SwiftRiver deployment
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
class Swiftriver_Controller extends Template_Controller {

	/**
	 * Template for this controller
	 * @var string
	 */
	public $template = '';

	/**
	 * Disable automatic rendering
	 * @var bool
	 */
	public $auto_render = FALSE;
	
	public function __construct()
	{
		parent::__construct();
	}
    
	/**
	 * REST endpoint used to check whether this plugin is
	 * available on the Ushahidi deployment
	 */
	public function ping()
	{
		header("Content-type:application/json; charset=utf-8");

		// Response
		$response = array(
			'deployment' => Kohana::config('settings.site_name'),
			'platform_version' => Kohana::config('settings.ushahidi_version'),
			'plugin_version' => '0.1',
		);

		echo json_encode($response);
	}
	
	/**
	 * REST endpoint for receiving drops via HTTP POST
	 */
	public function drops()
	{
		header("Content-type: application/json; charset=utf-8");

		// Check for POST request
		if ($_POST)
		{
			// Validate the post data
			$post = Validation::factory($_POST)
				->pre_filter('trim')
				->add_rules('checksum', 'required')
				->add_rules('drops', 'required')
				->add_rules('client_id', 'required');

			// Validate
			if ($post->validate(FALSE))
			{
				Kohana::log("info", "Recieved drops for posting as incidents");

				// Use the client's id to retrive its private key (secret)
				$client_orm = Swiftriver_Client_Model::get_by_client_id($post->client_id);

				// If private key not found, return 403
				if ( ! $client_orm)
				{
					Kohana::log('error', sprintf("Client authorization failed. Invalid client id: %s", $post->client_id));

					// Invalid client id specified - Access denied
					header("HTTP/1.1 401 Authorization failed", TRUE, 401);
					echo json_encode(array(
						"status" => "REQUEST_DENIED",
						"message" => "Authorization failed"
					));
					return;
				}

				Kohana::log('debug', "Authorization succeeded. Verifying checksum...");

				// Decode the drops payload for the server to recompute the checksum
				// for verification
				$drops = base64_decode($post->drops);

				// Compute SHA256 hash_hmac of the request data - use client's private key
				$server_checksum = $this->_get_request_hash($client_orm->client_secret, $drops);

				// Compare the two hash_hmac values - submitted vs computed
				if ($server_checksum !== $post->checksum)
				{
					Kohana::log('debug', "Server computed checksum: ".$server_checksum);
					Kohana::log('error', "Checksum verification failed. Invalid client checksum: ".$post->checksum);
				
					header("HTTP/1.1 401 Authorization failed", TRUE, 401);
					echo json_encode(array(
						"status" => "REQUEST_DENIED",
						"message" => "Checksum verification failed."
					));
					return;
				}

				Kohana::log("debug", "Checksum verification succeeded.");
				$drops = json_decode($drops, TRUE);

				// Create reports (incidents) from the drops
				// This step saves the drops and their tags
				try
				{
					Kohana::log("debug", sprintf("Expecting %d reports to be created from the submitted drops", count($drops)));

					if (Swiftriver_Client_Model::create_reports($client_orm, $drops))
					{
						echo json_encode(array(
							"status" => "OK",
							"message" => "Drops successfully posted"
						));
					}
					else
					{
						// Unknown error occurred
						header("HTTP/1.1 500 Server Error", TRUE, 500);
						echo json_encode(array(
							"status" => "INVALID_REQUEST",
							"message" => "An unknown error has occurred. Please try again"
						));
					}
				}
				catch (Kohana_Exception $e)
				{
					Kohana::log("error", $e->getMessage());

					// If fail return 400 status code
					header("HTTP/1.1 400 Bad request", TRUE, 400);
					echo json_encode(array(
						"status" => "INVALID_REQUEST",
						"message" => "An error was encountered while posting the drops"
					));
				}
			}
			else
			{
				// Validation failed
				Kohana::log('error', "Request validation failed: ".Kohana::debug($post->errors()));

				// Badly formed request - missing parameters
				header("HTTP/1.1 400 Bad Request", TRUE, 400);
				echo json_encode(array(
					"status" => "INVALID_REQUEST",
					"message" => "Some parameters are missing from the request data",
					"errors" => $post->errors()
				));
			}
		}
		else
		{
			Kohana::log("error", "The client did not submit a HTTP POST request");

			header("HTTP/1.1 405 Method not allowed", TRUE, 405);
			echo json_encode(array(
				"status" => "INVALID_REQUEST",
				"message" => "Only HTTP POST requests are allowed"
			));
		}
	}
	
	/**
	 * Computes an SHA256 hash_hmac of the specified drops array. The resulting
	 * value is compared with the checksum submitted by the client for purposes
	 * of verifying the authenticity of the request
	 *
	 * @param  string  $client_secret
	 * @param  array   $drops
	 * @return mixed   SHA256 hash_hmac if successful, FALSE otherwise
	 */
	private function _get_request_hash($client_secret, $drops)
	{
		try
		{
			return hash_hmac("sha256", $drops, $client_secret);
		}
		catch(Exception $e)
		{
			Kohana::log('error', $e->getMessage());
			return FALSE;
		}
	}    
}