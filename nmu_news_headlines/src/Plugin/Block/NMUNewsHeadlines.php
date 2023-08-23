<?php
/**
* @file
* Contains \Drupal\nmu_news_headlines\Plugin\Block\NMUNewsHeadlines
*/
namespace Drupal\nmu_news_headlines\Plugin\Block;

use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Database;
use Drupal\nmu_common_code\Service\Admin;
use Drupal\nmu_common_code\Service\ErrorHandler;


/**
* Provides a 'nmu_news_headlines' List Block
*
* @Block(
*   id = "nmu_news_headlines_block",
*   admin_label = @Translation("NMU News Headlines Block"),
*   category = @Translation("NMU Blocks")
* )
*/
class NMUNewsHeadlines extends BlockBase {

	/**
	 * {@inheritdoc}
	 */
	public function __construct() {
		Admin::RefuseDirectAccess();
	}


	public function build() {
		try {
			$database = \Drupal\Core\Database\Database::getConnection('default', 'www_charlie');
			$query = $database->query("SELECT * FROM www_webadmin.cms_events WHERE HP=:strHP AND OccuranceDate=:iDay AND (Spot=1 OR Spot=2) ORDER BY Spot",
				[
					':strHP' => 'NMU Homepage',
					':iDay' => mktime(0, 0, 0, date("n"), date("j"), date("y")),
				]);
			$aResults = $query->fetchAll();

			$aOutput = [];
			if(count($aResults) > 0) {
				foreach ($aResults as $aRow) {

					$aTemp = [];
					if ($aRow->OccuranceDate != "" && is_numeric($aRow->EventDate))
						$aTemp['EventDate'] = date("F n, Y", $aRow->EventDate);
					else
						$aTemp['EventDate'] = $aRow->EventDate;

					$aTemp['Spot'] = $aRow->Spot;
					$aTemp['Title'] = $aRow->Title;
					if($aTemp['EventDate'] != "")
						$aTemp['Text'] = '<p>'.$aTemp['EventDate'].' &mdash; '.substr($aRow->Text, 3);
					else
						$aTemp['Text'] = $aRow->Text;
					$aTemp['Type'] = $aRow->Type;
					$aTemp['Footer'] = $aRow->Footer;
					$aTemp['Link'] = $aRow->Link;
					if($aRow->Image != "")
					{
						$aTemp['Image'] = str_replace(" ", "_", $aRow->Image);
						$aTemp['ImageAlt'] = $aRow->ImageAlt;
					}
					else
					{
						$strDir = "/d8files/default/HomePageNewsAndEvents/";
						$aImageOptions = scandir("/htdocs".$strDir);
						$aFinalOptions = [];
						for($I=0; $I<count($aImageOptions); $I++)
							if(substr($aImageOptions[$I], 0, 1) != ".")
								$aFinalOptions[] = $aImageOptions[$I];
						$strImage = $aFinalOptions[rand(0 , count($aFinalOptions)-1)];

						$aTemp['Image'] = "https://dev.nmu.edu".$strDir.$strImage;
						$aTemp['ImageAlt'] = str_replace(pathinfo('/htdocs'.$strDir.$strImage)['PATHINFO_EXTENSION'], "", $strImage);
					}

					$aOutput[] = $aTemp;
				}
			}


			return array(
				'#theme' => 'nmu_news_headlines_template',
				'#stories' => $aOutput,
			);
		}
		catch (\Exception $ex)
		{
			\Drupal::logger('nmu_news_headlines')->error($ex->getMessage());
		}
	}
}

