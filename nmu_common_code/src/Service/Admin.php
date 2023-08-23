<?php

namespace Drupal\nmu_common_code\Service;

use Drupal\user\Entity\User;
use Drupal;

class Admin
{
	public function __construct()
	{
		Admin::RefuseDirectAccess();
	}


	public static function RefuseDirectAccess() {
		if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
			exit('This file can not be accessed directly...');
		}
	}


	public static function Debug_IsAdmin()
	{
		$user = User::load(Drupal::currentUser()->id());
		$username = $user->get('name')->value;

		if($username == "aquinn" || $username == "ericjohn" || $username == "mkinnune") {
			return true;
		}

		if(isset($_SERVER['USER']) && $_SERVER['USER'] != "") {
			return true;
		}

		if(isset($_SERVER["SHELL"]) && $_SERVER["SHELL"] != "") {
			return true;
		}

		return false;
	}


	public static function ErroHandler($type, $ex)
	{
		watchdog_exception($type, $ex);
		drupal_set_message($ex->getMessage(), 'error');
	}



	/*
	function ServerBan($iIP, $iCurrentTime, $strUserAgent, $strScriptURL, $strProtectedForm, $strWhose)
	{
		$strQuery = 'INSERT INTO cms_form_gateway (`ip_addr`, `submit_date`, `user_agent`, `script_url`, `ban`, `form`, `whose`) VALUES (\''.$iIP.'\', \''.$iCurrentTime.'\', \''.$strUserAgent.'\', \''.$strScriptURL.'\', \'1\', \''.$strProtectedForm.'\', \''.$strWhose.'\');';
		$this->classSqlQuery->MySQL_Queries($strQuery);

		//fail2ban is watching our error logs for this message and will trigger a ban if it is seen
		error_log('ban-this-user', 0);
		echo '<h1>Banned</h1><p>Your IP address has been banned from this server.  Please contact <a href="mailto:edesign@nmu.edu">edesign@nmu.edu</a> if you believe this ban was not warranted.</p>';

		exit;
	}

	function CheckDBSize($strDBName)
	{
		$strWhere = "";
		if ($strDBName != "")
			$strWhere = " WHERE SCHEMA_NAME='".addslashes($strDBName)."'";

		$strQuery = "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA ".$strWhere." ORDER BY SCHEMA_NAME";
		$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);

		$iTotal = 0;
		foreach ($aResults as $aRow)
			$iTotal += $this->ListSizes($aRow['SCHEMA_NAME'], "", false);

		if ($iTotal > 0)
			print'<div class="col col-lw col-text-right">Total Size</div><div class="col">'.round($iTotal / 1000, 2).' GB</div>';

		return true;
	}

	function CheckTableSize($strDBName, $strTableName)
	{
		$this->ListSizes($strDBName, $strTableName, true);
		return true;
	}


	private function ListSizes($strDBName, $strTableName, $bShowTables)
	{
		if ($strDBName == "" && $strTableName != "")
			throw new Exception("If you send a table name, you must also send a database name.");

		if (!isset($GLOBALS['Admin_ListSizes']) || $GLOBALS['Admin_ListSizes'] == "")
		{
			print'<style>
			.col {
				display:inline-block;
				padding-right:10px;
			}

			.col-w {
				min-width:475px;
			}

			.col-text-right {
				text-align:right;
			}

			.col-lw {
				min-width:275px;
			}
			</style>';
		}

		$strWhere = "";
		if ($strDBName != "")
			$strWhere = " WHERE TABLE_SCHEMA='".$strDBName."' ";
		if ($strTableName != "")
			$strWhere .= " AND table_name='".$strTableName."' ";

		$strQuery = "SELECT TABLE_SCHEMA, table_name AS 'Table', round(((data_length + index_length) / 1024 / 1024), 2) 'size' FROM information_schema.TABLES ".$strWhere." ORDER BY size DESC";
		$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);

		$iSize = 0;
		foreach ($aResults as $aRow)
		{
			if ($bShowTables)
			{
				print'<div class="col col-w">'.$aRow['TABLE_SCHEMA'].' - '.$aRow['Table'].'</div><div class="col">'.round($aRow['size'], 2).' mb</div>';
				print'<div style="clear:both;"></div>';
			}

			$iSize += $aRow['size'];
		}

		if (!$bShowTables && $iSize > 0)
			print'<div class="col col-lw">'.$strTableName.'</div><div class="col">'.round($iSize, 2).' mb</div>';
		elseif ($bShowTables && $iSize > 0)
			print'<div class="col col-w col-text-right">Total Size</div><div class="col">'.round($iSize, 2).' mb</div>';
		print'<div style="clear:both; padding-bottom:10px;"></div>';

		return $iSize;
	}
	*/
}

