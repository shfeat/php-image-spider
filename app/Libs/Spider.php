<?php namespace App\Libs;

class Spider {

	/**
	 * @var \App\Libs\Sites\Site
	 */
	protected $site;
	
	protected $id;
	
	protected $config = array();
	
	protected $data = array();
	
	protected $data0 = array('from'=>0, 'done_archives'=>[], 'redo_archives'=>[], 'redo_list'=>[], 'error_log'=>[]);
	
	protected $log_types = array(
		'spider_start'		=> '[spider start]',
		'spider_done'		=> '[spider done]',
		'list_start'		=> '[list start]',
		'list_done'			=> '[list done]',
		'archive_start'		=> '[-archive start]',
		'archive_done' 		=> '[--archive done]',
		'image_start' 		=> '[----image start]',
		'image_done' 		=> '[----image done]',
		'image_error' 		=> '[----image error]',
	);
	
	public function __construct($key)
	{		
		$siteclass = 'App\\Libs\\Sites\\'.ucfirst($key);
		if(!class_exists($siteclass))
		{
			echo 'site '.$key." not supported\n";
			exit;
		}
		
		$this->site = new $siteclass;
		$this->id = date('Ymd_His');
		$this->config['data_file'] = ROOTPATH.'storage/data/'.$key.'.php';
		$this->config['spider_log'] = $this->site->key.'_'.$this->id.'.log';
		$this->config['save_folder'] = $this->site->key.'/';
		$this->get_data();
	}
	
	/**
	 * get file database
	 */
	protected function get_data()
	{
		if(file_exists($this->config['data_file']))
		{
			include_once $this->config['data_file'];
		}
		else
		{
			$data = $this->data0;
			$this->set_config($data);
		}
		return $this->data = $data;
	}
	
	/**
	 * set file database
	 * @param string $data
	 */
	protected function set_data($data=null)
	{
		if(is_null($data))
		{
			$data = $this->data;
		}
		$data = $data? var_export($data, true) : var_export($this->data0, true);
		$string = "<?php\n\$data = ".$data.";\n?>";
		file_put_contents($this->config['data_file'], $string);
	}
	
	/**
	 * write log
	 * @param string $type
	 * @param string $line
	 */
	protected function log($type, $line='')
	{
		$type = isset($this->log_types[$type])? $this->log_types[$type] : "[unknown type : $type]";
		$line OR $line = date('Y-m-d H:i:s')."\n";
		$line = $type.' '.$line;
		(substr($line, -1) == "\n") OR $line .= "\n";
		file_put_contents($this->config['spider_log'], $line, FILE_APPEND);
	}
	
	/**
	 * download images of an archive
	 * @param Archive $archive
	 */
	protected function download_archive_images(Archive $archive)
	{
		$archive_start_at = microtime(true);
		$this->site->parse_archive($archive);
		$all_count = count($archive->images);
		$done_count = 0;
		$digit = strlen($all_count);
		$digit = $digit > 3? $digit : 3;
		$this->log('archive_start', date('Y-m-d H:i:s', $archive_start_at).' '.$archive->id." $all_count images start");
		for($i=0; $i<$all_count; $i++)
		{
			if(PHP_OS == 'WINNT')
			{
				$title = iconv(strtoupper($archive->site->charset), strtoupper($GLOBALS['app_config']['win_charset']), $archive->title);
			}
			if ($archive->cover_mode)
			{ 
				$archive_folder = '';
				$file_name = $archive->id.'_'.$title.'.'.get_file_ext($archive->images[$i]);
				// we need a shorter $file_id to write in log because $file_name would be too long sometimes. 
				$file_id = $archive->id.'_.'.get_file_ext($archive->images[$i]);
			}
			else 
			{
				$archive_folder = $archive->id.'_'.$title.'/';
				$file_name = $archive->id.'_'.sprintf("%0{$digit}d", $i+1).'.'.get_file_ext($archive->images[$i]);
				$file_id = $file_name;
			}
			$save_path = ROOTPATH.'storage/images/'.$this->config['save_folder'].$archive_folder;
			mkdir_ex($save_path);
			$file = $save_path.$file_name;
			if(file_exists($file))
			{
				$done_count++;
				continue;
			}
			
			// to do: detect file size & type
			$image_start_at = microtime(true);
			$result = curl_download($archive->images[$i], $file);
			$image_end_at = microtime(true);
			if($result['status']['flag'])
			{
				$line = date('Y-m-d H:i:s', $image_end_at).' '.$file_id.' ok '.sprintf('%.1fs', $image_end_at-$image_start_at);
				$this->log('image_done', $line);
				$done_count++;
			}
			else
			{
				unlink($file);
				$line = date('Y-m-d H:i:s', $image_end_at).' '.$file_id.' error '.sprintf('%.1fs', $image_end_at-$image_start_at);
				$this->log('image_error', $line);
			}
			// usleep(mt_rand(200, 500)); 
		}
		
		$archive_end_at = microtime(true);
		if($done_count < $all_count)
		{
			$line = date('Y-m-d H:i:s', $archive_end_at).' '.$archive->id." $done_count/$all_count"
				.' done(need redo) '.sprintf('%.1fs', $archive_end_at-$archive_start_at);
			$this->log('archive_done', $line);
			return false;
		}
		else
		{
			$line = date('Y-m-d H:i:s', $archive_end_at).' '.$archive->id." $done_count/$all_count"
				.' done '.sprintf('%.1fs', $archive_end_at-$archive_start_at);
			$this->log('archive_done', $line);
			return true;
		}
	}
	

	/**
	 * let appetency run
	 * @param int $start
	 * @param int $end
	 */
	public function run($start=0, $end=0)
	{
		$start = (intval($start) > 0)? intval($start) : 1;
		$end = (intval($end) >= $start)? intval($end) : PHP_INT_MAX;
		
		$notfound_list_no = 0;
		$notfound_list_count = 0;
		$spider_archives_count = 0;
		$spider_done_archives_count = 0;
		$redo_list = array();
		$redo_log = array();
		
		$spider_start_at = date('Y-m-d H:i:s');
		$this->log('spider_start');
		for($list_no=$start; $list_no<=$end; $list_no++)
		{
			$archives = $this->site->get_list_archives($this->site->list_url($list_no));
			$list_done_archives_count = 0;
			if (empty($archives))
			{
				if($notfound_list_count > 5)
				{//no archives for 5 times? maybe list page reach the end
					break;
				}
				$notfound_list_no = $list_no;
				$notfound_list_count++;
			}
			else
			{
				$list_start_at = date('Y-m-d H:i:s');
				if($notfound_list_count)
				{
					$redo_list[] = $notfound_list_no;
					$notfound_list_count = 0;
				}
				foreach($archives as $archive)
				{
					if(!$archive->id)
					{
						continue;
					}
						
					if($archive->id <= $this->data['from'] && empty($this->data['redo_list']))
					{// all jobs have been done before, so break 2
						break 2;
					}
						
					if(in_array($archive->id, $this->data['done_log']))
					{
						$list_done_archives_count++;
						continue;
					}
					
					$spider_archives_count++;
					if($this->download_archive_images($archive))
					{
						$this->data['done_log'][] = $archive->id;
						$list_done_archives_count++;
						$spider_done_archives_count++;
					}
					else 
					{
						$this->data['redo_log'][] = $archive->id; 
					}
					$this->set_data();
				}
			}
			if($archives)
			{
				$list_end_at = date('Y-m-d H:i:s'); 
				$list_archives_count = count($archives);
				$line = $list_end_at." list page No.$list_no : $list_done_archives_count/$list_archives_count done "
					.sprintf('%.1fs', $list_end_at-$list_start_at);
				$this->log('list_done', $line);
				
				for($i=0; $i<$list_archives_count; $i++)
				{
					unset($archives[$i]);// wish it works
				}
			}
		}
		
		$spider_end_at = date('Y-m-d H:i:s');
		$line = $spider_end_at." $spider_done_archives_count/$spider_archives_count done. start at $spider_start_at, end at $spider_end_at";
		$this->log('spider_done', $line);
		
		rsort($this->data['done_log']);
		rsort($this->data['redo_log']);
		$this->set_data();
	}
}

