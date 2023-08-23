<?php

namespace Drupal\nmu_common_code\Service;

use Drupal\Core\Database\Database;
use Drupal\nmu_common_code\Service\Admin;
use Drupal\nmu_common_code\Service\SessionMgmt;


class BaseClass
{
	protected $classSession;

	protected $strObjName = "";
	protected $bIsPersistant;
	protected $strStorageName = "BaseClassNameStorage";

	protected function __construct() {
		Admin::RefuseDirectAccess();
	}

	protected function Setup($strObjectName, $bIsPersistant=false, $bReset=false)
	{
		$this->classSession = new SessionMgmt();

		$this->bIsPersistant = $bIsPersistant ?? false;
		$this->BaseClass_StoreNameAndType($strObjectName, $bIsPersistant);

		if($bIsPersistant === true && $bReset === false)
			$this->BaseClass_SelfLoad();
		$this->BaseClass_StoreSelf();
	}


	protected function BaseClass_StoreNameAndType($strObjectName, $bIsPersistant)
	{
		if(!isset($GLOBALS[$this->strStorageName])) {
			$GLOBALS[$this->strStorageName] = [];
		}

		if (($strObjectName == null || $strObjectName == "") && $bIsPersistant != false)
			throw new Exception('You must provide an object name for a persistant class. It can be anything. It is used so that it can be loaded when you request the object on another page. (failed in BaseClass_StoreName)');
		elseif ($strObjectName != "" && in_array($strObjectName, $GLOBALS[$this->strStorageName]) && $bIsPersistant == false)
			throw new Exception('The object name "'.$this->strObjName.'" has already been used. It must be unique for a given page. On future pages you will need to reference it using it name if you want it to be persistant. (failed in BaseClass_StoreName)');
		elseif ($strObjectName == null || $strObjectName == "")
		{
			$iUniqueName = str_replace(" ", "", str_replace("0.", "", microtime()));
			while(in_array($iUniqueName, $GLOBALS[$this->strStorageName]))
				$iUniqueName .= 1;

			$GLOBALS[$this->strStorageName][] = $iUniqueName;
			$this->strObjName = $iUniqueName;
		}
		elseif ($this->strObjName != null )
		{
			$GLOBALS[$this->strStorageName][] = $strObjectName;
			$this->strObjName = $strObjectName;
		}
	}


	function BaseClass_SelfDump()
	{
		dpm($this);
	}


	protected function BaseClass_SelfLoad()
	{
		$aSessions = $this->classSession->SessionMgmt_Select($this->strObjName);
		foreach ($aSessions as $strName=>$strValue)
			$this->{$strName} = $strValue;
	}


	protected function BaseClass_StoreSelf()
	{
		$this->classSession->SessionMgmt_Set($this->strObjName, $this);
	}


	function __destruct()
	{
	}
}