<?php
require_once('xmlrpc/xmlrpc.inc');
require_once('xmlrpc/xmlrpc_wrappers.inc');

class RTorrent
{
	public static function dClose( $hash )
	{
		$message = new xmlrpcmsg('d.close', array(new xmlrpcval($hash, "string")));
		$res = self::send( $message );
		if ( $res != false && $GLOBALS['config']->verbose > 1 ) echo 'The downloading was succefully stopped' . NL;
		return $res;
	}
	
	public static function dErase( $hash )
	{
		$message = new xmlrpcmsg('d.erase', array(new xmlrpcval($hash, "string")));
		$res = self::send( $message );
		if ( $res != false && $GLOBALS['config']->verbose > 1 ) echo 'The download was succefully deleted from rtorrent' . NL;
		return $res;
	}
	
	public static function downloadList( $view='main' )
	{
		$message = new xmlrpcmsg('download_list', array(new xmlrpcval($view, "string")));
		$res = self::send( $message );
		if ( $res != false )
		{
			$res_value = $res->value();
			if ( is_array( $res_value->scalarval() ) && count( $res_value->scalarval() ) > 0 )
			{
				foreach ( $res_value->scalarval() as $value )
					$hashes[] = $value->scalarval();
				return $hashes;
			} else return array();
		} else return $res;
	}

	public static function dGetSizeBytes( $hash )
	{
		$message = new xmlrpcmsg('d.get_size_bytes', array(new xmlrpcval($hash, "string")));
		$res = self::send( $message );
		if ( $res != false )
		{
			$res_value = $res->value();
			$res = $res_value->scalarval();
		}
		return $res;
	}

	public static function dGetCompletedBytes( $hash )
	{
		$message = new xmlrpcmsg('d.get_completed_bytes', array(new xmlrpcval($hash, "string")));
		$res = self::send( $message );
		if ( $res != false )
		{
			$res_value = $res->value();
			$res = $res_value->scalarval();
		}
		return $res;
	}

	public static function dGetDownRate( $hash )
	{
		$message = new xmlrpcmsg('d.get_down_rate', array(new xmlrpcval($hash, "string")));
		$res = self::send( $message );
		if ( $res != false )
		{
			$res_value = $res->value();
			$res = $res_value->scalarval();
		}
		return $res;
	}

	public static function dIsActive( $hash )
	{
		$message = new xmlrpcmsg('d.is_active', array(new xmlrpcval($hash, "string")));
		$res = self::send( $message );
		if ( $res != false )
		{
			$res_value = $res->value();
			$res = $res_value->scalarval();
		}
		return $res;
	}

	public static function dIsHashChecked( $hash )
	{
		$message = new xmlrpcmsg('d.is_hash_checked', array(new xmlrpcval($hash, "string")));
		$res = self::send( $message );
		if ( $res != false )
		{
			$res_value = $res->value();
			$res = $res_value->scalarval();
		}
		return $res;
	}

	public static function dIsHashChecking( $hash )
	{
		$message = new xmlrpcmsg('d.is_hash_checking', array(new xmlrpcval($hash, "string")));
		$res = self::send( $message );
		if ( $res != false )
		{
			$res_value = $res->value();
			$res = $res_value->scalarval();
		}
		return $res;
	}

	public static function dIsOpen( $hash )
	{
		$message = new xmlrpcmsg('d.is_open', array(new xmlrpcval($hash, "string")));
		$res = self::send( $message );
		if ( $res != false )
		{
			$res_value = $res->value();
			$res = $res_value->scalarval();
		}
		return $res;
	}

	public static function dGetUpRate( $hash )
	{
		$message = new xmlrpcmsg('d.get_up_rate', array(new xmlrpcval($hash, "string")));
		$res = self::send( $message );
		if ( $res != false )
		{
			$res_value = $res->value();
			$res = $res_value->scalarval();
		}
		return $res;
	}


	public static function dGetName( $hash )
	{
		$message = new xmlrpcmsg('d.get_name', array(new xmlrpcval($hash, "string")));
		$res = self::send( $message );
		if ( $res != false )
		{
			$res_value = $res->value();
			$res = $res_value->scalarval();
		}
		return $res;
	}

	
	private static function send( $message )
	{
		$rtorrent_attrs = $GLOBALS['config']->email->get_responses->rtorrent_url->attributes();
		if ( $rtorrent_attrs['enable'] == 1 )
		{
			try
			{
				$xc = new xmlrpc_client(trim($GLOBALS['config']->email->get_responses->rtorrent_url));								
				$r = @$xc->send($message);
				if ($r->faultCode()) throw new Exception('Unable to make XMLRPC call to rtorrent: ' . $r->faultString(), $r->faultCode());
				return $r;
			} catch (Exception $e) {
				if ($GLOBALS['config']->verbose>0) echo $e->getMessage() . NL . SP;
				return false;
			}
		} else {
			if ( $GLOBALS['config']->verbose > 0 ) echo 'Controlling of rtorrent is disabled in the config file' . NL;
			return false;
		}
	}
}
?>