-- phpMyAdmin SQL Dump
-- version 4.7.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 13, 2018 at 04:41 PM
-- Server version: 10.1.29-MariaDB
-- PHP Version: 7.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `dragonquestmonsters`
--

-- --------------------------------------------------------

--
-- Table structure for table `dwm2r_activitymonitor`
--

CREATE TABLE `dwm2r_activitymonitor` (
  `AutoID` int(11) NOT NULL,
  `IPAddress` varchar(15) NOT NULL,
  `UserAgent` varchar(255) NOT NULL,
  `Flags` varchar(10) NOT NULL,
  `Seed` varchar(9) NOT NULL,
  `CreatedDTS` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `dwm2r_activitymonitor`
--

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dwm2r_activitymonitor`
--
ALTER TABLE `dwm2r_activitymonitor`
  ADD PRIMARY KEY (`AutoID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dwm2r_activitymonitor`
--
ALTER TABLE `dwm2r_activitymonitor`
  MODIFY `AutoID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;
