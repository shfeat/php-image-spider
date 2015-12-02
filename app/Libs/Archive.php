<?php namespace App\Libs;

use App\Libs\Sites\Site;

/**
 * Archive or Post? The same thing.
 */
class Archive {
	
	/**
	 * @var Site
	 */
	public $site;
	public $id;
	public $url;
	public $cover_mode = false;
	public $cover;
	public $title;
	public $html = '';
	public $images = array();
	
	public function __construct($url, Site $site)
	{
		$this->site = $site;
		$this->id = $this->site->archive_id($url);
		$this->url = $url;
	}
	
	public function cover_mode($cover_model, $cover='')
	{
		if($cover_model)
		{
			$this->cover_mode = true;
			$this->cover = $cover;
		}
	}
	
}