<?php

/* a class to draw lots of text into Minecraft map items
   copyright © cc-by 2011 Kroc Camen <camendesign.com>
   uses NBT Decoder / Encoder for PHP by Justin Martin */

namespace Kroc\McMaps;

class McMapBook {
	public $path    = '';		//the full path to the data folder to write the maps into
	public $map_id  = 0;		//the map number to begin writing at
	
	public $padding = 8;		//space to reserve from the writing to the edge of the page
	public $colour  = 55;		//text colour index, according to palette (see 'mcmap.php')
	
	public $fonts = array (
		//(note: ['pts'] = GD points size (instead of px), ['h'] = line height)
		
		//"04b-03" - Copyright © 1998-2003 Yuji Yoshimoto - <dsg4.com/04>
		//'Freeware - You may use them as you like'
		'04b-03'		=> array ('pts' => 6, 'h' => 7,  'ttf' => '04b03/04B_03__'),
		
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
		'Nayupixel'		=> array ('pts' => 7, 'h' => 8,  'ttf' => 'nayupixel'),
		
		//Uni 05 - Craig Kroeger - <miniml.com>
		//'free to use in commercial or personal work'
		'Uni 05'		=> array ('pts' => 6, 'h' => 7,  'ttf' => 'uni_05_x/uni05_53'),
		
		//Pixel Explosion 01 - Xpaider
		//? - assumed free
		'Pixel Explosion'	=> array ('pts' => 5, 'h' => 8,  'ttf' => 'XPAIDERP')
	);
	//set to one of those above (the array element, not just the name)
	public $font;
	
	public $hypenate = true;	//should I hypenate words to fit them in?
	public $verbose = false;	//output progress
	
	/* ================================================================================================== PRIVATE === */
	
	private $map;			//the current page image
	private $page;			//the current page number
	
	function __construct ($path, $map_id = 0) {
		//set default font
		$this->font = $this->fonts['Uni 05'];
		
		//set the Minecraft world to modify, and what map number to start at
		$this->path   = $path;
		$this->map_id = $map_id;
	}
	
	private function newPage () {
		$this->map = new McMap() ;
		
		if ($this->verbose) echo "\nPage ".$this->page.": (map_".($this->map_id + ($this->page - 1)).".dat)\n";
		
		//right align page number
		$this->writeText (128 - $this->padding - $this->textWidth ($this->page), $this->font['h'], $this->page);
	}
	
	private function savePage () {
		$id = $this->map_id + ($this->page - 1);
		if ($this->verbose) imagepng ($this->map->image, $this->path."map_$id.png", 9);
		return $this->map->save ($this->path."map_$id.dat");
	}
	
	private function nextPage () {
		//output and destroy the old page
		$this->savePage ($this->page);
		unset ($this->map);
		
		//create next page
		$this->page++;
		$this->newPage ();
	}
	
	private function textWidth ($text) {
		$box = imagettfbbox ($this->font['pts'], 0, $this->font['ttf'], $text);
		return $box[2] - $box[6];
	}
	
	private function writeText ($x, $y, $text) {
		return $this->map->writeText ($x, $y, $this->colour, $this->font['ttf'], $this->font['pts'], $text);
	}
	
	/* =================================================================================================== PUBLIC === */
	
	public function generate ($text) {
		//for convenience, remember the widths of these characters
		$space = $this->textWidth (' ');
		$hypen = $this->textWidth ('-');
		
		//start afresh
		$this->page = 1;
		$this->newPage ();
		
		$x = 0;	//current insertion point across the page, in pixels
		$y = 3;	//current line number (not px) down the page
		
		//split into individual words, including line-breaks
		foreach (explode (" ", preg_replace ("/\r?\n/", " \n ", $text)) as $word) {
			//replace tab chars with four spaces
			$word = str_replace ("\t", "    ", $word);
			
			switch ($word) {
			case "\n":
				//newline
				if ($this->verbose) echo "\n";
				$y++; $x = 0;
				
				//is the page full?
				if (($y+1) * $this->font['h'] >= 128) {
					//start a new page
					$this->nextPage ();
					$x = 0; $y = 3;
				}
				continue;
				
			default:
				//will this word fit on the end of the line?
				if ($x + $this->textWidth ($word) > 128-($this->padding * 2)) {
					//no; is the page full?
					//(check before hypenating so as to not hyphenate between pages)
					if (($y+2) * $this->font['h'] >= 128) {
						//start a new page
						$this->nextPage ();
						$x = 0; $y = 3;
						
					//no; can we hyphenate this word?
					} elseif ($this->hypenate && mb_strlen ($word) >= 6) {
						//work backwards from word length determining at what point it fits
						//(note: word cannot be broken before the second letter, and cannot end
						//       with less than 2 letters on the new line)
						for ($i=mb_strlen ($word, 'UTF-8')-1; $i>2; $i--) if (
							//I’m not really testing for anything here, just compacting code
							$split = mb_substr ($word, 0, $i-1, 'UTF-8')
						) if (
							//will this much of the word--plus hypen--fit?
							$x + $this->textWidth ($split) + $hypen <= 128-($this->padding * 2)
						) {
							//yes; write it
							$this->writeText (
								$this->padding + $x, $y * $this->font['h'], "$split-"
							);
							if ($this->verbose) echo "$split-\n";
							
							//move to the next line and write the remainder
							$y++; $x = 0;
							$split = '-'.mb_substr (
								$word, $i-1, mb_strlen ($word, 'UTF-8') - $i+1, 'UTF-8'
							).' ';
							$this->writeText ($this->padding, $y * $this->font['h'], $split);
							$x += $this->textWidth ($split);
							if ($this->verbose) echo $split;
							
							//move straight to the next word
							continue 2;
						}
					}
					
					//carriage return
					$y++; $x = 0;
					if ($this->verbose) echo "\n";
				}
				
				//write the word
				$this->writeText ($this->padding + $x, $y * $this->font['h'], $word);
				if ($this->verbose) echo "$word ";
				
				//proceed to the next word
				$x += $this->textWidth ($word) + $space;
			}
		}
		
		$this->savePage ();
		unset ($this->map);
		
		//return the next available map ID so that additional books can be generated without overwriting
		return $this->map_id + $this->page;
	}
}
