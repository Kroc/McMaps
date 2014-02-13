<?php

/* a class to read and write Minecraft map item files
   copyright © cc-by 2011 Kroc Camen <camendesign.com>
   uses NBT Decoder / Encoder for PHP by Justin Martin */

require_once 'nbt/nbt.class.php';

//set font directory
putenv ('GDFONTPATH='.realpath (dirname (__FILE__).'/fonts'));

class McMap {
	private $nbt;			//the NBT class for reading/writing the file structure
	
	public $image;			//the GD image handle
	public $palette = array ();	//Notch’s palette for map items
	
	/* ============================================================================================================== */
	
	function __construct () {
		//instantiate a new NBT strucutre
		$this->nbt = new NBT ();
		//populate the default data for a Minecraft map item
		$this->nbt->root[0] = array ('name' => '', 'type' => NBT::TAG_COMPOUND, 'value' => array (
			array ('name' => 'data', 'type' => NBT::TAG_COMPOUND, 'value' => array (
				//1:1 zoom
				array ('name' => 'scale',	'type' => NBT::TAG_BYTE, 	'value' => 0),
				//'overworld' (vs 'nether')
				array ('name' => 'dimenson',	'type' => NBT::TAG_BYTE, 	'value' => 0),
				//default map size
				array ('name' => 'height',	'type' => NBT::TAG_SHORT,	'value' => 128),
				array ('name' => 'width',	'type' => NBT::TAG_SHORT,	'value' => 128),
				//locate the map somewhere where the player is unlikely to ever step
				array ('name' => 'xCenter',	'type' => NBT::TAG_INT,		'value' => -12500000),
				array ('name' => 'zCenter',	'type' => NBT::TAG_INT,		'value' => -12500000),
				//start with a blank map
				array (
					'name' => 'colors', 'type' => NBT::TAG_BYTE_ARRAY,
					//create an empty bytearray for the 128x128 map image
					'value' => array_fill (0, (128 * 128) - 1, 0)
				)
			))
		));
		
		//create a blank canvas for the image
		$this->image = imagecreate (128, 128);
		
		//assign the colour palette
		foreach (array (
			//see <minecraftwiki.net/wiki/Map_Item_Format#Color_table>
			array ('r' => 255,	'g' => 255,	'b' => 255), 	//0  Not explored
			array ('r' => 255,	'g' => 0,	'b' => 255),	//1  Not explored
			array ('r' => 255,	'g' => 0,	'b' => 255),	//2  Not explored
			array ('r' => 255,	'g' => 0,	'b' => 255),	//3  Not explored
			
			array ('r' => 89,	'g' => 125,	'b' => 39),	//4  Grass
			array ('r' => 109,	'g' => 153,	'b' => 48),	//5  Grass
			array ('r' => 127,	'g' => 178,	'b' => 56),	//6  Grass
			array ('r' => 109,	'g' => 153,	'b' => 48),	//7  Grass
			array ('r' => 174,	'g' => 164,	'b' => 115),	//8  Sand/Gravel
			array ('r' => 213,	'g' => 201,	'b' => 140),	//9  Sand/Gravel
			array ('r' => 247,	'g' => 233,	'b' => 163),	//10 Sand/Gravel
			array ('r' => 213,	'g' => 201,	'b' => 140),	//11 Sand/Gravel
			array ('r' => 117,	'g' => 117,	'b' => 117),	//12 Other
			array ('r' => 144,	'g' => 144,	'b' => 144),	//13 Other
			array ('r' => 167,	'g' => 167,	'b' => 167),	//14 Other
			array ('r' => 144,	'g' => 144,	'b' => 144),	//15 Other
			array ('r' => 180,	'g' => 0,	'b' => 0),	//16 Lava
			array ('r' => 220,	'g' => 0,	'b' => 0),	//17 Lava
			array ('r' => 255,	'g' => 0,	'b' => 0),	//18 Lava
			array ('r' => 220,	'g' => 0,	'b' => 0),	//19 Lava
			array ('r' => 112,	'g' => 112,	'b' => 180),	//20 Ice
			array ('r' => 138,	'g' => 138,	'b' => 220),	//21 Ice
			array ('r' => 160,	'g' => 160,	'b' => 255),	//22 Ice
			array ('r' => 138,	'g' => 138,	'b' => 220),	//23 Ice
			array ('r' => 117,	'g' => 117,	'b' => 117),	//24 Other
			array ('r' => 144,	'g' => 144,	'b' => 144),	//25 Other
			array ('r' => 167,	'g' => 167,	'b' => 167),	//26 Other
			array ('r' => 144,	'g' => 144,	'b' => 144),	//27 Other
			array ('r' => 0,	'g' => 87,	'b' => 0),	//28 Leaves
			array ('r' => 0,	'g' => 106,	'b' => 0),	//29 Leaves
			array ('r' => 0,	'g' => 124,	'b' => 0),	//30 Leaves
			array ('r' => 0,	'g' => 106,	'b' => 0),	//31 Leaves
			array ('r' => 180,	'g' => 180,	'b' => 180),	//32 Snow
			array ('r' => 220,	'g' => 220,	'b' => 220),	//33 Snow
			array ('r' => 255,	'g' => 255,	'b' => 255),	//34 Snow
			array ('r' => 220,	'g' => 220,	'b' => 220),	//35 Snow
			array ('r' => 115,	'g' => 118,	'b' => 129),	//36 Clay
			array ('r' => 141,	'g' => 144,	'b' => 158),	//37 Clay
			array ('r' => 164,	'g' => 168,	'b' => 184),	//38 Clay
			array ('r' => 141,	'g' => 144,	'b' => 158),	//39 Clay
			array ('r' => 129,	'g' => 74, 	'b' => 33),	//40 Dirt
			array ('r' => 157,	'g' => 91, 	'b' => 40),	//41 Dirt
			array ('r' => 183,	'g' => 106,	'b' => 47),	//42 Dirt
			array ('r' => 157,	'g' => 91, 	'b' => 40),	//43 Dirt
			array ('r' => 79, 	'g' => 79, 	'b' => 79),	//44 Smoothstone/Cobblestone/Ore
			array ('r' => 96, 	'g' => 96, 	'b' => 96),	//45 Smoothstone/Cobblestone/Ore
			array ('r' => 112,	'g' => 112,	'b' => 112),	//46 Smoothstone/Cobblestone/Ore
			array ('r' => 96, 	'g' => 96, 	'b' => 96),	//47 Smoothstone/Cobblestone/Ore
			array ('r' => 45, 	'g' => 45,	'b' => 180),	//48 Water
			array ('r' => 55, 	'g' => 55,	'b' => 220),	//49 Water
			array ('r' => 64, 	'g' => 64,	'b' => 255),	//50 Water
			array ('r' => 55, 	'g' => 55,	'b' => 220),	//51 Water
			array ('r' => 73, 	'g' => 58,	'b' => 35),	//52 Log/Tree/Wood
			array ('r' => 89, 	'g' => 71,	'b' => 43),	//53 Log/Tree/Wood
			array ('r' => 104,	'g' => 83,	'b' => 50),	//54 Log/Tree/Wood
			array ('r' => 89, 	'g' => 71,	'b' => 43),	//55 Log/Tree/Wood
			
			array ('r' => 180,	'g' => 177,	'b' => 172), 	//56
			array ('r' => 220,	'g' => 217,	'b' => 211), 	//57
			array ('r' => 255,	'g' => 252,	'b' => 245), 	//58
			array ('r' => 135,	'g' => 133,	'b' => 129), 	//59
			array ('r' => 152,	'g' => 89,	'b' => 36), 	//60
			array ('r' => 186,	'g' => 109,	'b' => 44), 	//61
			array ('r' => 216,	'g' => 127,	'b' => 51), 	//62
			array ('r' => 114,	'g' => 67,	'b' => 27), 	//63
			array ('r' => 125,	'g' => 53,	'b' => 152), 	//64
			array ('r' => 153,	'g' => 65,	'b' => 186), 	//65
			array ('r' => 178,	'g' => 76,	'b' => 216), 	//66
			array ('r' => 94,	'g' => 40,	'b' => 114), 	//67
			array ('r' => 72,	'g' => 108,	'b' => 152), 	//68
			array ('r' => 88,	'g' => 132,	'b' => 186), 	//69
			array ('r' => 102,	'g' => 153,	'b' => 216), 	//70
			array ('r' => 54,	'g' => 81,	'b' => 114), 	//71
			array ('r' => 161,	'g' => 161,	'b' => 36), 	//72
			array ('r' => 197,	'g' => 197,	'b' => 44), 	//73
			array ('r' => 229,	'g' => 229,	'b' => 51), 	//74
			array ('r' => 121,	'g' => 121,	'b' => 27), 	//75
			array ('r' => 89,	'g' => 144,	'b' => 17), 	//76
			array ('r' => 109,	'g' => 176,	'b' => 21), 	//77
			array ('r' => 127,	'g' => 204,	'b' => 25), 	//78
			array ('r' => 67,	'g' => 108,	'b' => 13), 	//79
			array ('r' => 170,	'g' => 89,	'b' => 116), 	//80
			array ('r' => 208,	'g' => 109,	'b' => 142), 	//81
			array ('r' => 242,	'g' => 127,	'b' => 165), 	//82
			array ('r' => 128,	'g' => 67,	'b' => 87), 	//83
			array ('r' => 53,	'g' => 53,	'b' => 53), 	//84
			array ('r' => 65,	'g' => 65,	'b' => 65), 	//85
			array ('r' => 76,	'g' => 76,	'b' => 76), 	//86
			array ('r' => 40,	'g' => 40,	'b' => 40), 	//87
			array ('r' => 108,	'g' => 108,	'b' => 108), 	//88
			array ('r' => 132,	'g' => 132,	'b' => 132), 	//89
			array ('r' => 153,	'g' => 153,	'b' => 153), 	//90
			array ('r' => 81,	'g' => 81,	'b' => 81), 	//91
			array ('r' => 53,	'g' => 89,	'b' => 108), 	//92
			array ('r' => 65,	'g' => 109,	'b' => 132), 	//93
			array ('r' => 76,	'g' => 127,	'b' => 153), 	//94
			array ('r' => 40,	'g' => 67,	'b' => 81), 	//95
			array ('r' => 89,	'g' => 44,	'b' => 125), 	//96
			array ('r' => 109,	'g' => 54,	'b' => 153), 	//97
			array ('r' => 127,	'g' => 63,	'b' => 178), 	//98
			array ('r' => 67,	'g' => 33,	'b' => 94), 	//99
			array ('r' => 36,	'g' => 53,	'b' => 125), 	//100
			array ('r' => 44,	'g' => 65,	'b' => 153), 	//101
			array ('r' => 51,	'g' => 76,	'b' => 178), 	//102
			array ('r' => 27,	'g' => 40,	'b' => 94), 	//103
			array ('r' => 72,	'g' => 53,	'b' => 36), 	//104
			array ('r' => 88,	'g' => 65,	'b' => 44), 	//105
			array ('r' => 102,	'g' => 76,	'b' => 51), 	//106
			array ('r' => 54,	'g' => 40,	'b' => 27), 	//107
			array ('r' => 72,	'g' => 89,	'b' => 36), 	//108
			array ('r' => 88,	'g' => 109,	'b' => 44), 	//109
			array ('r' => 102,	'g' => 127,	'b' => 51), 	//110
			array ('r' => 54,	'g' => 67,	'b' => 27), 	//111
			array ('r' => 108,	'g' => 36,	'b' => 36), 	//112
			array ('r' => 132,	'g' => 44,	'b' => 44), 	//113
			array ('r' => 153,	'g' => 51,	'b' => 51), 	//114
			array ('r' => 81,	'g' => 27,	'b' => 27), 	//115
			array ('r' => 17,	'g' => 17,	'b' => 17), 	//116
			array ('r' => 21,	'g' => 21,	'b' => 21), 	//117
			array ('r' => 25,	'g' => 25,	'b' => 25), 	//118
			array ('r' => 13,	'g' => 13,	'b' => 13), 	//119
			array ('r' => 176,	'g' => 168,	'b' => 54), 	//120
			array ('r' => 215,	'g' => 205,	'b' => 66), 	//121
			array ('r' => 250,	'g' => 238,	'b' => 77), 	//122
			array ('r' => 132,	'g' => 126,	'b' => 40), 	//123
			array ('r' => 64,	'g' => 154,	'b' => 150), 	//124
			array ('r' => 79,	'g' => 188,	'b' => 183), 	//125
			array ('r' => 92,	'g' => 219,	'b' => 213), 	//126
			array ('r' => 48,	'g' => 115,	'b' => 112), 	//127
			array ('r' => 52,	'g' => 90,	'b' => 180), 	//128
			array ('r' => 63,	'g' => 110,	'b' => 220), 	//129
			array ('r' => 74,	'g' => 128,	'b' => 255), 	//130
			array ('r' => 39,	'g' => 67,	'b' => 135), 	//131
			array ('r' => 0,	'g' => 153,	'b' => 40), 	//132
			array ('r' => 0,	'g' => 187,	'b' => 50), 	//133
			array ('r' => 0,	'g' => 217,	'b' => 58), 	//134
			array ('r' => 0,	'g' => 114,	'b' => 30), 	//135
			array ('r' => 14,	'g' => 14,	'b' => 21), 	//136
			array ('r' => 18,	'g' => 17,	'b' => 26), 	//137
			array ('r' => 21,	'g' => 20,	'b' => 31), 	//138
			array ('r' => 11,	'g' => 10,	'b' => 16), 	//139
			array ('r' => 79,	'g' => 1,	'b' => 0), 	//140
			array ('r' => 96,	'g' => 1,	'b' => 0), 	//141
			array ('r' => 112,	'g' => 2,	'b' => 0), 	//142
			array ('r' => 59,	'g' => 1,	'b' => 0) 	//143
		) as $colour)
			$this->palette[] = imagecolorallocate ($this->image, $colour['r'], $colour['g'], $colour['b'])
		;
		//the ‘void’
		imagecolortransparent ($this->image, $this->palette[0]);
	}
	
	function __destruct () {
		//release handles, everything else should go away by itself
		unset ($this->nbt);
		imagedestroy ($this->image);
	}
	
	/* ============================================================================================================== */	
	
	public function writeText ($x, $y, $color_id, $ttf, $pts, $text) {
		return imagettftext (
			//the negative version of the colour index turns anti-aliasing off (crashes Minecraft otherwise)
			$this->image, $pts, 0, $x, $y, -1 * $this->palette[$color_id], $ttf, $text
		);
	}
	
	/* load: read in a map file and paint it onto the GD image for further manipulation
	   ---------------------------------------------------------------------------------------------------------------*/
	public function load ($file) {
		$this->nbt->purge ();
		$this->nbt->loadFile ($file);
		
		//which element is the 'colors' array?
		foreach ($this->nbt->root[0]['value'][0]['value'] as &$node) if ($node['name'] == 'colors') break;
		
		for ($y=0; $y < 128; $y++) for ($x=0; $x < 128 ; $x++)
			@imagesetpixel ($this->image, $x, $y, $this->palette[$node['value'][$x + ($y*128)]])
		;
	}
	
	/* save: take the GD image, put it into the byte array, and save to disk
	   -------------------------------------------------------------------------------------------------------------- */
	public function save ($file) {
		//update the image data in the NBT:
		for ($y=0; $y < 128; $y++) for ($x=0; $x < 128 ; $x++)
			$this->nbt->root[0]['value'][0]['value'][6]['value'][$x + ($y*128)] = imagecolorat ($this->image, $x, $y)
		;
		//save the NBT file to disk
		return $this->nbt->writeFile ($file);
	}
	
	/* setImage: resize & palettize a GD image source onto the map
	   -------------------------------------------------------------------------------------------------------------- */
	public function setImage ($src) {
		//resize the source image to 128x128
		$buffer = imagecreatetruecolor (128, 128);
		imagecopyresized ($buffer, $src, 0, 0, 0, 0, 128, 128, imagesx ($src), imagesy ($src));
		
		//copy the image pixel-by-pixel to the map, changing the colours to fit Minecraft
		for ($y=0; $y < 128; $y++) for ($x=0; $x < 128 ; $x++) {
			//get the 32-bit colour
			$c = imagecolorsforindex ($src, imagecolorat ($src, $x, $y));
			//draw it, using the closest color in the Minecraft map palette
			imagesetpixel (
				$this->image, $x, $y,
				imagecolorclosestalpha ($this->image, $c['red'], $c['green'], $c['blue'], $c['alpha'])
			);
		}
		unset ($buffer);
	}
}

?>
