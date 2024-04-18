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
        // ANCHOR stNewHand
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
            $cards_to_second_player = $this->{$second_player_deck}->pickCards( 6, 'deck', $second_player['player_id'] );
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
        // ANCHOR stCardDrawing
        // Some special card effects may be triggered here
        $player_id = self::getActivePlayerId();
        $sql = "SELECT player_status FROM player WHERE player_id = $player_id";
        $player = self::getNonEmptyObjectFromDB( $sql );
        $player_status = json_decode($player['player_status']);
        foreach ($player_status as $status) {
            switch ($status) {
                case 13:
                    $sql = "UPDATE player SET player_action = player_action + 2 WHERE player_id = $player_id";
                    self::DbQuery( $sql );
                    $this->updatePlayerBoard($player_id);
                    $this->removeStatusFromStatusLst($player_id, 13);
                    break;
                case 10:
                    $sql = "UPDATE player SET player_productivity = player_productivity - 2 WHERE player_id = $player_id";
                    self::DbQuery( $sql );
                    $this->updatePlayerBoard($player_id);
                    $this->removeStatusFromStatusLst($player_id, 10);
                    break;
                case 55: // can not draw cards
                    $this->addStatus2StatusLst($player_id, False, 55);
                    $this->gamestate->nextState( "playerTurn" );
                    break;
                case 105: // skip one round
                    $this->addStatus2StatusLst($player_id, False, 105);
                    $this->gamestate->nextState( "playerEndTurn" );
                    break;
                default:
                    break;
            }
        }

        // Normal Drawing Phase
        // get active player team

        $active_player_team = $this->getActivePlayerDeck($player_id);
        // Draw a card from the deck
        $pick_cards = $active_player_team->pickCards( 2, 'deck', $player_id );
        // Notify the player about the card drawn
        self::notifyPlayer($player_id, 'cardDrawn', clienttranslate( 'You draw 2 cards' ), [
            'cards' => $pick_cards,
            'player_id' => $player_id,
        ]);
        // switch to next state
        $this->gamestate->nextState( "playerTurn" );
    }

    function stCardEffect() {
        // ANCHOR stCardEffect
        // determine what card effect should be done / finished
        $sql = "SELECT * FROM playing_card WHERE disabled = FALSE";
        $card_effect_info = self::getNonEmptyObjectFromDB( $sql );
        $card_id = $card_effect_info['card_id'];
        $player_id = $card_effect_info['player_id'];
        $card_type_arg = $card_effect_info['card_type_arg'];
        $player_deck = $this->getActivePlayerDeck($card_effect_info['player_id']);
        switch ($card_type_arg) {
            case 1: // Draw 3 cards, then discard 1 card from your hand. (seems ok)
                $picked_cards_list = $player_deck->pickCards( 3, 'deck', $player_id );
                self::notifyPlayer($player_id, 'cardDrawn', clienttranslate( 'You draw 3 cards' ), [
                    'cards' => $picked_cards_list,
                    'player_id' => $player_id,
                ]);
                // endEffect have two type : normal and active
                $this->endEffect("activeplayerEffect");
                return;
                break;
            case 2: // Play a card without paying its cost. (DOES NOT count as an action)
                $sql = "UPDATE player SET player_action = player_action + 1 WHERE player_id = $player_id";
                self::DbQuery( $sql );
                $this->updatePlayerBoard($player_id);
                $this->addStatus2StatusLst($player_id, False, 2);
                break;
            case 3: // Double the effect of a function card. (Play this card first, then the function card)
                $this->addStatus2StatusLst($player_id, False, 3);
                break;
            case 4: // Dismiss 1 opponent's forward player. (This card can be played during opponent's SHOOTING phase, which DOES NOT count as an action)
                break;
            case 6: // Choose 1 card, at random, from your opponent's hand and discard it.
                $opponent_playerId = $this->getNonActivePlayerId(); 
                $player_deck_opponent = $this->getNonActivePlayerDeck($player_id);
                $opponent_hand = $player_deck_opponent->getPlayerHand($opponent_playerId);
                $selected_thrown_card = array_rand($opponent_hand, 1);
                $this->throwCards($opponent_playerId, [$opponent_hand[$selected_thrown_card]['id']]);
                self::notifyAllPlayers('cardThrown', clienttranslate( '${player_name} throws a card' ), [
                    'player_name' => $this->getActivePlayerName(),
                    'player_id' => $player_id,
                    'card_id' => $card_id,
                    'card_type_arg' => $card_type_arg,
                ]);
                break;
            case 7: // active player Gain 2 energy in this round. (ok)
                $sql = "UPDATE player SET player_productivity =  player_productivity + 2 WHERE player_id = $player_id";
                self::DbQuery( $sql );
                $this->updatePlayerBoard($player_id);
            case 8: // Look at the top 5 cards from your draw deck, then put them back in any order either on top of or at the bottom of your draw deck.
                $this->endEffect("activeplayerEffect");
                return;
                break;
            case 10: // Opponent -2 energy next round.
                $this->addStatus2StatusLst($player_id, True, 10);
                break;
            case 11:
                $this->endEffect("activeplayerEffect");
                return;
                break;
            case 13: // Get extra 2 actions in your next round
                $this->addStatus2StatusLst($player_id, False, 13);
                break;
            case 53: // This card can only be played when you have 3 cards or fewer in your hand. Draw 3 cards.
                $picked_cards_list = $player_deck->pickCards( 3, 'deck', $player_id );
                self::notifyPlayer($player_id, 'cardDrawn', clienttranslate( 'You draw ${card_num} cards' ), [
                    'cards' => $picked_cards_list,
                    'card_num' => $card_num,
                    'player_id' => $player_id,
                ]);
            case 54: // Power + 2 this round
                $sql = "UPDATE player SET player_power =  player_power + 2 WHERE player_id = $player_id";
                self::DbQuery( $sql );
                $this->addStatus2StatusLst($player_id, False, 54);
                $this->updatePlayerBoard($player_id);
                break;
            case 55: //Your opponent cannot draw cards next round.
                $this->addStatus2StatusLst($player_id, True, 55);
                break;
            case 56: // Draw 2 cards, then discard 2 cards from all your hand cards.
                $picked_cards_list = $player_deck->pickCards( 2, 'deck', $player_id );
                self::notifyPlayer($player_id, 'cardDrawn', clienttranslate( 'You draw ${card_num} cards' ), [
                    'cards' => $picked_cards_list,
                    'card_num' => $card_num,
                    'player_id' => $player_id,
                ]);
                $this->endEffect("activeplayerEffect");
                return;
                break;
            case 57: // When Jeffrey comes into play, search your discard pile for 3 cards and put them in your hand.
                $this->endEffect("activeplayerEffect");
                // TODO: implement this in activeplayerEffect state
                return;
                break;
            case 63: // When Jude is placed, the productivity player in the same position on opponent's side must leave the field to discard pile.
                // TODO: implement this
                break;
            case 64: // When Ceci is placed, discard 1 card from your hand.
                $this->endEffect("activeplayerEffect");
                return;
                break;
            case 105: // Your opponent skips 1 round.
                $this->addStatus2StatusLst($player_id, True, 105);
                break;
            case 108: // Return 1 card from the field to your hand.
                $this->endEffect("activeplayerEffect");
                return;
                break;
            case 109: // When Harry is placed, discard 2 cards from your hand.
                $this->endEffect("activeplayerEffect");
                return;
                break;
            case 112:
                $this->endEffect("activeplayerEffect");
                return;
                break;
            default:
                break;
        }
        $sql = "SELECT player_status FROM player WHERE player_id = $player_id";
        $player_status = json_decode(self::getUniqueValueFromDB( $sql ));
        if (in_array(3, $player_status)) {
            $this->removeStatusFromStatusLst($player_id, 3);
            $this->gamestate->nextState( "cardEffect" );
        } else {
            $this->endEffect("normal");
        }
    }

    function stCardActiveEffect() {
        // ANCHOR stCardActiveEffect
        // determine what card active effect should be done / finished
        $sql = "SELECT * FROM playing_card WHERE disabled = FALSE";
        $card_active_effect_info = self::getNonEmptyObjectFromDB( $sql );
        $player_id = $card_active_effect_info['player_id'];
        $player_deck = $this->getActivePlayerDeck($player_id); // this is the deck of the player who plays the card
        switch ( $card_active_effect_info['card_type_arg'] ) {
            case 8:
                $all_draw_deck = $player_deck->getCardsInLocation('deck');
                $top_five_cards = array_slice($all_draw_deck, 0, 5);
                $top_five_cards_json = json_encode($top_five_cards);
                $sql = "UPDATE playing_card SET card_info = '{$top_five_cards_json}' WHERE disabled = FALSE";
                self::DbQuery( $sql );
                self::notifyPlayer($player_id, 'showCardsOnTempStock', clienttranslate( 'You draw 5 cards and you need to rearrange them. (put on the top or the bottom)' ), [
                    'cards' => $top_five_cards,
                    'player_id' => $player_id,
                    'card_type_arg' => 8,
                ]);
                break;
            case 57:
                $all_discard_cards = $player_deck->getCardsInLocation('discard');
                self::notifyPlayer($player_id, 'showCardsOnTempStock', clienttranslate( 'You picks 3 cards from discard pile' ), [
                    'cards' => $all_discard_cards,
                    'player_id' => $player_id,
                ]);
                break;
            default:
                break;
        }
    }

    function stEndHand() {
        // ANCHOR stEndHand 
        // End the game and do some scoring here

        // ... code the function
        $this->gamestate->nextState( "endGame" );
    }

    function stChangeActivePlayer() {
        // ANCHOR stChangeActivePlayer
        $sql = "SELECT * from playing_card WHERE disabled = FALSE";
        $playing_card_info = self::getNonEmptyObjectFromDB( $sql );
        if ( $playing_card_info['card_status'] == "validating" ) {
            $this->activeNextPlayer();
            $this->gamestate->nextState( "counterattack" );
        } else if ( $playing_card_info['card_status'] == "validated" ) {
            $this->gamestate->changeActivePlayer( $playing_card_info['player_id'] );
            if ( $playing_card_info['card_launch'] ) {
                $this->gamestate->nextState( "cardEffect" );
            } else {
                $sql = "UPDATE playing_card SET disabled = TRUE WHERE disabled = FALSE";
                $this->gamestate->nextState( "playerTurn" );
            }
        }

    }

    function stPlayerEndTurn() {
        // ANCHOR stPlayerEndTurn
        // reset the player to limit
        $player_id = self::getActivePlayerId();
        $sql = "UPDATE player SET player_productivity = player_productivity_limit, player_action = player_action_limit WHERE player_id = $player_id";
        self::DbQuery( $sql );
        $this->updatePlayerBoard($player_id);
        // handle player status
        $sql = "SELECT player_status FROM player WHERE player_id = $player_id";
        $player = self::getNonEmptyObjectFromDB( $sql );
        $player_status = json_decode($player['player_status']);
        foreach ($player_status as $status) {
            switch ($status) {
                case 54: // deduct 2 power
                    $sql = "UPDATE player SET player_power = player_power - 2 WHERE player_id = $player_id";
                    self::DbQuery( $sql );
                    $this->updatePlayerBoard($player_id);
                    $this->removeStatusFromStatusLst($player_id, 54);
                    break;
                case 3: // remove the status, double effect is only for one round
                    $this->removeStatusFromStatusLst($player_id, 3);
                    break;
                default:
                    break;
            }
        }
        $this->activeNextPlayer();
        $this->gamestate->nextState( "cardDrawing" );
    }

    public function stShoot() {
        // ANCHOR - stShoot
        $player_id = self::getActivePlayerId();
        // checking the user has enough power to shoot
        $sql = "SELECT player_power FROM player WHERE player_id = $player_id";
        $player_power = self::getUniqueValueFromDB( $sql );
        if ($player_power < 10) {
            throw new BgaUserException( self::_("You do not have enough power to shoot") );
        }
        // roll the dice, get the two dice number
        $diceOne = mt_rand(1, 6);
        $diceTwo = mt_rand(1, 6);
        $diceTotal = $diceOne + $diceTwo;
        // get the player's shooting_number
        $sql = "SELECT shooting_number FROM player WHERE player_id = $player_id";
        $shooting_number_text = self::getUniqueValueFromDB( $sql );
        $shooting_number = json_decode($shooting_number_text);
        // check whether the diceTotal is in the shooting_number
        if (in_array($diceTotal, $shooting_number)) {
            // the player has shot the goal
            $sql = "UPDATE player SET player_score = player_score + 1 WHERE player_id = $player_id";
            self::DbQuery( $sql );
            if (in_array(100, $shooting_number)) {
                $sql = "UPDATE player SET shooting_number = '{$this->cat_original_shooting_numbers}' WHERE player_id = $player_id";
                self::DbQuery( $sql );
            } else if (in_array(101, $shooting_number)) {
                $sql = "UPDATE player SET shooting_number = '{$this->squirrel_original_shooting_numbers}' WHERE player_id = $player_id";
                self::DbQuery( $sql );
            }
            // Refresh the player board by using lastest data (Fetch the data from database again this time) 
            $sql = "select player_score, player_action, player_productivity, player_team, player_power from player where player_id = $player_id";
            $player = self::getNonEmptyObjectFromDB( $sql );
            self::notifyAllPlayers( "updatePlayerBoard", "", array(
                'player_id' => $player_id,
                'player_productivity' => $player['player_productivity'],
                'player_action' => $player['player_action'],
                'player_score' => $player['player_score'],
                'player_power' => $player['player_power'],
            ) );
            self::notifyAllPlayers( "shoot_roll", clienttranslate( '${player_name} shoots the goal by hitting number ${diceTotal}' ), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'diceOne' => $diceOne,
                'diceTwo' => $diceTwo,
                'diceTotal' => $diceTotal,
            ) );

            // change the game state to playerEndTurn
            $this->gamestate->nextState( "throwCard" );
        } else {
            // add one more shooting number to the shooting_number
            $new_shooting_number = $this->addShootingNumbersFromSmallestNumber($shooting_number);
            $sql = "UPDATE player SET shooting_number = '{$new_shooting_number}' WHERE player_id = $player_id";
            self::DbQuery( $sql );
            self::notifyAllPlayers( "shoot_roll", clienttranslate( '${player_name} misses the goal by hitting number ${diceTotal}' ), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'diceOne' => $diceOne,
                'diceTwo' => $diceTwo,
                'diceTotal' => $diceTotal,
            ) );
            // change the game state to playerEndTurn
            $this->gamestate->nextState( "throwCard" );
        }
        
    }
}