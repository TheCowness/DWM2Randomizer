-- phpMyAdmin SQL Dump
-- version 4.7.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 04, 2018 at 03:09 AM
-- Server version: 10.1.29-MariaDB
-- PHP Version: 7.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dragonquestmonsters`
--

-- --------------------------------------------------------

--
-- Table structure for table `dragonwarriormonsters2`
--

CREATE TABLE `dragonwarriormonsters2` (
  `id` int(11) NOT NULL,
  `name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `dragonwarriormonsters2`
--

INSERT INTO `dragonwarriormonsters2` (`id`, `name`) VALUES
(1, 'DrakSlime'),
(2, 'SpotSlime'),
(3, 'WingSlime'),
(4, 'TreeSlime'),
(5, 'Snaily'),
(6, 'SlimeNite'),
(7, 'Babble'),
(8, 'BoxSlime'),
(9, 'PearlGel'),
(10, 'Slime'),
(11, 'Healer'),
(12, 'FangSlime'),
(13, 'RockSlime'),
(14, 'SlimeBorg'),
(15, 'Slabbit'),
(16, 'KingSlime'),
(17, 'Metaly'),
(18, 'Metabble'),
(19, 'SpotKing'),
(20, 'TropicGel'),
(21, 'MimeSlime'),
(22, 'HaloSlime'),
(23, 'MetalKing'),
(24, 'GoldSlime'),
(25, 'GranSlime'),
(26, 'WonderEgg'),
(36, 'DragonKid'),
(37, 'Tortragon'),
(38, 'Pteranod'),
(39, 'Gasgon'),
(40, 'FairyDrak'),
(41, 'LizardMan'),
(42, 'Poisongon'),
(43, 'Swordgon'),
(44, 'Drygon'),
(45, 'Dragon'),
(46, 'MiniDrak'),
(47, 'MadDragon'),
(48, 'Rayburn'),
(49, 'Chamelgon'),
(50, 'LizardFly'),
(51, 'Andreal'),
(52, 'KingCobra'),
(53, 'Vampirus'),
(54, 'SnakeBat'),
(55, 'Spikerous'),
(56, 'GreatDrak'),
(57, 'Crestpent'),
(58, 'WingSnake'),
(59, 'Coatol'),
(60, 'Orochi'),
(61, 'BattleRex'),
(62, 'SkyDragon'),
(63, 'Serpentia'),
(64, 'Divinegon'),
(65, 'Orligon'),
(66, 'GigaDraco'),
(71, 'Tonguella'),
(72, 'Almiraj'),
(73, 'CatFly'),
(74, 'PillowRat'),
(75, 'Saccer'),
(76, 'GulpBeast'),
(77, 'Skullroo'),
(78, 'WindBeast'),
(79, 'Beavern'),
(80, 'Antbear'),
(81, 'SuperTen'),
(82, 'IronTurt'),
(83, 'Mommonja'),
(84, 'HammerMan'),
(85, 'Grizzly'),
(86, 'Yeti'),
(87, 'ArrowDog'),
(88, 'NoctoKing'),
(89, 'BeastNite'),
(90, 'MadGopher'),
(91, 'FairyRat'),
(92, 'Unicorn'),
(93, 'Goategon'),
(94, 'WildApe'),
(95, 'Trumpeter'),
(96, 'KingLeo'),
(97, 'DarkHorn'),
(98, 'MadCat'),
(99, 'BigEye'),
(100, 'Gorago'),
(101, 'CatMage'),
(102, 'Dumbira'),
(106, 'Picky'),
(107, 'Wyvern'),
(108, 'BullBird'),
(109, 'Florajay'),
(110, 'DuckKite'),
(111, 'MadPecker'),
(112, 'MadRaven'),
(113, 'MistyWing'),
(114, 'AquaHawk'),
(115, 'Dracky'),
(116, 'KiteHawk'),
(117, 'BigRoost'),
(118, 'StubBird'),
(119, 'LandOwl'),
(120, 'MadGoose'),
(121, 'MadCondor'),
(122, 'Emyu'),
(123, 'Blizzardy'),
(124, 'Phoenix'),
(125, 'ZapBird'),
(126, 'Garudian'),
(127, 'WhipBird'),
(128, 'FunkyBird'),
(129, 'RainHawk'),
(130, 'Azurile'),
(131, 'Shantak'),
(132, 'CragDevil'),
(141, 'MadPlant'),
(142, 'FireWeed'),
(143, 'FloraMan'),
(144, 'WingTree'),
(145, 'CactiBall'),
(146, 'Gulpple'),
(147, 'Toadstool'),
(148, 'AmberWeed'),
(149, 'Slurperon'),
(150, 'Stubsuck'),
(151, 'Oniono'),
(152, 'DanceVegi'),
(153, 'TreeBoy'),
(154, 'Devipine'),
(155, 'FaceTree'),
(156, 'HerbMan'),
(157, 'BeanMan'),
(158, 'EvilSeed'),
(159, 'ManEater'),
(160, 'Snapper'),
(161, 'GhosTree'),
(162, 'Rosevine'),
(163, 'Egdracil'),
(164, 'Warubou'),
(165, 'Watabou'),
(166, 'Eggplaton'),
(167, 'FooHero'),
(176, 'GiantSlug'),
(177, 'Catapila'),
(178, 'Gophecada'),
(179, 'Butterfly'),
(180, 'WeedBug'),
(181, 'GiantWorm'),
(182, 'Lipsy'),
(183, 'StagBug'),
(184, 'Pyuro'),
(185, 'ArmyAnt'),
(186, 'GoHopper'),
(187, 'TailEater'),
(188, 'ArmorPede'),
(189, 'Eyeder'),
(190, 'GiantMoth'),
(191, 'Droll'),
(192, 'ArmyCrab'),
(193, 'MadHornet'),
(194, 'Belzebub'),
(195, 'WarMantis'),
(196, 'HornBeet'),
(197, 'Sickler'),
(198, 'Armorpion'),
(199, 'Digster'),
(200, 'Skularach'),
(201, 'MultiEyes'),
(211, 'Pixy'),
(212, 'MedusaEye'),
(213, 'AgDevil'),
(214, 'Demonite'),
(215, 'DarkEye'),
(216, 'EyeBall'),
(217, 'SkulRider'),
(218, 'EvilBeast'),
(219, 'Bubblemon'),
(220, '1EyeClown'),
(221, 'Gremlin'),
(222, 'ArcDemon'),
(223, 'Lionex'),
(224, 'GoatHorn'),
(225, 'Orc'),
(226, 'Ogre'),
(227, 'GateGuard'),
(228, 'ChopClown'),
(229, 'BossTroll'),
(230, 'Grendal'),
(231, 'Akubar'),
(232, 'MadKnight'),
(233, 'EvilWell'),
(234, 'Gigantes'),
(235, 'Centasaur'),
(236, 'EvilArmor'),
(237, 'Jamirus'),
(238, 'Durran'),
(239, 'Titanis'),
(240, 'LampGenie'),
(246, 'Spooky'),
(247, 'Skullgon'),
(248, 'PutrePup'),
(249, 'RotRaven'),
(250, 'Mummy'),
(251, 'DarkCrab'),
(252, 'DeadNite'),
(253, 'Shadow'),
(254, 'Skulpent'),
(255, 'Hork'),
(256, 'Mudron'),
(257, 'NiteWhip'),
(258, 'WindMerge'),
(259, 'Reaper'),
(260, 'Inverzon'),
(261, 'FoxFire'),
(262, 'CaptDead'),
(263, 'DeadNoble'),
(264, 'WhiteKing'),
(265, 'BoneSlave'),
(266, 'Skeletor'),
(267, 'Servant'),
(268, 'Lazamanus'),
(269, 'Copycat'),
(270, 'MadSpirit'),
(271, 'PomPomBom'),
(272, 'Niterich'),
(281, 'JewelBag'),
(282, 'EvilWand'),
(283, 'MadCandle'),
(284, 'CoilBird'),
(285, 'Facer'),
(286, 'SpikyBoy'),
(287, 'MadMirror'),
(288, 'RogueNite'),
(289, 'Puppetor'),
(290, 'Goopi'),
(291, 'Voodoll'),
(292, 'MetalDrak'),
(293, 'Balzak'),
(294, 'SabreMan'),
(295, 'CurseLamp'),
(296, 'Brushead'),
(297, 'Roboster'),
(298, 'Roboster2'),
(299, 'EvilPot'),
(300, 'Gismo'),
(301, 'LavaMan'),
(302, 'IceMan'),
(303, 'Mimic'),
(304, 'Exaucers'),
(305, 'MudDoll'),
(306, 'Golem'),
(307, 'StoneMan'),
(308, 'BombCrag'),
(309, 'GoldGolem'),
(310, 'DarkMate'),
(311, 'ProtoMech'),
(312, 'CloudKing'),
(316, 'Petiteel'),
(317, 'Moray'),
(318, 'WalrusMan'),
(319, 'RayGigas'),
(320, 'Anemon'),
(321, 'Aquarella'),
(322, 'Merman'),
(323, 'Octokid'),
(324, 'PutreFish'),
(325, 'Octoreach'),
(326, 'Angleron'),
(327, 'FishRider'),
(328, 'RushFish'),
(329, 'Gamanian'),
(330, 'Clawster'),
(331, 'CancerMan'),
(332, 'RogueWave'),
(333, 'Scallopa'),
(334, 'SeaHorse'),
(335, 'HoodSquid'),
(336, 'MerTiger'),
(337, 'AxeShark'),
(338, 'Octogon'),
(339, 'KingSquid'),
(340, 'Digong'),
(341, 'WhaleMage'),
(342, 'Aquadon'),
(343, 'Octoraid'),
(344, 'Grakos'),
(345, 'Poseidon'),
(346, 'Pumpoise'),
(347, 'Starfish'),
(351, 'DracoLord'),
(352, 'DracoLord2'),
(353, 'LordDraco'),
(354, 'Hargon'),
(355, 'Sidoh'),
(356, 'Genosidoh'),
(357, 'Baramos'),
(358, 'Zoma'),
(359, 'AsuraZoma'),
(360, 'Pizzaro'),
(361, 'PsychoPiz'),
(362, 'Esterk'),
(363, 'Mirudraas'),
(364, 'Mirudraas2'),
(365, 'Mudou'),
(366, 'DeathMore'),
(367, 'DeathMore2'),
(368, 'DeathMore3'),
(369, 'Darkdrium'),
(370, 'Orgodemir'),
(371, 'Orgodemir2'),
(372, 'Darck');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dragonwarriormonsters2`
--
ALTER TABLE `dragonwarriormonsters2`
  ADD PRIMARY KEY (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;