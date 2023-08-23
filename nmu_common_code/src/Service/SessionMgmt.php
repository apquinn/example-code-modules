<?php

namespace Drupal\nmu_common_code\Service;

use Drupal\Core\Database\Database;
use Drupal\nmu_common_code\Service\Admin;


class SessionMgmt
{
	private $strSessionID;

	public function __construct()
	{
		try
		{
			Admin::RefuseDirectAccess();

			if(isset($_SESSION['SessionMgmt_SessionID']) && $_SESSION['SessionMgmt_SessionID'] != "")
				$this->strSessionID = $_SESSION['SessionMgmt_SessionID'];
			else
				$this->strSessionID = session_id();

			$this->SessionMgmt_TouchSession();
		}
		catch (Exception $ex)
		{
			\Drupal::logger('nmu_common_code')->error($ex->getMessage());
		}
	}

	public function SessionMgmt_SetSessionID($iID)
	{
		try
		{
			if($iID != "")
			{
				$this->strSessionID = $iID;
				$_SESSION['SessionMgmt_SessionID'] = $iID;

				$this->SessionMgmt_TouchSession();
			}

			return $this->strSessionID;
		}
		catch (Exception $ex)
		{
			\Drupal::logger('nmu_common_code')->error($ex->getMessage());
		}
	}

	public function SessionMgmt_GetSessionID()
	{
		try
		{
			$this->SessionMgmt_TouchSession();

			return $this->strSessionID;
		}
		catch (Exception $ex)
		{
			\Drupal::logger('nmu_common_code')->error($ex->getMessage());
		}
	}

	public function SessionMgmt_Set($strFieldName, $objObject)
	{
		try
		{
			$this->SessionMgmt_TouchSession();

			$aSessions = $this->SessionMgmt_SelectAll();
			$aSessions[$strFieldName] = $objObject;

			$classSqlQuery = Database::getConnection('default', 'www_webadmin');
			$classSqlQuery->update('www_admin.sessionmgmt_session_storage')
				->condition('SessionID', $this->strSessionID)
				->fields(['SessionData' => serialize($aSessions),])
				->execute();

			return true;
		}
		catch (Exception $ex)
		{
			\Drupal::logger('nmu_common_code')->error($ex->getMessage());
		}
	}


	public function SessionMgmt_Select($strFieldName)
	{
		try
		{
			$this->SessionMgmt_TouchSession();

			$aResults = $this->SessionMgmt_SelectAll();
			if(isset($aResults[$strFieldName]))
				return $aResults[$strFieldName];
			else
				### 9/24 return empty instead of array. 
				return '';
		}
		catch (Exception $ex)
		{
			\Drupal::logger('nmu_common_code')->error($ex->getMessage());
		}
	}


	public function SessionMgmt_SelectAll()
	{
		try
		{
			$this->SessionMgmt_TouchSession();

			$classSqlQuery = Database::getConnection('default', 'www_webadmin');
			$aResults = $classSqlQuery
				->query("SELECT * FROM www_admin.sessionmgmt_session_storage WHERE SessionID=:SessionID", [
						':SessionID' => $this->strSessionID,
				])
				->fetchAll();

			if (count($aResults) > 0)
				return unserialize($aResults[0]->SessionData);
			else
				return [];
		}
		catch (Exception $ex)
		{
			\Drupal::logger('nmu_common_code')->error($ex->getMessage());
		}
	}


	public function SessionMgmt_DeleteValue($strFieldName)
	{
		try
		{
			$this->SessionMgmt_TouchSession();

			if (!isset($strFieldName) || $strFieldName == "")
				\Drupal::logger('nmu_common_code')->error("FiledName is required for SessionMgmt_DeleteValue");
			else
			{
				$aSessions = $this->SessionMgmt_SelectAll();

				if (is_array($aSessions) && count($aSessions) > 0 && isset($aSessions[$strFieldName])) {
					unset($aSessions[$strFieldName]);

					$classSqlQuery = Database::getConnection('default', 'www_webadmin');
					$classSqlQuery->update('www_admin.sessionmgmt_session_storage')
						->condition('SessionID', $this->strSessionID)
						->fields(['SessionData' => serialize($aSessions), 'LastTouchDate' => time(),])
						->execute();
				}
			}
			return true;
		}
		catch (Exception $ex)
		{
			\Drupal::logger('nmu_common_code')->error($ex->getMessage());
		}
	}

	public function SessionMgmt_DeleteAll()
	{
		try
		{
			$this->SessionMgmt_TouchSession();

			$classSqlQuery = Database::getConnection('default', 'www_webadmin');
			$classSqlQuery->update('www_admin.sessionmgmt_session_storage')
				->condition('SessionID', $this->strSessionID)
				->fields(['SessionData' => "",])
				->execute();

			return true;
		}
		catch (Exception $ex)
		{
			\Drupal::logger('nmu_common_code')->error($ex->getMessage());
		}
	}


	private function SessionMgmt_CreateSession()
	{
		try
		{
			$iTime = time();
			$strTime = date("n-j-Y G:ia");

			$classSqlQuery = Database::getConnection('default', 'www_webadmin');
			$classSqlQuery->insert('www_admin.sessionmgmt_session_storage')
				->fields(['SessionID' => $this->strSessionID, 'SessionData' => "", 'LastTouchDate' => $iTime, "LastTouchDateStr" => $strTime, ])
				->execute();
		}
		catch (Exception $ex)
		{
			\Drupal::logger('nmu_common_code')->error($ex->getMessage());
		}
	}

	private function SessionMgmt_TouchSession()
	{
		try
		{
			$classSqlQuery = Database::getConnection('default', 'www_webadmin');
			$classSqlQuery->delete('www_admin.sessionmgmt_session_storage')
				->condition('LastTouchDate', (time() - ini_get("session.gc_maxlifetime")))
				->execute();

			$aResults = $classSqlQuery
				->query("SELECT * FROM www_admin.sessionmgmt_session_storage WHERE SessionID=:SessionID ORDER BY ID DESC", [
					':SessionID' => $this->strSessionID,
				])
				->fetchAll();

			if (count($aResults) == 0)
				$this->SessionMgmt_CreateSession();
			elseif(count($aResults) > 1) {
				$this->SessionMgmt_DestroySession();
				$this->SessionMgmt_CreateSession();
			}
			else {
				$iTime = time();
				$strTime = date("n-j-Y G:ia");

				$classSqlQuery->update('www_admin.sessionmgmt_session_storage')
					->condition('SessionID', $this->strSessionID)
					->fields(['LastTouchDate' => $iTime, 'LastTouchDateStr' => $strTime, ])
					->execute();
			}

			return true;
		}
		catch (Exception $ex)
		{
			\Drupal::logger('nmu_common_code')->error($ex->getMessage());
		}
	}

	private function SessionMgmt_DestroySession()
	{
		try
		{
			$classSqlQuery = Database::getConnection('default', 'www_webadmin');
			$classSqlQuery->delete('www_admin.sessionmgmt_session_storage')
				->condition('SessionID', $this->strSessionID)
				->execute();

			return true;
		}
		catch (Exception $ex)
		{
			\Drupal::logger('nmu_common_code')->error($ex->getMessage());
		}
	}
}
