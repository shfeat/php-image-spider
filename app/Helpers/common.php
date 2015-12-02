<?php
function is_int_ex($var)
{
	if(is_numeric($var) && is_int($var + 0))  return true;
	return false;
}

function filter_style($content, $div=true)
{
	if($div) $content = preg_replace('/<div[^>]*>|<\/div>/i','',$content);//去<div>
	$content = preg_replace("/style=.+?['|\"]/i",'',$content);//去除样式
	$content = preg_replace("/class=.+?['|\"]/i",'',$content);//去除样式
	$content = preg_replace("/id=.+?['|\"]/i",'',$content);//去除样式
	$content = preg_replace("/width=.+?['|\"]/i",'',$content);//去除样式
	$content = preg_replace("/height=.+?['|\"]/i",'',$content);//去除样式
	$content = preg_replace("/border=.+?['|\"]/i",'',$content);//去除样式
	return $content;
}

function get_file_ext($filename)
{
	$ext = substr(strrchr($filename, '.'), 1);
	return $ext? $ext : '';
}

function mkdir_ex($path, $mod='0777')
{
    if(!is_dir($path))
	{
        mkdir_ex(dirname($path), $mod);
        mkdir($path, $mod);
    }
}

function dump($var)
{
	$vars = func_get_args();
	foreach($vars as $var)
	{
		var_dump($var);
	}
	exit;
}
// ip in china
function random_ip()
{
	$ip_long = array(
		array('607649792', '608174079'), //36.56.0.0-36.63.255.255
		array('1038614528', '1039007743'), //61.232.0.0-61.237.255.255
		array('1783627776', '1784676351'), //106.80.0.0-106.95.255.255
		array('2035023872', '2035154943'), //121.76.0.0-121.77.255.255
		array('2078801920', '2079064063'), //123.232.0.0-123.235.255.255
		array('-1950089216', '-1948778497'), //139.196.0.0-139.215.255.255
		array('-1425539072', '-1425014785'), //171.8.0.0-171.15.255.255
		array('-1236271104', '-1235419137'), //182.80.0.0-182.92.255.255
		array('-770113536', '-768606209'), //210.25.0.0-210.47.255.255
		array('-569376768', '-564133889'), //222.16.0.0-222.95.255.255
	);
	$rand_key = mt_rand(0, 9);
	return long2ip(mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));
}

function curl($url, $options=array())
{
	$result = array();
	if( is_callable('curl_init') )
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		//curl_setopt($ch, CURLOPT_ENCODING, "gzip");
			
		// 超时
		$options['timeout'] = isset($options['timeout'])? intval($options['timeout']) : 10;
		curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeout']);
			
		// 返回
		if(isset($options['noreturn']) && $options['noreturn'])
		{
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
		}
		else
		{
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		}
			
		// 是否SSL
		if(isset($options['ssl']) && $options['ssl'])
		{
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);  //这两行一定要加，不加会报SSL 错误
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		}
			
		// http header
		if(isset($options['header']) && $options['header'])
		{
			if(!is_array($options['header']))
			{
				$options['header'] = array($options['header']);
			}
			$headers = array();
			foreach($options['header'] as $key=>$value)
			{
				if(is_int_ex($key))
				{
					$headers[] = $value;
				}
				else
				{
					$headers[] = $key.':'.$value;
				}
			}
			//curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}

		// 是否POST
		if( (isset($options['post']) && $options['post']) || (isset($options['get']) && !$options['get']) )
		{
			curl_setopt($ch, CURLOPT_POST, true);
			if(isset($options['data']) && $options['data'])
			{
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($options['data']));
			}
		}
			
		// 伪造来源
		if(isset($options['referer']) && $options['referer'])
		{
			curl_setopt($ch, CURLOPT_REFERER, $options['referer']);
		}
			
		// 用户代理
		if(isset($options['useragent']) && $options['useragent'])
		{
			curl_setopt($ch, CURLOPT_USERAGENT, $options['useragent']);
		}
			
		$resp = curl_exec($ch);
		$info = curl_getinfo($ch);
		$result = array('status' => $info, 'data' => $resp);
		$result['status']['flag'] = empty($resp)? false : true;
		$result['status']['flag'] = in_array($info['http_code'], array(0,400,401,403,404,408,410,500,502,503,504))? false : $result['status']['flag'];
		curl_close($ch);
	}
	else
	{
		if( (isset($options['post']) && $options['post']) || (isset($options['get']) && !$options['get']) )
		{
			$method = 'POST';
			$request['content'] = $data;
		}
		else
		{
			$method = 'GET';
		}
		$request['method'] = $method;
			
		if(isset($options['header']) && $options['header'])
		{

			if(!is_array($options['header']))
			{
				$options['header'] = array($options['header']);
			}
			$headers = '';
			foreach($options['header'] as $key=>$value)
			{
				if(is_int_ex($key))
				{
					$headers .= $value.';';
				}
				else
				{
					$headers .= $key.':'.$value.';';
				}
			}
			$headers = substr($headers, 0 , -1);
			$request['header'] = $headers;
		}
		$stream_context = stream_context_create(array('http'=>$request));
		$resp = @file_get_contents($url, FALSE, $stream_context);
		$result = array('status'=>array(), 'data'=>$resp);
		$result['status']['flag'] = empty($resp)? false:true;
	}

	return $result;
}

function curl_download($url, $filename, $options=array())
{
	$ch = curl_init();
	$fp = fopen($filename, 'wb');
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FILE, $fp);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	//curl_setopt($hander,CURLOPT_RETURNTRANSFER,true);//以数据流的方式返回数据,当为false是直接显示出来

	// 超时
	$options['timeout'] = isset($options['timeout'])? intval($options['timeout']) : 60;
	curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeout']);

	// 是否SSL
	if(isset($options['ssl']) && $options['ssl'])
	{
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);  //这两行一定要加，不加会报SSL 错误
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	}

	// http header
	if(isset($options['header']) && $options['header'])
	{
		if(!is_array($options['header']))
		{
			$options['header'] = array($options['header']);
		}
		$headers = array();
		foreach($options['header'] as $key=>$value)
		{
			if(is_int_ex($key))
			{
				$headers[] = $value;
			}
			else
			{
				$headers[] = $key.':'.$value;
			}
		}
		//curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}

	// 伪造来源
	if(isset($options['referer']) && $options['referer'])
	{
		curl_setopt($ch, CURLOPT_REFERER, $options['referer']);
	}

	// 用户代理
	if(isset($options['useragent']) && $options['useragent'])
	{
		curl_setopt($ch, CURLOPT_USERAGENT, $options['useragent']);
	}

	$resp = curl_exec($ch);
	$info = curl_getinfo($ch);
	$result = array('status' => $info, 'data' => $resp);
	$result['status']['flag'] = empty($resp)? false : true;
	$result['status']['flag'] = in_array($info['http_code'], array(0,400,401,403,404,408,410,500,502,503,504))? false : $result['status']['flag'];
	curl_close($ch);
	fclose($fp);
	return $result;
}

function curl_options($referer)
{
	$ip = random_ip();
	$header = array('CLIENT-IP' => $ip, 'X-FORWARDED-FOR' => $ip);
	return array(
		'referer' => $referer,
		'useragent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:42.0) Gecko/20100101 Firefox/42.0',
		'header' => $header
	);
}
