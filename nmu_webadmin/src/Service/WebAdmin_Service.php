<?php

namespace Drupal\nmu_webadmin\Service;

use Drupal\nmu_common_code\Service\SessionMgmt;
use Drupal\nmu_common_code\Service\Admin;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;
use Drupal\user\Entity\User;
use Drupal;
use Exception;


class WebAdmin_Service
{
	public function __construct()
	{
		Admin::RefuseDirectAccess();
	}

	public static function GetNav()
	{
		$session = new SessionMgmt();
		$db = \Drupal\Core\Database\Database::getConnection('default', 'www_webadmin');

		$user = User::load(Drupal::currentUser()->id());
		$username = $user->get('name')->value;

#$session->SessionMgmt_Set('WebAdmin.nav', '');
		$nav = $session->SessionMgmt_Select('WebAdmin.nav');
		if((!is_array($nav) && $nav == "") || (is_array($nav) && count($nav) == 0)) {
			$options = [];
			$nav = [];
			$primaryNav = [];

			$userResults = $db->query("SELECT * FROM www_new_webadmin.user WHERE Username=:Username", [':Username' => $username, ])->fetchAll();
			if(count($userResults) == 1) {
				$userSubRights = unserialize($userResults[0]->CompSubAccess);
				foreach(unserialize($userResults[0]->CompAccess) as $component) {
					$compResult = $db->query("SELECT * FROM www_new_webadmin.component WHERE Title=:Title", [':Title' => $component, ])->fetchAll();
					if(count($compResult) != 1) throw new exception("Component '".$component->Title."' for user does not exist.");

					if($compResult[0]->AccessType == "all") {
						$compResult[0]->Navigation = unserialize($compResult[0]->Navigation);
					}
					else {
						$compResult[0]->Navigation = $userSubRights[$compResult[0]->Title];
					}

					unset($compResult[0]->ID);
					unset($compResult[0]->AccessType);

					$primaryNav[] = $compResult[0]->Title;

					$options = [];
					foreach($compResult[0]->Navigation as $item) {
						$options[] = $item;
					}
					$subNav[$compResult[0]->Title] = $options;
				}
			}
			elseif(count($userResults) > 1) {
				throw new exception("Multiple entries found for username: $username");
			}

			$nav['primaryNav'] = $primaryNav;
			$nav['subNav'] = $subNav;
			$nav['navSelected'] = $session->SessionMgmt_Select('navSelected');
			$nav['navSubSelected'] = $session->SessionMgmt_Select('navSubSelected');

			$session->SessionMgmt_Set('WebAdmin.nav', $nav);
		}

		return $nav;
	}

	public static function SaveState($navValue, $subNavValue)
	{
		$session = new SessionMgmt();
		$WebAdmin_nav = $session->SessionMgmt_Select('WebAdmin.nav');


		$WebAdmin_nav['navSelected'] = $navValue;
		$WebAdmin_nav['navSubSelected'] = $subNavValue;

		$session->SessionMgmt_Set('WebAdmin.nav', $WebAdmin_nav);
	}

	public static function GetComponent()
	{
		$session = new SessionMgmt();
		$WebAdmin_nav = $session->SessionMgmt_Select('WebAdmin.nav');

		if(WebAdmin_Service::ConfirmComponentAccess() !== false) {
			$output = [];
			$output['component-title'] = $WebAdmin_nav['navSelected'];
			$output['component-name'] = str_replace(' ', '', $WebAdmin_nav['navSelected']);

			$output['component-file'] = drupal_get_path('module', 'nmu_webadmin').'/src/Form/Components/'.$output['component-name'].'.php';
			if(!file_exists($output['component-file'])) {
				$user = User::load(Drupal::currentUser()->id());
				$username = $user->get('name')->value;

				throw new exception("user $username tried to access ".$output['component-file'].", but the file doesn't exist.");
				return false;
			}

			$output['template-name'] = strtolower(str_replace(' ', '-', $output['component-title'])).'-template.html.twig';
			$output['ajax-file'] = drupal_get_path('module', 'nmu_webadmin').'/src/Form/Components/'.$output['component-name'].'_Ajax.php';
			$output['js-file'] = 'nmu_webadmin/nmu_webadmin_'.strtolower(str_replace(' ', '_', $output['component-title'])).'_libraries';

			require_once $output['component-file'];
			require_once $output['ajax-file'];

			return $output;
		}
		else {
			return false;
		}
	}

	private static function ConfirmComponentAccess()
	{
		$db = \Drupal\Core\Database\Database::getConnection('default', 'www_webadmin');

		$session = new SessionMgmt();
		$WebAdmin_nav = $session->SessionMgmt_Select('WebAdmin.nav');

		$user = User::load(Drupal::currentUser()->id());
		$username = $user->get('name')->value;

		$userResults = $db->query("SELECT * FROM www_new_webadmin.user WHERE Username=:Username", [':Username' => $username, ])->fetchAll();
		if(count($userResults) != 1) {
			throw new exception("user $username attempted to access webadmin. User not in user table.");
			return false;
		}

		if($WebAdmin_nav['navSelected'] == '') {
			return false;
		}

		$allowedComps = unserialize($userResults[0]->CompAccess);
		if(!in_array($WebAdmin_nav['navSelected'], $allowedComps)) { 
			throw new exception("user $username attempted to access ".$WebAdmin_nav['navSelected'].". They have not been granted access.");
			return false;
		}

		$compResult = $db->query("SELECT * FROM www_new_webadmin.component WHERE Title=:Title", [':Title' => $WebAdmin_nav['navSelected'], ])->fetchAll();
		if(count($compResult) != 1) {
			throw new exception("user $username attempted to access ".$WebAdmin_nav['navSelected'].". Component does not exist in component table.");
			return false;
		}

		if(count(unserialize($compResult[0]->Navigation)) > 0 && $WebAdmin_nav['navSubSelected'] == "") {
			return false;
		}

		if(count(unserialize($compResult[0]->Navigation)) > 0) {
			if($compResult[0]->AccessType == "all") {
				if($WebAdmin_nav['navSubSelected'] != "" && !in_array($WebAdmin_nav['navSubSelected'], unserialize($compResult[0]->Navigation))) {
					throw new exception("user $username attempted to access ".$WebAdmin_nav['navSelected'].", ".$WebAdmin_nav['navSubSelected'].". ".$WebAdmin_nav['navSubSelected']." does not exist for ".$WebAdmin_nav['navSelected'].".");
					return false;
				}
			}
			else {
				if(!in_array($WebAdmin_nav['navSubSelected'], unserialize($userResults[0]->CompSubAccess)[$WebAdmin_nav['navSelected']])) {
					throw new exception("user $username attempted to access ".$WebAdmin_nav['navSelected'].", ".$WebAdmin_nav['navSubSelected'].". Access to ".$WebAdmin_nav['navSubSelected']." has not been granted to $username.");
					return false;
				}
			}
		}

		return true;
	}


	public static function WebadminAjaxLink(&$form)
	{
		$new_element = [];
		$elmentsToSkip = ['#ajax'];

		WebAdmin_Service::FindElements($form, 'webadmin_ajax_link', $results);

		foreach($results as &$element) {
			$variables = [];

			if(!isset($element['element']['#ajax']['callback']) || $element['element']['#ajax']['callback'] == "") {
				throw new exception("webadmin_ajax_link must have an ajax callback");
			} else {
				$variables['callback'] = $element['element']['#ajax']['callback'];
			}

			if(isset($element['element']['#ajax']['variables'])) {
				foreach($element['element']['#ajax']['variables'] as $name=>$value) {
					$variables[$name] = $value;
				}
			}

			$temp = [];
			$temp['#url'] = Url::fromRoute('nmu_webadmin.ajax', $variables);
			foreach($element['element'] as $name=>$value) {
				if($name == "#type") {
					$temp['#type'] = 'link';
				} elseif($name == "#title") {
					$temp['#title'] = $element['element']['#title'];
				} elseif($name == "#url") {
					$temp['#url'] = Url::fromRoute('nmu_webadmin.ajax', $variables);
				} elseif (!in_array($name, $elmentsToSkip)) {
					$temp[$name] = $value;
				}
			}
			$new_element = $temp;

			$found = false;
			if(isset($element['element']['#attributes'])) {
				foreach($element['element']['#attributes'] as $name=>$value) {
					if($name == "class") {
						$new_element['#attributes'][$name] = [str_replace('use-ajax', '', $value).' use-ajax'];
						$found = true;
					} else {
						$new_element['#attributes'][$name] = [$value];
					}
				}
			}

			if(!$found) {
				$new_element['#attributes']['class'] = ['use-ajax'];
			}

			$name = str_replace('#', '', $element['key']);
			WebAdmin_Service::ArrayInsertAfter($element['key'], $element['parent'], $name, $new_element);
		}
	}

	public static function FindElements(&$form, $elementName, &$results)
	{
		if(!is_array($results)) {
			$results = [];
		}

		foreach($form as $key=>&$element) {
			if(is_array($element) && isset($element['#type']) && $element['#type'] == "webadmin_ajax_link") {
				$temp = [];
				$temp['key'] = $key;
				$temp['element'] = $element;
				$temp['parent'] = &$form;
				$results[] = $temp;
			} elseif (is_array($element)) {
				WebAdmin_Service::FindElements($element, $elementName, $results);
			}
		}
	}

	public static function BuildOptionsList($array)
	{
		$options = [];
		if(count($array) > 0) {
			$options[''] = '';
			foreach($array as $item) {
				$options[$item] = $item;
			}
		}

		return $options;
	}
	
	private static function ArrayInsertAfter($key, array &$array, $newKey, $newValue) 
	{
		$newArray = array();

		if (array_key_exists($key, $array)) {
			foreach ($array as $currentKey=>$currentValue) {
				$newArray[$currentKey] = $currentValue;
				if ($currentKey === $key) {
					$newArray[$newKey] = $newValue;
				}
			}

			$array = $newArray;
		}
	}
}

