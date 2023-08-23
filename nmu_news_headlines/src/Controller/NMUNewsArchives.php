<?php

/**
 * Created by PhpStorm.
 * User: aquinn
 * Date: 9/10/20
 * Time: 10:10 AM
 */

namespace Drupal\nmu_news_headlines\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\nmu_common_code\Service\Admin;
use Drupal\nmu_common_code\Service\ErrorHandler;


class NMUNewsArchives extends ControllerBase {
	protected $db;

	public function __construct() {
		$this->db = \Drupal\Core\Database\Database::getConnection('charlie.nmu.edu', 'www_webadmin');
	}


	public function Headlines(int $year) {
		try {
			$headlines = [];

			$query = $this->db->query("SELECT A.ID, A.Title, A.Blurb, A.Date FROM www_webadmin.cms_news A, www_webadmin.cms_news_categories B
									   WHERE Date>=:startDate
									   AND Date<=:endDate
									   AND Date<:newest
									   AND A.CategoryID=B.ID
									   AND ParentCategory IN (SELECT ID FROM cms_news_categories WHERE OwnedBy=29)
									   AND IsLive=1
									   ORDER BY Date desc",
				[
					':startDate' => mktime(0,0,0, 1,1,$year),
					':endDate' => mktime(0,0,0, 1,1,$year+1)-1,
					':newest' => 1525060800,
				]);
			$results = $query->fetchAll();
			foreach ($results as $row)
			{
				$temp = [];
				$temp['title'] = $row->Title;
				$temp['link'] = "/mc/news_archives/$year/$row->ID";
				$temp['blurb'] = $row->Blurb;
				$temp['date'] = date("l j, Y", $row->Date);
				$headlines[] = $temp;
			}

			return array(
				'#theme' => 'nmu_news_archives_headlines_template',
				'#headlines' => $headlines,
			);
		}
		catch (\Exception $ex)
		{
			\Drupal::logger('nmu_news_headlines')->error($ex->getMessage());
		}
	}


	public function Story(int $year, int $id) {
		try {
			$story = [];
			$images = [];

			$query = $this->db->query("SELECT * FROM www_webadmin.cms_news WHERE ID=:id",
				[
					':id' => $id,
				]);
			$results = $query->fetchAll();

			$story['title'] = $results[0]->Title;
			$tmp_content = $results[0]->Content;
			$tmp_content = strip_tags($tmp_content, '<br><p><a><em><i><strong><b>'); //only allow certain tags in the html
      $tmp_content = $value = preg_replace('/(<[^>]*) class=("[^"]+"|\'[^\']+\')([^>]*>)/i', '$1$3', $tmp_content); //remove all classes
      $story['content'] = $value = preg_replace('/(<[^>]*) style=("[^"]+"|\'[^\']+\')([^>]*>)/i', '$1$3', $tmp_content); //remove all inline styles
			$story['author'] = $results[0]->Author;
			$story['authorPhone'] = $results[0]->AuthorPhone;
			$story['authorEmail'] = $results[0]->AuthorEmail;
			$story['authorTitle'] = $results[0]->AuthorTitle;
			$story['date'] = date("l j, Y", $results[0]->Date);
			$story['back'] = "/mc/news_archives/$year";

			$query = $this->db->query("SELECT * FROM www_webadmin.cms_news_images WHERE OwnerID=:id ORDER BY Position",
				[
					':id' => $id,
				]);
			$results = $query->fetchAll();
			foreach($results as $row) {
				$temp = [];
				$temp['filename'] = $row->Filename;
				$temp['title'] = $row->Title;
				$images[] = $temp;
			}
			$story['images'] = $images;


			return array(
				'#theme' => 'nmu_news_archives_story_template',
				'#story' => $story,
			);
		}
		catch (\Exception $ex)
		{
			\Drupal::logger('nmu_news_headlines')->error($ex->getMessage());
		}
	}
}

