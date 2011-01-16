<?php

require_once('functions.php');

getMediagicDBResult( 'DELETE FROM cast;' );
getMediagicDBResult( 'DELETE FROM companies;' );
getMediagicDBResult( 'DELETE FROM countries;' );
getMediagicDBResult( 'DELETE FROM directors;' );
getMediagicDBResult( 'DELETE FROM genres;' );
getMediagicDBResult( 'DELETE FROM torrents;' );
getMediagicDBResult( 'DELETE FROM video;' );
getMediagicDBResult( 'DELETE FROM videocast;' );
getMediagicDBResult( 'DELETE FROM videocompanies;' );
getMediagicDBResult( 'DELETE FROM videocountries;' );
getMediagicDBResult( 'DELETE FROM videodirectors;' );
getMediagicDBResult( 'DELETE FROM videogenres;' );
getMediagicDBResult( 'DELETE FROM videoscrubbers;' );



?>