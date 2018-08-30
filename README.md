# DWM2Randomizer
Randomizer for Dragon Warrior Monsters II
6/25/2018

This repository for is the source code for the DWM2 Randomizer hosted at https://cowness.net/DWM2R/


Current Features:
 - Randomized monster loadouts, including stat growth, skill resistances, and skills learned
 - Randomized encounters: Which monsters appear in the field are randomized, with base stats altered to fit their randomized growths
 - Selectable Starting Monster
 - Genius Mode: All monsters have high intelligence, so that it's easier to learn your skills
 - EXP Scaling: Alter the amount of experience yielded by enemies.
 - Stat Scaling: Alter the stats that wild enemies have.  I've intentionally given a ton of options here until it's better-tested; some may be removed later.
 - Boss Scaling: Similar to above, but HP scaling is softened slightly.
 - Yeti Mode: All* monsters are yetis!!!
 - QOL patches, such as opening the Starry Shrine's back door early to allow breeding during Oasis/Pirate.

* - A few monsters in Yeti Mode are not actually yetis.  This includes monsters needed to complete the game (Hoodsquid, Madgopher, Army Ant) and monsters that NPCs offer to breed with you.


Known notable issues:
 - The Starry Shrine's back door opens earlier than intended -- do not talk to the Monster Master before acquiring Slash.
 - A softlock can occur if you walk out the front door of the Starry Shrine before the Pirate key world is completed.
 - Most enemies seem to focus the back monster in your party with all single-target attacks.  The cause of this is not known.
 - Enemies with high Int can end up being abnormally strong after stats are randomized.


 

Long-Term Planned Features:
 - Randomized Fixed Items & Shops/Prices (And random items?  Or at least buff Oasis' items?)
 - Want to "remove" the WLD stat (set it to zero for all wild monsters)
 - Better base-stat calculation
 - Pokemon Trainer Mode: Choose from three random starting monsters (Choice won't affect the rest of the seed)
 - Difficulty sliders to scale EXP yields or enemy base stats
 - More/better options for growth 
 - More silly text changes
 - All outstanding comments flagged "TODO" in index.php