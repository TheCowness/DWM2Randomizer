using System;
using System.Windows.Forms;
using System.Security.Cryptography;
using System.IO;
using System.Collections.Generic;

namespace DW4RandoHacker
{
    public partial class Form1 : Form
    {
        byte[] romData;
        Random r1;
        List<int> ValidMonsterIDs = new List<int>();

        public Form1()
        {
            InitializeComponent();
        }

        private void btnBrowse_Click(object sender, EventArgs e)
        {
            OpenFileDialog openFileDialog1 = new OpenFileDialog();

            openFileDialog1.InitialDirectory = "c:\\";
            openFileDialog1.Filter = "txt files (*.txt)|*.txt|All files (*.*)|*.*";
            openFileDialog1.FilterIndex = 2;
            openFileDialog1.RestoreDirectory = true;

            if (openFileDialog1.ShowDialog() == DialogResult.OK)
            {
                txtFileName.Text = openFileDialog1.FileName;
            }
        }

        private void Form1_Load(object sender, EventArgs e)
        {
            txtSeed.Text = ((DateTime.Now.Ticks ^ 2) % 2147483647).ToString();

            //This is the ID stored in the SRAM that determines which monster you have.
            //It's also used within the table of base-stats for each monster.
            //NOTE 0x1B is Butch and I don't think he should be used?
            for (int i = 0; i <= 0x17E; i++)
            {
                if (
                    (i >= 0x01 && i <= 0x1B) ||
                    (i >= 0x24 && i <= 0x42) ||
                    (i >= 0x47 && i <= 0x66) ||
                    (i >= 0x6A && i <= 0x84) ||
                    (i >= 0x8D && i <= 0xA7) ||
                    (i >= 0xB0 && i <= 0xC9) ||
                    (i >= 0xD3 && i <= 0xF0) ||
                    (i >= 0xF6 && i <= 0x110) ||
                    (i >= 0x119 && i <= 0x138) ||
                    (i >= 0x13C && i <= 0x15B) ||
                    (i >= 0x15F && i <= 0x174)
                    )
                {
                    ValidMonsterIDs.Add(i);
                }
            }

            try
            {
                using (TextReader reader = File.OpenText("lastFileDWM2R.txt"))
                {
                    txtFileName.Text = reader.ReadLine();

                    txtSeed.Text = reader.ReadLine();
                    txtFlags.Text = reader.ReadLine();
                }
            }
            catch
            {
                // ignore error
            }
        }

        private void btnNewSeed_Click(object sender, EventArgs e)
        {
            txtSeed.Text = ((DateTime.Now.Ticks ^ 2) % 2147483647).ToString();
        }

        private void btnRandomize_Click(object sender, EventArgs e)
        {
            if (!loadRom())
                return;

            hackRom();
            saveRom();
        }

        private bool hackRom()
        {
            r1 = new Random(int.Parse(txtSeed.Text));
            ShuffleMonsterGrowth("Redistribute");
            ShuffleMonsterResistances();
            ShuffleMonsterSkills();
            ShuffleEncounters("Based on Growth");

            //Wherever "clear water" is mentioned, write "tonic" over the word "water"
            //TODO: Find more of these; Bizhawk's text search is glitchy.  Do a text dump?
            WriteText(0x266624, "tonic");
            WriteText(0x0A107B, "tonic");
            return true;
        }
        
        private bool loadRom()
        {
            try
            {
                romData = File.ReadAllBytes(txtFileName.Text);
            }
            catch
            {
                MessageBox.Show("Empty file name(s) or unable to open files.  Please verify the files exist.");
                return false;
            }
            return true;
        }

        private void saveRom()
        {
            string finalFile = Path.Combine(Path.GetDirectoryName(txtFileName.Text), "DWM2R_" + txtSeed.Text + "_" + txtFlags.Text + ".gbc");
            File.WriteAllBytes(finalFile, romData);
            //lblIntensityDesc.Text = "ROM hacking complete!  (" + finalFile + ")";
            //txtCompare.Text = finalFile;
        }
        
        private void swap(int firstAddress, int secondAddress)
        {
            byte holdAddress = romData[secondAddress];
            romData[secondAddress] = romData[firstAddress];
            romData[firstAddress] = holdAddress;
        }

        private int[] swapArray(int[] array, int first, int second)
        {
            int holdAddress = array[second];
            array[second] = array[first];
            array[first] = holdAddress;
            return array;
        }
        
        private void Form1_FormClosing(object sender, FormClosingEventArgs e)
        {
            if (txtFileName.Text != "")
                using (StreamWriter writer = File.CreateText("lastFile4.txt"))
                {
                    writer.WriteLine(txtFileName.Text);

                    writer.WriteLine(txtSeed.Text);
                    writer.WriteLine(txtFlags.Text);
                }
        }

        private void WriteText(int address,string text)
        {
            int i = 0;
            foreach(char c in text)
            {
                int x = 0;
                if(c >= 'a' && c <= 'z')
                {
                    x = c - 'a' + 0x24;
                }else if(c >= 'A' && c <= 'Z')
                {
                    x = c - 'A' + 0x0A;
                }else if(c >= '0' && c <= '9')
                {
                    x = c - '1';
                    if(c == '0')
                    {
                        x += 10;
                    }
                }
                else
                {
                    x = 0x90;
                }
                romData[address + i] = (byte) x;
                i++;
            }
        }


        private bool ShuffleMonsterGrowth(string rando_level)
        {
            int monster_data_length = 47;
            int first_monster_byte = 0xD4368;
            int monster_count = 313;
            int[] tier_one_skills = { 1, 4, 7, 10, 13, 16, 19, 21, 22, 25, 27, 30, 32, 33, 34, 35, 36, 37, 39, 41, 43, 45, 46, 47, 49, 51, 52, 53, 54, 56, 57, 58, 59, 60, 61, 62, 63, 64, 68, 72, 74, 75, 76, 78, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 120, 121, 122, 123, 124, 125, 126, 127, 128, 129, 130, 131, 132, 133, 137, 138, 139, 141, 143, 144, 145, 146, 147, 148, 149, 150, 151, 153, 155, 156, 157, 158, 159, 160, 161, 162, 163, 164, 165, 166, 167, 168, 169 };

            for (int i = 0; i < monster_count; i++)
            {
                if (rando_level == "Redistribute")
                {
                    //Let's randomize the monster's growth stats, but have them add up to the same value.
                    int total_stats = 0;
                    for (int j = 0; j < 6; j++)
                    {
                        total_stats += (int)romData[first_monster_byte + i * monster_data_length + 14 + j];
                        romData[first_monster_byte + i * monster_data_length + 14 + j] = 0;
                    }

                    //Start by assigning 30 points: 20 to one stat and 10 to another (Or the same?)
                    int slot1 = r1.Next() % 6; //Named slot1 because C# is throwing a fit if I re-use the same var name in the loop below...
                    romData[first_monster_byte + i * monster_data_length + 14 + slot1] = (byte)((int)romData[first_monster_byte + i * monster_data_length + 14 + slot1] + 20);
                    slot1 = r1.Next() % 6;
                    romData[first_monster_byte + i * monster_data_length + 14 + slot1] = (byte)((int)romData[first_monster_byte + i * monster_data_length + 14 + slot1] + 10);
                    total_stats -= 30;

                    while (total_stats > 0)
                    {
                        int slot = r1.Next() % 6;
                        //Do not let the stat go over 31
                        if ((int)romData[first_monster_byte + i * monster_data_length + 14 + slot] < 31)
                        {
                            romData[first_monster_byte + i * monster_data_length + 14 + slot] = (byte)((int)romData[first_monster_byte + i * monster_data_length + 14 + slot] + 1);
                            total_stats--;
                        }
                    }
                }
            }

            return true;
        }


        private bool ShuffleMonsterResistances()
        {
            int monster_data_length = 47;
            int first_monster_byte = 0xD4368;
            int monster_count = 313;
            
            for (int i = 0; i < monster_count; i++)
            {
                //Repeat for resistances.  There are 27 of these...
                int total_resistances = 0;
                for (int j = 0; j < 27; j++)
                {
                    total_resistances += (int)romData[first_monster_byte + i * monster_data_length + 20 + j];
                    romData[first_monster_byte + i * monster_data_length + 20 + j] = 0;
                }
                while (total_resistances > 0)
                {
                    int slot = r1.Next() % 27;
                    //Do not let the stat go over 3
                    if ((int)romData[first_monster_byte + i * monster_data_length + 20 + slot] <= 3)
                    {
                        romData[first_monster_byte + i * monster_data_length + 20 + slot] = (byte)((int)romData[first_monster_byte + i * monster_data_length + 20 + slot] + 1);
                        total_resistances--;
                    }
                }
            }

            return true;
        }


        private bool ShuffleMonsterSkills()
        {
            int monster_data_length = 47;
            int first_monster_byte = 0xD4368;
            int monster_count = 313;
            int[] tier_one_skills = { 1, 4, 7, 10, 13, 16, 19, 21, 22, 25, 27, 30, 32, 33, 34, 35, 36, 37, 39, 41, 43, 45, 46, 47, 49, 51, 52, 53, 54, 56, 57, 58, 59, 60, 61, 62, 63, 64, 68, 72, 74, 75, 76, 78, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 120, 121, 122, 123, 124, 125, 126, 127, 128, 129, 130, 131, 132, 133, 137, 138, 139, 141, 143, 144, 145, 146, 147, 148, 149, 150, 151, 153, 155, 156, 157, 158, 159, 160, 161, 162, 163, 164, 165, 166, 167, 168, 169 };

            for (int i = 0; i < monster_count; i++)
            {
                //Randomize skills!  Pick three of these.
                int skill1 = r1.Next() % tier_one_skills.Length;
                int skill2 = r1.Next() % tier_one_skills.Length;
                while (skill2 == skill1)
                {
                    skill2 = r1.Next() % tier_one_skills.Length;
                }
                int skill3 = r1.Next() % tier_one_skills.Length;
                while (skill3 == skill1 || skill3 == skill2)
                {
                    skill3 = r1.Next() % tier_one_skills.Length;
                }
                romData[first_monster_byte + i * monster_data_length + 10] = (byte)tier_one_skills[skill1];
                romData[first_monster_byte + i * monster_data_length + 11] = (byte)tier_one_skills[skill2];
                romData[first_monster_byte + i * monster_data_length + 12] = (byte)tier_one_skills[skill3];
            }

            return true;
        }


        private bool ShuffleEncounters(string rando_level)
        {
            int encounter_data_length = 26;
            int first_encounter_byte = 0xD008F;
            int encounter_count = 614;
            int monster_data_length = 47;
            int first_monster_byte = 0xD4368;

            for (int i = 0; i < encounter_count; i++)
            {
                //Should probably choose monster independently of the rest of this.
                if(rando_level == "Based on Growth")
                {
                    //Which monster is this?
                    int monster_index = r1.Next() % ValidMonsterIDs.Count;
                    while(ValidMonsterIDs[monster_index] == 0x1B)
                    {
                        monster_index = r1.Next() % ValidMonsterIDs.Count;
                    }
                    monster_index = 27;
                    romData[first_encounter_byte + i * encounter_data_length + 0] = (byte) (ValidMonsterIDs[monster_index] % 256);
                    romData[first_encounter_byte + i * encounter_data_length + 1] = (byte) (Math.Floor((double) ValidMonsterIDs[monster_index] / 256));

                    //Add up the monster's GROWTH values
                    int total_growth_stats = 0;
                    for (int j = 0; j < 6; j++)
                    {
                        total_growth_stats += (int)romData[first_monster_byte + monster_index * monster_data_length + 14 + j];
                    }
                    //Add up the monster's BASE STATS
                    int total_stats = 0;
                    for (int j = 0; j < 6; j++)
                    {
                        total_stats += (int)romData[first_encounter_byte + i * encounter_data_length + 10 + j * 2];
                        total_stats += (int)romData[first_encounter_byte + i * encounter_data_length + 10 + j * 2 + 1] * 256;
                    }
                    //Take the percentage of the GROWTH allocated to each stat and muliply by the total BASE
                    for (int j = 0; j < 6; j++)
                    {
                        double new_stat = (int)romData[first_monster_byte + monster_index * monster_data_length + 14 + j];
                        new_stat = Math.Floor(new_stat * total_stats / total_growth_stats);
                        romData[first_encounter_byte + i * encounter_data_length + 10 + j * 2] = (byte)(new_stat % 256);
                        romData[first_encounter_byte + i * encounter_data_length + 10 + j * 2 + 1] = (byte)((int)(new_stat / 256));
                    }

                    //Now, what to do for skills?
                    //Learn their first skill
                    romData[first_encounter_byte + i * encounter_data_length + 2] = romData[first_monster_byte + monster_index * monster_data_length + 10];
                    if (i > 150)
                    {
                        //Learn their second skill
                        romData[first_encounter_byte + i * encounter_data_length + 3] = romData[first_monster_byte + monster_index * monster_data_length + 11];
                    }
                    if (i > 300)
                    {
                        //Learn their third skill
                        romData[first_encounter_byte + i * encounter_data_length + 4] = romData[first_monster_byte + monster_index * monster_data_length + 12];
                    }
                }
                //Adjust EXP gains so that nothing gives 1 EXP... If the monster is worth less than 20 EXP (6 or less EXP after splitting three ways), double its value.
                if((int) romData[first_encounter_byte + i * encounter_data_length + 6] + (int)romData[first_encounter_byte + i * encounter_data_length + 7] * 256 < 20)
                {
                    romData[first_encounter_byte + i * encounter_data_length + 6] = (byte) ((int)romData[first_encounter_byte + i * encounter_data_length + 6] * 2);
                }
            }
            //Hoodsquid should always know LureDance
            romData[0xD0335] = (byte)0x7A;

            return true;
        }
    }
}
