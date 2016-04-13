/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `export` (
  `id_export` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `export_type` varchar(50) NOT NULL,
  `filename` varchar(255) NOT NULL DEFAULT '',
  `file_content` longblob NOT NULL,
  `generated_at` datetime NOT NULL,
  `downloaded_at` datetime DEFAULT NULL,
  `generated_by` varchar(50) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_export`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
/*!50100 PARTITION BY RANGE (id_export)
(PARTITION p0 VALUES LESS THAN (1000) ENGINE = InnoDB,
 PARTITION p1 VALUES LESS THAN (2000) ENGINE = InnoDB,
 PARTITION p2 VALUES LESS THAN (3000) ENGINE = InnoDB,
 PARTITION p3 VALUES LESS THAN (4000) ENGINE = InnoDB,
 PARTITION p4 VALUES LESS THAN MAXVALUE ENGINE = InnoDB) */;
/*!40101 SET character_set_client = @saved_cs_client */;