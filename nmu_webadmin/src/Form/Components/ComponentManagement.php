<?php

namespace Drupal\nmu_webadmin\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Exception;

use Drupal\nmu_common_code\Service\Admin; 
use Drupal\nmu_webadmin\Form\WebAdmin_CompBase_Form; 

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\RemoveCommand;


class ComponentManagement extends WebAdmin_CompBase_Form
{
	private $AjaxClass;

	public function __construct() {
		Admin::RefuseDirectAccess();
		parent::Setup('ComponentManagement');
		$this->AjaxClass = new ComponentManagement_Ajax();
	}

	public function Main(&$form, $action)
	{
		$this->form = &$form;
		$this->action = $action;

		$this->form['#component_add'] = [
			'#type' => 'webadmin_ajax_link',
			'#title' => 'add new component',
			'#ajax' => [
				'callback' => 'ComponentEdit_Open',
				'variables' => [
					'action' => 'new',
					'div' => 'component_add_div',
				],
			],
		];

		$this->form['component_add_div'] = [
			'#type' => 'html_tag',
			'#tag' => 'div',
			'#value' => '',
			'#attributes' => [
				'id' => 'component_add_div',
			],
		];

		$this->form['Title_Orig'] = [
			'#type' => 'hidden',
			'#value' => '',
			'#attributes' => [
				'id' => 'edit-title-orig',
			],
		];

		$this->form['Title'] = [
			'#type' => 'textfield',
			'#title' => 'Component name',
			'#value' => '',
			'#size' => 40,
			'#maxlength' => 100,
			'#required' => TRUE,
			'#attributes' => [
				'id' => 'edit-title',
			],
		];

		$options = [
			'all' => 'all',
			'individual' => 'individual', 
		];

		$this->form['AccessType'] = [
			'#type' => 'radios',
			'#title' => 'What type of access control will this component have?',
			'#options' => $options,
			'#description' => 'If all users will have access to all menu items, select "all"',
			'#default_value' => $options['all'],
		];

		for($I=1; $I<=8; $I++) {
			$this->form['SubNav-'.$I] = [
				'#type' => 'textfield',
				'#title' => 'Menu item '.$I,
				'#value' => '',
				'#size' => 20,
				'#attributes' => [
					'id' => 'edit-subnav'.$I,
				],
			];
		}

		$this->form['savebutton'] = [
			'#type' => 'button',
			'#value' => 'save',
			'#ajax' => [
				'callback' => [$this->AjaxClass, 'Component_SaveComp'],
				'event' => 'click',
				'wrapper' => '',
				'progress' => array('message' => '',), 
			],
		];

		$this->form['cancelbutton'] = [
			'#type' => 'button',
			'#value' => 'cancel',
			'#ajax' => [
				'callback' => [$this->AjaxClass, 'Component_CancelEdit'],
			],
		];

		$this->form['components'] = ComponentManagement::ListComonents();
	}


	public static function ListComonents() {
		$formComponents = [];

		$db = \Drupal\Core\Database\Database::getConnection('default', 'www_webadmin');
		$compResults = $db->query("SELECT * FROM www_new_webadmin.component ORDER BY Title")->fetchAll();
		foreach($compResults as $row) {
			$element = [];
			$machineName = str_replace(" ", "_", $row->Title);
			$element['#component_edit'] = [
				'#type' => 'webadmin_ajax_link',
				'#title' => 'edit',
				'#prefix' => '<div id="'.$machineName.'">',
				'#suffix' => '&nbsp;',
				'#ajax' => [
					'callback' => 'ComponentEdit_Open',
					'variables' => [
						'action' => 'edit',
						'div' => 'component_edit_div-'.$machineName,
						'title' => $row->Title,
					],
				],
			];

			$element['#component_del'] = [
				'#type' => 'webadmin_ajax_link',
				'#title' => 'delete',
				'#ajax' => [
					'callback' => 'ComponentDel',
					'variables' => [
						'group' => $machineName,
					],
				],
				'#attributes' => [
					'id' => 'component_del-'.$machineName,
				],
			];

			$element['#component_del_confirm'] = [
				'#type' => 'webadmin_ajax_link',
				'#title' => 'confirm deletion',
				'#ajax' => [
					'callback' => 'ComponentDelConfirm',
					'variables' => [
						'group' => $machineName,
					],
				],
				'#attributes' => [
					'id' => 'component_del_confirm-'.$machineName,
					'style'=>'display: none;',
				],
			];

			$element['component_title'] = [
				'#type' => 'html_tag',
				'#prefix' => '&nbsp;',
				'#suffix' => '</div>',
				'#tag' => 'span',
				'#value' => $row->Title,
			];

			$element['component_edit_div'] = [
				'#type' => 'html_tag',
				'#tag' => 'div',
				'#value' => '',
				'#attributes' => [
					'id' => 'component_edit_div-'.$machineName,
				],
			];

			$formComponents['component-'.$row->Title] = $element;
		}

		return $formComponents;
	}

	public function submitForm(array &$form, FormStateInterface $form_state) {
		// TODO: Implement submitForm() method.
	}
}