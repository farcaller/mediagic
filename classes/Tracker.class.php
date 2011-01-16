<?php

class Tracker
{
	public $enable;
	public $search;
	public $name;
	public $type;
	public $minimum_number_of_seeds=100;
	public $cookies='';
	public $encoding='UTF-8';
	public $hostname='';
	public $filters;
	public $directories;
	public $regexp;
	public $xpath;
	public $scrubbers;
	public $logo;

	public function createTrackerByXMLData($tracker_xml)
	{
		if ( isset( $tracker_xml->search ))
		{
			$this->search = $tracker_xml->search;
			$parsed_url = parse_url(trim($tracker_xml->search->url));
			$this->hostname = $parsed_url['scheme'].'://'.$parsed_url['host'];
		}
		else throw new Exception('Variable "search_url" for this tracker is not defined. Please check corresponding XML file');

		if ( $attrs=$tracker_xml->attributes() )
		{
			if (!empty( $attrs['enable'] ) ) $this->enable = trim($attrs['enable']);
			
			if (!empty( $attrs['name'] ) ) $this->name = trim($attrs['name']);
			else $this->name = $parsed_url['host'];

			if (!empty( $attrs['encoding'] ) ) $this->encoding = trim($attrs['encoding']);
			
			if (!empty( $attrs['type'] ) ) $this->type = trim($attrs['type']);
		}

		if ( isset( $tracker_xml->minimum_number_of_seeds ))
			$this->minimum_number_of_seeds = trim($tracker_xml->minimum_number_of_seeds);

		if ( isset( $tracker_xml->regexp ))
			$this->regexp = $tracker_xml->regexp;

		if ( isset( $tracker_xml->cookies ) && count( $tracker_xml->cookies ) > 0 )
		{
			foreach ($tracker_xml->cookies[0] as $key=>$val)
				$this->cookies .= trim($key).'='.trim($val).';';
			$this->cookies = substr($this->cookies, 0, -1);
		}

		if ( isset( $tracker_xml->xpath ) )
			$this->xpath = $tracker_xml->xpath;
			
		if ( isset ( $tracker_xml->scrubbers ) )
			$this->scrubbers = $tracker_xml->scrubbers;

		if ( isset( $tracker_xml->filters ) )
			$this->filters = $tracker_xml->filters;

		if ( isset( $tracker_xml->directories ) )
			$this->directories = $tracker_xml->directories;
		else throw new Exception('Variable "directories" for this tracker is not defined. Please check corresponding XML file');

		if ( isset( $tracker_xml->logo ) )
			$this->logo = dirname($_SERVER["PHP_SELF"]) . '/' . $tracker_xml->logo;

	}
	
	public function getTorrents($search='')
	{
		$torrents = array();
		$last_page = false;
		$page = trim($this->search->start_page_number);
		while ($last_page == false)
		{
			$search_url = trim($this->search->url);
			$search_url = str_replace('%title',urlencode($search),$search_url);
			$search_url = str_replace('%page',$page,$search_url);
			$my_curl = new MyCurl($search_url, $this->cookies, $this->encoding);
			try {
				$data = $my_curl -> getData();
			} catch (Exception $e) {
				throw $e;
			}

			if ($GLOBALS['config']->verbose>0)
				echo SP;
			$data = str_replace( "<br />", " ", $data ); // hack to avoid a problem with getting values near <br />
			$xml = getXMLByDataHTML( $data );

			$search_results = $xml->xpath( $this->xpath->search_results );
	
			if ( !is_array($search_results) )
				throw new Exception('Downloaded page doesn\'t contain any search results',2);
	
			if ( count($search_results) == 0 ) $last_page = true;
	
			foreach ($search_results as $key=>$value)
			{
				$block = false;
				
				$title = $value->xpath($this->xpath->title);
				$title = trim($title[0]);
	
				$seeds_count = $value->xpath($this->xpath->seeds_count);
				$seeds_count = ( isset( $seeds_count[0] ) ? $seeds_count[0] : 0 );
	
				if (isset($this->xpath->date))
				{
					$date_attrs = $this->xpath->date->attributes();
					$date = $value->xpath($this->xpath->date);
					$date = trim($date[0]);
					
					if (isset ($date_attrs['regexp']))
					{
						setlocale(LC_TIME, trim($date_attrs['locale']));					
						preg_match(trim($date_attrs['regexp']), $date, $res);
						$hours=$res[ strpos( $date_attrs['format'], 'h' )+1 ];
						$minutes=$res[ strpos( $date_attrs['format'], 'm' )+1 ];
						$day=$res[ strpos( $date_attrs['format'], 'D' )+1 ];
						$month=$res[ strpos( $date_attrs['format'], 'M' )+1 ];
						$year=$res[ strpos( $date_attrs['format'], 'Y' )+1 ];
						$int_month = (int)$month;
						if ((string)$month != (string)$int_month)
						{
							for ($i=1; $i<=12; $i++)
							{
								if (mb_ucwords($month) == mb_ucwords( strftime ( '%b', mktime(12, 0, 0, $i, 1) ) ) ||
									mb_ucwords($month) == mb_ucwords( strftime ( '%B', mktime(12, 0, 0, $i, 1) ) ) )
									$int_month = $i;
							}
						}
						$timestamp = mktime($hours, $minutes, 0, $int_month, $day, $year);
					} else $timestamp = strtotime($date);

					if ( isset($this->filters->age) && (time() - $timestamp) > ($this->filters->age)*24*60*60 )
						$block = "The file is too old";
				}
				
	
				$size = $value->xpath($this->xpath->size);
				preg_match("/(\d*\.*\d*)\s*(\D*)/", $size[0], $size_res);
				if ($size_res['2'] == 'GB')
					$size = $size_res[1]*1024;
				elseif ($size_res['2'] == 'kB' || $size_res['2'] == 'KB')
					$size = $size_res[1]/1024;
				else
					$size = $size_res[1];
					

				foreach ($this->filters->decline as $k=>$v)
				{
					if (mb_stripos($title, (string)$v)!==false){
						$block = "The title of the torrent is in the black list";
					}
				}
	
				if ( isset ($this->filters->max_size) && $size > $this->filters->max_size )
					$block = "The size of the file is bigger than maximum size";
	
				if ( isset ($this->filters->min_size) && $size < $this->filters->min_size )
					$block = "The size of the file is less than minimum size";
						
				if ( $seeds_count < $this->minimum_number_of_seeds )
				{
					$block = "The number of seeds is less than necessary amount";
					$last_page = true;
				}

				if ( $block == false )
				{
					$torrent_file_url = $value->xpath( $this->xpath->torrent_file_url );
					$torrent_file_url = $torrent_file_url[0]->attributes();
					$torrent_file_url = $torrent_file_url['href'];
					if ( strpos( $torrent_file_url, 'http://' ) !== 0 )
						$torrent_file_url = $this->hostname . '/' . $torrent_file_url;

					$details_url = $value->xpath( $this->xpath->details_url );
					$details_url = $details_url[0]->attributes();
					$details_url = $details_url['href'];
					if ( strpos( $details_url, 'http://' ) !== 0 )
						$details_url = $this->hostname . '/' . $details_url;
	
					
					$torrent = new Torrent();
					$torrent -> create( $this, $title, $torrent_file_url, $details_url );
					if ($torrent->isInDB())
						$block = 'Already downloaded';
				}

				if ($GLOBALS['config']->verbose>1 || ( $GLOBALS['config']->verbose>0 && $block == false))
					echo $title.NL.$size.' MB - '.(!empty($seeds_count)?$seeds_count:0).' seeds'.NL;
			
				if ( $block == false )
					$torrents[] = $torrent;
				elseif ($GLOBALS['config']->verbose>1)
					echo $block.NL;

				if ($GLOBALS['config']->verbose>1 || ( $GLOBALS['config']->verbose>0 && $block == false))
					echo SP;
			}
			$page += trim($this->search->page_increament);
		}
		return $torrents;
	}

}

//
?>
