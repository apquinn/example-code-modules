<?php

namespace Drupal\nmu_webadmin\Form;

use Drupal\nmu_common_code\Service\Admin;
use Drupal\nmu_common_code\Service\SessionMgmt;
use Drupal\nmu_webadmin\Service\WebAdmin_Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use \Drupal\Core\Render\RendererInterface;
use Exception;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\CssCommand;


class WebAdmin_Form extends FormBase {
	protected $localhost;
	protected $charlie;

	public function __construct() {
		Admin::RefuseDirectAccess();
	}

	public function buildForm(array $form, FormStateInterface $form_state) {
		try 
		{
			$navSpecs = WebAdmin_Service::GetNav();

			$form['webadmin_only']['compSelect'] = [
				'#type' => 'select',
				'#title' => 'Function',
				'#default_value' => $navSpecs['navSelected'],
				'#options' => WebAdmin_Service::BuildOptionsList($navSpecs['primaryNav']),
				'#ajax' => [
					'callback' => '::NavStateChange_Ajax',
					'event' => 'change',
					'wrapper' => 'nav-sub',
				],
			];


			$options = isset($navSpecs['navSelected']) && $navSpecs['navSelected'] != '' ? WebAdmin_Service::BuildOptionsList($navSpecs['subNav'][$navSpecs['navSelected']]) : [];

			$form['webadmin_only']['navSelect'] = [
				'#type' => 'select',
				'#default_value' => isset($navSpecs['navSubSelected']) ? $navSpecs['navSubSelected'] : '',
				'#options' => $options,
				'#validated' => 'true',
				'#attributes' => [
					'class' => ['navSelect_visibility',],
					'style' => count($options) > 0 ? 'display: block;' : 'display: none;',
				],
				'#ajax' => [
					'callback' => '::SubNavStateChange_Ajax',
					'event' => 'change',
					'wrapper' => 'component-body',
				],
			];

			$this->RunComponent($form, $form_state, 'Main');
		}
		catch(Exception $e) {
			Admin::ErroHandler('webadmin', $e);
		}

		return $form;
	}

	public function NavStateChange_Ajax(array &$form, FormStateInterface &$form_state) {
		WebAdmin_Service::SaveState($form_state->getValue('compSelect'), '');
		$navSpecs = WebAdmin_Service::GetNav();

		$form['webadmin_only']['navSelect']['#options'] = $navSpecs['navSelected'] != '' ? WebAdmin_Service::BuildOptionsList($navSpecs['subNav'][$navSpecs['navSelected']]) : [];
		$form['webadmin_only']['navSelect']['#default_value'] = '';

		$this->RunComponent($form, $form_state, 'Main');

		$response = new AjaxResponse();
		$response->addCommand(new HtmlCommand("#nav-sub", $form['webadmin_only']['navSelect']));

		$visible = $navSpecs['navSelected'] != "" && count($navSpecs['subNav'][$navSpecs['navSelected']]) > 0 ? '' : 'none';
		$response->addCommand(new CssCommand('.navSelect_visibility', ['display' => $visible]));

		$form_state->setRebuild(TRUE);

		return $response;
	}

	public function SubNavStateChange_Ajax(array &$form, FormStateInterface &$form_state) {
		WebAdmin_Service::SaveState($form_state->getValue('compSelect'), $form_state->getValue('navSelect'));

		$this->RunComponent($form, $form_state, 'Main');

		$response = new AjaxResponse();
		$form_state->setRebuild(TRUE);

		return $response;
	}

	private function RunComponent(array &$form, FormStateInterface &$form_state, $function) {
		$navSpecs = WebAdmin_Service::GetNav();
		$compInfo = WebAdmin_Service::GetComponent();

		if($compInfo !== false) {
			$fullClass = "\\Drupal\\nmu_webadmin\\Form\\".$compInfo['component-name'];
			$classComponent = new $fullClass();
			$classComponent->$function($form, $navSpecs['navSubSelected']);

			if(!isset($form['#webadmin_twig_action']))
				$form['#webadmin_action'] = '';

			$form['#webadmin_component_template'] = $compInfo['template-name'];
			$form['#webadmin_component_js'] = $compInfo['js-file'];
			$form['#webadmin_component_ajax'] = $compInfo['ajax-file'];

			WebAdmin_Service::WebadminAjaxLink($form);
		}
		else {
			$msg = '...';
		}
	}

	public function submitForm(array &$form, FormStateInterface $form_state) {
		$this->RunComponent($form, $form_state, 'submitForm');
	}

	public function getFormId() {
		return 'webadmin_form';
	}
}
