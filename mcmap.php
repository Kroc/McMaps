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
			array ('r' => 89, 	'g' => 71,	'b' => 43)	//55 Log/Tree/Wood
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