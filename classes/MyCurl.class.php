<?php

class MyCurl {
	private $response;
	private $response_meta_info;
    private $url;
    private $cookies;
	private $raw;
	private $encoding;
	private $user_agent;
	
	function __construct($url, $cookies='',  $encoding='UTF-8', $raw=false) {
    	$this->url = $url;
    	$this->cookies = $cookies;
    	$this->raw = $raw;
    	$this->encoding = $encoding;
    	$this->user_agent = $GLOBALS['config']->user_agent;
	}
	
	function getData()
	{
		if ($GLOBALS['config']->verbose>0) echo "( Downloading ".$this->url . ' )' . NL;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if ($this->raw==true) curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, array(&$this,'readHeader'));
		if ( !empty( $this->cookies ) ) curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Expect:"));
		curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
		if ($this->raw==true)
		{
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
			$this->response=curl_exec($ch);	
		}
		else
		{
			$this->response=mb_convert_encoding(curl_exec($ch),'HTML-ENTITIES', $this->encoding);
			//echo $this->response;
			//die();
		}
		if ( empty( $this->response ) ) {
			throw new Exception('ERROR: Can\'t download requested URL');
		}
		$headers = curl_getinfo($ch);
		if (!isset($this->response_meta_info)) $this->response_meta_info=array();
		$this->response_meta_info = array_merge($headers, $this->response_meta_info);
		curl_close($ch);
		if (is_bool($this->response)) {
			if ($this->response==false){
				throw new Exception('ERROR: No connection');
			} else {
				//null the response, because there are actually no data
				$this->response=null;
			}

		}
		return $this->response;
	}

	/**
	 * CURL callback function for reading and processing headers
	 * Override this for your needs
	 * 
	 * @param object $ch
	 * @param string $header
	 * @return integer
	 */
	private function readHeader($ch, $header) {
		//extracting example data: filename from header field Content-Disposition
		//echo $header;
		$filename = $this->extractCustomHeader('Content-Disposition: attachment; filename="', '".?\n', $header);
		if ($filename) {
			$this->response_meta_info['content_disposition'] = trim($filename);
			//echo $filename;
		}
		return strlen($header);
	}

	private function extractCustomHeader($start,$end,$header) {
		$pattern = '/'. $start .'(.*?)'. $end .'/';
		if (preg_match($pattern, $header, $result)) {
			return $result[1];
		} else {
			return false;
		}
	}
	
	function getHeaders() {
		return $this->response_meta_info;
	}
}

function curlHeaderCallback($resURL, $strHeader) {
	GLOBAL $filename;
	$pattern = '/Content-Disposition: attachment; filename=(.*?)\n/';
	if (preg_match($pattern, $strHeader, $result)) {
		$filename=trim($result);
	}
}

?>
