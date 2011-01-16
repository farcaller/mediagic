-- phpMyAdmin SQL Dump
-- version 2.11.8.1deb1ubuntu0.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 22, 2009 at 01:49 PM
-- Server version: 5.0.67
-- PHP Version: 5.2.6-2ubuntu4.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `mediagic`
--

-- --------------------------------------------------------

--
-- Table structure for table `cast`
--

CREATE TABLE IF NOT EXISTS `cast` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `actor` varchar(128) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9436 ;

-- --------------------------------------------------------

--
-- Table structure for table `commands`
--

CREATE TABLE IF NOT EXISTS `commands` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `command` varchar(64) NOT NULL,
  `target` longblob NOT NULL,
  `timestamp` int(12) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=579 ;

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE IF NOT EXISTS `companies` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `company` varchar(128) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=893 ;

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE IF NOT EXISTS `countries` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `country` varchar(128) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=294 ;

-- --------------------------------------------------------

--
-- Table structure for table `directors`
--

CREATE TABLE IF NOT EXISTS `directors` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `director` varchar(128) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1196 ;

-- --------------------------------------------------------

--
-- Table structure for table `genres`
--

CREATE TABLE IF NOT EXISTS `genres` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `genre` varchar(128) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=650 ;

-- --------------------------------------------------------

--
-- Table structure for table `torrents`
--

CREATE TABLE IF NOT EXISTS `torrents` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `torrentid` int(10) unsigned NOT NULL,
  `tracker` varchar(128) NOT NULL,
  `title` varchar(128) NOT NULL,
  `data_file_hash` varchar(64) NOT NULL,
  `torrent_filename` text NOT NULL,
  `data_file_dir` text NOT NULL,
  `data_file_name` text NOT NULL,
  `timestamp` int(12) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1531 ;

-- --------------------------------------------------------

--
-- Table structure for table `video`
--

CREATE TABLE IF NOT EXISTS `video` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `title` varchar(128) NOT NULL,
  `filename` text NOT NULL,
  `plot` text NOT NULL,
  `rating` varchar(128) NOT NULL,
  `userrating` varchar(50) NOT NULL,
  `length` int(10) unsigned NOT NULL,
  `year` int(10) unsigned NOT NULL,
  `coverfile` text NOT NULL,
  `audio` text NOT NULL,
  `video` text NOT NULL,
  `file_hash` varchar(64) NOT NULL,
  `file_size` int(15) unsigned NOT NULL,
  `translation` varchar(128) NOT NULL,
  `fan_art` text NOT NULL,
  `part` tinyint(1) NOT NULL default '1',
  `timestamp` int(12) NOT NULL,
  `scrubber` varchar(128) NOT NULL,
  `scrub_title` varchar(128) NOT NULL,
  `hide` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2133 ;

-- --------------------------------------------------------

--
-- Table structure for table `videocast`
--

CREATE TABLE IF NOT EXISTS `videocast` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `video_id` int(10) unsigned NOT NULL,
  `actor_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12607 ;

-- --------------------------------------------------------

--
-- Table structure for table `videocompanies`
--

CREATE TABLE IF NOT EXISTS `videocompanies` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `video_id` int(10) unsigned NOT NULL,
  `company_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2179 ;

-- --------------------------------------------------------

--
-- Table structure for table `videocountries`
--

CREATE TABLE IF NOT EXISTS `videocountries` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `video_id` int(10) unsigned NOT NULL,
  `country_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1698 ;

-- --------------------------------------------------------

--
-- Table structure for table `videodirectors`
--

CREATE TABLE IF NOT EXISTS `videodirectors` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `video_id` int(10) unsigned NOT NULL,
  `director_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2247 ;

-- --------------------------------------------------------

--
-- Table structure for table `videogenres`
--

CREATE TABLE IF NOT EXISTS `videogenres` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `video_id` int(10) unsigned NOT NULL,
  `genre_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3771 ;

-- --------------------------------------------------------

--
-- Table structure for table `videoscrubbers`
--

CREATE TABLE IF NOT EXISTS `videoscrubbers` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `video_id` int(10) NOT NULL,
  `scrubber` varchar(128) NOT NULL,
  `scrubber_id` int(10) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1533 ;
