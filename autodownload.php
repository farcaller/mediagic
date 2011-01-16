<?php

require_once('functions.php');


if ($config->verbose>0) echo date("r").NL;

$count_trackers=0;
$torrents=array();
$new_torrents=array();
$trackers=getTrackers();

if ($config->verbose>0) echo SP.'Looking for the new torrents' . NL . SP;

if (count($trackers) > 0)
{
	foreach ($trackers as $key=>$tracker_xml)
	{
		try
		{
			$tracker = new Tracker;
			$tracker -> createTrackerByXMLData($tracker_xml);
			if ($config->verbose>0) echo 'Processing '. $tracker->name . NL;
			if ($tracker->enable == 1)
			{
				if ($config->verbose>0) echo SP;
				$count_trackers++;
				if (isset ($tracker->filters->accept))
					$search_terms = $tracker->filters->accept;
				else $search_terms = array('');
				foreach ($search_terms as $search)
				{
					$new_torrents = $tracker->getTorrents($search);
					if (is_array($new_torrents))
						$torrents = array_merge($torrents, $new_torrents);
				}
			} else 
				if ($config->verbose>0) echo 'The tracker is disabled. Skipping.' . NL . SP;
		} catch (Exception $e) {
			if ($config->verbose>0) echo $e->getMessage() . NL . SP;
		}
	}
}

if ( $count_trackers == 0 )
{
	if ($config->verbose>0) echo "No trackers to proceed. Exiting now." . NL;
	die();
}

if ($config->verbose>0) echo count($torrents) . ' new torrents were founded.' . NL . SP;

foreach ($torrents as $key=>$torrent)
{
	if ($config->verbose>0) echo ($key+1) . ' of ' . count($torrents) . ': '. $torrent->title . NL . SSP;
	if ($config->verbose>0) echo 'Downloading torrent file.'. NL;
	
	try
	{
		$torrent->downloadTorrentFile();
		$data_file_path = $torrent->tracker_obj->directories->datafiles_dir.$torrent->data_filename;
		$result = getMediagicDBResult("SELECT * FROM torrents WHERE data_file_hash='".$torrent->data_file_hash."';");
		$torrent_res_count = mysql_num_rows($result);
		
		if ($torrent_res_count != 0)
		{
			if ($config->verbose>0) echo "The data file [ " . $torrent->data_filename . " ] is already registered in the database." . NL;
			if (! myFileExist($data_file_path) )
			{
				if ($config->verbose>0) echo 'The file [ '. $data_file_path . ' ] is not exist' . NL;
				if ($config->verbose>1) echo "Checking data files saved by other torrents:" . NL; 
				while ($row = mysql_fetch_assoc($result))
				{
					if (! myFileExist ($data_file_path) )
					{
						if ($config->verbose>1) echo ' * Checking [ ' . $row['data_file_dir'].$row['data_file_name'].' ] ... '; 
						if ( myFileExist( $row['data_file_dir'].$row['data_file_name'] ) )
						{
							if ($config->verbose>1) echo 'File exists.' . NL; 
							if ($config->verbose>0) echo 'Attempting to create a symlink to [ ' . $row['data_file_dir'].$row['data_file_name'] . ' ]' . NL;
							if ( ! @ symlink ($row['data_file_dir'].$row['data_file_name'], $data_file_path) )
							{
								throw new Exception('ERROR: Can\'t create the symlink. Skipping.');
							} elseif ($config->verbose>0) echo 'Successful.' . NL;
						} elseif ($config->verbose>0) echo 'Missing.' . NL; 
					}
				}
				if (! myFileExist($data_file_path) ) throw new Exception("Registered data file is missing. Proposing that you deleted data file." . NL . "Skipping this torrent as well.");
			}
		}


		$files = array();
		$part = false;

		if ($torrent->tracker_obj->type == 'Video')
		{
			if ($config->verbose>1) echo 'The type of the torrent is "Video".' . NL;
			if ( is_array($torrent->files) )
			{
				foreach ($torrent->files as $v)
				{
					if ( mb_strpos($v['path'], 'VIDEO_TS/') !== false )
					{
						$torrent->dvd = true;
						$video_ts_folder = mb_substr($torrent->data_filename . '/' . $v['path'], 0, mb_strpos($torrent->data_filename . '/' . $v['path'], 'VIDEO_TS/')-1);
						$test_array = array( 'correct_hash' => false, 'path' => $video_ts_folder, 'size' => 0, 'video_ts' => true );
						if ( ! in_array($test_array, $files) )
							$files[] = $test_array;
					}
					
					$files[] = array( 'correct_hash' => false, 'path' => $torrent->data_filename . '/' . $v['path'], 'size' => $v['size'] );
				}
			} else {
				$files[] = array( 'correct_hash' => true, 'path' => $torrent->data_filename, 'size' => $torrent->size );
			}
			
			if ($config->verbose>1) echo 'The torrent contains ' . count($files) . ' file(s).' . NL;

			if ($config->verbose>1) echo 'Checking videos for duplicates.' . NL;

			foreach ($files as $k=>$v)
			{
				$video_file_path = $torrent->tracker_obj->directories->datafiles_dir . $v['path'];
				if ( isVideo($video_file_path) || ( isset($v['video_ts']) && $v['video_ts'] == true ) )
				{
					if ($v['correct_hash'] == true)
					{
						$result = getMediagicDBResult("SELECT * FROM video WHERE file_hash='".$torrent->data_file_hash."';");
						$video_res_count = mysql_num_rows($result);
						
						if ($video_res_count != 0)
						{
							if ($config->verbose>0) echo "Duplicate of [ " . $v['path'] . " ] was founded in the database." . NL;
							if (! myFileExist($video_file_path) )
							{
								if ($config->verbose>0) echo 'The file [ '. $video_file_path . ' ] is not exist' . NL;
								if ($config->verbose>1) echo "Checking video files registered in the database:" . NL; 
								while ($row = mysql_fetch_assoc($result))
								{
									if (! myFileExist ($video_file_path) )
									{
										if ($config->verbose>1) echo ' * Checking [ ' . $row['filename'].' ] ... '; 
										if ( myFileExist( $row['filename'] ) )
										{
											if ($config->verbose>1) echo 'File exists.' . NL; 
											if ($config->verbose>0) echo 'Attempting to create a symlink to [ ' . $row['filename'] . ' ]' . NL;
											if ( ! @ symlink ($row['filename'], $video_file_path) )
											{
												if ($config->verbose>0) echo 'ERROR: Can\'t create the symlink.'.NL;
											} elseif ($config->verbose>0) echo 'Successful.' . NL;
										} elseif ($config->verbose>1) echo 'Missing.' . NL; 
									}
								}
								if (! myFileExist( $video_file_path ) ) echo "Registered video file is missing. Downloading as new.".NL;
							}
						}
					}
				} else unset($files[$k]);
			}
		}

		if ($torrent->isNewByFilename())
		{
			if ($config->verbose>0)  echo 'Saving [ '. $torrent->filename . ' ]' . NL;
			$torrent->saveToFS();
		} else {
			if ($config->verbose>0) echo 'There is a torrent file with the same name already exist.' . NL;
		}

		if ($torrent_res_count != 0)
		{
			if ($config->verbose>1) echo "Removing duplicate entities from the database" . NL;
			getMediagicDBResult("DELETE FROM torrents WHERE data_file_hash='".$torrent->data_file_hash."' AND ( tracker='" . addslashes( $torrent->tracker_name ) . "' OR tracker='');", false);
			if ($config->verbose>1) echo mysql_affected_rows(). " entities were removed." . NL;
		}

		if ($config->verbose>1) echo "Updating database with the new torrent." . NL;
		$torrent->saveToDBAsNew();


		if ($torrent->tracker_obj->type == 'Video')
		{
			if ($config->verbose>1) echo 'Checking videos for duplicates with the same name.' . NL;
			foreach ($files as $k=>$v)
			{
				$v['part'] = ! ( count($files) == 1 || ( isset($v['video_ts']) && $v['video_ts'] == true ) );
				$video = new Video();
				if ( $video->create( (($v['part']) ? getTitleFromFileName($v['path']) : $torrent->title ),
					$torrent->tracker_obj->directories->datafiles_dir.$v['path'], $v['part'],
					$torrent->title,
					 (($v['correct_hash']) ? $torrent->data_file_hash : false), $v['size'],
					( isset( $torrent->tracker_obj->scrubbers->relevant ) ? $torrent->torrent_id : 0 ),
					( isset( $torrent->tracker_obj->scrubbers->relevant ) ? trim( $torrent->tracker_obj->scrubbers->relevant ) : false ),
					( isset( $torrent->tracker_obj->scrubbers->default ) ? trim( $torrent->tracker_obj->scrubbers->default ) : false )) )
				{
					if ( isset($torrent->tracker_obj->scrubbers->automatic) && $torrent->tracker_obj->scrubbers->automatic == 1 )
						$video->getInfo();
					$video->saveToDB();
					$video->updateExtDB();
				} else {
					throw new Exception('The file with the same name allready exists in the database. Skipping.');
				}
			}
			if ( isset($video) ) 
			{
				if ( isset( $config->email ) )
				{		
					$email_config = $config->email;
					$email_attrs = $email_config->attributes();
					if ( trim( $email_attrs['enable'] ) == 1 )
						$video->notifyMe( ($torrent->dvd != true ? $files : ''), $torrent);
				}
				unset($video);
			}
		}

	} catch (Exception $e) {
		if ($config->verbose>0) echo $e->getMessage() . NL;
	}
	if ($config->verbose>0) echo SP;
}

//Command::execute();


?>