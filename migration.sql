DROP TABLE IF EXISTS `transaction`;
DROP TABLE IF EXISTS `batch`;
DROP TABLE IF EXISTS `card_type`;
DROP TABLE IF EXISTS `merchant`;
DROP TABLE IF EXISTS `transaction_type`;

CREATE TABLE `batch` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ref_num` VARCHAR(24) NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ref_num_date_UNIQUE` (`ref_num`,`date`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;


CREATE TABLE `card_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;


CREATE TABLE `merchant` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `merchant_id` varchar(18) NOT NULL,
  `merchant_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;


CREATE TABLE `transaction_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;


CREATE TABLE `transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `merchant_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `type` int(11) NOT NULL,
  `card_type` int(11) NOT NULL,
  `card_number` varchar(20) NOT NULL,
  `amount` decimal(8,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `batch_idx` (`batch_id`),
  KEY `transaction_merchant_idx` (`merchant_id`),
  KEY `transaction_card_type_idx` (`card_type`),
  KEY `transaction_type_idx` (`type`),
  CONSTRAINT `transaction_batch` FOREIGN KEY (`batch_id`) REFERENCES `batch` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `transaction_card_type` FOREIGN KEY (`card_type`) REFERENCES `card_type` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `transaction_merchant` FOREIGN KEY (`merchant_id`) REFERENCES `merchant` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `transaction_type` FOREIGN KEY (`type`) REFERENCES `transaction_type` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `transaction_merge`;
CREATE VIEW `transaction_merge` 
SELECT b.ref_num,b.date as 'batch_date', m.merchant_id, m.merchant_name, t.date, tt.name as 'transaction_type', ct.name as 'card_type', t.card_number,t.amount FROM transaction as t 
LEFT JOIN merchant as m ON t.merchant_id = m.id
LEFT JOIN batch as b ON t.batch_id = b.id
LEFT JOIN transaction_type tt ON t.type = tt.id
LEFT JOIN card_type ct ON t.card_type = ct.id;