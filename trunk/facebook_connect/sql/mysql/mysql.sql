CREATE TABLE IF NOT EXISTS `facebookconnectuser` (
  `fb_uid` bigint NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY  (`fb_uid`),
  KEY `user_id_key` ( `user_id` )
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
