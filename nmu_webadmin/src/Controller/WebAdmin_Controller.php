<?php  
/**
 * Created by PhpStorm.
 * User: aquinn
 * Date: 9/10/20
 * Time: 10:10 AM
 */


namespace Drupal\nmu_webadmin\Controller;  

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\nmu_common_code\Service\Admin; 
use Drupal\nmu_common_code\Service\SessionMgmt;

use Drupal\nmu_webadmin\Service\WebAdmin_Service;


class WebAdmin_Controller extends ControllerBase {
	protected $localhost;

### Check lifetime on session
### Component Access
### Add my VM to backups

	/**  
	* {@inheritdoc}  
	*/  
	public function __construct() {
		$this->session = new SessionMgmt();
	}

	public function WebAdmin_Main() {
		$form = \Drupal::formBuilder()->getForm('\Drupal\nmu_webadmin\Form\WebAdmin_Form');


		return [
			'#theme' => 'webadmin_template',
			'#form' => $form,
		];
	}  

	public function WebAdmin_Ajax() {
		$navSpecs = WebAdmin_Service::GetNav();
		$compInfo = WebAdmin_Service::GetComponent();
		
		require_once $compInfo['ajax-file'];

		$fullClass = "\\Drupal\\nmu_webadmin\\Form\\".$compInfo['component-name'].'_Ajax';
		$classComponent = new $fullClass();

		$functionName=$_REQUEST['callback'];
		$results = $classComponent::$functionName();

		return $results;
	}
}


