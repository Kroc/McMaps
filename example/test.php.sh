#!/usr/bin/php
<?php

/* NOTE: This is a shell script. Please make it executable (`chmod +x test.php.sh`),
   and run it from the terminal (`./test.php.sh`). if you try to use McMaps from a webserver,
   it won’t have write access to the Minecraft directory
*/

use Kroc\McMaps\McMap;
use Kroc\McMaps\McMapBook;

require __DIR__ . '/../vendor/autoload.php';


/* put any image on a Minecraft map item:
   ====================================================================================================================== */
//initialise the map
$map = new McMap ();
//load your image
$src = imagecreatefrompng ("./test.png");
//apply the image to the map
$map->setImage ($src);

//if you want to, save the resulting conversion to visually confirm
imagepng ($map->image, "./map_0.png");

//save the data file
$map->save ("./map_0.dat");

//clean up
unset ($src);
unset ($map);


/* write books in Minecraft:
   ====================================================================================================================== */
//McMapBook is a class that, given text, generates multiple maps with the text on, automatically word-wrapping for you

//initialise the book
$book = new McMapBook (
	//the full path to the data folder of the Minecraft world, e.g. '~/Application Support/minecraft/saves/World1/data/'
	'./',
	//the map ID to begin writing at, i.e. 'map_0.dat'
	1
);
$book->verbose = true;
$book->colour = 16;	//see the colour palette in 'mcmap.php' and <minecraftwiki.net/wiki/Map_Item_Format#Color_table>

//note: ensure the number of maps required already exist in your minecraft world, if you use McMaps to generate a map that
//	hasn’t yet been crafted in the game, when you craft it, it will be overwritten. just run your McMaps script again

//write the text
//(you can use the next ID returned to start another book)
$next_id = $book->generate (<<<TXT
Leisure by W. H. Davies

What is this life if, full of care, we have no time to stand and stare?—

No time to stand beneath the boughs, and stare as long as sheep and cows:

No time to see, when woods we pass, where squirrels hide their nuts in grass:

No time to see, in broad daylight, streams full of stars, like skies at night:

No time to turn at Beauty's glance, and watch her feet, how they can dance:

No time to wait till her mouth can enrich that smile her eyes began?

A poor life this if, full of care, we have no time to stand and stare.
TXT
);

//clean up
unset ($book);

?>
