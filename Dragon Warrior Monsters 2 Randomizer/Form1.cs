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

            txtSeed.Text = (DateTime.Now.Ticks ^2 % 2147483647).ToString();
            
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
            txtSeed.Text = (DateTime.Now.Ticks ^ 2 % 2147483647).ToString();
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
            //Modify a Slime's stat growth to put all stats at 31 (the max)
            romData[0xD451D] = 31;
            romData[0xD451E] = 31;
            romData[0xD451F] = 31;
            romData[0xD4520] = 31;
            romData[0xD4521] = 31;
            romData[0xD4522] = 31;

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
        
    }
}
