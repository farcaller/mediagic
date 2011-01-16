<?php

function __autoload($class_name)
{
    require_once 'classes/' . $class_name . '.class.php';
}

function br2nl($text) 
{
	return preg_replace('/<br\\s*?\/??>/i', "\n", $text);
}

if(!function_exists('mb_ucfirst'))
{
	function mb_ucfirst( $string, $e = false )
	{ 
		if ($e == false) $e = trim($GLOBALS['config']->system_encoding);
		if (function_exists('mb_strtoupper') && function_exists('mb_substr') && !empty($string)) { 
			$string = mb_strtolower($string, $e);
			$upper = mb_strtoupper($string, $e);
			preg_match('#(.)#us', $upper, $matches); 
			$string = $matches[1] . mb_substr($string, 1, mb_strlen($string, $e), $e); 
		} else { 
			$string = ucfirst($string); 
		} 
		return $string; 
	}
}

function mb_ucfirst_arr($array, $e = false)
{
	foreach ($array as $value)
	{
		$new_arr[] = mb_ucfirst($value, $e);
	}
	return $new_arr;
}


function mb_ucwords_arr($array, $e = false)
{
	foreach ($array as $value)
	{
		$new_arr[] = mb_ucwords($value, $e);
	}
	return $new_arr;
}

if(!function_exists('mb_ucwords'))
{
	function mb_ucwords($str, $e = false )
	{
		if ($e == false) $e = trim($GLOBALS['config']->system_encoding);
		return mb_convert_case($str, MB_CASE_TITLE, $e);
	}
}

function getXMLByDataHTML($data)
{
	$doc = new DOMDocument();
	$doc->strictErrorChecking = FALSE;
	@$doc->loadHTML($data);
	$xml = simplexml_import_dom($doc);
	return $xml;
}

function getXMLByFilename($filename)
{
	$doc = new DOMDocument();
	$doc->strictErrorChecking = FALSE;
	$doc->load($filename);
	//$doc = preg_replace("/>\s*/", ">", $doc);
	//$doc = preg_replace("/\s*</", "<", $doc);
	$xml = simplexml_import_dom($doc);
	return $xml;
}

function getTrackers()
{
	$xml=array();
	$trackers_xml_dir = dirname($_SERVER["PHP_SELF"]).'/trackers/';
	if (is_dir($trackers_xml_dir)) {
		if ($dh = opendir($trackers_xml_dir)) {
			while (($file = readdir($dh)) !== false) {
				if ( strpos( $file, '.' ) !==0 ) {
					$xml[] = getXMLByFilename($trackers_xml_dir . $file);
				}
			}
			closedir($dh);
		}
	}
	return $xml;
}

function getScrubbers()
{
	$xml=array();
	$scrubbers_xml_dir = dirname($_SERVER["PHP_SELF"]).'/scrubbers/';
	if (is_dir($scrubbers_xml_dir)) {
		if ($dh = opendir($scrubbers_xml_dir)) {
			while (($file = readdir($dh)) !== false) {
				if ( strpos( $file, '.' ) !==0 ) {
					$xml[] = array('filename'=>$file, 'xml'=>getXMLByFilename($scrubbers_xml_dir . $file) );
				}
			}
			closedir($dh);
		}
	}
	return $xml;
}

function getFilesByRequest()
{
	$files=$GLOBALS['config']->directories->video;
	if (isset($_SERVER["QUERY_STRING"]))
	{
		$needed = (int) $_SERVER["QUERY_STRING"];
		$m=0;
		foreach ($files as $file)
		{
			if (myFileExist($file))
			{
				if ($m===$needed && is_dir($file))
				{
					if (@$handle = opendir($file)) {				
						while (false !== ($new_file = readdir($handle))) { 
							$new_files[] = $file.$new_file;
						}
					}
				}
				$m++;
			}
		}
		if (is_array($new_files))
		{
			$files=$new_files;
		}
	}
	
	return $files;
}

// MySQL Functions

function getMySQLResult ($server, $username, $password, $db, $q, $closeConnection=true) {
	$conn = mysql_connect($server, $username, $password);
	mysql_select_db($db);
	$res = mysql_query($q);
	if ($closeConnection == true)
		mysql_close($conn);
	if ($res === false) {
		throw new Exception('Can\'t proceed with the MySQL queue: ' . $q . NL . mysql_error() . NL );
	}
	return $res;
}

function getMediagicDBResult ($q, $closeConnection=true) {
	$conf = $GLOBALS['config']->mediagic_db;
	return getMySQLResult(trim($conf->server), trim($conf->username), trim($conf->password), trim($conf->db_name), $q, $closeConnection);
}

function getMythTVDBResult ($q, $closeConnection=true) {
	$conf = $GLOBALS['config']->mythtv;
	return getMySQLResult(trim($conf->server), trim($conf->username), trim($conf->password), trim($conf->db_name), $q, $closeConnection);
}

function myFileExist ($filepath)
{
	return ( file_exists( $filepath ) || is_link( $filepath ) );
}

function filename_extension($filename) {//{{{
   $pos = strrpos($filename, '.');
   if($pos===false) {
       return false;
   } else {
       return substr($filename, $pos+1);
   }
}

function isVideo($path)
{
	$is_video = true;
	foreach ($GLOBALS['not_videos'] as $v)
	{
		if ( !($ext = filename_extension($path)) || mb_strtolower($ext) == mb_strtolower($v))
		{
			$is_video = false;
		}
	}
	return $is_video;
}

function getVideosFromDB ()
{
	$q = "SELECT id FROM video;";
	$res = getMediagicDBResult($q);
	if (mysql_num_rows($res) != 0)
	{
		while ($video_data = mysql_fetch_assoc($res))
		{
			$video = new Video();
			$video->setID($video_data['id']);
			$video->getFromDBByID();
			$videos[] = $video;
		}
		return $videos;
	} else return false;

}

function getTitleFromFileName ($filename)
{
	$title = basename($filename);
	$title = substr($title,0,strrpos($title, '.'));
	return $title;
}

function trimArray($input)
{
	if ( ! is_array($input) )
		return trim($input);
	return array_map('trimArray', $input);
}
	
function maxExplode($string)
{
	$string = str_replace('.', ',', $string);
	$string = str_replace(';', ',', $string);
	$string = str_replace('/', ',', $string);
	$string = str_replace('\\', ',', $string);
	return trimArray(explode(',', $string));
}

function imagecreatefrombmp($p_sFile) 
{ 
	//    Load the image into a string 
	if ( ! @ $file = fopen($p_sFile,"rb") ) return false;
	$read = fread($file,10); 
	while(!feof($file)&&($read<>"")) 
		$read    .=    fread($file,1024); 
	$temp    =    unpack("H*",$read); 
	$hex    =    $temp[1]; 
	$header    =    substr($hex,0,108); 
	if (substr($header,0,4)=="424d") 
	{ 
		$header_parts    =    str_split($header,2); 
		$width            =    hexdec($header_parts[19].$header_parts[18]); 
		$height            =    hexdec($header_parts[23].$header_parts[22]); 
		unset($header_parts); 
	} 
	$x                =    0; 
	$y                =    1; 
	$image            =    imagecreatetruecolor($width,$height); 
	$body            =    substr($hex,108); 
	$body_size        =    (strlen($body)/2); 
	$header_size    =    ($width*$height); 
	$usePadding        =    ($body_size>($header_size*3)+4); 
	for ($i=0;$i<$body_size;$i+=3) 
	{ 
		if ($x>=$width) 
		{ 
			if ($usePadding) 
				$i    +=    $width%4; 
			$x    =    0; 
			$y++; 
			if ($y>$height) 
				break; 
		} 
		$i_pos    =    $i*2; 
		$r        =    hexdec($body[$i_pos+4].$body[$i_pos+5]); 
		$g        =    hexdec($body[$i_pos+2].$body[$i_pos+3]); 
		$b        =    hexdec($body[$i_pos].$body[$i_pos+1]); 
		$color    =    imagecolorallocate($image,$r,$g,$b); 
		imagesetpixel($image,$x,$height-$y,$color); 
		$x++; 
	} 
	
	unset($body); 
	return $image; 
}

/** 
 * PNG ALPHA CHANNEL SUPPORT for imagecopymerge(); 
 * This is a function like imagecopymerge but it handle alpha channel well!!! 
 **/ 

// A fix to get a function like imagecopymerge WITH ALPHA SUPPORT 
// Main script by aiden dot mail at freemail dot hu 
// Transformed to imagecopymerge_alpha() by rodrigo dot polo at gmail dot com 
function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){ 
    if(!isset($pct)){ 
        return false; 
    } 
    $pct /= 100; 
    // Get image width and height 
    $w = imagesx( $src_im ); 
    $h = imagesy( $src_im ); 
    // Turn alpha blending off 
    imagealphablending( $src_im, false ); 
    // Find the most opaque pixel in the image (the one with the smallest alpha value) 
    $minalpha = 127; 
    for( $x = 0; $x < $w; $x++ ) 
    for( $y = 0; $y < $h; $y++ ){ 
        $alpha = ( imagecolorat( $src_im, $x, $y ) >> 24 ) & 0xFF; 
        if( $alpha < $minalpha ){ 
            $minalpha = $alpha; 
        } 
    } 
    //loop through image pixels and modify alpha for each 
    for( $x = 0; $x < $w; $x++ ){ 
        for( $y = 0; $y < $h; $y++ ){ 
            //get current alpha value (represents the TANSPARENCY!) 
            $colorxy = imagecolorat( $src_im, $x, $y ); 
            $alpha = ( $colorxy >> 24 ) & 0xFF; 
            //calculate new alpha 
            if( $minalpha !== 127 ){ 
                $alpha = 127 + 127 * $pct * ( $alpha - 127 ) / ( 127 - $minalpha ); 
            } else { 
                $alpha += 127 * $pct; 
            } 
            //get the color index with new alpha 
            $alphacolorxy = imagecolorallocatealpha( $src_im, ( $colorxy >> 16 ) & 0xFF, ( $colorxy >> 8 ) & 0xFF, $colorxy & 0xFF, $alpha ); 
            //set pixel with the new color + opacity 
            if( !imagesetpixel( $src_im, $x, $y, $alphacolorxy ) ){ 
                return false; 
            } 
        } 
    } 
    // The image copy 
    imagecopy($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h); 
}

function humanSize($bytes)
{
	$measure = 'KB';
	$amount = round( $bytes/1024, 2 );
	if ( $amount > 1024*1024 )
	{
		$amount = round( $amount/1024/1024, 2 );
		$measure = 'GB';
	} elseif ( $amount > 1024 )
	{
		$amount = round( $amount/1024, 2 );
		$measure = 'MB';
	}

	return $amount . ' ' . $measure;

}

function getOS()
{
	ob_start();
	phpinfo(INFO_GENERAL);
	$php_info = ob_get_contents();
	ob_end_clean();
	preg_match('/System =\> ([^\s]*)\s/', $php_info, $sys_res);
	return $sys_res[1];
}

function getTemplate($filename)
{
	//echo dirname($_SERVER["PHP_SELF"]) . '/templates/' . $filename;
	//die();
	$handle = fopen( dirname($_SERVER["PHP_SELF"]) . '/templates/' . $filename, "r");
	$contents = fread($handle, filesize( dirname($_SERVER["PHP_SELF"]) . '/templates/' .  $filename ));
	fclose($handle);
	return $contents;
}

function imap_get_texts($conn, $uid, $structure = false, $part_id = 0)
{
	if ( !$structure )
		$structure = imap_fetchstructure($conn, $uid, FT_PEEK + FT_UID);
	if ( isset( $structure->parts ) )
	{
		foreach ($structure->parts as $key => $part)
		{
			$new_part_id = ( $part_id == 0 ?  $key+1 : $part_id . '.' . ($key+1) );
			$tmp_res_arr = imap_get_texts($conn, $uid, $part,  $new_part_id);
			if (is_array($tmp_res_arr))
				foreach ( $tmp_res_arr as $tmp_res )
					$res[] = $tmp_res;
		}
	} else {
		if ($structure->type == 0)
		{

			if ($part_id == 0)
				$body = imap_body($conn, $uid, FT_PEEK + FT_UID);
			else
				$body = imap_fetchbody($conn, $uid, $part_id, FT_PEEK + FT_UID);

			if ($structure->encoding == 3)
				$body = base64_decode( $body );
			elseif ($structure->encoding == 4)
				$body = quoted_printable_decode( $body );
			
			foreach ( $structure->parameters as $parameter )
				if( $parameter->attribute == 'CHARSET' && strtoupper( trim( $parameter->value ) ) != strtoupper( trim( $GLOBALS['config']->system_encoding ) ) )
					$body = iconv( strtoupper( trim( $parameter->value ) ), strtoupper( trim( $GLOBALS['config']->system_encoding ) ) . '//IGNORE', $body );
			$res[] = $body;
		}
	}
	return $res;
}

  /////////////////////////////////////
 ///      Autostart functions      ///
/////////////////////////////////////


$config = getXMLByFilename( dirname( $_SERVER["SCRIPT_FILENAME"]) . '/config.xml' );

$not_videos = trim($config->not_videos);
$not_videos = explode( ',', $not_videos);
while (list($key, $val) = each($not_videos))
	$not_videos[$key] = trim($val);

mb_internal_encoding( trim( $config->system_encoding ) );

define('NL', "\n");
define('SP', "---------------------------------------------\n");
define('SSP', ". . . . . . . . . . . . . . . . . . . . . . .\n");

$country_list = array( 'Afghanistan', 'Albania', 'Algeria', 'Andorra', 'Angola',
'Antigua and Barbuda', 'Argentina', 'Armenia', 'Australia', 'Austria', 'Azerbaijan',
'Bahamas', 'Bahrain', 'Bangladesh', 'Barbados', 'Belarus', 'Belgium', 'Belize', 'Benin', 
'Bhutan', 'Bolivia', 'Bosnia and Herzegovina', 'Botswana', 'Brazil', 'Brunei', 'Bulgaria', 
'Burkina Faso', 'Burundi', 'Cambodia', 'Cameroon', 'Canada', 'Cape Verde', 
'Central African Republic', 'Chad', 'Chile', 'China', 'Colombi', 'Comoros', 'Congo (Brazzaville)', 
'Congo', 'Costa Rica', 'Cote d\'Ivoire', 'Croatia', 'Cuba', 'Cyprus', 'Czech Republic', 'Denmark', 
'Djibouti', 'Dominica', 'Dominican Republic', 'East Timor (Timor Timur)', 'Ecuador', 'Egypt', 
'El Salvador', 'Equatorial Guinea', 'Eritrea', 'Estonia', 'Ethiopia', 'Fiji', 'Finland', 'France', 
'Gabon', 'Gambia, The', 'Georgia', 'Germany', 'Ghana', 'Greece', 'Grenada', 'Guatemala', 'Guinea', 
'Guinea-Bissau', 'Guyana', 'Haiti', 'Honduras', 'Hungary', 'Iceland', 'India', 'Indonesia', 'Iran', 
'Iraq', 'Ireland', 'Israel', 'Italy', 'Jamaica', 'Japan', 'Jordan', 'Kazakhstan', 'Kenya', 
'Kiribati', 'Korea, North', 'Korea, South', 'Kuwait', 'Kyrgyzstan', 'Laos', 'Latvia', 'Lebanon', 
'Lesotho', 'Liberia', 'Libya', 'Liechtenstein', 'Lithuania', 'Luxembourg', 'Macedonia', 'Madagascar', 
'Malawi', 'Malaysia', 'Maldives', 'Mali', 'Malta', 'Marshall Islands', 'Mauritania', 'Mauritius', 
'Mexico', 'Micronesia', 'Moldova', 'Monaco', 'Mongolia', 'Morocco', 'Mozambique', 'Myanmar', 
'Namibia', 'Nauru', 'Nepa', 'Netherlands', 'New Zealand', 'Nicaragua', 'Niger', 'Nigeria', 'Norway', 
'Oman', 'Pakistan', 'Palau', 'Panama', 'Papua New Guinea', 'Paraguay', 'Peru', 'Philippines', 
'Poland', 'Portugal', 'Qatar', 'Romania', 'Russia', 'Rwanda', 'Saint Kitts and Nevis', 'Saint Lucia', 
'Saint Vincent', 'Samoa', 'San Marino', 'Sao Tome and Principe', 'Saudi Arabia', 'Senegal', 
'Serbia and Montenegro', 'Seychelles', 'Sierra Leone', 'Singapore', 'Slovakia', 'Slovenia', 
'Solomon Islands', 'Somalia', 'South Africa', 'Spain', 'Sri Lanka', 'Sudan', 'Suriname', 
'Swaziland', 'Sweden', 'Switzerland', 'Syria', 'Taiwan', 'Tajikistan', 'Tanzania', 'Thailand', 
'Togo', 'Tonga', 'Trinidad and Tobago', 'Tunisia', 'Turkey', 'Turkmenistan', 'Tuvalu', 'Uganda', 
'Ukraine', 'United Arab Emirates', 'United Kingdom', 'United States', 'Uruguay', 'Uzbekistan', 
'Vanuatu', 'Vatican City', 'Venezuela', 'Vietnam', 'Yemen', 'Zambia', 'Zimbabwe',
'Абхазия', 'Австралия', 'Австрия', 'Азербайджан', 'Азорские острова', 'Аландские острова', 
'Албания', 'Алжир', 'Американское Самоа', 'Ангилья', 'Ангола', 'Андорра', 'Антигуа и Барбуда', 
'Нидерландские Антильские острова', 'Аомынь', 'Аргентина', 'Армения', 'Аруба', 'Афганистан', 
'Багамы', 'Бангладеш', 'Барбадос', 'Бахрейн', 'Белиз', 'Белоруссия', 'Бельгия', 'Бенин', 'Бермуды', 
'Болгария', 'Боливия', 'Босния и Герцеговина', 'Ботсвана', 'Бразилия', 'Бруней', 'Буркина Фасо', 
'Бурунди', 'Бутан', 'Вануату', 'Ватикан', 'Великобритания', 'Венгрия', 'Венесуэла', 
'Британские Виргинские острова', 'Американские Виргинские острова', 'Восточный Тимор', 'Вьетнам', 
'Габон', 'Гаити', 'Гайана', 'Гамбия', 'Гана', 'Гваделупа', 'Гватемала', 'Гвинея', 'Гвинея-Бисау', 
'Германия', 'Гибралтар', 'Гондурас', 'Гонконг', 'Сянган', 'Гренада', 'Гренландия', 'Греция', 
'Грузия', 'Гуам', 'Дания', 'Джерси', 'Джибути', 'Доминика', 'Доминиканская Республика', 'Египет', 
'Замбия', 'Западная Сахара', 'Зимбабве', 'Израиль', 'Индия', 'Индонезия', 'Иордания', 'Ирак', 
'Иран', 'Ирландия', 'Исландия', 'Испания', 'Италия', 'Йемен', 'Кабо-Верде', 'Казахстан', 
'Каймановы острова', 'Камбоджа', 'Камерун', 'Канада', 'Катар', 'Кения', 'Кипр', 'Киргизия', 
'Кирибати', 'Китай', 'Кокосовые острова', 'Колумбия', 'Коморские Острова', 'Республика Конго', 
'Конго', 'ДР Конго', 'КНДР', 'Корея', 'Республика Корея', 'Косово', 'Коста-Рика', 'Кот-д’Ивуар', 
'Куба', 'Кувейт', 'Острова Кука', 'Лаос', 'Латвия', 'Лесото', 'Либерия', 'Ливан', 'Ливия', 'Литва', 
'Лихтенштейн', 'Люксембург', 'Маврикий', 'Мавритания', 'Мадагаскар', 'Мадейра', 'Майотта', 
'Македония', 'Малави', 'Малайзия', 'Мали', 'Мальдивы', 'Мальта', 'Марокко', 'Мартиника', 
'Маршалловы Острова', 'Мексика', 'Федеративные Штаты Микронезии', 'Мозамбик', 'Молдавия', 'Монако', 
'Монголия', 'Монтсеррат', 'Мьянма', 'Мэн', 'Нагорно-Карабахская Республика', 'Намибия', 'Науру', 
'Непал', 'Нигер', 'Нигерия', 'Нидерланды', 'Никарагуа', 'Ниуэ', 'Новая Зеландия', 'Новая Каледония', 
'Норвегия', 'Остров Норфолк', 'ОАЭ', 'Оман', 'Пакистан', 'Палау', 'Палестина', 'Панама', 'Папуа', 
'Парагвай', 'Перу', 'Острова Питкэрн', 'Польша', 'Португалия', 'Приднестровье', 'Пуэрто-Рико', 
'Реюньон', 'Остров Рождества', 'Россия', 'СССР', 'Руанда', 'Румыния', 'Сальвадор', 'Самоа', 
'Сан-Марино', 'Сан-Томе и Принсипи', 'Саудовская Аравия', 'Свазиленд', 'Шпицберген', 
'Остров Святой Елены', 'Северные Марианские острова', 'Турецкая Республика Северного Кипра', 
'Сейшельские Острова', 'Сенегал', 'Сен-Пьер и Микелон', 'Сент-Винсент и Гренадины', 
'Сент-Киттс и Невис', 'Сент-Люсия', 'Сербия', 'Сингапур', 'Сирия', 'Словакия', 'Словения', 'США', 
'Соломоновы Острова', 'Сомали', 'Сомалиленд', 'Судан', 'Суринам', 'Сьерра-Леоне', 'Таджикистан', 
'Таиланд', 'Тайланд', 'Тайвань', 'Тамил-Илам', 'Танзания', 'Тёркс и Кайкос', 'Того', 'Токелау', 'Тонга', 
'Тринидад и Тобаго', 'Тувалу', 'Тунис', 'Туркмения', 'Турция', 'Уганда', 'Узбекистан', 'Украина', 
'Острова Уоллис и Футуна', 'Уругвай', 'Фарерские острова', 'Фиджи', 'Филиппины', 'Финляндия', 
'Фолклендские острова', 'Франция', 'Французская Гвиана', 'Французская Полинезия', 
'Французские Южные и Антарктические Территории', 'Хорватия', 'ЦАР', 'Чад', 'Черногория', 'Чехия', 
'Чили', 'Швейцария', 'Швеция', 'Шри-Ланка', 'Эквадор', 'Экваториальная Гвинея', 'Эритрея', 'Эстония', 
'Эфиопия', 'Южная Георгия и Южные Сандвичевы острова', 'Южная Осетия', 'Южно-Африканская Республика', 
'ЮАР', 'Ямайка', 'Япония');

?>