DROP TABLE IF EXISTS `ls_attachlink`;
CREATE TABLE `ls_attachlink` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `fid` int(10) NOT NULL DEFAULT '0',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1:文章 2：BBS',
  `score` int(10) NOT NULL,
  `linkinfo` text COMMENT '链接信息',
   `otherinfo` text COMMENT '链接信息',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
