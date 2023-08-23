<?php

namespace Drupal\nmu_webadmin\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Database\Database;
use Exception;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\HtmlCommand;

use Drupal\nmu_common_code\Service\Admin; 
use Drupal\nmu_webadmin\Service\WebAdmin_Service;


class ComponentManagement_Ajax
{
	public function __construct() {
		Admin::RefuseDirectAccess();
	}


	public static function ComponentDel()
	{
		$response = new AjaxResponse();
		$response->addCommand(new InvokeCommand(NULL, 'ShowConfirmation', [$_REQUEST['group']]));

		return $response;
	}

	
	public static function ComponentDelConfirm()
	{
		$response = new AjaxResponse();
		$origName = str_replace("_", " ", $_REQUEST['group']);

		$db = \Drupal\Core\Database\Database::getConnection('default', 'www_webadmin');
		$users = $db->query("SELECT * FROM www_new_webadmin.user")->fetchAll();
		foreach($users as $user) {
			$newCompAccess = [];
			foreach(unserialize($user->CompAccess) as $comp) {
				if($comp != $origName) {
					$newCompAccess[] = $comp;
				}
			}
			$newCompAccess = serialize($newCompAccess);

			$newCompSubAccess = [];
			foreach(unserialize($user->CompSubAccess) as $key=>$compSubAccess) {
				if($key != $origName) {
					$newCompSubAccess[$key] = $compSubAccess;
				}
			}
			$newCompSubAccess = serialize($newCompSubAccess);

			$db->update('www_new_webadmin.user')
				->condition('Username', $user->Username)
				->fields([
					'CompAccess' => $newCompAccess, 
					'CompSubAccess' => $newCompSubAccess
				])
				->execute();
		}

		$compName = str_replace("_", "", $_REQUEST['group']);
		$libraryYml = explode("\n", file_get_contents(drupal_get_path('module', 'nmu_webadmin').'/nmu_webadmin.libraries.yml'));
		$start = 'nmu_webadmin_'.$compName.'_libraries';
		$end = 'END '.$start;
		$newYml = '';

		$record = true;
		foreach($libraryYml as $line) {
			if(strstr($line, $start)) {
				$record = false;
			}

			if($record) {
				$newYml .= $line."\n";
			}

			if(strstr($line, $end)) {
				$record = true;
			}
		}
		file_put_contents(drupal_get_path('module', 'nmu_webadmin').'/nmu_webadmin.libraries.yml', $newYml);

		### js file
		$file = drupal_get_path('module', 'nmu_webadmin').'/js/'.str_replace(" ", "", $_REQUEST['group']).'.js';
		if(file_exists($file)) {
			unlink($file);
		}

		### template file
		$file = drupal_get_path('module', 'nmu_webadmin').'/templates/'.strtolower(str_replace(" ", "-", $_REQUEST['group'])).'-template.html.twig';
		if(file_exists($file)) {
			unlink($file);
		}

		### component files
		$file = drupal_get_path('module', 'nmu_webadmin').'/src/Form/Components/'.str_replace(" ", "-", $_REQUEST['group']).'.php';
		if(file_exists($file)) {
			unlink($file);
		}

		$file = drupal_get_path('module', 'nmu_webadmin').'/src/Form/Components/'.str_replace(" ", "-", $_REQUEST['group']).'_Ajax.php';
		if(file_exists($file)) {
			unlink($file);
		}

		$db->delete('www_new_webadmin.component')
			->condition('Title', $origName)
			->execute();

		$response->addCommand(new RemoveCommand('#'.$_REQUEST['group']));
		return $response;
	}


	public static function ComponentEdit_Open() 
	{
		$title = ""; $access = ""; $subnav = [];
		$response = new AjaxResponse();

		if($_REQUEST['action'] == "edit") {
			$db = \Drupal\Core\Database\Database::getConnection('default', 'www_webadmin');
			$compResults = $db->query("SELECT * FROM www_new_webadmin.component WHERE Title=:Title", [':Title' => $_REQUEST['title'], ])->fetchAll();
			if(count($compResults) != 1) {
				throw new exception("Edit ".$_REQUEST['title']." found ".count($compResults)."database entries");
			}

			$title = $compResults[0]->Title;
			$access = $compResults[0]->AccessType;
			$subnav = unserialize($compResults[0]->Navigation);
		}
		$subnav = array_pad($subnav ,8, '');

		$response->addCommand(new InvokeCommand(NULL, 'ShowEditFields', [$_REQUEST['div'], $title, $access, $subnav]));
		return $response;
	}


	public static function Component_CancelEdit() 
	{
		$subnav = array_pad([] ,8, '');

		$response = new AjaxResponse();
		$response->addCommand(new InvokeCommand(NULL, 'HideEditFields', ['', "", "", $subnav]));
		return $response;
	}


	public static function Component_SaveComp(array &$form, FormStateInterface &$form_state)
	{
		$db = \Drupal\Core\Database\Database::getConnection('default', 'www_webadmin');
		$response = new AjaxResponse(); 
		$foundIssue = false;

		if($_REQUEST['Title_Orig'] != "" && $_REQUEST['Title_Orig'] != $_REQUEST['Title']) {
			$compResults = $db->query("SELECT ID FROM www_new_webadmin.component WHERE Title=:Title", [':Title' => $_REQUEST['Title'], ])->fetchAll();
			if(count($compResults) > 0) {
				$response->addCommand(new AfterCommand('#title-warning', '<p id="title-warning-text" class="warning">Component title must be unique</p>', []));
				$foundIssue = true;
			}
			else {
				$response->addCommand(new RemoveCommand('#title-warning-text'));
			}
		}

		if($foundIssue === false && $_REQUEST['Title'] == "") {
			$response->addCommand(new AfterCommand('#title-warning', '<p id="title-warning-text" class="warning">Component title is required</p>', []));
			$foundIssue = true;
		}
		elseif($foundIssue === false) {
			$response->addCommand(new RemoveCommand('#title-warning-text'));
		}

		$nav = [];
		for($I=1; $I<=8; $I++) {
			if($_REQUEST['SubNav-'.$I] != "") {
				$nav[] = $_REQUEST['SubNav-'.$I];
			}
		}

		if($foundIssue === true) {
			return $response;
		}


		## All is well, do it.
		if($_REQUEST['Title_Orig'] == "") {
			$db->insert('www_new_webadmin.component')
				->fields([
					'Title' => $_REQUEST['Title'],
					'Navigation' => serialize($nav),
					'AccessType' => $_REQUEST['AccessType'],
				])->execute();
		} else {
			$db->update('www_new_webadmin.component')
				->condition('Title', $_REQUEST['Title_Orig'])
				->fields([
					'Title' => $_REQUEST['Title'],
					'AccessType' => $_REQUEST['AccessType'],
					'Navigation' => serialize($nav),
					'Title' => $_REQUEST['Title'],
				])
				->execute();

			if($_REQUEST['Title_Orig'] != $_REQUEST['Title']) {
				$userResults = $db->select('www_new_webadmin.user', 't')
					->fields('t', ['Username', 'CompAccess', 'CompSubAccess', 'SiteAccess'])
					->condition('CompAccess', "%" . $db->escapeLike($_REQUEST['Title_Orig']) . "%", 'LIKE')
					->execute()
					->fetchAll();

				foreach($userResults as $user) {
					$compAccess = unserialize($user->CompAccess);
					foreach($compAccess as &$comp) {
						if($comp == $_REQUEST['Title_Orig']) {
							$comp = $_REQUEST['Title'];
						}
					}

					$newCompSubAccess = [];
					foreach(unserialize($user->CompSubAccess) as $key=>$compSub) {
						if($key != $_REQUEST['Title_Orig']) {
							$newCompSubAccess[$key] = $compSub;
						}
						else {
							if($_REQUEST['AccessType'] == "individual") {
								$newCompSubAccess[$_REQUEST['Title']] = $compSub;
							}
						}
					}

					if($_REQUEST['AccessType'] == "individual" && !isset($newCompSubAccess[$_REQUEST['Title']])) {
						$subRights = [];
						for($I=1; $I<=8; $I++) {
							if($_REQUEST['SubNav-'.$I] != "") {
								$subRights[] = $_REQUEST['SubNav-'.$I];
							}
						}

						$newCompSubAccess[$_REQUEST['Title']] = $subRights;
					}

					$db->update('www_new_webadmin.user')
						->condition('Username', $user->Username)
						->fields([
							'CompAccess' => serialize($compAccess),
							'CompSubAccess' => serialize($newCompSubAccess),
						])
						->execute();
				}
			}
		}

		$newContents = ComponentManagement_Ajax::Component_NewComp($_REQUEST['Title']);
		if($_REQUEST['Title_Orig'] != "" && $_REQUEST['Title_Orig'] != $_REQUEST['Title']) {
			$libraryYml = file_get_contents(drupal_get_path('module', 'nmu_webadmin').'/nmu_webadmin.libraries.yml');

			$oldLibraryHeading = 'nmu_webadmin_'.str_replace(" ", "", $_REQUEST['Title_Orig']).'_libraries';
			$newLibraryHeading = 'nmu_webadmin_'.str_replace(" ", "", $_REQUEST['Title']).'_libraries';
			$libraryYml = str_replace($oldLibraryHeading, $newLibraryHeading, $libraryYml);

			$oldLibraryJS= str_replace(" ", "", $_REQUEST['Title_Orig']).'.js';
			$NewLibraryJS = str_replace(" ", "", $_REQUEST['Title']).'.js';
			$libraryYml = str_replace($oldLibraryJS, $NewLibraryJS, $libraryYml);

			file_put_contents(drupal_get_path('module', 'nmu_webadmin').'/nmu_webadmin.libraries.yml', $libraryYml);

			### js file
			$oldFile = drupal_get_path('module', 'nmu_webadmin').'/js/'.str_replace(" ", "", $_REQUEST['Title_Orig']).'.js';
			$newFile = drupal_get_path('module', 'nmu_webadmin').'/js/'.str_replace(" ", "", $_REQUEST['Title']).'.js';
			if(file_exists($oldFile)) {
				rename($oldFile, $newFile);
			} else {
				file_put_contents($oldFile, $newContents['js']);
				chmod($newFile, 0664);
			}

			### template file
			$oldFile = drupal_get_path('module', 'nmu_webadmin').'/templates/'.strtolower(str_replace(" ", "-", $_REQUEST['Title_Orig'])).'-template.html.twig';
			$newFile = drupal_get_path('module', 'nmu_webadmin').'/templates/'.strtolower(str_replace(" ", "-", $_REQUEST['Title'])).'-template.html.twig';
			if(file_exists($oldFile)) {
				rename($oldFile, $newFile);
			} else {
				file_put_contents($newFile, $newContents['twig']);
				chmod($newFile, 0664);
			}

			### component files
			$oldFile = drupal_get_path('module', 'nmu_webadmin').'/src/Form/Components/'.str_replace(" ", "", $_REQUEST['Title_Orig']).'.php';
			$newFile = drupal_get_path('module', 'nmu_webadmin').'/src/Form/Components/'.str_replace(" ", "", $_REQUEST['Title']).'.php';
			if(file_exists($oldFile)) {
				rename($oldFile, $newFile);
			} else {
				file_put_contents($newFile, $newContents['comp']);
				chmod($newFile, 0664);
			}

			$oldFile = drupal_get_path('module', 'nmu_webadmin').'/src/Form/Components/'.str_replace(" ", "", $_REQUEST['Title_Orig']).'_Ajax.php';
			$newFile = drupal_get_path('module', 'nmu_webadmin').'/src/Form/Components/'.str_replace(" ", "", $_REQUEST['Title']).'_Ajax.php';
			if(file_exists($oldFile)) {
				rename($oldFile, $newFile);
			} else {
				file_put_contents($newFile, $newContents['ajax']);
				chmod($newFile, 0664);
			}
		}
		elseif($_REQUEST['Title_Orig'] == "") {
			$libraryHeading = str_replace(" ", "", $_REQUEST['Title']);
			$libraryYml = file_get_contents(drupal_get_path('module', 'nmu_webadmin').'/nmu_webadmin.libraries.yml');
			$libraryYml .= '
				nmu_webadmin_'.$libraryHeading.'_libraries:
				  version: 1.0
				  js:
				    js/'.str_replace(" ", "", $_REQUEST['Title']).'.js: {}
				  dependencies:
				    - core/jquery
				    - core/drupal.ajax
				#### END nmu_webadmin_'.$libraryHeading.'_libraries ####'."\n";
			file_put_contents(drupal_get_path('module', 'nmu_webadmin').'/nmu_webadmin.libraries.yml', str_replace("\t", "", $libraryYml));

			### js file
			$newFile = drupal_get_path('module', 'nmu_webadmin').'/js/'.str_replace(" ", "", $_REQUEST['Title']).'.js';
			if(file_exists($newFile)) {
				throw new exception("Adding ".$_REQUEST['Title'].", but js file already exists");
			} else {
				file_put_contents($newFile, $newContents['js']);
				chmod($newFile, 0664);
			}

			### template file
			$newFile = drupal_get_path('module', 'nmu_webadmin').'/templates/'.strtolower(str_replace(" ", "-", $_REQUEST['Title'])).'-template.html.twig';
			if(file_exists($newFile)) {
				throw new exception("Adding ".$_REQUEST['Title'].", but twig file already exists");
			} else {
				file_put_contents($newFile, $newContents['twig']);
				chmod($newFile, 0664);
			}

			### component files
			$newFile = drupal_get_path('module', 'nmu_webadmin').'/src/Form/Components/'.str_replace(" ", "", $_REQUEST['Title']).'.php';
			if(file_exists($newFile)) {
				throw new exception("Adding ".$_REQUEST['Title'].", but comp file already exists");
			} else {
				file_put_contents($newFile, $newContents['comp']);
				chmod($newFile, 0664);
			}

			$newFile = drupal_get_path('module', 'nmu_webadmin').'/src/Form/Components/'.str_replace(" ", "", $_REQUEST['Title']).'_Ajax.php';
			if(file_exists($newFile)) {
				throw new exception("Adding ".$_REQUEST['Title'].", but comp_ajax file already exists");
			} else {
				file_put_contents($newFile, $newContents['ajax']);
				chmod($newFile, 0664);
			}
		}

		$response->addCommand(new InvokeCommand(NULL, 'HideEditFields', ['edit_field_storage', "", "", ""]));

		if($_REQUEST['Title_Orig'] == "") {
			$msg = $_REQUEST['Title']." was created succesfully";
		} else {
			$msg = $_REQUEST['Title']." was updated succesfully";
		}
		$response->addCommand(new InvokeCommand(NULL, 'AddMessage', ['add-component-msg', $msg]));

		$form['components'] = ComponentManagement::ListComonents();
		WebAdmin_Service::WebadminAjaxLink($form['components']);
		$response->addCommand(new HtmlCommand("#components", $form['components']));
		$form_state->setRebuild(TRUE);

		return $response;
	}


	private static function Component_NewComp($name)
	{
		$name = str_replace(" ", "", $name);
		$output = [];

		$output['comp'] = str_replace("\t\t\t", "", '<?php

			namespace Drupal\nmu_webadmin\Form;

			use Drupal\Core\Form\FormStateInterface;
			use Drupal\nmu_common_code\Service\Admin; 
			use Drupal\nmu_webadmin\Form\WebAdmin_CompBase_Form; 

			class '.$name.' extends WebAdmin_CompBase_Form
			{
				private $AjaxClass;

				public function __construct() {
					Admin::RefuseDirectAccess();
					parent::Setup("'.$name.'");
					$this->AjaxClass = new '.$name.'_Ajax();
				}

				public function Main(array &$form, $action)
				{
					$this->form = $form;
					$this->action = $action; # this is the sub menu item that was clicked

					return $this->form;
				}
			}');


		$output['ajax'] = str_replace("\t\t\t", "", '<?php

			namespace Drupal\nmu_webadmin\Form;

			use Drupal\Core\Form\FormStateInterface;
			use Drupal\Core\Ajax\AjaxResponse;

			class '.$name.'_Ajax
			{
				public function __construct() {
					Admin::RefuseDirectAccess();
				}
			}');

		$output['twig'] = "{% if action == '' %}\n{% endif %}";
		$output['js'] = "// Place your javasctipt here";

		return $output;
	}
}
