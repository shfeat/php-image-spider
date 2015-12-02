<?php namespace App\Libs\Sites;

use \App\Libs\Archive;

abstract class Site {
	
	/**
	 * site name 
	 * @var string
	 */
	public $key;
	
	/**
	 * site domain
	 * @var string
	 */
	public $domain;
	
	/**
	 * sometimes, we can directly get source image url from list page, and one archive one image at the same time(eg. *booru)
	 * set this true and complete $this->get_image_by_cover() in this situation.
	 * @var boolean
	 */
	public $cover_mode = false;
	
	/**
	 * html charset 
	 * @var string
	 */
	public $charset = 'UTF-8';
	
	/**
	 * get archive id from url
	 * @param string $url
	 * @return string $archive_id
	 */
	abstract public function archive_id($url);
	
	/**
	 * get archive url with archive id
	 * @param string $id
	 * @return string $url
	 */
	abstract public function archive_url($id);
	
	/**
	 * get list page url with page No.
	 * @param int $pageno
	 * @return string $list_url
	 */
	abstract public function list_url($pageno);
	
	/**
	 * get archives in list page
	 * @param string $list_url
	 * @return array <Archive>
	 */
	abstract public function get_list_archives($list_url);
	
	/**
	 * parse archive page html and get image urls to be downloaded
	 * @param Archive $archive
	 * @return array $urls
	 */
	abstract public function parse_archive(Archive $archive);
	
	/**
	 * convert cover image url into archive image url 
	 * @param string $cover
	 */
	protected function get_image_by_cover($cover)
	{}
	
}