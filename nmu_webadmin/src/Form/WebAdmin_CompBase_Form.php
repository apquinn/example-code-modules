<?php

namespace Drupal\nmu_webadmin\Form;

use Drupal\nmu_common_code\Service\BaseClass;
use Drupal\Core\Database\Database;
use Drupal\user\Entity\User;
use Drupal;


class WebAdmin_CompBase_Form extends BaseClass 
{
	protected $form;
	protected $className;
	protected $action;
	protected $phase;
	protected $variables;

	public function __construct() {
	}

	protected function Setup($strObjectName, $bIsPersistant=true, $bReset=false) {
		###  bIsPersistant & bReset are overriden to true and false on purpose. Eliminating them as input options causes Drupal warning.
		parent::Setup($strObjectName, true, false);

		if (!isset($this->phase) || $this->phase == "") {
			$this->phase = 1;
		}
	}

	protected function GetPhase() {
		return $this->phase;
	}

	protected function GetNextPhase() {
		$this->phase++;
		
		return $this->phase;
	}

	protected function GetPrevPhase() {
		if($this->phase > 1)
			$this->phase--;
		
		return $this->phase;
	}

	public function GetForm() {
		return $this->form;
	}

}

