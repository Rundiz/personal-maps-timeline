-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 22, 2024 at 01:40 PM
-- Server version: 11.3.2-MariaDB
-- PHP Version: 8.2.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `personal_location_history`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity`
--

DROP TABLE IF EXISTS `activity`;
CREATE TABLE IF NOT EXISTS `activity` (
  `activity_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `segment_id` bigint(20) NOT NULL COMMENT 'refer to semanticsegments.id',
  `start_latLng` varchar(100) DEFAULT NULL,
  `end_latLng` varchar(100) DEFAULT NULL,
  `distanceMeters` decimal(30,20) DEFAULT NULL,
  `probability` decimal(30,20) DEFAULT NULL,
  `topCandidate_type` varchar(100) DEFAULT NULL,
  `topCandidate_probability` decimal(30,20) DEFAULT NULL,
  `parking_location_latLng` varchar(100) DEFAULT NULL,
  `parking_startTime` datetime DEFAULT NULL COMMENT 'based on Google maps timeline exported date/time (local date/time)',
  PRIMARY KEY (`activity_id`),
  KEY `segment_id` (`segment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `semanticsegments`
--

DROP TABLE IF EXISTS `semanticsegments`;
CREATE TABLE IF NOT EXISTS `semanticsegments` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `startTime` datetime DEFAULT NULL COMMENT 'based on Google maps timeline exported date/time (local date/time)',
  `endTime` datetime DEFAULT NULL COMMENT 'based on Google maps timeline exported date/time (local date/time)',
  `startTimeTimezoneUtcOffsetMinutes` int(11) DEFAULT NULL,
  `endTimeTimezoneUtcOffsetMinutes` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Google maps timeline exported main object.';

-- --------------------------------------------------------

--
-- Table structure for table `timelinememory`
--

DROP TABLE IF EXISTS `timelinememory`;
CREATE TABLE IF NOT EXISTS `timelinememory` (
  `tmem_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `segment_id` bigint(20) NOT NULL COMMENT 'refer to semanticsegments.id',
  `trip_distanceFromOriginKms` int(10) DEFAULT NULL,
  PRIMARY KEY (`tmem_id`),
  KEY `segment_id` (`segment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timelinememory_trip_destinations`
--

DROP TABLE IF EXISTS `timelinememory_trip_destinations`;
CREATE TABLE IF NOT EXISTS `timelinememory_trip_destinations` (
  `tmem_trip_dest_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tmem_id` bigint(20) NOT NULL COMMENT 'refer to timelinememory.tmem_id',
  `identifier_placeId` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`tmem_trip_dest_id`),
  KEY `tmem_id` (`tmem_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timelinepath`
--

DROP TABLE IF EXISTS `timelinepath`;
CREATE TABLE IF NOT EXISTS `timelinepath` (
  `tlp_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `segment_id` bigint(20) NOT NULL COMMENT 'refer to semanticsegments.id',
  `point` varchar(100) DEFAULT NULL,
  `time` datetime DEFAULT NULL COMMENT 'based on Google maps timeline exported date/time (local date/time)',
  PRIMARY KEY (`tlp_id`),
  KEY `segment_id` (`segment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Relationship one(segment) to many(paths).';

-- --------------------------------------------------------

--
-- Table structure for table `visit`
--

DROP TABLE IF EXISTS `visit`;
CREATE TABLE IF NOT EXISTS `visit` (
  `visit_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `segment_id` bigint(20) NOT NULL COMMENT 'refer to semanticsegments.id',
  `hierarchyLevel` int(5) DEFAULT NULL,
  `probability` decimal(30,20) DEFAULT NULL,
  `topCandidate_placeId` varchar(100) DEFAULT NULL,
  `topCandidate_semanticType` varchar(100) DEFAULT NULL,
  `topCandidate_probability` decimal(30,20) DEFAULT NULL,
  `topCandidate_placeLocation_latLng` varchar(100) DEFAULT NULL,
  `isTimelessVisit` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`visit_id`),
  KEY `segment_id` (`segment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
COMMIT;
