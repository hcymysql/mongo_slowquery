/*
SQLyog Ultimate v12.09 (64 bit)
MySQL - 10.1.10-MariaDB-enterprise-log : Database - mongo_slowsql
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`mongo_slowsql` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `mongo_slowsql`;

/*Table structure for table `mongo_slow_query_review` */

CREATE TABLE `mongo_slow_query_review` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键自增ID',
  `checksum` varchar(100) DEFAULT NULL COMMENT 'md5校验值',
  `querysql` text COMMENT '查询语句',
  `ip` varchar(50) DEFAULT NULL COMMENT '主机IP',
  `tag` varchar(100) DEFAULT NULL COMMENT '主机标签',
  `dbname` varchar(100) DEFAULT NULL COMMENT '数据库名',
  `port` int(11) DEFAULT NULL COMMENT '数据库端口',
  `ns` varchar(100) DEFAULT NULL COMMENT '查询集合',
  `origin_user` varchar(100) DEFAULT NULL COMMENT '来源用户',
  `client_ip` varchar(100) DEFAULT NULL COMMENT '应用端IP',
  `exec_time` float DEFAULT NULL COMMENT '执行时间',
  `last_time` datetime DEFAULT NULL COMMENT '最近时间',
  `count` int(11) DEFAULT '1' COMMENT '执行次数',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE_checksum` (`checksum`),
  KEY `IX_last_time` (`last_time`),
  KEY `IX_i_d_p` (`ip`,`dbname`,`port`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='慢SQL日志记录表';

/*Table structure for table `mongo_status_info` */

CREATE TABLE `mongo_status_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键自增ID',
  `ip` varchar(50) DEFAULT NULL COMMENT '输入被监控Mongo的IP地址',
  `tag` varchar(50) NOT NULL COMMENT '输入被监控Mongo的主机名字',
  `user` varchar(100) DEFAULT NULL COMMENT '输入被监控Mongo的用户名',
  `pwd` varchar(100) DEFAULT NULL COMMENT '输入被监控Mongo的密码',
  `port` int(11) DEFAULT NULL COMMENT '输入被监控Mongo的端口号',
  `dbname` varchar(100) DEFAULT NULL COMMENT '输入被监控Mongo的数据库名',
  PRIMARY KEY (`id`),
  KEY `IX_tag` (`tag`),
  KEY `IX_i_d_p` (`ip`,`port`,`dbname`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='监控信息表';

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
