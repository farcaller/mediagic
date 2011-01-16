<?php

require_once('functions.php');

//Command::addToQueue( 'email', serialize( array( 'body' => $s_body, 'subject' => 'Status', 'send_to_array' => array( array( 'name'=>'Vadim', 'email'=>'vnizzz@gmail.com' ) ), 'html'=>true, 'attachments'=>(is_array($attachments)?$attachments:'') ) ) );
//Command::execute();
//die();

if ( $config->verbose > 0 ) echo date("r") . NL . SP;
if ( isset( $config->email ) )
{
	$email_config = $config->email;
	$email_attrs = $email_config->attributes();

	if ( trim( $email_attrs['enable'] ) == 1 )
	{
		$get_responses_config = $email_config->get_responses;
		$get_responses_config_attrs = $get_responses_config->attributes();
		if ( trim( $get_responses_config_attrs['enable'] ) == 1 )
		{
			try
			{
				if ($config->verbose>1) echo 'Connecting to IMAP' . NL;
				if (! $conn = @imap_open('{' . $get_responses_config->imap_host . '}' . $get_responses_config->imap_mailbox, 
										$email_config->username,
										$email_config->password))
					throw new Exception('Unable to connect to IMAP: ' . imap_last_error());

				$MC = imap_check($conn);
				if ($config->verbose>1) echo 'Getting messages' . NL;
				if (! $overview = @imap_fetch_overview($conn, "1:{$MC->Nmsgs}") )
					throw new Exception('Unable to recieve emails: ' . imap_last_error());
				
				if ($config->verbose>1) echo count( $overview ) . ' messages in the inbox' . NL;
				
				if ( count( $overview ) > 0 )
				{
					$needle_suffix = trim( $email_config->subject->prefix );
					if ( $GLOBALS['config']->system_encoding != 'UTF-8' )
						$needle_suffix = iconv( 'UTF-8', $GLOBALS['config']->system_encoding, $needle_suffix);
					if ($config->verbose>1) echo SP.NL;
					foreach( $overview as $mes_int_id => $message)
					{
						$subject = '';
						$from = '';

						foreach( imap_mime_header_decode($message->from) as $from_part)
						{
							if ( $from_part->charset != $GLOBALS['config']->system_encoding && $from_part->charset != 'default' )
							{
								$from .= iconv( $from_part->charset, $GLOBALS['config']->system_encoding . '//IGNORE', $from_part->text);
							} else $from .= $from_part->text;
						}
						
						foreach( imap_mime_header_decode($message->subject) as $subj_part)
						{
							if ( $subj_part->charset != $GLOBALS['config']->system_encoding && $subj_part->charset != 'default' )
							{
								$subject .= iconv( $subj_part->charset, $GLOBALS['config']->system_encoding . '//IGNORE', $subj_part->text);
							} else $subject .= $subj_part->text;
						}

						if ($config->verbose>1)
						{
							echo 'Message #' . ($mes_int_id + 1) . NL;
							echo 'From: ' . $from. NL;
							echo 'Subject: ' . $subject . NL;
						}
						
						preg_match( '/(.*)\<(.*)\>/', $from, $from_arr);
						$from_name = trim($from_arr[1]);
						$from_email = $from_arr[2];

						//echo $from_arr[1];
						$trusted_senders = $email_config->send_to;
						
						$block_sender = true;
						
						foreach ($trusted_senders as $trusted_sender)
							if( trim($trusted_sender->email) == trim( $from_email ) )
								$block_sender = false;

						if ( $config->verbose > 1 )
						{
							if ( $message->seen != 0 ) echo 'Message is marked as read. Skipping'. NL;
							elseif ( $block_sender == true ) echo 'Message sender is not in the list of trusted users. Skipping'. NL;
						}

						if ($message->seen == 0 && $block_sender==false )
						{
							$body = '';
							$parts = imap_get_texts($conn, $message->uid);
							foreach ( $parts as $part )
								$body .= $part;
							$body = br2nl( $body );
							$body = strip_tags( $body );
							$body = str_replace("&nbsp;", ' ', $body);
							$body = html_entity_decode( $body );
							//echo $body . SP. NL;
							$commands = array();

							if ( stripos($body, '#Delete') !== false )
							{
								if ( $config->verbose > 0 ) echo 'Command \'DELETE\' was found' . NL;
								preg_match('/\#(\w{40})/', $body, $hash_arr);
								$hash = $hash_arr[1];
								if ( !empty( $hash ) )
								{
									$torrent = new Torrent();
									$torrent->getFromDB('WHERE data_file_hash=\'' . $hash . '\'');
									//print_r($torrent);
									if ( !empty( $torrent->id ) )
									{
										if ( $config->verbose > 0 ) echo 'Adding command \'DELETE\' for the torrent [ ' . $torrent->title . ' ] to the queue' . NL;
										Command::addToQueue('delete', $hash);
										imap_setflag_full($conn, $message->uid ,"\\Seen", ST_UID);
									} elseif ( $config->verbose > 1 ) echo 'The torrent file with specified hash-id is not registered in the database. Skipping' . NL;
								} elseif ( $config->verbose > 0 ) echo 'The hash-id is missed. Skipping' . NL;
							}

							if ( stripos($body, '#Status') !== false )
							{
								// нужно показывать красивый ползунок статума загрузк
								// нужно показывать кол-во пиров
								if ( $config->verbose > 0 ) echo 'Command [ STATUS ] was found. Adding to the queue.' . NL;
								$s_body = getTemplate('status.html');
								$used_images = array('red'=>false, 'green'=>false, 'yellow'=>false);

								$incomplete = RTorrent::downloadList( 'incomplete' );
								if ($incomplete !== false)
								{
									$s_body = str_ireplace( '%r_status%', "RTorrent is running", $s_body );
									//$st_body .= (int)count( $incomplete ). " torrents incompleted \r\n" . SSP;
									if ( count( $incomplete ) > 0 )
									{
										if ( count( $incomplete ) == 1 ) $s_body = str_ireplace( '%d_count%', 'There is 1 incompleted download', $s_body );
										else $s_body = str_ireplace( '%d_count%', 'There are ' . count( $incomplete ) . ' incompleted downloads', $s_body );

										$downloads_list = '';
										preg_match('/<\!--files_block_start-->(.*)<\!--files_block_end-->/ms', $s_body, $files_block);
										preg_match('/<\!--file_start-->(.*)<\!--file_end-->/ms', $s_body, $download_inst);

										$attachments = '';
										$global_cid = md5( time() . 'ololo');
										foreach ( $incomplete as $hash )
										{
											//echo  RTorrent::dIsActive( $hash ) . NL;
											//echo 'Active:' . RTorrent::dIsActive( $hash ) . ' HCd:' . RTorrent::dIsHashChecked( $hash ) . ' HCing:' . RTorrent::dIsHashChecking( $hash ) . ' Open:'. RTorrent::dIsOpen( $hash ) . NL;
											$torrent = new Torrent();
											$torrent->getFromDB('WHERE data_file_hash=\'' . $hash . '\'');
											if ( !empty( $torrent->id ) ) $t_title = $torrent->title;
											else $t_title = RTorrent::dGetName( $hash );
											$compl_bytes = RTorrent::dGetCompletedBytes( $hash );
											$bytes = RTorrent::dGetSizeBytes( $hash );

											$download = str_ireplace( '%title%', $t_title, $download_inst[1] );
											$download = str_ireplace( '%d_size%', humanSize( $compl_bytes ), $download );
											$download = str_ireplace( '%t_size%', humanSize( $bytes ), $download );
											$download = str_ireplace( '%percent%', round( ($compl_bytes/$bytes) * 100, 0) . '%', $download );
											$d_speed = RTorrent::dGetDownRate( $hash );
											$download = str_ireplace( '%d_speed%', humanSize( $d_speed ), $download );
											$download = str_ireplace( '%u_speed%', humanSize( RTorrent::dGetUpRate( $hash ) ), $download );
											if ($d_speed > 0)
											{
												$p_end = strftime ( '%B, %e / %I:%M', time() + ( ( $bytes - $compl_bytes ) / $d_speed ) );
												$download = str_ireplace( '%p_end%', $p_end, $download );
											} else {
												$download = str_ireplace( '%p_end%', 'Unknown', $download );
											}

											$is_active = RTorrent::dIsActive( $hash ) ;
											$is_open = RTorrent::dIsOpen( $hash ) ;
											
											if ( $is_active == 0 && $is_open == 0 )
											{
												$status_image = 'status_red.png';
												$used_images['red'] = true;
											} elseif ( $is_active == 1 && $is_open == 1 ) {
												$status_image = 'status_green.png';
												$used_images['green'] = true;
											} else {
												$status_image = 'status_yellow.png';
												$used_images['yellow'] = true;
											}
											
											$download = str_ireplace( '%status_image%', '<img src="cid:' . $global_cid . '/' . $status_image . '" width="10" height="10">', $download );


											$genres = '';
											$countries = '';
											$year = '';
											$cover = false;

											$video = new Video();
											if ( !empty( $torrent->id ) )
											{
												$cid = md5( time() . $video->scrub_title );
												$video->getFromDB ('WHERE file_hash = \'' . $hash . '\'');
												if ( empty($video->id) )
													$video->getFromDB ('WHERE filename like \'' . $torrent->data_file_dir .  $torrent->data_filename . '%\'');
												if ( !empty($video->id) )
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
																	if ($config->verbose>1) echo $e->getMessage() . NL;
																}
															}
														}
													}
													
													if ( isset( $GLOBALS['all_scrubbers'][$video->scrubber]->unknown ) )
														$unkn = trim( $GLOBALS['all_scrubbers'][$video->scrubber]->unknown );
													else $unkn = '';
	
													$genres = implode( " / ", $video->genres);
													if ($genres == $unkn) $genres = '';

													$countries = implode( ", ", $video->countries );
													if ($countries == $unkn) $countries = '';

													if ( !empty($video->year) )
														$year = '(' . $video->year . ')';
												}
												$cover = $video->createImage(63, 79, false, false);
												
												if ($cover != false)
												{
													$download = str_ireplace( '%cover%', '<img src="cid:'.$cid.'/' . md5($video->title) . '.jpg" width="63" height="79">', $download );
													$attachments[] = array( 'type' => 'raw', 'name' => md5($video->title) . '.jpg', 'data' => $cover, 'cid' => $cid, 'mime' => 'image/jpeg');
												}
											}
											
											$download = str_ireplace( '%year%', $year, $download );
											$download = str_ireplace( '%genres%', $genres, $download );
											$download = str_ireplace( '%countries%', $countries, $download );

											$downloads_list .=  $download;
											
											//$st_body .= $t_title . ' ..... ' . humanSize( $compl_bytes ) . ' of ' . humanSize( $bytes ) . ' (' . round( ($compl_bytes/$bytes) * 100, 2) . '%) downloaded' . "\r\n";
										}
										$files_block = preg_replace('/<\!--file_start-->(.*)<\!--file_end-->/ms', $downloads_list, $files_block[1]);
										$s_body = preg_replace('/<\!--files_block_start-->(.*)<\!--files_block_end-->/ms', $files_block, $s_body);
										
										if ( strpos( $s_body, '%cover%' ) )
										{
											$s_body = str_ireplace( '%cover%', '<img src="cid:'.$global_cid.'/cover.png" width="63" height="79">',  $s_body );
											$attachments[] = array( 'path' => dirname($_SERVER["PHP_SELF"]) . '/images/vidcasesmall.png', 'name' => 'cover.png', 'cid' => $global_cid, 'mime' => 'image/png' );
										}
										if ( $used_images['red'] == true ) $attachments[] = array( 'path' => dirname($_SERVER["PHP_SELF"]) . '/images/status_red.png', 'name' => 'status_red.png', 'cid' => $global_cid, 'mime' => 'image/png' );
										if ( $used_images['yellow'] == true ) $attachments[] = array( 'path' => dirname($_SERVER["PHP_SELF"]) . '/images/status_yellow.png', 'name' => 'status_yellow.png', 'cid' => $global_cid, 'mime' => 'image/png' );
										if ( $used_images['green'] == true ) $attachments[] = array( 'path' => dirname($_SERVER["PHP_SELF"]) . '/images/status_green.png', 'name' => 'status_green.png', 'cid' => $global_cid, 'mime' => 'image/png' );
									} else {
										$s_body = str_ireplace( '%d_count%', "There are no incompleted downloads", $s_body );
									}
								} else {
									$s_body = str_ireplace( '%r_status%', "RTorrent is not running", $s_body );
									$s_body = preg_replace('/<\!--files_block_start-->(.*)<\!--files_block_end-->/ms','', $s_body);
								}
								$os = getOS();
								if ( $config->verbose > 1 ) echo 'Trying to get names of disks' . NL;								
								if ( $config->verbose > 1 ) echo 'System "' . $os . '" was detected' . NL;								
								if ( $os == 'Linux' )
								{
									exec('df -x tmpfs', $df);	
									if ( is_array( $df ) && count( $df ) > 0 )
									{
										$devs = array();
										$devs_list = '';
										foreach ( $df as $line)
										{
											$is_preg = preg_match('/^(\/[^\s]*)\s.*\s(\/[^\s]*)$/', $line, $line_preg);
											if ( $is_preg )
											{
												$devs[] = array( 'dev_name' => $line_preg[1], 'mount_point' => $line_preg[2], 'free' => humanSize( disk_free_space( $line_preg[2] ) ), 'total' => humanSize( disk_total_space( $line_preg[2] ) ) );
//												str_ireplace( '%disk_stat%', '[ ' . $line_preg[1] . ' mounted to ' . $line_preg[2] . ' ] ' . humanSize( disk_free_space( $line_preg[2] ) ) . ' of ' .  humanSize( disk_total_space( $line_preg[2] ) ) . ' is free', $s_body );
											}
										}
										
										if ( count( $devs ) > 0 )
										{
											preg_match('/<\!--devs_block_start-->(.*)<\!--devs_block_end-->/ms', $s_body, $devs_block);
											preg_match('/<\!--dev_start-->(.*)<\!--dev_end-->/ms', $s_body, $dev_inst);

											foreach ( $devs as $dev )
											{
												$dev_content = str_ireplace( '%dev_name%', $dev['dev_name'], $dev_inst[1] );
												$dev_content = str_ireplace( '%mount_point%', $dev['mount_point'], $dev_content );
												$dev_content = str_ireplace( '%disk_free%', $dev['free'], $dev_content );
												$dev_content = str_ireplace( '%disk_total%', $dev['total'], $dev_content );
												$devs_list .=  $dev_content;
											}
											
											$devs_block = preg_replace('/<\!--dev_start-->(.*)<\!--dev_end-->/ms', $devs_list, $devs_block[1]);
											$s_body = preg_replace('/<\!--devs_block_start-->(.*)<\!--devs_block_end-->/ms', $devs_block, $s_body);
										}
									}
								}
								$s_body = preg_replace('/<\!--devs_block_start-->(.*)<\!--devs_block_end-->/ms','', $s_body);
								Command::addToQueue( 'email', serialize( array('body' => $s_body, 'subject' => 'Status', 'send_to_array' => array( array( 'name'=>$from_name, 'email'=>$from_email ) ), 'html'=>true, 'attachments'=>(is_array($attachments)?$attachments:'') ) ) );
								imap_setflag_full($conn, $message->uid ,"\\Seen", ST_UID);
							}

						}
						if ($config->verbose>1) echo SP;
					}
				}
				imap_close($conn);
			} catch (Exception $e) {
				if ($config->verbose>0) echo $e->getMessage() . NL . SP;
			}
		} elseif ($config->verbose>0) echo 'Mail functions are disabled in the config file' . NL;
	} elseif ($config->verbose>0) echo 'Mail functions are disabled in the config file' . NL;
} elseif ($config->verbose>0) echo 'Unable to find mail settings in the config file' . NL;

Command::execute();

?>