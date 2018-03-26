<?php
/* dbsettings.php just overwrites these three variables */
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
	$tmp_seed = $initial_seed;
	
	$counter = $tmp_seed % 256;
	$tmp_seed = floor($tmp_seed / 256);
	$seed = $tmp_seed % 65536;
	$tmp_seed = floor($tmp_seed / 65536);
	$discard = $tmp_seed % 16;
	
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
	//die();
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
			/*
			//TODO: Put in better boss detection (i.e. get a list of the indexes of all bosses)
			if(ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 0 * 2 + 1]) > 3){
				$skip_hp_cuz_boss = 1;
			}
			*/
			
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

			$lv  = ord($romData[$first_encounter_byte + $i * $encounter_data_length + 9]);
			$hp  = ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 0 * 2]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 0 * 2 + 1]);
			$mp  = ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 1 * 2]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 1 * 2 + 1]);
			$atk = ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 2 * 2]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 2 * 2 + 1]);
			$def = ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 3 * 2]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 3 * 2 + 1]);
			$agl = ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 4 * 2]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 4 * 2 + 1]);
			$int = ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 5 * 2]) + ord($romData[$first_encounter_byte + $i * $encounter_data_length + 10 + 5 * 2 + 1]);
			//Now, what to do for skills?
			for($j = 0; $j < 4; $j++){
				//Loop through all three skills the monster should learn, plus a BONUS SKILL
				if($j <> 3){
					$skill = ord($romData[$first_monster_byte + $MonsterGrowthIndex * $monster_data_length + 10 + $j]);
				}else{
					$skill = Random() % 169 + 1;
				}
				
				//This variable is the "return value" from our loop.
				$return_skill = 0xFF; //0xFF is "no skill".
				while(true){
					$skill_qry = "select * from dragonwarriormonsters2_skills where id = ".$skill." and lv <= ".$lv." and hp <= ".$hp." and mp <= ".$mp." and atk <= ".$atk." and def <= ".$def." and agl <= ".$agl." and `int` <= ".$int;
					execute($skill_qry);
					$result = get();
					if(count($result) !== 0){
						$return_skill = $result["id"];
						$skill = $result["SUCCESSOR"];
						if($skill == 0){
							//Break if there is no next skill to look up.
							break;
						}
					}else{
						//Break if we don't qualify for the current skill.
						break;
					}
				}
				$romData[$first_encounter_byte + $i * $encounter_data_length + 2 + $j] = chr($return_skill);
			}
			//Hoodsquid should always know LureDance
			//$romData[0xD0335] = chr(0x7A);
			if($i == 26){
				$romData[$first_encounter_byte + $i * $encounter_data_length + 4] = chr(0x7A);
			}
			
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
			
			
			//TODO: Make WLD zero for all random encounters
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
	}

	//TODO: If it needs it, make starting monster have a minimum of 10 on each stat (15-20 for HP/MP?)
	//TODO: Pick from three monsters instead of just selecting one
	//TODO: Distribute stats on monsters by deleveling them and then leveling them back up
	//TODO: Does everyone actually have 31 int growth or did I forget that?
	//TODO: All enemy monsters seem to target back monster?
	//TODO: Why did Putrepup in Ice world hit so hard?
	//TODO: Sky world enemies hurt!!!
	//TODO: Mudo's HP was randomized and it hurt WutFace
	
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
		<div class="col-sm"><input type="text" name="Flags" value="(Not Yet Supported)" id="flags_input" size=15 /></div>
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
	
	$("input[type=radio]").change(function(){
		RefreshFlagString();
	});
});
function NewSeed(){
	$("#seed_input").val(Math.floor(Math.random()*268435455));
}
function RefreshFlagString(){
	//TODO: Finish this.
	flags = 0;
	flag_ctr = 0;
	$("input[type=radio]").each(function(){
		if($(this).prop("checked")){
			flags += 2**(flag_ctr);
		}else{
			flags += 0;
		}
		flag_ctr++;
	});
	$("#flags_input").val(flags);
}
</script>

</body>
</html>