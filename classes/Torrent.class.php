<?php

class Torrent
{
	public $torrent_id;
	public $tracker_obj;
	public $tracker_name;
	public $title;
	public $id;
	public $torrent_file_url;
	public $details_url;
	public $data_file_hash;
	public $data;
	public $filename;
	public $original_filename;
	public $data_filename;
	public $data_file_dir;
	public $files;
	public $size;
	public $dvd;

	function create( $tracker, $title, $torrent_file_url, $details_url )
	{
		$this->tracker_name = $tracker->name;
		$this->tracker_obj = $tracker;
		$this->title = $title;
		$this->torrent_file_url = $torrent_file_url;
		$this->details_url = $details_url;
		$this->data_file_dir = $this->tracker_obj->directories->datafiles_dir;
		$this->torrent_id = $this->getTorrentId();
	}
	
	function getTorrentId()
	{
		if ( !empty( $this->tracker_obj ) ) $tracker = $this->tracker_obj;
		if ( !empty( $this->torrent_file_url ) )
		{
			preg_match( trim( $tracker->regexp->torrent_id ), $this->torrent_file_url, $result);
			return $result[1];
		}
	}

	function getFromDB ($whereclause='')
	{
		$res = getMediagicDBResult("SELECT * FROM torrents ".$whereclause." ORDER BY timestamp ASC;");
		if (mysql_num_rows($res) != 0) {
			$row = mysql_fetch_assoc($res);
			$this->id = $row['id'];
			$this->torrent_id = $row['torrentid'];
			$this->tracker_name = $row['tracker'];
			$this->title = $row['title'];
			$this->data_file_hash = $row['data_file_hash'];
			$this->filename = $row['torrent_filename'];
			$this->data_filename = $row['data_file_name'];
			$this->data_file_dir = $row['data_file_dir'];
		}
	}

	function isInDB()
	{

		$count_in_db = mysql_num_rows(getMediagicDBResult("SELECT * FROM torrents WHERE torrentid=".$this->torrent_id." AND tracker='".$this->tracker_name."';"));
		return ($count_in_db>0);
	}
	
	function downloadTorrentFile()
	{
		if (!empty ($this->tracker_obj)) $tracker = $this->tracker_obj;
		if (!empty($this->torrent_file_url)) $download_link = $this->torrent_file_url;
		
		try
		{
			$mc = new MyCurl($download_link, $tracker->cookies, $tracker->encoding, true);
			$this->data = $mc->getData();
			$headers = $mc->getHeaders();
			if ( isset($headers['content_disposition']) )
			{
				$this->original_filename = $headers['content_disposition'];
				$len = strrpos($headers['content_disposition'], '.');
				if (!$len) $len = strlen($headers['content_disposition']);
				$this->filename = $this->tracker_obj->directories->torrents_dir
					. $this->tracker_name . '.' . substr($headers['content_disposition'], 0, $len) 
					. '.' . $this->torrent_id . '.torrent';
			}
			else throw new Exception('Can\'t download torrent file properly');
			$this->getInfoFromFile();
		} catch (Exception $e) {
			throw $e;
		}
	}

	function getInfoFromFile() {
		$parsedTorrent = $this->parse_torrent($this->data);
		$this->data_file_hash = $parsedTorrent['info_hash'];
		$this->data_filename = $parsedTorrent['info']['name'];
		if (isset($parsedTorrent['info']['files']) && is_array($parsedTorrent['info']['files']))
		{
			$size = 0;
			foreach( $parsedTorrent['info']['files'] as $k=>$v )
			{
				$name = '';
				foreach( $v['path'] as $kk=>$vv )
					$name .= $vv.'/';
				$this->files[] = array( 'path' => substr($name,0,-1), 'size' => $v['length']);
				$size += $v['length'];
			}
			$this->size = $size;
		} else {
			$this->size = $parsedTorrent['info']['length'];
		}
	}

	function isNewByFilename()
	{
		return (! file_exists( $this->filename ));
	}

	function saveToFS()
	{
		if ( @ $fp = fopen( $this->filename, "w" ) )
		{
			fwrite( $fp, $this->data );
			fclose( $fp );			
		}	else {
			throw new Exception('ERROR: Can\'t save file to the directory. Please check permissions.');
		}
	}
	
	function saveToDBAsNew()
	{
		$q = "INSERT into torrents (torrentid, tracker, title, data_file_hash, torrent_filename, data_file_dir, data_file_name, timestamp) VALUES (".
				$this->torrent_id.", '".
				addslashes($this->tracker_name)."', '".
				addslashes($this->title)."', '".
				addslashes($this->data_file_hash)."', '".
				addslashes($this->filename)."', '".
				addslashes($this->data_file_dir)."', '".
				addslashes($this->data_filename)."', ".
				time().");";
				
		if (! getMediagicDBResult($q) )
		throw new Exception('ERROR: Can\'t update database');
	}
	
	function parse_torrent($s)
	{
		static $str;
		//if ($s == false ) $str = $this->data;
		//else 
		$str = $s;
	
	    //echo $str{0};
	
		if ($str{0} == 'd') {
		   $str = substr($str,1);
		   $ret = array();
		   while (strlen($str) && $str{0} != 'e') {
			  $key = $this->parse_torrent($str);
			  if (strlen($str) == strlen($s)) break; // prevent endless cycle if no changes made
			  if (!strcmp($key, "info")) {
				  $save = $str;
			  }
	//          echo ".",$str{0};
			  $value = $this->parse_torrent($str);
			  if (!strcmp($key, "info")) {
				  $tosha = substr($save, 0, strlen($save) - strlen($str));
				  $ret['info_hash'] = sha1($tosha);
			  }
	
			  // process hashes - make this stuff an array by piece
			  if (!strcmp($key, "pieces")) {
				  $value = explode("====",
							 substr(
							   chunk_split( $value, 20, "===="),
							   0, -4
							 )
						   );
			  };
			  $ret[$key] = $value;
		   }
		   $str = substr($str,1);
		   return $ret;
		} else if ($str{0} == 'i') {
	//       echo "_";
		   $ret = substr($str, 1, strpos($str, "e")-1);
		   $str = substr($str, strpos($str, "e")+1);
		   return $ret;
		} else if ($str{0} == 'l') {
	//       echo "#";
		   $ret = array();
		   $str = substr($str, 1);
		   while (strlen($str) && $str{0} != 'e') {
			  $value = $this->parse_torrent($str);
			  if (strlen($str) == strlen($s)) break; // prevent endless cycle if no changes made
			  $ret[] = $value;
		   }
		   $str = substr($str,1);
		   return $ret;
		} else if (is_numeric($str{0})) {
	//       echo "@";
		   $namelen = substr($str, 0, strpos($str, ":"));
		   $name = substr($str, strpos($str, ":")+1, $namelen);
		   $str = substr($str, strpos($str, ":")+1+$namelen);
		   return $name;
		}
	}
}
?>