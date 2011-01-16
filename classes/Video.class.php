<?php

class Video
{
	public $id;
	public $title;
	public $plot;
	public $genres; //
	public $directors; //
	public $year = 0;
	public $audio;
	public $video;
	public $translation;
	public $cast; //
	public $companies; //
	public $countries; //
	public $rating;
	public $userrating;
	public $coverfile;
	public $length = 0;
	public $filename;
	public $file_hash;
	public $fan_art;
	public $scrubbers;
	public $scrubber;
	public $part;
	public $timestamp;
	public $scrub_title;
	public $file_size;

	function create($title, $filename, $part, $scrub_title, $file_hash=false, $file_size=0, $scrubber_id=0, $scrubber_rel=false, $scrubber_def=false, $force=false)
	{
		$this->filename = $filename;
		if ( $filename == false || ! $this->getFromDBByFilename()) {
			$this->title = $title;
			$this->file_size = $file_size;
			$this->part = $part;
			$this->scrubber = $scrubber_def;
			$this->scrub_title = $scrub_title;
			if ( $file_hash ) $this->file_hash = $file_hash;
			if ( $scrubber_rel != false && $scrubber_id != 0 ) $this->scrubbers[] = array('scrubber' => $scrubber_rel, 'scrubber_id' => $scrubber_id);
			return true;
		} else return $force;
	}

	function getIDByFilename()
	{
		$res = getMediagicDBResult("SELECT * FROM video WHERE filename='".addslashes($this->filename)."' ORDER BY timestamp ASC;");
		if (mysql_num_rows($res) != 0) {
			$row = mysql_fetch_assoc($res);
			$this->id = $row['id'];
			return true;
		} else return false;
	}
	
	function getFromDBByFilename()
	{
		return $this->getFromDB("WHERE filename='".addslashes($this->filename)."'");
	}
	
	function setID($id)
	{
		$this->id = $id;
	}
	
	function getFromDBByID()
	{
		return $this->getFromDB("WHERE id=".$this->id."");
	}


	function getFromDB ($whereclause='')
	{
		$res = getMediagicDBResult("SELECT * FROM video ".$whereclause." ORDER BY timestamp ASC;");
		if (mysql_num_rows($res) != 0) {
			$row = mysql_fetch_assoc($res);
			$this->id = $row['id'];
			$this->title = $row['title'];
			$this->plot = $row['plot'];
			$this->year = $row['year'];
			$this->audio = $row['audio'];
			$this->video = $row['video'];
			$this->translation = $row['translation'];
			$this->rating = $row['rating'];
			$this->userrating = $row['userrating'];
			$this->coverfile = $row['coverfile'];
			$this->length = $row['length'];
			$this->filename = $row['filename'];
			$this->file_hash = $row['file_hash'];
			$this->file_size = $row['file_size'];
			$this->fan_art = $row['fan_art'];
			$this->part = $row['part'];
			$this->timestamp = $row['timestamp'];
			$this->scrubber = $row['scrubber'];
			$this->scrub_title = $row['scrub_title'];
			
			$genres_res = getMediagicDBResult("SELECT genres.genre FROM genres INNER JOIN videogenres".
						" ON genres.id=videogenres.genre_id AND videogenres.video_id=".$this->id.";");
			$directors_res = getMediagicDBResult("SELECT directors.director FROM directors INNER JOIN videodirectors".
						" ON directors.id=videodirectors.director_id AND videodirectors.video_id=".$this->id.";");
			$cast_res = getMediagicDBResult("SELECT cast.actor FROM cast INNER JOIN videocast".
						" ON cast.id=videocast.actor_id AND videocast.video_id=".$this->id.";");
			$companies_res = getMediagicDBResult("SELECT companies.company FROM companies INNER JOIN videocompanies".
						" ON companies.id=videocompanies.company_id AND videocompanies.video_id=".$this->id.";");
			$countries_res = getMediagicDBResult("SELECT countries.country FROM countries INNER JOIN videocountries".
						" ON countries.id=videocountries.country_id AND videocountries.video_id=".$this->id.";");
			$scrubbers_res = getMediagicDBResult("SELECT scrubber, scrubber_id FROM videoscrubbers".
						" WHERE video_id=".$this->id.";");

			if (mysql_num_rows($genres_res) != 0)
				while ($genre = mysql_fetch_assoc($genres_res))
					$this->genres[]=$genre['genre'];

			if (mysql_num_rows($directors_res) != 0)
				while ($director = mysql_fetch_assoc($directors_res))
					$this->directors[]=$director['director'];
					
			if (mysql_num_rows($genres_res) != 0)
				while ($actor = mysql_fetch_assoc($cast_res))
					$this->cast[]=$actor['actor'];
					
			if (mysql_num_rows($companies_res) != 0)
				while ($company = mysql_fetch_assoc($companies_res))
					$this->companies[]=$company['company'];
					
			if (mysql_num_rows($countries_res) != 0)
				while ($country = mysql_fetch_assoc($countries_res))
					$this->countries[]=$country['country'];

			if (mysql_num_rows($scrubbers_res) != 0)
				while ($scrubber = mysql_fetch_assoc($scrubbers_res))
					$this->scrubbers[]=$scrubber;
			return true;
		} else return false;
	}

	function setScrubber($scrubber_name)
	{
		$this->scrubber = $scrubber_name;
	}
	
	function setPart($part)
	{
		$this->part = $part;
	}

	function getInfo()
	{
		if ( ! isset( $GLOBALS['all_scrubbers'] ) )
		{
			$scrubbers = getScrubbers();
			if (count($scrubbers) > 0)
			{
				foreach ($scrubbers as $key=>$scrubber_info)
				{
					try
					{
						$scrubber = new Scrubber();
						$scrubber -> createScrubberByXMLData($scrubber_info);
						if ($scrubber->enable == true || $scrubber->test == true)
							$GLOBALS['all_scrubbers'][$scrubber->name] = $scrubber;
					} catch (Exception $e) {
						if ($config->verbose>1) echo $e->getMessage() . NL . SP;
					}
				}
				if ( (!isset( $GLOBALS['all_scrubbers'] )) || count( $GLOBALS['all_scrubbers'] ) == 0 )
					throw new Exception('There are no active scrubbers available');
			} else throw new Exception('There are no scrubbers available');
		}
		
		if ( empty( $this->scrubber ) && count( $GLOBALS['all_scrubbers'] ) > 0 )
		{
			foreach ( $GLOBALS['all_scrubbers'] as $k => $v)
				if ( ( empty( $this->scrubber ) || (int)$v->priority > (int) $GLOBALS['all_scrubbers'][$this->scrubber]->priority) )
					$this->scrubber = $k;
		}

		if ( ! empty( $this->scrubber ) )
		{
			if ( is_array( $this->scrubbers ) )
			{
				$scrubber_id = 0;
				foreach ($this->scrubbers as $k=>$v)
					if ($v['scrubber'] == $this->scrubber)
						$scrubber_id = $v['scrubber_id'];
				if ($scrubber_id != 0)
				{
					if (!isset ( $GLOBALS['all_videos'][$this->scrub_title] ) )
						$GLOBALS['all_videos'][$this->scrub_title] = $GLOBALS['all_scrubbers'][$this->scrubber]->getInfo( array( 'id' => $scrubber_id ) ); 
					// нужно ловить exception в getInfoById, и если он выпадает - то искть по другому скрабберу
				} else {
					if (!isset ( $GLOBALS['all_videos'][$this->scrub_title] ) )
						$GLOBALS['all_videos'][$this->scrub_title] = $GLOBALS['all_scrubbers'][$this->scrubber]->getInfo( array( 'title' => $this->scrub_title ) ); 
				}
			} else {
				if (!isset ( $GLOBALS['all_videos'][$this->scrub_title] ) )
					$GLOBALS['all_videos'][$this->scrub_title] = $GLOBALS['all_scrubbers'][$this->scrubber]->getInfo( array( 'title' => $this->scrub_title ) ); 
			}
		}

		
		$data = $GLOBALS['all_videos'][$this->scrub_title];
		$current_scrubber = $GLOBALS['all_scrubbers'][$this->scrubber];
		
		if ( isset( $data['id'] ) ) 
		{
			$this->scrubbers[] = array('scrubber' => $this->scrubber, 'scrubber_id' => $data['id']);
			$scrubber_id = $data['id'];
		}
		if ( isset( $data['plot'] ) ) $this->plot = $data['plot']; else $this->plot = trim( $current_scrubber->unknown );
		if ( isset( $data['year'] ) ) $this->year = $data['year'];
		if ( isset( $data['audio'] ) ) $this->audio = $data['audio']; else $this->audio = trim( $current_scrubber->unknown );
		if ( isset( $data['video'] ) ) $this->video = $data['video']; else $this->video = trim( $current_scrubber->unknown );
		if ( isset( $data['translation'] ) ) $this->translation = $data['translation']; else $this->translation = trim( $current_scrubber->unknown );
		if ( isset( $data['rating'] ) ) $this->rating = $data['rating']; else $this->rating = trim( $current_scrubber->unknown );
		if ( isset( $data['userrating'] ) ) $this->userrating = $data['userrating']; else $this->userrating = trim( $current_scrubber->unknown );
		if ( isset( $data['length'] ) )
		{
			preg_match("/(\d{0,3})\D+(?:(\d{0,3})\D+)?(?:(\d{0,3}))?/mi", $data['length'], $preg_time);
			$this->length  = ( ! empty($preg_time[2]) ) ? $preg_time[1] * 60 + $preg_time[2] : $preg_time[1];
			$this->length = (int)$this->length.NL;
		}

		if ( isset( $data['countries'] ) )
		{
			$countries_array = maxExplode($data['countries']);
			if ( isset( $GLOBALS['all_scrubbers'][$this->scrubber]->check_countries ) && $GLOBALS['all_scrubbers'][$this->scrubber]->check_countries == 1 )
			{
				foreach ($countries_array as $country)
				{
					$country_name = '';
					foreach ($GLOBALS['country_list'] as $country_orig)
						if ( mb_strtolower($country_orig) == mb_strtolower($country))
							$country_name = $country_orig;
					if ($country_name!='' && ( !isset($this->countries) || !in_array($country_name, $this->countries)))
						$this->countries[] = $country_name;
					elseif ($country_name=='' && ( !isset($this->companies) || !in_array($country, $this->companies)))
						$this->companies[] = $country;
				}				
			} else $this->countries = $countries_array;
		} 
		
		if ( ! isset( $this->countries ) ) $this->countries[] = trim( $current_scrubber->unknown );
		
		
		if ( isset( $data['companies'] ) )
		{
			if (isset ($this->companies))
				$this->companies = array_merge( $this->companies, maxExplode( $data['companies'] ) );
			else
				$this->companies = maxExplode( $data['companies'] );
		}
		
		if ( ! isset ($this->companies) ) $this->companies[] = trim( $current_scrubber->unknown );
		
		if ( isset( $data['genres'] ) ) $this->genres = mb_ucfirst_arr( maxExplode( $data['genres'] ) );  else $this->genres[] = trim( $current_scrubber->unknown );
		if ( isset( $data['directors'] ) ) $this->directors = maxExplode( $data['directors'] ); else $this->directors[] = trim( $current_scrubber->unknown );
		if ( isset( $data['cast'] ) ) $this->cast = maxExplode( $data['cast'] ); else $this->cast[] = trim( $current_scrubber->unknown );
		
		$email = $GLOBALS['config']->email;
		$email_attrs = $email->attributes();
		
		if ( isset( $data['coverfile'] ) ) $this->coverfile = $data['coverfile'];

		if ( !isset( $this->coverfile ) && isset( $data['coverimage'] ) && ( $this->part != 1 || trim( $email_attrs['enable'] ) == 1 ) )
		{
			if ( strpos( $data['coverimage'], 'http://' ) !== 0 )
				$data['coverimage'] = $current_scrubber->hostname . '/' . $data['coverimage'];
			if ($GLOBALS['config']->verbose>1) echo 'Downloading a coverart for the video.' . NL;
			try
			{
				$parsed_url = parse_url( $data['coverimage'] );
				$cookies = ( $parsed_url['scheme'].'://'.$parsed_url['host'] == $current_scrubber->hostname) ? $current_scrubber->cookies : '';
				$mc = new MyCurl($data['coverimage'], $cookies, '', true);
				$cover_art_data = $mc->getData();					

				$image_tmp_path = $GLOBALS['config']->directories->tmp . $current_scrubber->name . '.' . $scrubber_id .'.image';

				if ( @ $fp = fopen($image_tmp_path, "w" ) )
				{
					fwrite( $fp, $cover_art_data );
					fclose( $fp );			
				}	else {
					throw new Exception('ERROR: Can\'t save image file to temporary folder ' . $GLOBALS['config']->directories->tmp . ' Please check permissions.');
				}

				if ( ! @ $image_meta = getimagesize($image_tmp_path) )
				{
					if (! @ unlink( $image_tmp_path ) )
						throw new Exception('ERROR: Can\'t download image file.' . NL . 'Can\'t remove temporary image '. $image_tmp_path);
					else throw new Exception('ERROR: Can\'t download image file.');
				}
				
				if ( isset( $GLOBALS['config']->coverart->ratio ) )
				{
					if ($GLOBALS['config']->verbose>1) echo 'Checking resolution of the image.' . NL;
					$ratio = $image_meta[0]/$image_meta[1];
					if (isset( $GLOBALS['config']->coverart->tolerable_limit ))
					{
						$allowed_ratio = array( 
								'min' => (float)$GLOBALS['config']->coverart->ratio - (float)$GLOBALS['config']->coverart->tolerable_limit, 
								'max' => (float)$GLOBALS['config']->coverart->ratio + (float)$GLOBALS['config']->coverart->tolerable_limit);
					} else {
						$allowed_ratio = array( 
								'min' => (float)$GLOBALS['config']->coverart->ratio, 
								'max' => (float)$GLOBALS['config']->coverart->ratio);
					}
					
					if ( $ratio < $allowed_ratio['min'] || $ratio > $allowed_ratio['max'] )
					{
						if ($GLOBALS['config']->verbose>1) echo 'The ratio of the image is not correct.' . NL
																	. 'Changing the resolution of the image to meet expectations' . NL;
						try
						{
							if ( @ gd_info() )
							{
								switch ( $image_meta[2] )
								{
									case (IMAGETYPE_GIF):
										$ip = @imagecreatefromgif($image_tmp_path);
										break;
									case (IMAGETYPE_JPEG):
										$ip = @imagecreatefromjpeg($image_tmp_path);
										break;
									case (IMAGETYPE_PNG):
										$ip = @imagecreatefrompng($image_tmp_path);
										break;
									case (IMAGETYPE_BMP):
										$ip = @imagecreatefrombmp($image_tmp_path);
										break;
								}
								
								if (! $ip ) throw new Exception('Can\'t change the resolution of the image.');
								
								if ( $ratio < $allowed_ratio['min'] )
									$resolution = array( 	'width' => $image_meta[1] * (float) $GLOBALS['config']->coverart->ratio, 
															'height' => $image_meta[1] );
								else 
									$resolution = array( 	'width' => $image_meta[0],
															'height' => $image_meta[0] / (float) $GLOBALS['config']->coverart->ratio );
								
								$tmp_image = imagecreatetruecolor($resolution['width'], $resolution['height']);
								
								$color = imagecolorallocate($tmp_image, 
															hexdec( substr( $GLOBALS['config']->coverart->background, 0, 2) ),
															hexdec( substr( $GLOBALS['config']->coverart->background, 2, 2) ),
															hexdec( substr( $GLOBALS['config']->coverart->background, 4, 2) ) );
								
								imagefilledrectangle( $tmp_image, 0, 0, $resolution['width'], $resolution['height'], $color);
								
								imagecopy($tmp_image, $ip, ( $resolution['width'] - $image_meta[0] ) / 2 , ( $resolution['height'] - $image_meta[1] ) / 2 , 0, 0, $image_meta[0], $image_meta[1]);								
								
								if (! @ imagejpeg($tmp_image, $image_tmp_path.'.jpeg'))
									throw new Exception('Can\'t change the resolution of the image.');
								
								if (! @ unlink( $image_tmp_path ) )
								{
									$image_tmp_path = $image_tmp_path.'.jpeg';
									throw new Exception('Can\'t remove temporary file.');
								}
								$image_tmp_path = $image_tmp_path.'.jpeg';
							} else throw new Exception('PHP_GD is not installed. Can\'t change the resolution of the image.');
						} catch (Exception $e) {
							if ($GLOBALS['config']->verbose>1) echo $e->getMessage() . NL;
						}
					}
				}

				if ($GLOBALS['config']->verbose>1) echo 'Saving the image.' . NL;
				
				$image_path = $GLOBALS['config']->directories->coverarts . $current_scrubber->name . '.' . basename($this->filename) . '.' . $scrubber_id .image_type_to_extension(exif_imagetype($image_tmp_path));
				if (! @ rename ($image_tmp_path, $image_path) )
				{
					if (! @ unlink( $image_tmp_path ) )
						throw new Exception('ERROR: Can\'t save the image file to the folder ' . $GLOBALS['config']->directories->coverarts . ' Please check permissions.' . NL . 'Can\'t remove temporary image '. $image_tmp_path);
					else throw new Exception('ERROR: Can\'t save the image file to the folder ' . $GLOBALS['config']->directories->coverarts . ' Please check permissions.');
				}
				$this->coverfile = $image_path;
				$GLOBALS['all_videos'][$this->scrub_title]['coverfile'] = $image_path;
			} catch (Exception $e) {
				if ($GLOBALS['config']->verbose>1) echo $e->getMessage() . NL;
			}
		}
	}
	
	function saveToDB()
	{
		if ($GLOBALS['config']->verbose>1) echo 'Updating database for [ ' . $this->filename . ' ]' . NL;
		if ( $this->getIDByFilename() == false )
		{
			$q = "INSERT into video ( 	filename, plot, rating, userrating, length, year,
										coverfile, audio, video, file_hash, file_size, translation, 
										fan_art, part, scrubber, scrub_title, 
										title, timestamp) VALUES ('".
				addslashes($this->filename)."', '".
				addslashes($this->plot)."', '".
				addslashes($this->rating)."', '".
				addslashes($this->userrating)."', ".
				addslashes($this->length).", ".
				addslashes($this->year).", '".
				addslashes($this->part != 1?$this->coverfile:'')."', '".
				addslashes($this->audio)."', '".
				addslashes($this->video)."', '".
				addslashes($this->file_hash)."', ".
				addslashes($this->file_size).", '".
				addslashes($this->translation)."', '".
				addslashes($this->fan_art)."', ".
				addslashes($this->part ? $this->part : 0).", '".
				addslashes($this->scrubber)."', '".
				addslashes($this->scrub_title)."', '".
				addslashes($this->title)."', ".
				time().");";
			
			if (! getMediagicDBResult($q) ) throw new Exception('ERROR: Can\'t update database');
			$this->getIDByFilename();
		} 
		else
		{
			$q = "UPDATE video SET 
					filename='".addslashes($this->filename)."', 
					plot='" . addslashes($this->plot) . "', 
					rating='" . addslashes($this->rating) . "', 
					userrating='" . addslashes($this->userrating) . "', 
					length=" . addslashes($this->length) . ", 
					year=" . addslashes($this->year) . ",
					coverfile='" . addslashes($this->part != 1?$this->coverfile:'') . "', 
					audio='" . addslashes($this->audio) . "', 
					video='" . addslashes($this->video) . "', " .
					( ! empty( $this->file_hash ) ? "file_hash='" . addslashes($this->file_hash) . "', " : "").
					"video=" . (int)$this->file_size . ", 
					translation='" . addslashes($this->translation) . "', 
					fan_art='" . addslashes($this->fan_art) . "', 
					part=" . addslashes($this->part ? $this->part : 0) . ", 
					scrubber='" . addslashes($this->scrubber) . "', 
					scrub_title='" . addslashes($this->scrub_title) . "', 
					title='" . addslashes($this->title) . "', 
					timestamp=" . time() . "
					where id=" . $this->id .";";			
			if (! getMediagicDBResult($q) ) throw new Exception('ERROR: Can\'t update database');		
		}

	$this->updateRelTable( @$this->cast, 'mediagic', 'cast', 'videocast', 'actor', 'actor_id');
	$this->updateRelTable( @$this->genres, 'mediagic', 'genres', 'videogenres', 'genre', 'genre_id');
	$this->updateRelTable( @$this->directors, 'mediagic', 'directors', 'videodirectors', 'director', 'director_id');
	$this->updateRelTable( @$this->companies, 'mediagic', 'companies', 'videocompanies', 'company', 'company_id');
	$this->updateRelTable( @$this->countries, 'mediagic', 'countries', 'videocountries', 'country', 'country_id');

		//print_r($this);
		if ( count( $this->scrubbers ) > 0 )
		{
			foreach ($this->scrubbers as $scrubber)
			{
				$q = "SELECT * from videoscrubbers WHERE video_id=" . $this->id . " AND scrubber_id=" . $scrubber['scrubber_id'] ." AND scrubber='" . $scrubber['scrubber'] . "';";
				$res = getMediagicDBResult( $q );
				if (mysql_num_rows($res) == 0)
				{
					$q = "INSERT into videoscrubbers (video_id, scrubber, scrubber_id) VALUES (" .  $this->id . ", '" .  $scrubber['scrubber'] . "', ". $scrubber['scrubber_id'] .");";
					getMediagicDBResult( $q );
				}

			}
		}
	}

	function updateExtDB()
	{
		$unkn = trim( $GLOBALS['all_scrubbers'][$this->scrubber]->unknown );
		if ( isset( $GLOBALS['config']->mythtv ) )
		{
			$myth_config = $GLOBALS['config']->mythtv;
			$myth_attrs = $myth_config->attributes();
			if ( trim( $myth_attrs['enable'] ) == 1 )
			{
				preg_match("/\d+\.?\d*/", str_replace(",", ".", $this->userrating), $myth_userrating);
				if ( $GLOBALS['config']->verbose > 0 ) echo 'Updating MythTV database' . NL;
				$myth_plot = '';
				if ( implode( "/", $this->genres ) != $unkn )
					$myth_plot .= implode( " / ", $this->genres);
				if (implode( ", ", $this->companies ) != $unkn || implode( ", ", $this->countries ) != $unkn)
					$myth_plot .= " ( ";
				if ( implode( ", ", $this->countries ) != $unkn )
					$myth_plot .= implode( ", ", $this->countries );
				if (implode( ", ", $this->companies ) != $unkn && implode( ", ", $this->countries ) != $unkn)
					$myth_plot .= ", ";
				if ( implode( ", ", $this->companies ) != $unkn )
					$myth_plot .= implode( ", ", $this->companies );
				if (implode( ", ", $this->companies ) != $unkn || implode( ", ", $this->countries ) != $unkn)
					$myth_plot .= " )";
					
				$myth_plot .= "\n" . (  ( mb_strlen($this->plot) > 680 ) ? mb_substr($this->plot, 0, 680) . '...' : $this->plot  ) . "\n\n";
				if ( implode( ", ", $this->cast ) != $unkn )
				{
					foreach ($this->cast as $k=>$v)
					{
						if ( $k<10 ) $myth_cast[] = $v;
					}
					$myth_plot .= 'В ролях: ' . implode( ", ", $myth_cast ) . "\n";
				}
				$myth_plot .= "Перевод: " . $this->translation . "\n";
				$myth_plot .= "Звук: " . $this->audio . "\n";
				$myth_plot .= "Видео: " . $this->video . "\n";
				
				$q = 'SELECT * from videometadata WHERE filename=\''.addslashes($this->filename).'\';';
				$res = getMythTVDBResult ( $q );
				if (mysql_num_rows($res) == 0)
				{
					$q = 'INSERT INTO videometadata (' .
					'title, director, plot, rating, year, userrating,' .
					'length, coverfile, browse, showlevel, filename) VALUES (\'' .
					addslashes( $this->title ) . '\', \'' . addslashes( implode( "/", $this->directors ) ). '\', \''.
					addslashes( $myth_plot ) . '\', \'' . addslashes( $this->rating ) . '\', ' .
					$this->year . ', ' .
					(isset($myth_userrating[0]) ? $myth_userrating[0] : 0) . ', ' . addslashes( $this->length ) . ', \'' .
					addslashes( $this->part != 1?$this->coverfile:'' ) . '\', 1, 1, \'' . addslashes( $this->filename ) . '\')';
					$res = getMythTVDBResult ( $q );
				} else {
					$q = 'UPDATE videometadata SET ' .
					'title=\'' . addslashes( $this->title ) . '\', '.
					'director=\'' . addslashes( implode( " / ", $this->directors ) ) . '\', '. 
					'plot=\'' . addslashes( $myth_plot ) . '\', '.
					'rating=\'' .  addslashes( $this->rating ) . '\', '.
					'year=' . $this->year . ', '.
					'showlevel=1, '.
					'userrating=' . ( isset($myth_userrating[0]) ? $myth_userrating[0] : 0 ) . ', '.
					'length=' . addslashes( $this->length ) . ', '.
					'coverfile=\'' . addslashes( $this->part != 1?$this->coverfile:'' ) . '\', '.
					'browse=1 WHERE filename=\'' . addslashes( $this->filename ) . '\';';
					//echo $q;
					$res = getMythTVDBResult ( $q );
				}
								
				$q = 'SELECT * from videometadata WHERE filename=\''.addslashes($this->filename).'\';';
				$res = getMythTVDBResult ( $q );
				if (mysql_num_rows($res) != 0)
				{
					$row = mysql_fetch_assoc($res);
					$this->updateRelTable( @$this->cast, 'mythtv', 'videocast', 'videometadatacast', 'cast', 'idcast', 'intid', 'idvideo', $row['intid']);
					$this->updateRelTable( @$this->genres, 'mythtv', 'videogenre', 'videometadatagenre', 'genre', 'idgenre', 'intid', 'idvideo', $row['intid']);
					$this->updateRelTable( @$this->countries, 'mythtv', 'videocountry', 'videometadatacountry', 'country', 'idcountry', 'intid', 'idvideo', $row['intid']);
				}
			}
		}
	}

	function updateRelTable( $data_array, $db_name, $table_name, $rel_table_name, $var_name, $var_id_name, $id_name='id', $video_id_name='video_id', $video_id=false)
	{
		if ( count( $data_array ) > 0 )
		{
			if ( $video_id === false ) $video_id = $this->id;
			foreach ($data_array as $value)
			{
				try
				{
					$q = "SELECT " . $id_name . " from " . $table_name . " WHERE " . $var_name . "='" . addslashes( $value ) . "';";
					switch ($db_name)
					{
						case 'mediagic':
						$res = getMediagicDBResult( $q );
						break;
						case 'mythtv':
						$res = getMythTVDBResult( $q );
						break;
					}
					if (mysql_num_rows($res) != 0)
					{
						$row = mysql_fetch_assoc($res);
						$var_id = $row[$id_name];
					} else {
						$q = "INSERT INTO " . $table_name . " (" . $var_name . ") VALUES ('" . addslashes( $value ) . "');";
						switch ($db_name)
						{
							case 'mediagic':
							$res = getMediagicDBResult( $q );
							break;
							case 'mythtv':
							$res = getMythTVDBResult( $q );
							break;
						}
						
						
						$q = "SELECT " . $id_name . " from " . $table_name . " WHERE " . $var_name . "='" . addslashes( $value ) . "';";
						switch ($db_name)
						{
							case 'mediagic':
							$res = getMediagicDBResult( $q );
							break;
							case 'mythtv':
							$res = getMythTVDBResult( $q );
							break;
						}
	
						if (mysql_num_rows($res) != 0)
						{
							$row = mysql_fetch_assoc($res);
							$var_id = $row[$id_name];
						}
					}
					
					$q = "SELECT * from " . $rel_table_name . " WHERE " . $video_id_name . "=" . $video_id . " AND " . $var_id_name . "=" . $var_id .";";
					switch ($db_name)
					{
						case 'mediagic':
						$res = getMediagicDBResult( $q );
						break;
						case 'mythtv':
						$res = getMythTVDBResult( $q );
						break;
					}
					if (mysql_num_rows($res) == 0)
					{
						$q = "INSERT into " . $rel_table_name . " (" . $video_id_name . ", " . $var_id_name . ") VALUES (" .  $video_id .", ".$var_id .");";
						switch ($db_name)
						{
							case 'mediagic':
							$res = getMediagicDBResult( $q );
							break;
							case 'mythtv':
							$res = getMythTVDBResult( $q );
							break;
						}
					}
				} catch (Exception $e) {
					if ($GLOBALS['config']->verbose>1) echo $e->getMessage() . NL;
				}
			}
		}
	}
	
	function createImage($width, $height, $filename='', $reflection=true)
	{
		$final_image = false;
		if ( !empty( $this->coverfile ) )
		{
			if ( @ $image_meta = getimagesize($this->coverfile) )
			{
				try
				{
					if ( @ gd_info() )
					{
						switch ( $image_meta[2] )
						{
							case (IMAGETYPE_GIF):
								$ip = @imagecreatefromgif($this->coverfile);
								break;
							case (IMAGETYPE_JPEG):
								$ip = @imagecreatefromjpeg($this->coverfile);
								break;
							case (IMAGETYPE_PNG):
								$ip = @imagecreatefrompng($this->coverfile);
								break;
							case (IMAGETYPE_BMP):
								$ip = @imagecreatefrombmp($this->coverfile);
								break;
						}
						
						if (! $ip ) throw new Exception('Can\'t change the resolution of the image.');

						
						$hd = false;
						
						$video_res = array();

						if ( isset( $this->video ) )
						{
							preg_match("/(\d{2,4})\s?(?:x|х)\s?(\d{2,4})/i", $this->video, $video_res);
							if ( (int)$video_res[1] >= 1200) $hdtv = true;
						}
						
						if ( !isset( $this->video ) || empty( $video_res[1] ) )
							if ( mb_stripos($this->scrub_title, 'HDTV') || mb_stripos($this->scrub_title, 'BD RIP') || mb_stripos($this->scrub_title, 'BD-RIP') || mb_stripos($this->scrub_title, 'BDRip') || mb_stripos($this->scrub_title, 'HD-DVD') || mb_stripos($this->scrub_title, 'HDDVD') || mb_stripos($this->scrub_title, '720p') || mb_stripos($this->scrub_title, '1080p') )
								$hdtv = true;
						
						if ($hdtv == true)
						{						

							$case_image_meta = getimagesize( dirname($_SERVER["PHP_SELF"]) . '/images/bluray.png' );

							$base_width =  $case_image_meta[0];
							if ( $reflection == true )
								$base_height =  $case_image_meta[1] * 1.2;
							else 
								$base_height =  $case_image_meta[1];
							$blank_image = imagecreatetruecolor( $base_width, $base_height );
							$color = imagecolorallocate($blank_image, 255, 255, 255); 
							imagefilledrectangle( $blank_image, 0, 0, $base_width, $base_height, $color);


							$resolution = array( 	'width' => $case_image_meta[0]*0.935,
													'height' =>$case_image_meta[1]*0.864 );
							$tmp_image = imagecreatetruecolor($resolution['width'], $resolution['height']);


							imagecopyresampled( $tmp_image, $ip, 0, 0, 0, 
								($image_meta[1]-$case_image_meta[1]*0.864*$image_meta[0]/$case_image_meta[0]*0.935)/2,
								$case_image_meta[0]*0.935,
								$case_image_meta[1]*0.864,
								$image_meta[0],
								$case_image_meta[1]*0.864*($image_meta[0]/($case_image_meta[0]*0.935)) );

							$case_image = imagecreatefrompng( dirname($_SERVER["PHP_SELF"]) . '/images/bluray.png');
							imagealphablending($case_image, true); // setting alpha blending on
							imagesavealpha($case_image, true);

							$flare_image = imagecreatefrompng( dirname($_SERVER["PHP_SELF"]) . '/images/blurayflare.png');
							imagealphablending($flare_image, true); // setting alpha blending on
							imagesavealpha($flare_image, true);

							imagecopymerge_alpha($blank_image, $case_image, 0, 0, 0, 0, $case_image_meta[0], $case_image_meta[1], 100);
							imagecopymerge_alpha($blank_image, $tmp_image, $case_image_meta[0]*0.02, $case_image_meta[1]*0.1 , 0, 0, $resolution['width'], $resolution['height'], 100);
							imagecopymerge_alpha($blank_image, $flare_image, 0, 0 , 0, 0, $case_image_meta[0], $case_image_meta[1], 20);

							if ( $reflection == true )
							{
								$image_copy = imagecreatetruecolor($case_image_meta[0], $case_image_meta[1]*1.2);
								imagecopy( $image_copy, $blank_image, 0, 0, 0, 0, $case_image_meta[0], $case_image_meta[1]); 
								
								for ($i = 0; $i <  $case_image_meta[1]*0.2; $i++)
								{
									imagecopymerge($blank_image, $image_copy, 0, $case_image_meta[1]*0.992+$i, 0, $case_image_meta[1]*0.992-$i, $case_image_meta[0], 1, round(($case_image_meta[1]*0.2-$i)));
								}
								imagedestroy($image_copy);
							}
						} else {
							$case_image_meta = getimagesize( dirname($_SERVER["PHP_SELF"]) . '/images/vidcase.png' );
							$base_width =  $case_image_meta[0];
							if ( $reflection == true )
								$base_height =  $case_image_meta[1] * 1.2;
							else 
								$base_height =  $case_image_meta[1];
							$case_image_meta = getimagesize( dirname($_SERVER["PHP_SELF"]) . '/images/vidcase.png' );

							$blank_image = imagecreatetruecolor( $base_width, $base_height );
							$color = imagecolorallocate($blank_image, 255, 255, 255); 
							imagefilledrectangle( $blank_image, 0, 0, $base_width, $base_height, $color);

							$resolution = array( 	'width' => $case_image_meta[0]*0.775,
													'height' => $image_meta[1]*$case_image_meta[0]*0.775/$image_meta[0] );
							$tmp_image = imagecreatetruecolor($resolution['width'], $resolution['height']);
							imagecopyresampled( $tmp_image, $ip, 0, 0, 0, 0, $resolution['width'], $resolution['height'],  $image_meta[0], $image_meta[1] );

							$case_image = imagecreatefrompng( dirname($_SERVER["PHP_SELF"]) . '/images/vidcase.png');
							imagealphablending($case_image, true); // setting alpha blending on
							imagesavealpha($case_image, true);

							$flare_image = imagecreatefrompng( dirname($_SERVER["PHP_SELF"]) . '/images/flare.png');
							imagealphablending($flare_image, true); // setting alpha blending on
							imagesavealpha($flare_image, true);

							imagecopymerge_alpha($blank_image, $case_image, 0, 0, 0, 0, $case_image_meta[0], $case_image_meta[1], 100);
							imagecopymerge_alpha($blank_image, $tmp_image, $case_image_meta[0]*0.14, round(( $case_image_meta[1] - $resolution['height'] ) / 2 ) , 0, 0, $resolution['width'], $resolution['height'], 100);
							imagecopymerge_alpha($blank_image, $flare_image, 0, 0 , 0, 0, $case_image_meta[0], $case_image_meta[1], 20);
							if ( $reflection == true )
							{
								$image_copy = imagecreatetruecolor($case_image_meta[0], $case_image_meta[1]*1.2);
								imagecopy( $image_copy, $blank_image, 0, 0, 0, 0, $case_image_meta[0], $case_image_meta[1]); 
								
								for ($i = 0; $i <  $case_image_meta[1]*0.2; $i++)
								{
									imagecopymerge($blank_image, $image_copy, 0, $case_image_meta[1]*0.98+$i, 0, $case_image_meta[1]*0.98-$i, $case_image_meta[0], 1, round(($case_image_meta[1]*0.2-$i)));
								}
								imagedestroy($image_copy);
							}
						}
						
						$prefinal_image = imagecreatetruecolor($width, $height);
						imagecopyresampled($prefinal_image, $blank_image, 0, 0, 0, 0, $width, $height,  $base_width, $base_height);

						if ( $filename )
						{
							$final_image =  @ imagejpeg( $prefinal_image, $filename );
						} else {
							ob_start();
	
							if (! @ imagejpeg($prefinal_image))
								throw new Exception('Can\'t change the resolution of the image.');
	
							$final_image = ob_get_contents();
	
							ob_end_clean();
						}

						imagedestroy($ip);
						imagedestroy($tmp_image);
						imagedestroy($case_image);
						imagedestroy($flare_image);
						imagedestroy($prefinal_image);

					} else throw new Exception('PHP_GD is not installed. Can\'t change the resolution of the image.');
				} catch (Exception $e) {
					if ($GLOBALS['config']->verbose>1) echo $e->getMessage() . NL;
				}
			}
		}
		return $final_image;
	}	
	
	function notifyMe($files='', $torrent='')
	{
		if ( isset( $GLOBALS['config']->email ) )
		{
			$email = $GLOBALS['config']->email;
			
			$disk = dirname( $this->filename );
			while ( ! file_exists( $disk ) )
				$disk = dirname( $disk );

			$unkn = trim( $GLOBALS['all_scrubbers'][$this->scrubber]->unknown );
			
			$genres = implode( " / ", $this->genres);
			if ($genres == $unkn) $genres = '';
			$countries = implode( ", ", $this->countries );
			if ($countries == $unkn) $countries = ''; 
			$directors = implode( " / ", $this->directors );
			if ( $directors == $unkn ) $directors = '';
			if ( implode( ", ", $this->cast ) != $unkn )
			{
				foreach ($this->cast as $k=>$v)
				{
					if ( $k<10 ) $cast_arr[] = $v;
				}
				$cast = implode( ", ", $cast_arr );
			}
			
			if ( !empty($this->year) )
			{
				if ( !empty($countries) )
					$year = '(' . $this->year . ')';
				else $year = $this->year;
			}
			
			$final_image = $this->createImage(200, 300);

			$tracker = $torrent->tracker_obj;			
			$cid = md5( time() . $this->scrub_title );

			$body = getTemplate('newvideo.html');
			
			$body = str_ireplace( '%title%', $this->scrub_title, $body );
			if ( $final_image != false )
				$body = str_ireplace( '%cover_image%', '<img src="cid:'.$cid.'/cover.jpg">', $body );
			else $body = str_ireplace( '%cover_image%', '&nbsp', $body );
			$body = str_ireplace( '%genres%', $genres, $body );
			$body = str_ireplace( '%year%', $year, $body );
			$body = str_ireplace( '%directors%', $directors, $body );
			$body = str_ireplace( '%countries%', $countries, $body );
			if ( $this->plot != $unkn ) $body = str_ireplace( '%plot%', $this->plot, $body );
			else $body = str_ireplace( '%plot%', '', $body );
			if ( !empty( $cast ) ) $body = str_ireplace( '%cast%', $cast, $body );
			else $body = str_ireplace( '%cast%', '', $body );
			$body = str_ireplace( '%tracker_link%', '<a href="' . $torrent->details_url . '"><img src="cid:'.$cid.'/tracker.jpg" border="0"></a>', $body );
			$body = str_ireplace( '%google_link%', '<a href="http://www.google.ru/search?ie=utf-8&oe=utf-8&q=' . urlencode( iconv( $GLOBALS['all_scrubbers'][$this->scrubber]->encoding, 'UTF-8//IGNORE', $this->scrub_title ) ) . '"><img src="cid:'.$cid.'/google.jpg" border="0"></a>', $body );
			$body = str_ireplace( '%kinopoisk_link%', '<a href="http://www.kinopoisk.ru/index.php?x=0&y=0&kp_query=' . urlencode( iconv( $GLOBALS['all_scrubbers'][$this->scrubber]->encoding, 'CP1251//IGNORE', $this->scrub_title ) ) . '"><img src="cid:'.$cid.'/kp.jpg" border="0"></a>', $body );
			$body = str_ireplace( '%youtube_link%', '<a href="http://www.youtube.com/results?search_type=&aq=f&search_query=' . urlencode( iconv( $GLOBALS['all_scrubbers'][$this->scrubber]->encoding, 'UTF-8//IGNORE', $this->scrub_title ) ) . '"><img src="cid:'.$cid.'/youtube.jpg" border="0"></a>', $body );
			if ( $this->video != $unkn ) $body = str_ireplace( '%video%', 'Видео: ' . $this->video, $body );
			else $body = str_ireplace( '%video%', '', $body );
			if ( $this->audio != $unkn ) $body = str_ireplace( '%audio%', 'Звук: ' . $this->audio, $body );
			else $body = str_ireplace( '%audio%', '', $body );
			if ( $this->translation != $unkn ) $body = str_ireplace( '%translation%', 'Перевод: ' . $this->translation, $body );
			else $body = str_ireplace( '%translation%', '', $body );
			if ( $this->length != 0 ) $body = str_ireplace( '%length%', $this->length . ' мин', $body );
			else $body = str_ireplace( '%length%', '', $body );
			if ($this->userrating != $unkn) $body = str_ireplace( '%rating%', 'Рейтинг: ' . $this->userrating, $body );
			else $body = str_ireplace( '%rating%', '', $body );
			$body = str_ireplace( '%size%', humanSize($torrent->size), $body );
			$body = str_ireplace( '%free_space%', humanSize(disk_free_space($disk)) . '</nobr> of <nobr>' . humanSize(disk_total_space($disk)) . '</nobr> is free', $body );

			preg_match('/<\!--files_block_start-->(.*)<\!--files_block_end-->/ms', $body, $files_block);

			if ( is_array($files) && count($files)>1 )
			{
				preg_match('/<\!--file_start-->(.*)<\!--file_end-->/ms', $body, $file_inst);
				$files_list = '';
				foreach ($files as $file)
					$files_list .=  str_ireplace( '%file_size%', humanSize($file['size']), str_ireplace( '%file_title%', getTitleFromFileName($file['path']), $file_inst[1] ) );
				$body = preg_replace('/<\!--files_block_start-->(.*)<\!--files_block_end-->/ms', $files_list, $body);
			} else
				$body = preg_replace('/<\!--files_block_start-->(.*)<\!--files_block_end-->/ms','', $body);
			$body = str_ireplace( '%hash%', '#' . $this->file_hash , $body );
			$subject = $email->subject;
			$attachments[] = array( 'type' => 'raw', 'name' => 'cover.jpg', 'data' => $final_image, 'cid' => $cid, 'mime' => 'image/jpeg' );
			$attachments[] = array( 'name' => 'tracker.jpg', 'path' => $tracker->logo, 'cid' => $cid, 'mime' => 'image/jpeg' );
			$attachments[] = array( 'name' => 'kp.jpg', 'path' => dirname($_SERVER["PHP_SELF"]) . '/images/kp.jpg' , 'cid' => $cid, 'mime' => 'image/jpeg' );
			$attachments[] = array( 'name' => 'google.jpg', 'path' => dirname($_SERVER["PHP_SELF"]) . '/images/google.jpg' , 'cid' => $cid, 'mime' => 'image/jpeg' );
			$attachments[] = array( 'name' => 'youtube.jpg', 'path' => dirname($_SERVER["PHP_SELF"]) . '/images/youtube.jpg' , 'cid' => $cid, 'mime' => 'image/jpeg' );
			$serialized_message = serialize( array('body' => $body, 'subject'=>$subject->start_download . $this->scrub_title, 'encoding' => $GLOBALS['all_scrubbers'][$this->scrubber]->encoding, 'attachments' => $attachments, 'html' => true) );
			Command::addToQueue('email', $serialized_message);
		}
	}
}

//
?>