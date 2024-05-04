<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Aniversus implementation : © Leo Cheung <leocheung1718@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * aniversus.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in aniversus_aniversus.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */
  
require_once( APP_BASE_PATH."view/common/game.view.php" );
  
class view_aniversus_aniversus extends game_view
{
    protected function getGameName()
    {
        // Used for translations and stuff. Please do not modify.
        return "aniversus";
    }

    function transformNumber($x) {
        $pivot = 3;
        return 2 * $pivot - $x;
    }

  	function build_page( $viewArgs )
  	{		
  	    // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count( $players );

        /*********** Place your code below:  ************/
        $template = self::getGameName() . "_" . self::getGameName();
        
        // this will inflate our player block with actual players data
        // example
        // $this->page->begin_block($template, "playmatblock");
        // foreach ( $players as $player_id => $info ) {
        //     $team = array_shift($teams);
        //     $this->page->insert_block("playmatblock", array ("PLAYER_ID" => $player_id,
        //             "PLAYER_NAME" => $players [$player_id] ['player_name'],
        //             "PLAYER_COLOR" => $players [$player_id] ['player_color'],
        //             "TEAM" => $team ));
        // }
        $role_array = array("1" => "opponent", "2" => "me");

        for ($x = 1; $x <= 2; $x++) {
            $this->page->begin_block($template, "playeronplaymat_".$role_array[$x]);
            for ( $row = 1; $row <= 2; $row++ ) {
                for ( $col = 1; $col <= 5; $col++ ) {
                    if ($x == 1) {
                        $this->page->insert_block("playeronplaymat_".$role_array[$x], array( "row" => $row, "col" => $this->transformNumber($col), "role" => $role_array[$x],));
                    } else {
                        $this->page->insert_block("playeronplaymat_".$role_array[$x], array( "row" => $row, "col" => $col, "role" => $role_array[$x],));
                    }
                    
                }
            }
        }


        // this will make our My Hand text translatable
        $this->tpl['MY_HAND'] = self::_("My hand");

        /*
        
        // Examples: set the value of some element defined in your tpl file like this: {MY_VARIABLE_ELEMENT}

        // Display a specific number / string
        $this->tpl['MY_VARIABLE_ELEMENT'] = $number_to_display;

        // Display a string to be translated in all languages: 
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::_("A string to be translated");

        // Display some HTML content of your own:
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::raw( $some_html_code );
        
        */
        
        /*
        
        // Example: display a specific HTML block for each player in this game.
        // (note: the block is defined in your .tpl file like this:
        //      <!-- BEGIN myblock --> 
        //          ... my HTML code ...
        //      <!-- END myblock --> 
        

        $this->page->begin_block( "aniversus_aniversus", "myblock" );
        foreach( $players as $player )
        {
            $this->page->insert_block( "myblock", array( 
                                                    "PLAYER_NAME" => $player['player_name'],
                                                    "SOME_VARIABLE" => $some_value
                                                    ...
                                                     ) );
        }
        
        */



        /*********** Do not change anything below this line  ************/
  	}
}
