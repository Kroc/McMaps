<?php

/* a class to draw lots of text into Minecraft map items
   copyright © cc-by 2011 Kroc Camen <camendesign.com>
   uses NBT Decoder / Encoder for PHP by Justin Martin */

class McMapBook {
	public $fonts = array (
		//(note: ['pts'] = GD points size (instead of px), ['h'] = line height)
		
		//"04b-03" - Copyright © 1998-2003 Yuji Yoshimoto - <dsg4.com/04>
		//'Freeware - You may use them as you like'
		'04b-03'		=> array ('pts' => 6, 'h' => 8,  'ttf' => '04b03/04B_03__'),
		
		//"Alicia Marie" - Alicia Rios
		//'Free for personal use'
		'Alicia Marie'		=> array ('pts' => 6, 'h' => 8,  'ttf' => 'alicia_marie/alicia_marie'),
		
		//"CaZOOM" - Sara Batchelor
		//'Free for personal use'
		'CaZOOM'		=> array ('pts' => 7, 'h' => 8,  'ttf' => 'cazoom'),
		
		//"Handy" - Cal Henderson - <iamcal.com/misc/fonts>
		//'Free'
		'Handy'			=> array ('pts' => 6, 'h' => 10, 'ttf' => 'handy00'),
		
		//"Nayupixel" - Nathalie D. - <twitter.com/nnathaliee>
		//'Free for personal use'
		'Nayupixel'		=> array ('pts' => 6, 'h' => 8,  'ttf' => 'nayupixel'),
		
		//Uni 05 - Craig Kroeger - <miniml.com>
		//'free to use in commercial or personal work'
		'Uni 05'		=> array ('pts' => 6, 'h' => 7,  'ttf' => 'uni_05_x/uni05_53'),
		
		//Pixel Explosion 01 - Xpaider
		//? - assumed free
		'Pixel Explosion'	=> array ('pts' => 5, 'h' => 8,  'ttf' => 'XPAIDERP')
	);
	
	public $font;
	
	public $padding = 8;	//space to reserve from the writing to the edge of the page
	
	public $path    = '';	//the full path to the data folder to write the maps into
	public $map_id  = 0;	//the map number to begin writing at
	
	function __construct ($path, $map_id = 0) {
		//set default font
		$this->font = $this->fonts['Uni 05'];
		
		//set the Minecraft world to modify, and what map number to start at
		$this->path   = $path;
		$this->map_id = $map_id;
	}
	
	public function generate ($text) {
		//width of a space
		$space = $this->textWidth (' ');
		
		$page = 1;	//current page number
		$x    = 0;	//current insertion point across the page, in pixels
		$y    = 2;	//current line number (not px) down the page
		
		$map = $this->newPage ($page);
		
		//split into lines of text
		$lines = explode ("\n", $text);
		foreach ($lines as $line) {
			//split into individual words
			$words = explode (' ', $line);
			while ($word = current ($words)) {
				//replace tab chars with four spaces
				$word = str_replace("\t", "    ", $word);
				
				//will this word fit on the end of the line?
				if ($x + $this->textWidth ($word) > 128 - ($this->padding * 2)) {
					//carriage return
					$y++; $x = 0;
					echo "\n";
					
					//is the page full?
					if (($y+1) * $this->font['h'] >= 128) {
						//start a new page
						$map = $this->nextPage ($map, $page);
						$x = 0; $y = 2;
					}
				}
				
				//write the word
				$this->writeText ($map, $this->padding + $x, $y * $this->font['h'], 55, $word);
				echo "$word ";
				
				//proceed to the next word
				$x += $this->textWidth ($word) + $space; next ($words);
			}
			
			//line-break
			next ($lines); $y++; $x = 0;
			echo "\n";
			
			//is the page full?
			if (current ($lines) && $y * $this->font['h'] >= 128) {
				$map = $this->nextPage ($map, $page);
				$x = 0; $y = 2;
			}
		}
		
		$this->savePage ($map, $page);
		unset ($map);
		
		//return the next available map ID so that additional books can be generated without overwriting
		return $page++;
	}
	
	private function newPage ($page) {
		$map = new McMap ();
		
		echo "\nPage $page:\n";
		
		//right align page number
		$map->writeText (
			128 - $this->padding - $this->textWidth ($page), $this->font['h'],
			55, $this->font['ttf'], $this->font['pts'], $page
		);
		
		return $map;
	}
	
	private function savePage (&$map, $page) {
		$id = $this->map_id + ($page - 1);
		return $map->save ($this->path."map_$id.dat");
	}
	
	private function nextPage (&$map, &$page) {
		$this->savePage ($map, $page);
		unset ($map);
		return $this->newPage (++$page, $this->font);
	}
	
	public function textWidth ($text) {
		$box = imagettfbbox ($this->font['pts'], 0, $this->font['ttf'], $text);
		return $box[2] - $box[6];
	}
	
	public function writeText ($map, $x, $y, $colour, $text) {
		return $map->writeText ($x, $y, $colour, $this->font['ttf'], $this->font['pts'], $text);
	}
}


?>