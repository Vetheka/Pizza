/*
SQLyog Enterprise - MySQL GUI v8.18 
MySQL - 5.5.5-10.4.32-MariaDB : Database - pizza
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`pizza` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `pizza`;

/*Table structure for table `categories` */

DROP TABLE IF EXISTS `categories`;

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `categories` */

insert  into `categories`(`category_id`,`category_name`) values (4,'Dessert'),(1,'Drink'),(3,'Pizza'),(2,'Salad');

/*Table structure for table `discounts` */

DROP TABLE IF EXISTS `discounts`;

CREATE TABLE `discounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('top_selling','menu_item') DEFAULT NULL,
  `percent` int(11) DEFAULT NULL,
  `enabled` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `discounts` */

/*Table structure for table `employees` */

DROP TABLE IF EXISTS `employees`;

CREATE TABLE `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `employees` */

insert  into `employees`(`id`,`role`,`password`) values (1,'cashier','b4c94003c562bb0d89535eca77f07284fe560fd48a7cc1ed99f0a56263d616ba'),(2,'manager','866485796cfa8d7c0cf7111640205b83076433547577511d81f8030ae99ecea5'),(3,'owner','43a0d17178a9d26c9e0fe9a74b0b45e38d32f27aed887a008a54bf6e033bf7b9');

/*Table structure for table `inventory` */

DROP TABLE IF EXISTS `inventory`;

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(10,3) NOT NULL DEFAULT 0.000,
  `unit` enum('kg','g','l','ml','pcs','box','pack') NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `low_stock_threshold` decimal(10,3) NOT NULL DEFAULT 1.000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `inventory` */

insert  into `inventory`(`id`,`item_name`,`description`,`quantity`,`unit`,`category`,`low_stock_threshold`,`created_at`,`updated_at`) values (1,'','','10.000','kg','','0.000','2025-06-17 08:30:43','2025-06-17 08:53:21'),(2,'lemon flour','','5.000','pack','Flour','5.000','2025-06-17 08:44:45','2025-06-17 08:57:40');

/*Table structure for table `order_details` */

DROP TABLE IF EXISTS `order_details`;

CREATE TABLE `order_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `order_details` */

/*Table structure for table `order_items` */

DROP TABLE IF EXISTS `order_items`;

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `fk_product` (`product_id`),
  KEY `order_items_ibfk_1` (`order_id`),
  CONSTRAINT `fk_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `order_items` */

insert  into `order_items`(`order_item_id`,`order_id`,`product_id`,`quantity`,`price`) values (1,1,1,2,'8.99'),(2,1,4,1,'9.49'),(3,2,1,1,'8.99'),(4,3,1,1,'8.99'),(5,4,1,3,'8.99'),(6,10,1,1,'8.99'),(7,10,4,1,'9.49');

/*Table structure for table `order_items1` */

DROP TABLE IF EXISTS `order_items1`;

CREATE TABLE `order_items1` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) GENERATED ALWAYS AS (`price` * `quantity`) STORED,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `order_items1` */

insert  into `order_items1`(`item_id`,`order_id`,`product_id`,`quantity`,`price`,`total`,`discount_percent`) values (1,72,1,13,'7.64','99.32','0.00'),(2,73,2,1,'8.92','8.92','0.00'),(3,73,3,1,'10.19','10.19','0.00'),(4,74,2,1,'8.92','8.92','0.00'),(5,75,2,2,'8.92','17.84','0.00'),(6,76,1,1,'7.64','7.64','0.00'),(7,76,2,1,'8.92','8.92','0.00'),(8,76,3,1,'10.19','10.19','0.00'),(9,76,6,1,'4.25','4.25','0.00'),(10,77,3,3,'10.19','30.57','0.00'),(11,77,1,1,'7.64','7.64','0.00'),(12,77,2,1,'8.92','8.92','0.00'),(13,77,7,1,'11.89','11.89','0.00'),(14,77,6,1,'4.25','4.25','0.00');

/*Table structure for table `orders` */

DROP TABLE IF EXISTS `orders`;

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `order_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `orders` */

insert  into `orders`(`order_id`,`user_id`,`total`,`status`,`order_date`) values (1,4,'27.47','Pending','2025-05-09 08:15:55'),(2,4,'8.99','Pending','2025-05-09 08:21:04'),(3,4,'8.99','Pending','2025-05-09 08:25:01'),(4,4,'26.97','Pending','2025-05-09 08:25:09'),(5,4,'8.99','Pending','2025-05-09 08:27:25'),(6,4,'8.99','Pending','2025-05-09 08:31:48'),(7,4,'8.99','Pending','2025-05-09 08:31:54'),(8,4,'8.99','Pending','2025-05-09 08:34:10'),(9,4,'8.99','Pending','2025-05-09 08:36:22'),(10,4,'18.48','Pending','2025-05-09 08:50:53'),(11,1,'27.47','Pending','2025-05-09 03:56:46'),(17,1,'36.46','Pending','2025-05-09 04:02:54');

/*Table structure for table `orders1` */

DROP TABLE IF EXISTS `orders1`;

CREATE TABLE `orders1` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `order_date` datetime NOT NULL,
  `status` varchar(50) NOT NULL,
  `table_number` int(11) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `orders1` */

insert  into `orders1`(`order_id`,`user_id`,`total`,`order_date`,`status`,`table_number`,`discount`) values (1,1,'36.46','2025-05-09 04:06:53','Done',NULL,'0.00'),(2,1,'36.46','2025-05-09 04:08:01','Done',NULL,'0.00'),(3,1,'28.97','2025-05-09 04:22:10','Done',NULL,'0.00'),(4,1,'9.49','2025-05-09 04:22:33','Done',NULL,'0.00'),(5,1,'18.48','2025-05-09 04:30:24','Done',NULL,'0.00'),(6,1,'28.97','2025-05-09 04:33:15','Done',1,'0.00'),(7,1,'8.99','2025-05-09 04:42:10','Done',1,'0.00'),(8,1,'13.99','2025-05-09 04:43:34','Done',1,'0.00'),(9,1,'32.47','2025-05-09 04:46:21','Done',2,'0.00'),(10,1,'0.00','2025-05-09 04:48:13','Done',3,'0.00'),(11,1,'9.49','2025-05-09 04:51:41','Done',4,'0.00'),(12,1,'8.99','2025-05-09 04:54:00','Done',5,'0.00'),(13,1,'8.99','2025-05-09 04:55:05','Done',6,'0.00'),(14,1,'8.99','2025-05-09 04:56:31','Done',6,'0.00'),(15,1,'8.99','2025-05-09 04:56:39','Done',7,'0.00'),(16,1,'49.95','2025-05-09 05:13:50','Done',8,'0.00'),(17,1,'0.00','2025-05-09 05:14:09','Done',8,'0.00'),(18,1,'0.00','2025-05-09 05:15:21','Done',8,'0.00'),(19,1,'8.99','2025-05-09 05:15:40','Done',9,'0.00'),(20,1,'0.00','2025-05-09 05:15:57','Done',9,'0.00'),(21,1,'19.48','2025-05-09 05:17:12','Done',10,'0.00'),(22,1,'0.00','2025-05-09 05:17:16','Done',10,'0.00'),(23,1,'29.97','2025-05-09 05:22:12','Done',11,'0.00'),(24,1,'19.48','2025-05-12 02:41:46','Done',11,'0.00'),(25,1,'41.96','2025-05-12 05:37:03','Done',7,'0.00'),(26,1,'0.00','2025-05-12 05:37:37','Done',7,'0.00'),(27,1,'17.98','2025-05-12 05:39:38','Done',8,'0.00'),(28,1,'0.00','2025-05-12 05:40:51','Done',8,'0.00'),(29,1,'8.99','2025-05-12 05:42:51','Done',1,'0.00'),(30,1,'29.97','2025-05-13 02:49:14','Done',2,'0.00'),(31,1,'13.99','2025-05-13 03:07:39','Done',3,'0.00'),(32,1,'51.96','2025-05-13 03:36:19','Done',1,'0.00'),(33,1,'95.91','2025-05-13 05:15:32','Done',2,'0.00'),(34,1,'9.49','2025-05-13 05:17:17','Done',3,'0.00'),(35,1,'8.99','2025-05-13 05:26:17','Done',4,'0.00'),(36,1,'28.47','2025-05-13 05:34:17','Done',1,'0.00'),(37,1,'19.48','2025-05-19 04:13:33','Done',1,'0.00'),(38,1,'26.97','2025-05-21 05:58:38','Done',1,'0.00'),(39,1,'0.00','2025-05-21 09:49:51','Done',2,'0.00'),(40,1,'0.00','2025-05-21 09:53:58','Done',8,'0.00'),(41,1,'0.00','2025-05-21 10:01:20','Done',1,'0.00'),(42,1,'0.00','2025-06-03 04:01:05','Done',1,'0.00'),(43,1,'0.00','2025-06-04 02:55:58','Done',1,'0.00'),(44,1,'0.00','2025-06-04 03:00:04','Done',2,'0.00'),(45,1,'0.00','2025-06-04 03:03:15','Done',1,'0.00'),(46,1,'0.00','2025-06-04 03:05:50','Done',1,'0.00'),(47,1,'0.00','2025-06-04 03:11:01','Done',1,'0.00'),(48,1,'0.00','2025-06-04 03:18:15','Done',16,'0.00'),(49,1,'0.00','2025-06-04 03:42:39','Done',1,'0.00'),(50,1,'0.00','2025-06-04 03:42:44','Done',1,'0.00'),(51,1,'0.00','2025-06-04 03:49:57','Done',1,'0.00'),(52,1,'0.00','2025-06-06 02:54:31','Done',1,'0.00'),(53,1,'0.00','2025-06-06 04:51:17','Done',1,'0.00'),(54,1,'0.00','2025-06-06 05:00:17','Done',1,'0.00'),(55,1,'0.00','2025-06-06 05:24:28','Done',1,'0.00'),(56,1,'0.00','2025-06-06 05:27:27','Done',1,'0.00'),(57,1,'0.00','2025-06-06 05:27:49','Done',1,'0.00'),(58,1,'0.00','2025-06-06 05:30:08','Done',1,'0.00'),(59,1,'0.00','2025-06-08 18:16:11','Done',2,'0.00'),(60,1,'0.00','2025-06-09 02:27:32','Done',3,'0.00'),(61,1,'0.00','2025-06-09 02:29:46','Done',4,'0.00'),(62,1,'0.00','2025-06-09 02:30:01','Done',1,'0.00'),(63,1,'0.00','2025-06-09 02:39:37','Done',5,'0.00'),(64,1,'0.00','2025-06-10 03:12:24','Done',6,'0.00'),(65,1,'7.64','2025-06-10 03:13:17','Done',6,'0.00'),(66,1,'7.64','2025-06-10 03:15:26','Done',6,'0.00'),(67,1,'7.64','2025-06-10 03:16:48','Done',6,'0.00'),(68,1,'7.64','2025-06-10 03:16:53','Done',6,'0.00'),(69,1,'7.64','2025-06-10 03:17:48','Done',6,'0.00'),(70,1,'44.16','2025-06-10 03:18:43','Done',7,'0.00'),(71,1,'91.68','2025-06-10 03:20:58','Done',1,'0.00'),(72,1,'99.32','2025-06-10 03:23:20','Done',1,'0.00'),(73,1,'19.11','2025-06-10 03:23:48','Done',2,'0.00'),(74,1,'8.92','2025-06-10 03:28:01','Done',3,'0.00'),(75,1,'17.84','2025-06-10 04:11:54','Done',4,'0.00'),(76,1,'31.00','2025-06-16 02:52:50','Done',5,'0.00'),(77,1,'63.27','2025-06-20 02:54:38','Pending',5,'0.00');

/*Table structure for table `products` */

DROP TABLE IF EXISTS `products`;

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount` decimal(5,2) DEFAULT 0.00,
  `category_id` int(11) DEFAULT NULL,
  `has_discount` tinyint(1) DEFAULT 0,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `category` varchar(100) DEFAULT NULL,
  `is_top_selling` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `promo_type` varchar(50) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`product_id`),
  KEY `fk_category` (`category_id`),
  CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `products` */

insert  into `products`(`product_id`,`product_name`,`price`,`discount`,`category_id`,`has_discount`,`discount_percent`,`category`,`is_top_selling`,`is_active`,`promo_type`,`image_path`) values (1,'Margherita Pizza','8.99','0.00',3,0,'0.00',NULL,0,1,NULL,NULL),(2,'Pepperoni Pizza','10.49','0.00',3,0,'0.00',NULL,0,1,NULL,NULL),(3,'BBQ Chicken Pizza','11.99','0.00',3,0,'0.00',NULL,0,1,'Top Selling',NULL),(4,'Veggie Pizza','9.49','0.00',NULL,1,'20.00',NULL,0,1,NULL,NULL),(6,'salad','5.00','0.00',2,0,'0.00',NULL,0,1,NULL,NULL),(7,'Veggie Pizza','13.99','0.00',NULL,1,'20.00',NULL,0,1,NULL,NULL),(8,'Margarita Salad','8.99','0.00',2,0,'0.00',NULL,0,1,NULL,NULL),(13,'Coca Cola','1.00','0.00',1,0,'0.00',NULL,0,1,NULL,NULL),(14,'Sprite','1.00','0.00',1,0,'0.00',NULL,0,1,NULL,NULL);

/*Table structure for table `promotions` */

DROP TABLE IF EXISTS `promotions`;

CREATE TABLE `promotions` (
  `promo_type` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`promo_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `promotions` */

insert  into `promotions`(`promo_type`,`is_active`,`discount_percent`) values ('menu',1,'15.00'),('top_items',1,'9.00');

/*Table structure for table `sales` */

DROP TABLE IF EXISTS `sales`;

CREATE TABLE `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `cashier_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `sales` */

/*Table structure for table `sales_items` */

DROP TABLE IF EXISTS `sales_items`;

CREATE TABLE `sales_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) DEFAULT NULL,
  `item_name` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  CONSTRAINT `sales_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `sales_items` */

/*Table structure for table `staff` */

DROP TABLE IF EXISTS `staff`;

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`staff_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `staff` */

insert  into `staff`(`staff_id`,`user_id`,`name`,`phone`,`email`,`address`,`position`,`salary`) values (1,1,'Sin Reaksa','0123456789','alice@example.com','123 POS St.','Cashier','500.00'),(2,2,'Lin lada','0987654321','bob@example.com','456 POS Ave.','Manager','2000.00'),(5,4,'Yu linglign','0968817699','lingling@gmail.com','Battambang',NULL,'1230.00');

/*Table structure for table `staff_info` */

DROP TABLE IF EXISTS `staff_info`;

CREATE TABLE `staff_info` (
  `staff_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`staff_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `staff_info_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `staff_info` */

/*Table structure for table `student` */

DROP TABLE IF EXISTS `student`;

CREATE TABLE `student` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `phone` varchar(15) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `student` */

/*Table structure for table `users` */

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role` enum('cashier','manager','owner') NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `users` */

insert  into `users`(`id`,`role`,`password`) values (1,'cashier','b4c94003c562bb0d89535eca77f07284fe560fd48a7cc1ed99f0a56263d616ba'),(2,'manager','866485796cfa8d7c0cf7111640205b83076433547577511d81f8030ae99ecea5'),(3,'owner','43a0d17178a9d26c9e0fe9a74b0b45e38d32f27aed887a008a54bf6e033bf7b9'),(4,'cashier','');

/*Table structure for table `inventory_view` */

DROP TABLE IF EXISTS `inventory_view`;

/*!50001 DROP VIEW IF EXISTS `inventory_view` */;
/*!50001 DROP TABLE IF EXISTS `inventory_view` */;

/*!50001 CREATE TABLE  `inventory_view`(
 `id` int(11) ,
 `item_name` varchar(100) ,
 `description` text ,
 `stock_quantity` decimal(10,3) ,
 `unit` enum('kg','g','l','ml','pcs','box','pack') ,
 `category` varchar(50) ,
 `low_stock_threshold` decimal(10,3) ,
 `created_at` timestamp ,
 `updated_at` timestamp 
)*/;

/*View structure for view inventory_view */

/*!50001 DROP TABLE IF EXISTS `inventory_view` */;
/*!50001 DROP VIEW IF EXISTS `inventory_view` */;

/*!50001 CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `inventory_view` AS select `inventory`.`id` AS `id`,`inventory`.`item_name` AS `item_name`,`inventory`.`description` AS `description`,`inventory`.`quantity` AS `stock_quantity`,`inventory`.`unit` AS `unit`,`inventory`.`category` AS `category`,`inventory`.`low_stock_threshold` AS `low_stock_threshold`,`inventory`.`created_at` AS `created_at`,`inventory`.`updated_at` AS `updated_at` from `inventory` */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
