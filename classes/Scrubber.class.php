<?php

class Scrubber
{
	public $enable = false;
	public $encoding = 'UTF-8';
	public $name;
	public $type = 'Video';
	public $priority = 10;
	public $search;
	public $cookies = '';
	public $hostname;
	public $check_countries = false;
	public $unknown = 'Unknown';

	function createScrubberByXMLData($scrubber_info)
	{
		$scrubber_xml = $scrubber_info['xml'];
		$scrubber_filename = $scrubber_info['filename'];
		
				
		if ( $attrs=$scrubber_xml->attributes() )
		{
			if (!empty( $attrs['enable'] ) && $attrs['enable'] == 1) $this->enable = true;
			
			if (!empty( $attrs['name'] ) ) $this->name = trim($attrs['name']);
			else $this->name = $scrubber_filename;

			if (!empty( $attrs['encoding'] ) ) $this->encoding = trim($attrs['encoding']);
			
			if (!empty( $attrs['type'] ) ) $this->type = trim($attrs['type']);
		}
		
		$parsed_url = parse_url(trim($scrubber_xml->search->page[0]->url));
		$this->hostname = $parsed_url['scheme'].'://'.$parsed_url['host'];
		
		if ( isset( $scrubber_xml->priority ) )
			$this->priority = trim($scrubber_xml->priority);

		if ( isset( $scrubber_xml->search ) )
			$this->search = $scrubber_xml->search;
		else throw new Exception('The search path is missing. Skipping.');

		if ( isset( $scrubber_xml->unknown ) )
			$this->unknown = $scrubber_xml->unknown;
		
		if ( isset( $scrubber_xml->check_countries ) && $scrubber_xml->check_countries==1)
			$this->check_countries = true;

		if ( isset( $scrubber_xml->cookies ) && count( $scrubber_xml->cookies ) > 0 )
		{
			foreach ($scrubber_xml->cookies[0] as $key=>$val)
				$this->cookies .= trim($key).'='.trim($val).';';
			$this->cookies = substr($this->cookies, 0, -1);
		}

	}
	
	function getInfo($case_array)
	{
		if ($GLOBALS['config']->verbose>0) echo 'Downloading detailed information from ' . $this->name . '.' . NL;
		$result = array();
		foreach ($this->search->page as $key=>$page)
		{
			
			if ( count( $result ) > 0 )
				$case_array = array_merge($case_array, $result);
			$search_url = trim($page->url);
			try
			{
				foreach ( $case_array as $var_name => $var_value )
				{
					if ( strpos($search_url, '%'.$var_name) !== false)
					{
						$search_url = str_replace('%'.$var_name, urlencode( str_replace( '&', '&amp;', $var_value ) ), $search_url);
						$result = array_merge($this->processPage(0, $page, $search_url),$result);
					}
				}
			} catch (Exception $e) {
				if ($GLOBALS['config']->verbose>1) echo $e->getMessage() . NL;
			}
		}

			//print_r($result);
			//die();
		return $result;
	}
	
	protected function processPage($xml='', $page='', $url=false)
	{
		if ($url==false)
		{
			$page_xml_arr = $xml->xpath( $page->url_xpath );
			if ( ! is_array( $page_xml_arr ) ) throw new Exception('Can\'t get any data with the xpath from the xml file');
			$url = $page_xml_arr[0];
			if ( isset ($page->url_regexp) && ! empty ($page->url_regexp))
			{
				if (preg_match( $page->url_regexp, $url->asXML(), $preg_res))
					$url = $preg_res[1];
			} 
			$url = trim( $url );
	
			if ( strpos( $url, 'http://' ) !== 0 )
				$url = $this->hostname . '/' . $url;
		}

		$my_curl = new MyCurl($url, $this->cookies, $this->encoding);
		$data = $my_curl -> getData();
		$newxml = getXMLByDataHTML( $data );

		if (isset( $page->sub_page ))
		{
			foreach ($page->sub_page as $key=>$sub_page)
			{
				try
				{
					$result = $this->processPage($newxml, $sub_page);
				} catch (Exception $e) {
					if ($GLOBALS['config']->verbose>1) echo $e->getMessage() . NL;
				}
			}
		}
		
		if (isset( $page->info ))
		{
			foreach ($page->info as $key=>$info)
			{
				$xpathed_xml = '';
				if (isset( $info->xpath ))
				{
					$xpathed_xml_arr = $newxml->xpath( $info->xpath );
					if (!is_array($xpathed_xml_arr)) throw new Exception('Can\'t get any data with the xpath from the xml file');
					foreach ($xpathed_xml_arr as $k=>$v)
						$xpathed_xml .= $v->asXML()."\n";
				} else $xpathed_xml = $newxml->asXML();

				$xpathed = br2nl( $xpathed_xml );
				$xpathed = strip_tags( $xpathed );
				$xpathed = html_entity_decode( $xpathed );
				$xpathed = str_replace("&nbsp", "", $xpathed);
				$xpathed = trim( preg_replace( "/(\xC2\xA0)*/sm", "", $xpathed ) );
				$xpathed = trim( preg_replace( "/\s*\n+/sm", "\n", $xpathed ) );

				//print_r($xpathed);
				//die();
				foreach ($info->regexp as $k=>$regexp)
				{
					if ( $attrs = $regexp->attributes() )
					{
						$name = trim($attrs['name']);
						if (preg_match( $regexp, $xpathed, $preg_res))
							$result[$name] = trim($preg_res[1]);
					}
				}
			}
		}
		
		return (isset( $result )) ? $result : array();
	}
}

?>