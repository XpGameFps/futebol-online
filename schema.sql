CREATE TABLE `matches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_home` varchar(255) NOT NULL,
  `team_away` varchar(255) NOT NULL,
  `match_time` datetime NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `streams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` int(11) NOT NULL,
  `stream_url` text NOT NULL,
  `stream_label` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `match_id` (`match_id`),
  CONSTRAINT `streams_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
