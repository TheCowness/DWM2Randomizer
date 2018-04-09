<?php
/* dbsettings.php just overwrites these three variables, forces SSL, and turns off error reporting */
$dbaddress = 'localhost';
$dbuser = 'USERNAME';
$dbpass = 'PASSWORD';
require_once('dbsettings.php');

$db = mysqli_connect($dbaddress,$dbuser,$dbpass);
mysqli_select_db($db,'dragonquestmonsters');
$dbreturn = '';
//echo 'Database initialized.<br>';


//Sends a query to the database.  Return value is stored in dbreturn.
function execute($query){
	global $dbreturn;
	global $db;
	$dbreturn = mysqli_query($db,$query);
	if(!$dbreturn){
		error_log("Error with query: ".$query."\n<br>".mysqli_error($db).'\n<br>');
		echo mysqli_error($db);
	}
}
//Translate dbreturn into a readable PHP array
function get(){
	global $dbreturn;
	global $db;
	$return = array();
	if($dbreturn !== FALSE){
		$array=mysqli_fetch_array($dbreturn);
		if(is_array($array)){
			foreach($array as $key=>$index){
				if(!is_numeric($key)){
					$return[$key] = $array[$key];
				}
			}
		}
	}
	return $return;
}

$initial_seed = 0;

//I'm a fan of memes.  Are you a fan of memes?  Here's a good meme: This is Dragon Warrior III's random number generator.
//Airkix please do not RNG manip my randomizer.
$seed = 0;
$counter = 0;
$discard = 0;

function Random($MultiRandom = true){ //Defaulting MultiRandom to true for this randomizer.
	global $counter;
	global $seed;
	global $discard;
	//If we're using the "Battle RNG" value, we throw away that many values before keeping one.
	$discarded = 0; //Throw away this many values
	if($MultiRandom == true){
		$discarded = $discard + 1;
	}
	for($j = 0; $j < $discarded+1; $j++){
		for ($i = 0; $i < 16; $i++){
			$seed = (($seed << 1) % 65536) ^ ((($seed >> 15) ^ 1) ? 0x1021 : 0);
			if($seed >= 65536) $seed = $seed % 65536;
		}
		$counter++;
		if($counter >= 256) $counter = $counter % 256;
	}
	return ($counter + $seed) % 256;
}





$romData;
$ValidMonsterIDs = array(); //This is the actual ID number of the monster
$ValidMonsterGrowthIndecies = array(); //This is the position of the monster in the "growths" list
$error = false;
$error_message = 'The following errors occurred while generating the new seed:';

$Flags = array();

function DWM2R()
{
	global $Flags;
	global $initial_seed;
	global $romData;
	
	if(array_key_exists("StartingMonster",$_REQUEST)){
		$Flags["StartingMonster"] = trim($_REQUEST["StartingMonster"]);
	}else{
		$Flags["StartingMonster"] = 0;
	}
	if(array_key_exists("Growth",$_REQUEST)){
		$Flags["Growth"] = trim($_REQUEST["Growth"]);
	}else{
		$Flags["Growth"] = 'None';
	}
	if(array_key_exists("Resistance",$_REQUEST)){
		$Flags["Resistance"] = trim($_REQUEST["Resistance"]);
	}else{
		$Flags["Resistance"] = 'None';
	}
	if(array_key_exists("Skills",$_REQUEST)){
		$Flags["Skills"] = trim($_REQUEST["Skills"]);
	}else{
		$Flags["Skills"] = 'None';
	}
	if(array_key_exists("Encounters",$_REQUEST)){
		$Flags["Encounters"] = trim($_REQUEST["Encounters"]);
	}else{
		$Flags["Encounters"] = 'None';
	}
	if(array_key_exists("YetiMode",$_REQUEST)){
		$Flags["YetiMode"] = trim($_REQUEST["YetiMode"]);
	}else{
		$Flags["YetiMode"] = 'Off';
	}
	if(array_key_exists("GeniusMode",$_REQUEST)){
		$Flags["GeniusMode"] = trim($_REQUEST["GeniusMode"]);
	}else{
		$Flags["GeniusMode"] = 'Off';
	}
	if(array_key_exists("Seed",$_REQUEST)){
		$Flags["Seed"] = trim($_REQUEST["Seed"]);
	}else{
		$Flags["Seed"] = 0;
	}
	$initial_seed = $Flags["Seed"];
	
	if(array_key_exists("Submit",$_REQUEST)){
		if (!loadRom())
			return;
		
		//Some functions to dump the ROM in hexidecimal or text format
		//RomDump();
		//RomTextDump();
		//This function will attempt to find all of the bosses in the ROM data based on stats I pulled from Gamefaqs
		//LocateBosses();
		//die();
		if(strlen($romData) > 0){
			hackRom();
			saveRom();
		}
	}
}

function hackRom()
{
	//This function checks the selected flags and determines which randomization subroutines to run
	
	//First, let's seed the random number generator.  We're using DW3's generator, which uses three variables.
	global $counter;
	global $seed;
	global $discard;
	global $initial_seed;
	global $flags;
	$tmp_seed = $initial_seed;
	
	$counter = $tmp_seed % 256;
	$tmp_seed = floor($tmp_seed / 256);
	$seed = $tmp_seed % 65536;
	$tmp_seed = floor($tmp_seed / 65536);
	$discard = $tmp_seed % 16;
	
	for($j = 0; $j < $flags["StartingMonster"]; $j++){
		//Burn random numbers to make monster choice matter.
		Random();
	}
	
	PopulateValidMonsterIDs();
	ShuffleMonsterGrowth();
	ShuffleMonsterResistances();
	ShuffleMonsterSkills();
	ShuffleEncounters();
	//TODO: Shuffle fixed items
	//TODO: Shuffle random items?
	//TODO: Shuffle shops/item prices
	CodePatches();
	
	return true;
}

function loadRom()
{
	global $romData;
	try
	{
		//This is code for processing a ROM that I already have on the server
		/*
		$fileroot = "F:\\ROMs\\Gameboy\\DWM2TA\\";
		$entry = 'DWM2TA.gbc';
		//$entry = 'DWM2TA_Random.gbc';
		$filename = $fileroot.$entry;
		*/

		$filename = $_FILES['InputFile']['tmp_name'];
		$file = fopen($filename, "rb");
		$romData = fread($file,filesize($filename));
		
		//When $romData is a standard PHP array, this 4MB file takes up over 128M of RAM.
		//When $romData is an SplFixedArray, this 4MB file takes up 67MB of RAM.
		//for($i = 0; $i < strlen($_romData); $i++){
		//	$romData[$i] = ord($_romData[$i]);
		//}
		
		fclose($file);
	}
	catch (Exception $e)
	{
		$error_message = "<br>Empty file name(s) or unable to open files.  Please verify the files exist.";
		return false;
	}
	return true;
}

function saveRom()
{
	global $romData;
	global $initial_seed;
	
	//This is code for saving a rom to the server instead of spitting it back out to the user
	/*
	$fileroot = "F:\\ROMs\\Gameboy\\DWM2TA\\";
	$entry = 'DWM2TA_Random.gbc';

	$filename = $fileroot.$entry;
	$file = fopen($filename, "w");
	fwrite($file,$romData);
	*/
	
	header('Content-Disposition: attachment; filename="DWM2_Rando_'.$initial_seed.'.gbc"');
	header("Content-Size: ".strlen($romData)*512);
	echo $romData;
	die();
}

function swap($firstAddress, $secondAddress)
{
	global $romData;
	$holdAddress = $romData[$secondAddress];
	$romData[$secondAddress] = $romData[$firstAddress];
	$romData[$firstAddress] = $holdAddress;
}


function WriteText($address, $text)
{
	global $romData;
	$i = 0;
	for($j = 0; $j < strlen($text); $j++)
	{
		$c = $text[$j];
		$x = 0;
		if($c >= 'a' && $c <= 'z')
		{
			$x = ord($c) - ord('a') + 0x24;
		}else if($c >= 'A' && $c <= 'Z')
		{
			$x = ord($c) - ord('A') + 0x0A;
		}else if($c >= '0' && $c <= '9')
		{
			$x = ord($c) - '1';
			if($c == '0')
			{
				$x += 10;
			}
		}
		else
		{
			$x = 0x90;
		}
		$romData[$address + $i] = chr($x);
		$i++;
	}
}

function RomDump(){
	/* Code to create a hexidecimal rom dump of the input rom. */
	global $romData;
	
	$fileroot = "F:\\ROMs\\Gameboy\\DWM2TA\\";
	$outfilename = $fileroot.'romdump100.txt';
	$outfile = fopen($outfilename, "w");
	
	$str = '';
	for($i = 0; $i < strlen($romData); $i++){
		if(strlen(dechex(ord($romData[$i]))) == 1){
			$str .= '0';
		}
		if(strlen(dechex(ord($romData[$i]))) == 0){
			$str .= '00';
		}
		$str .= dechex(ord($romData[$i]));
		if($i % 100 == 99){
			fwrite($outfile,$str."\n");
			$str = '';
		}
	}
	fclose($outfile);
}

function RomTextDump(){		
	/* Code to create a text dump of the input rom. */
	global $romData;
	
	$files_to_create = 1;
	
	for($j = 0; $j < $files_to_create; $j++){
		$fileroot = "F:\\ROMs\\Gameboy\\DWM2TA\\";
		$outfilename = $fileroot.'romtextdump'.$j.'.txt';
		$outfile = fopen($outfilename, "w");
		
		$str = '';
		for($i = strlen($romData)/$files_to_create*$j; $i < strlen($romData)/$files_to_create*($j+1); $i++){
			if((ord($romData[$i]) >= 0x24) && (ord($romData[$i]) < (0x24 + 26))){
				$str .= chr(ord($romData[$i]) - 0x24 + ord('a'));
			}
			elseif((ord($romData[$i]) >= 0x0A) && (ord($romData[$i]) < (0x0A + 26))){
				$str .= chr(ord($romData[$i]) - 0x0A + ord('A'));
			}
			elseif((ord($romData[$i]) >= 0) && (ord($romData[$i]) < 0x10)){
				$str .= chr(ord($romData[$i]) + ord('0'));
			}
			else{
				$str .= ' ';
			}
			if($i % 100 == 99){
				$str .= "\n";
			}
		}
		fwrite($outfile,$str);
		fclose($outfile);
	}
}



function PopulateValidMonsterIDs(){
	global $Flags;
	global $ValidMonsterIDs;
	global $ValidMonsterGrowthIndecies;
	
	//This is the ID stored in the SRAM that determines which monster you have.
	//It's also used within the table of base-stats for each monster.
	//NOTE 0x1B is Butch and I don't think he should be used?
	
	if($Flags["YetiMode"] == "On"){
		$ValidMonsterIDs[] = 0x56;
	}
	else
	{
		for ($i = 0; $i <= 0x17E; $i++)
		{
			if (
				($i >= 0x01 && $i <= 0x1A) || //Slimes
				($i >= 0x24 && $i <= 0x42) || //Dragons
				($i >= 0x47 && $i <= 0x66) || //Beasts
				($i >= 0x6A && $i <= 0x84) || //Birds
				($i >= 0x8D && $i <= 0xA7) || //Plants
				($i >= 0xB0 && $i <= 0xC9) || //Bugs
				($i >= 0xD3 && $i <= 0xF0) || //Devils
				($i >= 0xF6 && $i <= 0x110) || //Zombies
				($i >= 0x119 && $i <= 0x138) || //Materials
				($i >= 0x13C && $i <= 0x15B) || //Waters
				($i >= 0x15F && $i <= 0x174) //Bosses
				)
			{
				$ValidMonsterIDs[] = $i;
			}
		}
	}
	for ($i = 0; $i <= 0x17E; $i++)
	{
		if (
			($i >= 0x01 && $i <= 0x1B) || //Slimes (0x1B is Butch)
			($i >= 0x24 && $i <= 0x42) || //Dragons
			($i >= 0x47 && $i <= 0x66) || //Beasts
			($i >= 0x6A && $i <= 0x84) || //Birds
			($i >= 0x8D && $i <= 0xA7) || //Plants
			($i >= 0xB0 && $i <= 0xC9) || //Bugs
			($i >= 0xD3 && $i <= 0xF0) || //Devils
			($i >= 0xF6 && $i <= 0x110) || //Zombies
			($i >= 0x119 && $i <= 0x138) || //Materials
			($i >= 0x13C && $i <= 0x15B) || //Waters
			($i >= 0x15F && $i <= 0x174) //Bosses
			)
		{
			$ValidMonsterGrowthIndecies[] = $i;
		}
	}
	
	
	
}


function ShuffleMonsterGrowth()
{
	global $Flags;
	
	global $romData;
	$monster_data_length = 47;
	$first_monster_byte = 0xD4368;
	$monster_count = 313;

	for ($i = 0; $i < $monster_count; $i++)
	{
		if ($Flags["Growth"] == "Redistribute")
		{
			//Let's randomize the monster's growth stats, but have them add up to the same value.
			$total_stats = 0;
			for ($j = 0; $j < 6; $j++)
			{
				//We're going to set a minimum growth value at 1 because growth of 0 SUCKS
				$total_stats += ord($romData[$first_monster_byte + $i * $monster_data_length + 14 + $j]) - 1;
				$romData[$first_monster_byte + $i * $monster_data_length + 14 + $j] = chr(1);
			}

			//Start by assigning 30 points: 20 to one stat and 10 to another (Or the same?)
			$slot1 = Random() % 6; //Named slot1 because C# is throwing a fit if I re-use the same var name in the loop below...
			$romData[$first_monster_byte + $i * $monster_data_length + 14 + $slot1] = chr(ord($romData[$first_monster_byte + $i * $monster_data_length + 14 + $slot1]) + 20);
			$slot1 = Random() % 6;
			$romData[$first_monster_byte + $i * $monster_data_length + 14 + $slot1] = chr(ord($romData[$first_monster_byte + $i * $monster_data_length + 14 + $slot1]) + 10);
			$total_stats -= 30;

			while ($total_stats > 0)
			{
				$slot = Random() % 6;
				//Do not let the stat go over 31
				if (ord($romData[$first_monster_byte + $i * $monster_data_length + 14 + $slot]) < 31)
				{
					$romData[$first_monster_byte + $i * $monster_data_length + 14 + $slot] = chr(ord($romData[$first_monster_byte + $i * $monster_data_length + 14 + $slot]) + 1);
					$total_stats--;
				}
			}
		}
		
		//If we're in Genius Mode, all monsters get 31 int growth
		if($Flags["GeniusMode"] == "Yes"){
			$romData[$first_monster_byte + $i * $monster_data_length + 14 + 5] = chr(31);
		}

	}

	return true;
}



function ShuffleMonsterResistances()
{
	global $Flags;
	
	global $romData;
	$monster_data_length = 47;
	$first_monster_byte = 0xD4368;
	$monster_count = 313;
	
	for ($i = 0; $i < $monster_count; $i++)
	{
		//Repeat for resistances.  There are 27 of these...
		$total_resistances = 0;
		for ($j = 0; $j < 27; $j++)
		{
			$total_resistances += ord($romData[$first_monster_byte + $i * $monster_data_length + 20 + $j]);
			$romData[$first_monster_byte + $i * $monster_data_length + 20 + $j] = chr(0);
		}
		while ($total_resistances > 0)
		{
			$slot = Random() % 27;
			//Do not let the stat go over 3
			if (ord($romData[$first_monster_byte + $i * $monster_data_length + 20 + $slot]) <= 3)
			{
				$romData[$first_monster_byte + $i * $monster_data_length + 20 + $slot] = chr(ord($romData[$first_monster_byte + $i * $monster_data_length + 20 + $slot]) + 1);
				$total_resistances--;
			}
		}
	}

	return true;
}


function ShuffleMonsterSkills()
{
	global $Flags;
	
	global $romData;
	$monster_data_length = 47;
	$first_monster_byte = 0xD4368;
	$monster_count = 313;
	//2018 03 01 -- ealm -- Removing BeDragon (59) because screwwwwwwwwwww thaaaaaaaaaaaaaaaaaat spellllllllllllllllllll.
	$tier_one_skills = array( 1, 4, 7, 10, 13, 16, 19, 21, 22, 25, 27, 30, 32, 33, 34, 35, 36, 37, 39, 41, 43, 45, 46, 47, 49, 51, 52, 53, 54, 56, 57, 58, 60, 61, 62, 63, 64, 68, 72, 74, 75, 76, 78, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 120, 121, 122, 123, 124, 125, 126, 127, 128, 129, 130, 131, 132, 133, 137, 138, 139, 141, 143, 144, 145, 146, 147, 148, 149, 150, 151, 153, 155, 156, 157, 158, 159, 160, 161, 162, 163, 164, 165, 166, 167, 168, 169 );

	for ($i = 0; $i < $monster_count; $i++)
	{
		//Randomize skills!  Pick three of these.
		$skill1 = Random() % count($tier_one_skills);
		$skill2 = Random() % count($tier_one_skills);
		while ($skill2 == $skill1)
		{
			$skill2 = Random() % count($tier_one_skills);
		}
		$skill3 = Random() % count($tier_one_skills);
		while ($skill3 == $skill1 || $skill3 == $skill2)
		{
			$skill3 = Random() % count($tier_one_skills);
		}
		$romData[$first_monster_byte + $i * $monster_data_length + 10] = chr($tier_one_skills[$skill1]);
		$romData[$first_monster_byte + $i * $monster_data_length + 11] = chr($tier_one_skills[$skill2]);
		$romData[$first_monster_byte + $i * $monster_data_length + 12] = chr($tier_one_skills[$skill3]);
	}

	return true;
}



function ShuffleEncounters()
{
	global $Flags;
	
	global $romData;
	global $ValidMonsterIDs;
	global $ValidMonsterGrowthIndecies;
	
	$encounter_data_length = 26;
	$first_encounter_byte = 0xD008F;
	$encounter_count = 614;
	$monster_data_length = 47;
	$first_monster_byte = 0xD4368;
	
	//Code patch: Reduce level of SpikyBoys in Oasis to 1 so that they level faster
	$romData[0xD00CC] = chr(0x01);

	for ($i = 0; $i < $encounter_count; $i++)
	{
		//Should probably choose monster independently of the rest of this.
		if($Flags["Encounters"] == "Poorly") //Previously "Based On Growth"
		{
			//Which monster is this?
			if($i == 0 && $Flags["StartingMonster"] != 0){
				//Allow the starting monster to be selectable
				$monsterid = $Flags["StartingMonster"];
			}
			elseif($i == 26){
				//Special case: The hoodsquid needs to be a water-type. (0x13C - 0x15B, 32 monsters)
				//TODO: Yeti Mode won't put a yeti here ):
				$monsterid = Random() % 32 + 0x13C;
			}else{
				$monsterid = $ValidMonsterIDs[Random() % count($ValidMonsterIDs)];
			}
			$MonsterGrowthIndex = array_search($monsterid,$ValidMonsterGrowthIndecies);
			
			//Need to ensure Army Ant/Madgopher are obtainable before Ice, so let's not randomize them.
			//      I like the idea of replacing them with any monster in the Pirate overworld (all zones), but I think that requires me to manually track down all of the addresses of the overworld enemies.
			//		Madgopher is 90 (0x5A)
			//		Army Ant is 185 (0xB9)
			//		Note that each monster only shows up once.
			if(ord($romData[$first_encounter_byte + $i * $encounter_data_length + 0]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 1])*256 != 0x5A &&
			   ord($romData[$first_encounter_byte + $i * $encounter_data_length + 0]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 1])*256 != 0xB9){
				$romData[$first_encounter_byte + $i * $encounter_data_length + 0] = chr($monsterid % 256);
				$romData[$first_encounter_byte + $i * $encounter_data_length + 1] = chr(floor($monsterid / 256));
			}
			
			$skip_hp_cuz_boss = 0;
			//Here's a list of all of the bosses in the speedrun.  I hope.
			//I don't have stats for all of the arena monsters, and I'm not finding the post-game monsters in the ROM data.
			//In fact, I'm only finding partial matches for most of THESE monsters... would Gamefaqs lie to me?
			switch($i){
				case 385: //Oasis Beavern
				case 6: //Oasis CurseLamp
				case 130: //K-1 Babble
				case 131: //K-1 PearlGel
				case 132: //K-2 SpikyBoy
				case 133: //K-2 Pixy
				case 134: //K-2 Dracky
				case 135: //K-3 MadRaven
				case 136: //K-3 Kitehawk
				case 135: //K-3 MadRaven
				case 25: //Pirate Hoodsquid
				case 398: //Pirate Boneslave
				case 27: //Pirate CaptDead
				case 399: //Pirate KingSquid
				case 49: //Ice Bombcrag
				case 408: //Ice AgDevil
				case 409: //Ice Puppetor
				case 67: //Ice Goathorn
				case 410: //Ice ArcDemon
				case 411: //Ice Goathorn 2
				case 426: //Sky MadCondor
				case 108: //Sky Skeletor
				case 99: //Sky Niterich
				case 421: //Sky Metabble
				case 107: //Sky EvilArmor
				case 106: //Sky Mudou
				case 115: //Limbo GigaDraco
				case 114: //Limbo Centasaur
				case 116: //Limbo Garudian
				case 376: //Limbo Darck
					$skip_hp_cuz_boss = 1;
			}
			
			//Add up the monster's GROWTH values
			$total_growth_stats = 0;
			for ($j = 0; $j < 6; $j++)
			{
				if($j == 0 && $skip_hp_cuz_boss) continue;
				$total_growth_stats += ord($romData[$first_monster_byte + $MonsterGrowthIndex * $monster_data_length + 14 + $j]);
			}
			
			//Add up the monster's BASE STATS
			$total_stats = 0;
			for ($j = 0; $j < 6; $j++)
			{
				if($j == 0 && $skip_hp_cuz_boss) continue;
				$total_stats += ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + $j * 2]);
				$total_stats += ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + $j * 2 + 1]) * 256;
			}
			//Double the base stats for our starting monster, because Slash is a little weak at level one.
			if($j == 0) $total_stats *= 2;
			
			//Take the percentage of the GROWTH allocated to each stat and multiply by the total BASE
			for ($j = 0; $j < 6; $j++)
			{
				if($j == 0 && $skip_hp_cuz_boss) continue;
				$new_stat = ord($romData[$first_monster_byte + $MonsterGrowthIndex * $monster_data_length + 14 + $j]);
				$new_stat = floor($new_stat * $total_stats / $total_growth_stats);
				
				//Everyone gets minimum 10 HP and 5 str, agi, def, int.  (MP can be zero)
				//If this is our starting monster (Slash), double the baseline for all of those.
				if($j == 0){
					if($new_stat < 10 * (($i == 0) ? 2 : 1)) $new_stat = 10 * (($i == 0) ? 2 : 1);
				}elseif(($j == 2) or ($j == 3) or ($j == 4) or ($j == 5)){
					if($new_stat < 5 * (($i == 0) ? 2 : 1)) $new_stat = 5 * (($i == 0) ? 2 : 1);
				}
				
				$romData[$first_encounter_byte + $i * $encounter_data_length + 10 + $j * 2] = chr($new_stat % 256);
				$romData[$first_encounter_byte + $i * $encounter_data_length + 10 + $j * 2 + 1] = chr($new_stat / 256);
			}
		}
		//Ramp up early EXP gains with the following statements.
		if(ord($romData[$first_encounter_byte + $i * $encounter_data_length + 6]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 7]) * 256 < 20)
		{
			$romData[$first_encounter_byte + $i * $encounter_data_length + 6] = chr(ord($romData[$first_encounter_byte + $i * $encounter_data_length + 6]) * 2.5);
		}
		elseif(ord($romData[$first_encounter_byte + $i * $encounter_data_length + 6]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 7]) * 256 < 40)
		{
			$romData[$first_encounter_byte + $i * $encounter_data_length + 6] = chr(ord($romData[$first_encounter_byte + $i * $encounter_data_length + 6]) * 2);
		}
		elseif(ord($romData[$first_encounter_byte + $i * $encounter_data_length + 6]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 7]) * 256 < 100)
		{
			$romData[$first_encounter_byte + $i * $encounter_data_length + 6] = chr(ord($romData[$first_encounter_byte + $i * $encounter_data_length + 6]) * 1.5);
		}
		
		//If we're in Genius Mode, all wild monsters get 999 int
		if($Flags["GeniusMode"] == "On"){
			$romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 5 * 2] = chr(999 % 256);
			$romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 5 * 2 + 1] = chr(999 / 256);
		}
		
		
		
		
		//Now, let's teach random encounters their skills.  We'll give them the three they're supposed to learn, plus a bonus skill, and let it level up appropriately.
		$lv  = ord($romData[$first_encounter_byte + $i * $encounter_data_length + 9]);
		$hp  = ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 0 * 2]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 0 * 2 + 1]);
		$mp  = ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 1 * 2]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 1 * 2 + 1]);
		$atk = ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 2 * 2]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 2 * 2 + 1]);
		$def = ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 3 * 2]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 3 * 2 + 1]);
		$agl = ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 4 * 2]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 4 * 2 + 1]);
		$int = ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 5 * 2]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 5 * 2 + 1]);
		
		for($j = 0; $j < 4; $j++){
			//Loop through all three skills the monster should learn, plus a BONUS SKILL
			if($j <> 3){
				$skill = ord($romData[$first_monster_byte + $MonsterGrowthIndex * $monster_data_length + 10 + $j]);
			}else{
				$skill = Random() % 169 + 1;
				//Re-roll until this isn't the same skill as the three it innately learns.
				while($skill == $romData[$first_monster_byte + $MonsterGrowthIndex * $monster_data_length + 10 + 0] ||
					  $skill == $romData[$first_monster_byte + $MonsterGrowthIndex * $monster_data_length + 10 + 1] ||
					  $skill == $romData[$first_monster_byte + $MonsterGrowthIndex * $monster_data_length + 10 + 2])
					  {
					$skill = Random() % 169 + 1;
				}
			}
			
			//This variable is the "return value" from our loop.
			$return_skill = 0xFF; //0xFF is "no skill".
			while(true){
				//"Skill" is the ID of the skill we're trying to learn.
				$skill_qry = "select * from dragonwarriormonsters2_skills where id = ".$skill." and lv <= ".$lv." and hp <= ".$hp." and mp <= ".$mp." and atk <= ".$atk." and def <= ".$def." and agl <= ".$agl." and `int` <= ".$int;
				execute($skill_qry);
				$result = get();
				if(count($result) !== 0){
					//If we can learn this skill, plug it into return_skill
					$return_skill = $result["id"];
					$skill = $result["SUCCESSOR"];
					//echo 'Evolve into '.$result['Name'].': '.$lv.', '.$hp.', '.$mp.', '.$atk.', '.$def.', '.$agl.', '.$int.'<br>';
					if($skill == 0){
						//Break if there is no next skill to look up.
						break;
					}
				}else{
					//echo 'No evolution: '.$lv.', '.$hp.', '.$mp.', '.$atk.', '.$def.', '.$agl.', '.$int.'<br>';
					//Break if we don't qualify for the current skill.
					break;
				}
			}
			$romData[$first_encounter_byte + $i * $encounter_data_length + 2 + $j] = chr($return_skill);
		}
		//Hoodsquid should always know LureDance as its fourth move
		if($i == 26){
			$romData[$first_encounter_byte + $i * $encounter_data_length + 4] = chr(0x7A);
		}
		
		//Swap empty moves to the back.  Just gonna a "brute force" bubble sort; could be more efficient but it's nine swaps so whatever.
		for($j = 0; $j < 3; $j++){
			if(ord($romData[$first_encounter_byte + $i * $encounter_data_length + 2]) == 0xFF){
				swap($first_encounter_byte + $i * $encounter_data_length + 2,$first_encounter_byte + $i * $encounter_data_length + 3);
			}
			if(ord($romData[$first_encounter_byte + $i * $encounter_data_length + 3]) == 0xFF){
				swap($first_encounter_byte + $i * $encounter_data_length + 3,$first_encounter_byte + $i * $encounter_data_length + 4);
			}
			if(ord($romData[$first_encounter_byte + $i * $encounter_data_length + 4]) == 0xFF){
				swap($first_encounter_byte + $i * $encounter_data_length + 4,$first_encounter_byte + $i * $encounter_data_length + 5);
			}
		}
		//END ENCOUNTER SKILLS
	}

	//TODO: If it needs it, make starting monster have a minimum of 10 on each stat (15-20 for HP/MP?)
	//TODO: Pick from three monsters instead of just selecting one
	//TODO: Distribute stats on monsters by deleveling them and then leveling them back up
	//TODO: Does everyone actually have 31 int growth or did I forget that?
	//TODO: All enemy monsters seem to target back monster?
	//TODO: Why did Putrepup in Ice world hit so hard?
	//TODO: Sky world enemies hurt!!!
	//TODO: Mudo's HP was randomized and it hurt WutFace
	//TODO: Make WLD zero for all random encounters
	//TODO: Why is this where the TODOs are?
	//TODO: Maybe make it easier for random encounters to learn skills, so that early monsters know things? (Only use level requirement?)
	
	return true;
}

function CodePatches(){
	global $romData;
	//Wherever "clear water" is mentioned, write "tonic" over the word "water"
	//TODO: Find more of these; Bizhawk's text search is glitchy.  Do a text dump?
	WriteText(0x266624, "tonic");
	WriteText(0x0A107B, "tonic");
	
	//TODO: This code to randomize your team name in the arena is glitchy...  If I write too many (or too few?) characters over existing text, the text box can glitch out.  Might need to figure out what the extra characters are between messages...
	//This is an array of every instance of "Master ____ and the (Team Name)!", which changes with every rank.
	//These aren't hex values because my text dump is broken into 100-character lines.  Don't judge me.
	/*
	$Masters = [[155640,30],[155676,29],[155711,28],[155745,30],[155781,27],[155885,32],[156338,30],[156441,30],[156549,29],[156656,31],[156766,28],[156866,31],[156976,32]];
	$Titles = ["Master","Captain","Commander","Mistress","Lord","Lady","Sir","King","Queen","Princess","Prince","Duke","Duchess","Speedrunner","WR Holder","Lieutenant","Corporal","Officer","Admiral","Pirate Lord"];
	$Teams = ["Team","Sweaty Yetis","Alefgard Alliance","Dream Team","Good Monsters","Slime Time","Fun Friends","Berserkers","Random Monsters","Buttermilk Ranch"];
	for($i = 0; $i < count($Masters); $i++){
		//We've got 31 characters to work with, including 0xf900 which'll be the player's name
		//"(Title) ____ and the (Team)" = 12 characters + title/team names (Player name is two characters)
		//(Note to self:  I gotta make sure the longest title and the shortest team name don't exceed 20 characters)
		$title = Random() % count($Titles); 
		$team = Random() % count($Teams);
		//Roll team names until we get one that's short enough.
		while(strlen($Titles[$title]) + strlen($Teams[$team]) + 12 > $Masters[$i][1]){
			$team = Random() % count($Teams);
		}
		$_team_with_padding = $Teams[$team];
		while(strlen($Titles[$title]) + strlen($_team_with_padding) + 12 < $Masters[$i][1]){
			$_team_with_padding .= ' ';
		}
		
		WriteText($Masters[$i][0], $Titles[$title].' ');
		$romData[$Masters[$i][0]+strlen($Titles[$title])+1] = chr(0xF9);
		$romData[$Masters[$i][0]+strlen($Titles[$title])+2] = chr(0x00);
		WriteText($Masters[$i][0]+strlen($Titles[$title])+3, ' and the '.$_team_with_padding);
	}
	*/

	//Patch to open up the monster breeder's back door early (As soon as you get Slash)
	//0x3FAD-0x3FFF are no-ops, so we'll write our new code in that range.
	
	//First, move these three addresses that we're about to replace with the JP command into the new code block
	$romData[0x3FAD] = $romData[0x186C];
	$romData[0x3FAE] = $romData[0x186D];
	$romData[0x3FAF] = $romData[0x186E];
	//$romData[0x3FB0] = $romData[0x186F];
	
	$romData[0x186C] = chr(0x00); 	//	nop
	$romData[0x186D] = chr(0xC3); 	//	JP 0x3FAD	Jump to 0x3FAD
	$romData[0x186E] = chr(0xAD);	//	
	$romData[0x186F] = chr(0x3F);	//	
	
	//Now, add my own code...
	$romData[0x3FB1] = chr(0x3E);	//LD a,FCh		Load 0xFC into register a
	$romData[0x3FB2] = chr(0xFC);	//
	$romData[0x3FB3] = chr(0x5F);	//LD e,a		Load register A into register E
	
	//$romData[0x3FB4] = chr(0xCB);	//SLA C			(Turn C from 4 to 8 with a left shift, two-byte command)
	//$romData[0x3FB5] = chr(0x21);	//
	$romData[0x3FB4] = chr(0x0E);	//SLA C			(Load the number 8 into register c)
	$romData[0x3FB5] = chr(0x08);	//
	$romData[0x3FB6] = chr(0x1A);	//ld a,(de)		
	$romData[0x3FB7] = chr(0xB1);	//or c			
	$romData[0x3FB8] = chr(0x12);	//ld (de),a		
	
	$romData[0x3FB9] = chr(0xC9);	//	RET 		End subroutine (Instruction previously at 0x186F)
	//TODO: Is it possible to get rid of all of the people in front of the monster breeder?
}

function LocateBosses(){
	//I haven't been citing my sources for all of the information I'm pulling from Gamefaqs since it's in the ROM data anyway...
	//...but this data doesn't seem to be entirely accurate thecowLUL
	//boss stats source: https://gamefaqs.gamespot.com/gbc/525414-dragon-warrior-monsters-2-cobis-journey/faqs/14383
	
	global $romData;
	
	$encounter_data_length = 26;
	$first_encounter_byte = 0xD008F;
	$encounter_count = 614;
	$monster_data_length = 47;
	$first_monster_byte = 0xD4368;
	//Really shoulda put this in the database...
	$boss_stats = array(
		array("Oasis Beavern",5,98,16,20,8,36,120),
		array("Oasis CurseLamp",5,220,8,27,10,44,65),
		array("K-1 Babble",6,36,9,14,13,32,123),
		array("K-1 PearlGel",5,15,12,17,35,51,96),
		array("K-2 SpikyBoy",6,19,42,17,31,34,96),
		array("K-2 Pixy",7,29,24,20,14,54,79),
		array("K-2 Dracky",7,40,22,14,12,70,96),
		array("K-3 MadRaven",9,41,55,16,30,84,163),
		array("K-3 Kitehawk",9,46,35,26,21,42,146),
		array("K-3 MadRaven",9,41,55,16,30,84,163),
		array("Pirate Hoodsquid",12,350,100,78,39,85,180),
		array("Pirate Boneslave",13,72,58,58,45,49,99),
		array("Pirate CaptDead",13,500,105,66,48,50,144),
		array("Pirate KingSquid",38,2500,140,227,147,189,73),
		array("Ice Bombcrag",18,650,10,80,80,20,140),
		array("Ice AgDevil",18,550,50,90,59,109,216),
		array("Ice Puppetor",22,400,82,85,60,48,120),
		array("Ice Goathorn",27,850,89,95,65,92,165),
		array("Ice ArcDemon",27,210,89,105,71,84,211),
		array("Ice Goathorn 2",27,330,89,95,65,92,223),
		array("Sky MadCondor",30,600,119,131,90,110,159),
		array("Sky Skeletor",37,226,108,160,148,140,140),
		array("Sky Niterich",36,1500,106,177,149,110,510),
		array("Sky Metabble",37,20,368,95,999,670,522),
		array("Sky EvilArmor",38,450,140,185,150,138,280),
		array("Sky Mudou",38,3000,413,225,160,156,150),
		array("Limbo GigaDraco",42,1000,245,277,180,120,100),
		array("Limbo Centasaur",40,900,83,220,150,250,250),
		array("Limbo Garudian",41,800,88,206,160,251,320),
		array("Limbo Darck",44,4000,235,350,220,160,210),
		array("Butch Butch",0,755,322,547,238,644,500),
		array("Butch Pumpoise",40,800,388,238,255,330,500),
		array("Butch Drygon",40,480,210,432,677,270,500),
		array("Kameha50 MimeSlime",43,370,690,258,320,350,520),
		array("Kameha50 Tonguella",45,400,320,338,310,284,430),
		array("Kameha50 Golem",48,520,315,410,301,189,540),
		array("Kameha150 MetalKing",50,200,950,310,780,840,700),
		array("Kameha150 KingLeo",55,1200,490,370,480,460,680),
		array("Kameha150 GoldGolem",50,900,590,430,600,370,700),
		array("Terry GreatDrak",52,840,330,375,400,299,300),
		array("Terry Watabou",40,610,467,320,380,178,150),
		array("Terry Durran",53,1000,470,420,430,326,450),
		array("Elf Arrowdog",40,500,210,237,288,249,501),
		array("Elf AgDevil",47,1500,580,346,310,267,700),
		array("Power WindBeast",40,900,127,236,168,489,0),
		array("Power MadGoose",40,580,387,201,203,348,0),
		array("Power WhaleMage",42,750,590,199,228,247,0),
		array("Power SeaHorse",37,480,180,191,253,222,0),
		array("Power Octoreach",39,700,140,238,174,247,0),
		array("Power IceMan",45,1200,500,345,279,210,0),
		array("Power Shadow",40,600,300,235,240,170,0),
		array("Power BigEye",37,750,280,358,242,198,0),
		array("Power Balzak",45,2000,398,375,257,220,0),
		array("Power RotRaven",35,680,340,160,249,340,0),
		array("Power Jamirus",48,1400,750,247,349,410,0),
		array("Power SkyDragon",46,990,700,349,279,243,0),
		array("Power Gremlin",40,600,450,201,232,218,0),
		array("Traveler Pixy",52,680,400,357,374,443,700),
		array("Traveler Copycat",55,750,300,310,520,380,999),
		array("Traveler StoneMan",52,1300,360,458,268,247,700),
		array("Traveler WhipBird",53,950,830,334,374,438,700),
		array("Traveler MetalKing",48,600,200,410,859,320,160),
		array("Traveler RainHawk",99,3500,520,710,540,250,300),
		array("Traveler Coatol",46,1500,210,600,600,300,255),
		array("Baffle Crestpent",42,780,700,279,300,299,480),
		array("Baffle SpotKing",45,1300,550,378,214,308,800),
		array("Baffle Gulpple",45,840,590,289,299,387,0),
		array("Baffle FairyDrak",40,700,340,312,298,273,0),
		array("Baffle DuckKite",42,900,470,296,330,400,0),
		array("Baffle FunkyBird",42,890,500,301,267,430,0),
		array("Baffle FunkyBird",42,890,500,301,267,430,0),
		array("Baffle Slurperon",39,790,400,277,259,302,0),
		array("Baffle DanceVegi",37,680,430,259,249,240,0),
		array("Baffle MadPlant",39,880,380,243,350,289,510),
		array("Baffle Orc",43,900,350,346,289,190,770,6000),
		array("Baffle PutrePup",41,700,280,330,328,232,450),
		array("Baffle Devipine",43,840,400,300,358,222,660),
		array("Baffle Anemon",44,900,370,248,388,289,580),
		array("Baffle HerbMan",46,1300,700,341,273,370,900),
		array("Soul KingSlime",50,4000,280,329,289,334,800),
		array("Soul Coatol",48,640,800,289,410,329,800),
		array("Soul FangSlime",44,700,378,333,278,397,590),
		array("Soul Grizzly",45,870,360,372,245,379,288),
		array("Soul BeastNite",45,1200,420,358,343,201,600),
		array("Soul Slimeborg",44,650,790,217,327,418,740),
		array("Soul Unicorn",45,720,680,316,322,319,800),
		array("Soul SuperTen",44,600,380,312,298,417,600),
		array("Soul Slime",47,600,500,387,299,387,700),
		array("Soul RockSlime",40,680,230,265,362,265,450),
		array("Soul Metabble",40,289,700,146,798,688,770),
		array("Soul Gorago",55,990,780,432,378,688,800)
	);
	
	foreach($boss_stats as $boss){
		$foundit = 0;
		for ($i = 0; $i < $encounter_count; $i++){
			//Now, let's teach random encounters their skills.  We'll give them the three they're supposed to learn, plus a bonus skill, and let it level up appropriately.
			$lv  = ord($romData[$first_encounter_byte + $i * $encounter_data_length + 9]);
			$hp  = ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 0 * 2]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 0 * 2 + 1]);
			$mp  = ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 1 * 2]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 1 * 2 + 1]);
			$atk = ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 2 * 2]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 2 * 2 + 1]);
			$def = ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 3 * 2]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 3 * 2 + 1]);
			$agl = ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 4 * 2]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 4 * 2 + 1]);
			$int = ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 5 * 2]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 5 * 2 + 1]);
			
			$pts = 0;
			if($boss[1] == $lv ) $pts++;
			if($boss[2] == $hp ) $pts++;
			if($boss[3] == $mp ) $pts++;
			if($boss[4] == $atk) $pts++;
			if($boss[5] == $def) $pts++;
			if($boss[6] == $agl) $pts++;
			if($boss[7] == $int) $pts++;
			
			if($pts > 2){
				$foundit = 1;
				echo $boss[0].' located: '.$i.' ('.$pts.')<br>';
			}
		}
		if($foundit == 0){
			echo $boss[0].' NOT located!<br>';
		}
	}
}

DWM2R();

?>
<html>
<head>
	<script type="text/javascript" src="/Library/jquery-3.0.0.js"></script>
	<script type="text/javascript" src="/Library/bootstrap-4.0.0/js/bootstrap.js"></script>
	<link rel="stylesheet" href="/Library/bootstrap-4.0.0/css/bootstrap.css" />
</head>
<body>

<form action="index.php" method="POST" enctype="multipart/form-data">
<div class="container-fluid" style="max-width:800px;margin:0px auto;">
	<div class="row">
		<div class="col-sm" style="text-align:center"><h1>Dragon Warrior Monsters 2 Randomizer!</h1></div>
	</div>
	<div class="row">
		<div class="col-sm"><label for="flags_input">Flags:</label></div>
		<div class="col-sm"><input type="text" name="Flags" value="" id="flags_input" size=15 /></div>
		<div class="col-sm"><label for="seed_input">Seed:</label></div>
		<div class="col-sm"><input type="text" name="Seed" value="<?php echo $Flags['Seed'] ?>" id="seed_input" size=10 /></div>
		<div class="col-sm"><input type="button" name="NewSeedBtn" value="New Seed" id="NewSeedBtn" /></div>
	</div>
	<div class="row">
		<div class="col-sm"><label for="input_file">Input File:</label></div>
		<div class="col-sm"><input type="file" name="InputFile" value="" id="input_file" size=50 /></div>
	</div>
	<div class="row">
		<div class="col-sm">Starting Monster</div>
		<div class="col-sm">
			<select name="StartingMonster">
				<option value="0">Random</option>
				<?php
					$monster_list_query = "SELECT * FROM dragonwarriormonsters2 order by id asc";
					execute($monster_list_query);
					while($monster = get()){
				?>
				<option value="<?php echo $monster["id"]; ?>" <?php echo $Flags['StartingMonster'] == $monster["id"] ? 'selected' : '' ?>><?php echo $monster["name"]; ?></option>
				<?php
					}
				?>
			</select>
		</div>
	</div>
	<div class="row">
		<div class="col-sm">Monster Growth</div>
		<div class="col-sm"><input type="radio" name="Growth" value="Redistribute" id="growth_redist" <?php echo $Flags['Growth'] == 'Redistribute' ? 'checked' : '' ?> /> <label for="growth_redist" title="Monster stat growth values will add to the same total, but will be randomly distributed.">Redistribute</label></div>
		<div class="col-sm"><input type="radio" name="Growth" value="Shuffle" id="growth_shuffle" disabled <?php echo $Flags['Growth'] == 'Shuffle' ? 'checked' : '' ?> /> <label for="growth_shuffle" title="Monsters will keep the same six growth values, but they will be randomly shuffled.">Shuffle</label></div>
		<div class="col-sm"><input type="radio" name="Growth" value="None" id="growth_none" <?php echo $Flags['Growth'] == 'None' ? 'checked' : '' ?> /> <label for="growth_none" title="Do not randomize monster stats.">None</label></div>
	</div>
	<div class="row">
		<div class="col-sm">Monster Resistances</div>
		<div class="col-sm"><input type="radio" name="Resistance" value="Redistribute" id="resistance_redist" <?php echo $Flags['Resistance'] == 'Redistribute' ? 'checked' : '' ?> /> <label for="resistance_redist" title="Monster stat resistance values will add to the same total, but will be randomly distributed.">Redistribute</label></div>
		<div class="col-sm"><input type="radio" name="Resistance" value="Shuffle" id="resistance_shuffle" disabled  <?php echo $Flags['Resistance'] == 'Shuffle' ? 'checked' : '' ?> /> <label for="resistance_shuffle" title="Monsters will keep the same 27 resistance values, but they will be randomly shuffled.">Shuffle</label></div>
		<div class="col-sm"><input type="radio" name="Resistance" value="None" id="resistance_none" <?php echo $Flags['Resistance'] == 'None' ? 'checked' : '' ?> /> <label for="resistance_none" title="Do not randomize monster stats.">None</label></div>
	</div>
	<div class="row">
		<div class="col-sm">Shuffle Monster Skills</div>
		<div class="col-sm"><input type="radio" name="Skills" value="Random" id="skills_random" <?php echo $Flags['Skills'] == 'Random' ? 'checked' : '' ?> /> <label for="skills_random" title="Monsters learn completely random skills.  BeDragon is excluded.">Random</label></div>
		<div class="col-sm"><input type="radio" name="Skills" value="None" id="skills_none" <?php echo $Flags['Skills'] == 'None' ? 'checked' : '' ?> /> <label for="skills_none" title="Do not randomize monster skills.">None</label></div>
	</div>
	<div class="row">
		<div class="col-sm">Shuffle Encounters</div>
		<div class="col-sm"><input type="radio" name="Encounters" value="Poorly" id="encounters_random" <?php echo $Flags['Encounters'] == 'Poorly' ? 'checked' : '' ?> /> <label for="encounters_random" title="Shuffle enemy types.  Monsters' stats add to the same total, proportional to their growth values.  Later monsters are supposed to start with more skills, but no monsters start with advanced skills.">Poorly</label></div>
		<div class="col-sm"><input type="radio" name="Encounters" value="None" id="encounters_none" <?php echo $Flags['Encounters'] == 'None' ? 'checked' : '' ?> /> <label for="encounters_none" title="Do not randomize encounters.">Not at All</label></div>
	</div>
	<div class="row">
		<div class="col-sm">Max Monster Intelligence</div>
		<div class="col-sm"><input type="radio" name="GeniusMode" value="On" id="geniusmode_on" <?php echo $Flags['GeniusMode'] == 'On' ? 'checked' : '' ?>> <label for="geniusmode_on" title="All wild monsters have 999 Int; all monsters have 31 int growth. (Overrides randomized base int/growth)" />On</label></div>
		<div class="col-sm"><input type="radio" name="GeniusMode" value="Off" id="geniusmode_off" <?php echo $Flags['GeniusMode'] == 'Off' ? 'checked' : '' ?>> <label for="geniusmode_off" title="Do not max out monster intelligence." />Off</label></div>
	</div>
	<div class="row">
		<div class="col-sm">Yeti Mode</div>
		<div class="col-sm"><input type="radio" name="YetiMode" value="On" id="yeti_on" <?php echo $Flags['YetiMode'] == 'On' ? 'checked' : '' ?>> <label for="yeti_on" title="All monsters are yetis!" />On</label></div>
		<div class="col-sm"><input type="radio" name="YetiMode" value="Off" id="yeti_off" <?php echo $Flags['YetiMode'] == 'Off' ? 'checked' : '' ?>> <label for="yeti_off" title="Not all monsters are yetis." />Off</label></div>
	</div>
	<div class="row">
		<div class="col-sm" style="text-align:center"><input type="Submit" name="Submit" value="Randomize!"></div>
	</div>
</div>
</form>
<div class="container-fluid" style="max-width:800px;margin:0px auto;">
	<div class="row">
		This randomizer will work with both Cobi's Journey or Tara's Adventure, but the resulting rom will vary slightly based on which one you use.  Note that this randomizer is still in beta, and consequently has a few known bugs and isn't quite feature-complete.
	</div>
	<div class="row">
		For more details on how this randomizer works, planned features and changelog, and the source code/data, checkout the ReadMe on GitHub: <a href="https://github.com/TheCowness/DWM2Randomizer">https://github.com/TheCowness/DWM2Randomizer</a>
	</div>
</div>

<style type="text/css">
	form > .container-fluid{
		border-left:2px solid #ccc;
		border-top:2px solid #ccc;
	}
	form > .container-fluid > .row{
		border-right:2px solid #ccc;
		border-bottom:2px solid #ccc;
	}
</style>

<script type="text/javascript">
$(document).ready(function(){
	//Set a random seed, if we didn't just generate one.
	if($("#seed_input").val() == '0'){
		NewSeed();
	}
	$("#NewSeedBtn").click(function(){NewSeed();});
	
	//When any flag-field is changed, update the flag string
	$("input[type=radio]").change(function(){
		RefreshFlagString();
	});
	//When the flag string is changed, update all fields
	$("#flags_input").change(function(){
		SetFlagsFromString();
	});
	//Initialize the flag string
	RefreshFlagString();
});
function NewSeed(){
	$("#seed_input").val(Math.floor(Math.random()*268435455));
}
function RefreshFlagString(){
	//This function will serialize the important form flags into a text string so that they can be shared for races.
	//Because JS integers are basically 32-bit, we'll have to utilize an array.
	//Let's have each array value hold a six-bit integer (0-63), which will ultimately be represented in text by a single character
	flags = [];
	flag_ctr = 0;
	flag_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';
	//Start with all radio buttons.
	$("input[type=radio]").each(function(){
		if(flag_ctr % 6 == 0){
			flags.push(0);
		}
		if($(this).prop("checked")){
			flags[Math.floor(flag_ctr / 6)] += 2**(flag_ctr % 6);
		}else{
			flags[Math.floor(flag_ctr / 6)] += 0;
		}
		flag_ctr++;
	});
	
	$("#flags_input").val('');
	for(i = 0; i < flags.length; i++){
		$("#flags_input").val($("#flags_input").val()+flag_chars.charAt(flags[i]));
	}
}
function SetFlagsFromString(){
	//This will be the opposite of RefreshFlagString - turn on flags based on the Flags string.
	//TODO: Need a check to make sure the flags string is long enough.  Calculate length based on number of form elements?
	flag_string = $("#flags_input").val();
	flags = [];
	flag_ctr = 0;
	flag_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';
	//Convert from alphanumeric to the array we used above
	for(i = 0; i < flag_string.length; i++){
		flags.push(flag_chars.indexOf(flag_string.charAt(i)));
	}
	//Start with all radio buttons
	$("input[type=radio]").each(function(){
		if((flags[Math.floor(flag_ctr / 6)] & 2**(flag_ctr%6)) != 0){
			$(this).prop("checked","checked");
		}else{
			$(this).removeProp("checked");
		}
		flag_ctr++;
	});
}
</script>

</body>
</html>