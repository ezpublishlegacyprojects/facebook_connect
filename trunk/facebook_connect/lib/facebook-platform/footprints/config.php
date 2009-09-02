<?php

// Get these from http://developers.facebook.com
$api_key = 'b72334f41c909bead38d2d490905e6b0';
$secret  = '1bfe5a2a2a486a522998c4008f675c75';
/* While you're there, you'll also want to set up your callback url to the url
 * of the directory that contains Footprints' index.php, and you can set the
 * framed page URL to whatever you want.  You should also swap the references
 * in the code from http://apps.facebook.com/footprints/ to your framed page URL. */

// The IP address of your database
$db_ip = 'localhost';           

$db_user = 'ezp';
$db_pass = 'ezpezp';

// the name of the database that you create for footprints.
$db_name = 'sf_volano_simon_experimental';

/* create this table on the database:
CREATE TABLE `footprints` (
  `from` int(11) NOT NULL default '0',
  `to` int(11) NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  KEY `from` (`from`),
  KEY `to` (`to`)
)
*/
?>