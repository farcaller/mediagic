<?php

class Command
{
	public static function addToQueue($command, $target)
	{
		$q = "SELECT * from commands WHERE command='" . addslashes($command) . "' AND target='" . addslashes($target) . "';";
		$res = getMediagicDBResult( $q );
		if (mysql_num_rows($res) == 0)
		{
			$q = "INSERT into commands (command, target, timestamp) VALUES ('" .  addslashes($command) . "', '" . addslashes($target) . "', " . time() . ");";
			getMediagicDBResult( $q );
		} elseif ( $GLOBALS['config']->verbose > 0 ) echo 'The command is already in the queue. Skipping' . NL;
	}

	public static function execute()
	{
		if ( $GLOBALS['config']->verbose > 0 ) echo 'Proceeding the queue of commands' . NL;
		$q = "SELECT * from commands ORDER BY timestamp ASC;";
		$res = getMediagicDBResult( $q );
		
		if ( $GLOBALS['config']->verbose > 0 ) echo mysql_num_rows($res) . ' commands in the queue' . NL . SSP;
		
		if (mysql_num_rows($res) > 0)
		{
			while ($row = mysql_fetch_assoc($res))
			{
				switch ( $row['command'] )
				{
					case ('delete'):
					self::delete_download($row['target']);
					break;
					case ('email'):
					self::sendEmail($row['target']);
					break;
				}
				if ( $GLOBALS['config']->verbose > 0 ) echo SP;
			}
		}		
	}

	private static function delete_download($hash)
	{
		//Нужно добавить обновление всех баз данных с новой информацией о новом расположении дата-файла в папке Trash
		//Кроме этого, нужно перед удалением копировать в Trash торрент-файл, и после работы с рторрентом проверять что торрент-файл точно удалился 
		$torrent = new Torrent();
		$torrent->getFromDB('WHERE data_file_hash=\'' . $hash . '\'');
		if ( !empty($torrent->id) )
		{
			if ( $GLOBALS['config']->verbose > 0 ) echo 'Stopping the downloading of the torrent [ ' . $torrent->title . ' ]' . NL;

			$rt_res = RTorrent::dClose($hash);

			if ($rt_res != false)
			{
				sleep (5);
				$rt_res = RTorrent::dErase($hash);
				if ($rt_res != false)
				{
					if ( $GLOBALS['config']->verbose > 1) echo 'Removing command from the queue' . NL ;
					getMediagicDBResult("DELETE FROM commands WHERE command='delete' AND target='" . addslashes($hash) . "'");
					if (is_readable ($torrent->data_file_dir . $torrent->data_filename))
					{
						if ( $GLOBALS['config']->verbose > 1 ) echo 'Moving data files to trash' . NL;
						$trash_dir = $GLOBALS['config']->directories->trash;
						if (is_dir ($trash_dir))
						{
							if (! @ rename ($torrent->data_file_dir . $torrent->data_filename, $trash_dir . $torrent->data_filename) )
							{
								if ( $GLOBALS['config']->verbose > 0) echo 'Unable to move [' . $torrent->data_filename . '] to the trash at [' . $trash_dir . ']' . NL;
							}				
						} else {
							if ( $GLOBALS['config']->verbose > 0) echo 'Trash directory [' . $trash_dir . '] is not exist' . NL;
						}
					}
				}
			}
		} else {
			if ( $GLOBALS['config']->verbose > 1 ) echo 'Torrent with this hash-id is not registered in the database. Deleting command from queue' . NL;
			getMediagicDBResult("DELETE FROM commands WHERE command='delete' AND target='" . addslashes($hash) . "'");
		}
	}

	private static function sendEmail($ser_message)
	{
		if ( isset( $GLOBALS['config']->email ) )
		{		
			$email = $GLOBALS['config']->email;
			$email_attrs = $email->attributes();
	
			if ( trim( $email_attrs['enable'] ) == 1 )
			{
				require_once('PHPMailer/class.phpmailer.php');
				require_once('PHPMailer/class.smtp.php');

				$message = unserialize($ser_message);

				$body = $message['body'];

				if ( isset( $message['subject'] ) )
					$subject_suffix = $message['subject'];
				else $subject_suffix = '';
				
				if ( isset( $message['encoding'] ) )
					$encoding = $message['encoding'];
				else $encoding = 'UTF-8';

				if ( isset( $message['attachments'] ) )
					$attachments = $message['attachments'];
				else $attachments = false;

				if ( isset( $message['send_to_array'] ) )
					$send_to_array = $message['send_to_array']; 
				else 
				{
					foreach ( $email->send_to as $send_to_line)
					{
						$send_to_array[] = array( 'name'=>$send_to_line->name, 'email'=>$send_to_line->email );
					}
				}
	
				$mail = new PHPMailer();
	
				$send_from = $email->send_from;
	
				$send_to_string = '';
	
				$send_to_key = 0;

				foreach ($send_to_array as $send_to)
				{
					$send_to_string .= $send_to['name'] . ' <'. $send_to['email'] . '>' ;
					if ( $send_to_key != count($send_to_array) - 1 )
						$send_to_string .= ', ';
					$send_to_name =  $send_to['name'];
					if ( $email->encoding != 'UTF-8' )
						$send_to_name = iconv( $encoding, $email->encoding . '//IGNORE', $send_to_name);
					$mail->AddAddress( $send_to['email'], $send_to_name);
					$send_to_key++;
				}

				if ( $GLOBALS['config']->verbose > 0 ) echo 'Sending email to ' . $send_to_string . NL;
	
				if ( trim($send_from->type) == 'smtp')
				{
					$mail->IsSMTP();
					$smtp = $send_from->smtp;
					if ( isset( $smtp->smtp_auth ) )
					{
						if ( $smtp->smtp_auth == 'true' ||  $smtp->smtp_auth == '1') 
							$mail->SMTPAuth = true;
					}
					
					if ( isset( $smtp->smtp_secure ) ) $mail->SMTPSecure = $smtp->smtp_secure;
					if ( isset( $smtp->host ) ) $mail->Host = $smtp->host;
					if ( isset( $smtp->port ) ) $mail->Port = (int)$smtp->port;
					if ( isset( $email->username ) ) $mail->Username = $email->username;
					if ( isset( $email->password ) ) $mail->Password = $email->password;
				}
	
				$subject_prefix = $email->subject->prefix;
				if ( $email->encoding != 'UTF-8' )
					$subject_prefix = iconv( 'UTF-8', $email->encoding . '//IGNORE', $subject_prefix);
	
				if ( $encoding != $email->encoding)
				{
					$body = iconv( $encoding, $email->encoding . '//IGNORE', $body);
					$subject_suffix = iconv( $encoding, $email->encoding . '//IGNORE', $subject_suffix);
				}
	
				$mail->From       = $send_from->email;
				$mail->FromName   = $send_from->name;
				$mail->Subject    = $subject_prefix . ' ' . $subject_suffix;
				
				$mail->CharSet    = $email->encoding;

				if ( isset( $message['html'] ) && $message['html'] == true) $mail->IsHTML(true);
				//$mail->MsgHTML($body);
				
				$mail->Body = $body;
				
				if (!empty($attachments) && is_array($attachments) )
				{
					foreach ($attachments as $attachment)
					{
						if ( isset( $attachment['type'] ) && $attachment['type'] == 'raw' )
						{
							if ( !empty($attachment['data']) )
								$mail->AddStringEmbeddedImage($attachment['data'], $attachment['cid'] . '/' . $attachment['name'] , $attachment['name'], 'base64', $attachment['mime']);
						} elseif ( isset( $attachment['path'] ) ) {
							$mail->AddEmbeddedImage($attachment['path'], $attachment['cid'] . '/' . $attachment['name'] , $attachment['name'], 'base64', $attachment['mime']);
						}
					}
				}
	
				if(!$mail->Send() && $GLOBALS['config']->verbose>1)
				{
					if ( $GLOBALS['config']->verbose > 0 ) echo "Mailer Error: " . $mail->ErrorInfo . NL;
				} else {
					if ( $GLOBALS['config']->verbose > 1 ) echo "Message has been sent" . NL;
					getMediagicDBResult("DELETE FROM commands WHERE command='email' AND target='" . addslashes($ser_message) . "'");
				}
				$mail->ClearAddresses(); 
				$mail->ClearAttachments(); 
				$mail->SmtpClose();
		
			}
		}
	}

}

?>