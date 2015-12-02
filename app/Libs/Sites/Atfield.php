<?php namespace App\Libs\Sites;

use \App\Libs\Archive;

class Atfield extends Site {

	public $key = 'atfield';
	
	public $domain = 'http://www.jdlingyu.net/';
	
	public $cover_mode = false;
	
	public $charset = 'UTF-8';
	
	public function archive_id($url)
	{
		$archive_id = 0;
		if(preg_match('#'.$this->domain.'(\d+)/#', $url, $match))
		{
			$archive_id = $match[1];
		}
		return $archive_id;
	}
	
	public function archive_url($id)
	{
		return $this->domain.$id.'/';
	}
	
	public function list_url($pageno)
	{
		return ($pageno==1)? $this->domain : $this->domain.'page/'.$pageno.'/';
	}

	public function get_list_archives($list_url)
	{
		$result = curl($list_url, curl_options($this->domain));
		if(!$result['status']['flag']) return false;
	
		$listPQ = \phpQuery::newDocumentHTML($result['data']);
		$archives = $urls = array();
		
		// style 1
		$innerhtml = $listPQ['#postlist']->html();
		$pattern = '#<a.*?href="([^"]+)".*?<img.*?src="([^"]+)".*?alt="([^"]+)"#';
		if($innerhtml && preg_match_all($pattern, $innerhtml, $matches))
		{
			if(isset($matches[1]) && is_array($matches[1]))
			{
				foreach($matches[1] as $index=>$url)
				{
					if(!in_array($url, $urls))
					{
						$urls[] = $url;
						$archive = new Archive($url, $this);
						$archive->cover_mode($this->cover_mode, $matches[2][$index]);
						$archives[] = $archive;
					}
				}
			}
		}
		
		// style 2
		empty($innerhtml) AND $innerhtml = $listPQ['#content']['.entry-title']->html();
		$pattern = '#<a.*?href="([^"]+)".*?>(.+?)</a>#';
		if($innerhtml && preg_match_all($pattern, $innerhtml, $matches))
		{
			if(isset($matches[1]) && is_array($matches[1]))
			{
				foreach($matches[1] as $index=>$url)
				{
					if(!in_array($url, $urls))
					{
						$urls[] = $url;
						$archive = new Archive($url, $this);
						$archive->cover_mode($this->cover_mode);
						$archives[] = $archive;
					}
				}
			}
		}
		
		return $archives;
	}
	
	public function parse_archive(Archive $archive)
	{
		if($archive->cover_mode)
		{
			return [$this->get_image_by_cover($archive->cover)];
		}
		$result = curl($archive->url, curl_options($this->domain));
		$archivePQ = \phpQuery::newDocumentHTML($result['data']);
		
		$content = $archivePQ['#content'];
		$title = $content['.entry-title']->html();
		$imgs = $content['.entry-content']->html();
		if(preg_match_all('#'.$this->domain.'wp-content/[^"]*#', $imgs, $matches))
		{
			$archive->images = array_merge(array_unique($matches[0]));
		}
		if(!$archive->images)
		{
			$title = $content['.main-title']->html();
			$imgs = $content['.main-body']->html();
			if(preg_match_all('#<a[^>]+href="('.$this->domain.'wp-content/[^"]*)">#', $imgs, $matches))
			{
				$archive->images = array_merge(array_unique($matches[1]));
			}
		}
		
		if(strtolower($this->charset) != strtolower($GLOBALS['app_config']['charset']))
		{
			$archive->title = iconv(strtoupper($this->charset), strtoupper($GLOBALS['app_config']['charset']).'//IGNORE', $title);
		}
		$archive->title = $title;		
	}
	
}