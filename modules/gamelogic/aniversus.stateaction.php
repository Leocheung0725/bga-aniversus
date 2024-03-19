<?php
trait AniversusStateActions {
    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state actions
    ////////////
    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    
    /* Example
    function stMyGameState()
    {   
        // Do some stuff ...
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */
    
    function stNewHand() {
        try {
            // Red color player plays first
            // Find who is first player
            $sql = "SELECT player_id, player_name, player_color, player_team FROM player";
            // $players = self::loadPlayersBasicInfos();   // !! We must only return information visible by this player !!
            $players = self::getCollectionFromDb( $sql );
            // Do something with this information
            foreach ($players as $player_id => $player_info) {
                if ($player_info['player_color'] == "E96043") {
                    $first_player = $player_info;
                } else {
                    $second_player = $player_info;
                }
            }
            // Set red player as active player, red player has to play first
            // we would use this to deal initial cards to players ( 7 cards for first player, 8 cards for second player)
            // $players = $this->loadPlayersBasicInfos();
            // Create basic cards (IDs 1 to 13) for both decks
            $basicCards = array();
            foreach ($this->cards_info as $id => $card_info) {
                if ($card_info['id'] >= 1 && $card_info['id'] <= 13) {
                    $basicCards[] = array('type' => $card_info['type'], 'type_arg' => $card_info['id'], 'nbr' => $card_info['nbr']);
                }
            }
            // Create special cards for Cat deck (IDs 101 to 114)
            $catSpecialCards = array();
            foreach ($this->cards_info as $id => $card_info) {
                if ($card_info['id'] >= 101 && $card_info['id'] <= 114) {
                    $catSpecialCards[] = array('type' => $card_info['type'], 'type_arg' => $card_info['id'], 'nbr' => $card_info['nbr']);
                }
            }
            // Create special cards for Squirrel deck (IDs 51 to 64)
            $squirrelSpecialCards = array();
            foreach ($this->cards_info as $id => $card_info) {
                if ($card_info['id'] >= 51 && $card_info['id'] <= 64) {
                    $squirrelSpecialCards[] = array('type' => $card_info['type'], 'type_arg' => $card_info['id'], 'nbr' => $card_info['nbr']);
                }
            }
            // Add cards to Cat deck
            $this->catDeck->createCards($basicCards, 'deck');
            $this->catDeck->createCards($catSpecialCards, 'deck');
            // Add cards to Squirrel deck
            $this->squirrelDeck->createCards($basicCards, 'deck');
            $this->squirrelDeck->createCards($squirrelSpecialCards, 'deck');
            // Shuffle both decks
            $this->catDeck->shuffle('deck');
            $this->squirrelDeck->shuffle('deck');
            // Determine who is cat team and who is squirrel team
            $first_player_deck = ($first_player['player_team'] == 'cat') ? 'catDeck' : 'squirrelDeck';
            $second_player_deck = ($second_player['player_team'] == 'cat') ? 'catDeck' : 'squirrelDeck';
            // Deal cards to players
            $cards_to_first_player = $this->{$first_player_deck}->pickCards( 7, 'deck', $first_player['player_id'] );
            self::notifyPlayer($first_player['player_id'], 'newHand', '', array('cards' => $cards_to_first_player) );
            $cards_to_second_player = $this->{$second_player_deck}->pickCards( 8, 'deck', $second_player['player_id'] );
            self::notifyPlayer($second_player['player_id'], 'newHand', '', array('cards' => $cards_to_second_player) );
            // Activate first player (which is in general a good idea :) )
            $this->gamestate->changeActivePlayer( $first_player['player_id'] );
            // switch to next state
            $this->gamestate->nextState( "playerTurn" );
        } catch ( Exception $e ) {
            // logging does not actually work in game init :(
            // but if you calling from php chat it will work
            $this->error("Fatal error while creating game");
            $this->dump('err', $e);
        }
    }

    function stCardDrawing() {
        // get active player team
        $active_player = self::getActivePlayerId();
        $active_player_team = $this->getActivePlayerDeck($active_player);
        // Draw a card from the deck
        $pick_cards = $active_player_team->pickCards( 2, 'deck', $active_player );
        // Notify the player about the card drawn
        self::notifyPlayer($active_player, 'cardDrawn', '', $pick_cards);
        // switch to next state
        $this->gamestate->nextState( "playerTurn" );
    }

    function stCardEffect() {
        // determine what card effect should be done / finished
        $sql = "SELECT card_id, card_type, card_type_arg, player_id, card_status, card_location FROM playing_card WHERE active = TRUE";
        $card_effect_info = self::getNonEmptyObjectFromDB( $sql );
        $player_id = $card_effect_info['player_id'];
        $player_deck = $this->getActivePlayerDeck($card_effect_info['player_id']);
        switch ($card_type_arg) {
            case "1":
                // do something
                $card_num = 3;
                $picked_cards_list = $player_deck->pickCards( $card_num, 'deck', $card_effect_info['player_id'] );
                self::notifyPlayer($player_id, 'cardDrawn', clienttranslate( 'You draw ${card_num} cards' ), [
                    'cards' => $picked_cards_list,
                    'card_num' => $card_num,
                    'player_id' => $player_id,
                ]);
                // endEffect have two type : normal and active
                $this->endEffect($player_id, "active");
                break;
            case "2":
                // do something
                break;
            case "7":
                $sql = "UPDATE player SET player_productivity =  player_productivity + 2 WHERE player_id = $player_id";
                self::DbQuery( $sql );
                $this->endEffect($player_id, "normal");
                break;
            default:
                break;
        }
    }

    function stEndHand() {
        // End the game and do some scoring here

        // ... code the function
        $this->gamestate->nextState( "endGame" );
    }
}