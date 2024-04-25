<?php
trait AniversusPlayerActions {
    //////////// Player actions
    //////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in aniversus.action.php)
    */
    public function playFunctionCard( $player_id, $card_id, $card_type ) {
        //ANCHOR - playFunctionCard
        // check that this is player's turn and that it is a "possible action" at this game state
        self::checkAction( 'playFunctionCard' );
        // get the active player id
        $ActivePlayer = self::getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
        if ($player_id != $ActivePlayer) {
            throw new BgaUserException( self::_("You are not the active player") );
        }
        $card_info = $this->getCardinfoFromCardsInfo($card_type);
        $card_cost = $card_info['cost'];
        $card_team = $card_info['team'];
        $comsumed_action = 1;
        // check whether the user have this card in hand
        $card_deck_info = $player_deck->getCard($card_id);
        if ( $card_deck_info['location'] != 'hand' ) {
            throw new BgaUserException( self::_("This card is not in your hand") );
        }
        // get the active player energy and action number
        $sql = "select player_score, player_action, player_productivity, player_team from player where player_id = $player_id";
        $player = self::getNonEmptyObjectFromDB( $sql );
        // check whether the card belongs to the player's team
        if ($player['player_team'] != $card_team && $card_team != "basic") {
            throw new BgaUserException( self::_("This card does not belong to your team") );
        }
        // special handling for some cards
        switch ($card_type) {
            case 2:
                $sql = "UPDATE player SET player_productivity = player_productivity + $card_cost WHERE player_id = $player_id";
                $comsumed_action = 0;
                break;
            case 4:
                $sql = "SELECT * FROM playing_card WHERE player_id = $player_id";
                $playing_card_info = self::getNonEmptyObjectFromDB( $sql );
                if ($playing_card_info['card_info'] == 'redcard') {
                    $comsumed_action = 0;
                }
                $opponent_deck = $this->getNonActivePlayerDeck($player_id);
                $opponent_players = $opponent_deck->getCardsInLocation('playmat');
                $opponent_forward_players = array_filter($opponent_players, function($player) {
                    return $player['location_arg'] <= 5;
                });
                if ( count($opponent_forward_players) <= 0 ) {
                    throw new BgaUserException( self::_("Your opponent does not have any forward player in playmat") );
                }
                break;
            case 5:
                // handle the id:5 card (counter attack card)
                throw new BgaUserException( self::_("This card is a counterattack card, it just can be used for reaction of opponent's function card. Please select other cards to play.") );
                break;
            case 6:
                // this card need the opponent have at least one card in hand
                $opponent_deck = $this->getNonActivePlayerDeck($player_id);
                if ($opponent_deck->countCardInLocation('hand') == 0) {
                    throw new BgaUserException( self::_("Your opponent does not have any card in hand") );
                }
            case 8:
                // need the player have at least 5 card in deck
                if ($player_deck->countCardInLocation('deck') < 5) {
                    throw new BgaUserException( self::_("You do not have enough cards in deck to play this card") );
                }
            case 9:
                // handle the id: 9 card, it is a unplayable card
                throw new BgaUserException( self::_("This card is unplayable. Please select other cards to play.") );
                break;
            case 11:
                if ($player_deck->countCardInLocation('playmat') == 0) {
                    throw new BgaUserException( self::_("You do not have any player in playmat") );
                }
            case 12:
                // TODO : more checking
                if ($player_deck->countCardInLocation('playmat') == 0) {
                    throw new BgaUserException( self::_("You do not have any player in playmat") );
                }
            case 53:
                if ($player_deck->countCardInLocation('hand') > 3 ) {
                    throw new BgaUserException( self::_("You can not have more than 3 cards in hand") );
                }
                break;
            case 108:
                if ($player_deck->countCardInLocation('playmat') == 0) {
                    throw new BgaUserException( self::_("You do not have any player in playmat") );
                }
                break;
            default:
                break;
        }
        // check whether the user have enough action and productivity to play this card
        if ($player['player_action'] - $comsumed_action < 0) {
            throw new BgaUserException( self::_("You do not have enough action to play this card") );
        }
        if ($player['player_productivity'] < $card_cost) {
            throw new BgaUserException( self::_("You do not have enough productivity to play this card") );
        }
        // play the card
        $sql = "UPDATE player SET player_action = player_action - $comsumed_action, player_productivity = player_productivity - $card_cost WHERE player_id = $player_id";
        self::DbQuery( $sql );
        // move the card from hand to discard Pile because this is function card
        $this->playCard2Discard($player_id, $card_id);
        // Notify all players about the card played
        self::notifyAllPlayers( "playFunctionCard", clienttranslate( '${player_name} plays ${card_name} : ${card_effect}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_info['name'],
            'card_id' => $card_id,
            'card_type' => $card_type,
            'card_effect' => $card_info['function'],
        ) );
        // Refresh the player board by using lastest data (Fetch the data from database again this time) 
        $this->updatePlayerBoard($player_id);
        // update database that what card the active player has played
        // REVIEW - debug : checkPlayingCard
        $this->checkPlayingCard();
        // playing_card updating
        $sql = "UPDATE playing_card SET "
        . "card_id = " . intval($card_id) . ", "
        . "card_type = '" . addslashes($card_info['type']) . "', "
        . "card_type_arg = " . intval($card_info['id']) . ", "
        . "card_launch = TRUE, "
        . "card_status = 'validating', "
        . "disabled = FALSE "
        . "WHERE player_id = " . intval($player_id);
        self::DbQuery( $sql );
        // check whether the opponent has counter attack card in hand
        $opponent_deck = $this->getNonActivePlayerDeck($player_id);
        if (empty($opponent_deck->getCardsOfTypeInLocation( 'Function' , 5 , 'hand' ))) {
            // Go to launch state, the card effect would be done
            $this->gamestate->nextState( "launch" );
        } else {
            // Go to counterAttack state
            $this->gamestate->nextState( "changeActivePlayer_counterattack" );
        }
    }

    public function playPlayerCard($player_id, $card_id, $card_type, $row, $col) {
        // ANCHOR - playPlayerCard
        self::checkAction( 'playPlayerCard' );
        // get the active player id
        $ActivePlayer = self::getActivePlayerId();
        if ($player_id != $ActivePlayer) { throw new BgaUserException( self::_("You are not the active player") ); }
        $card_info = $this->getCardinfoFromCardsInfo($card_type);
        $card_cost = $card_info['cost'];
        $card_team = $card_info['team'];
        $card_category = $card_info['type'];
        $comsumed_action = 1;
        $player_deck = $this->getActivePlayerDeck($player_id);
        $actplayer_playmat = $player_deck->getCardsInLocation('playmat');
        $db_position = $this->encodePlayerLocation($row, $col);
        // check whether the user have this card in hand
        $card_deck_info = $player_deck->getCard($card_id);
        if ( $card_deck_info['location'] != 'hand' ) { throw new BgaUserException( self::_("This card is not in your hand") ); }
        // some special handling for some cards
        switch ($card_type) {
            case 64:
                if ($player_deck->countCardInLocation('hand') <= 1 ) {
                    throw new BgaUserException( self::_("You do not have enough cards in hand to play this card") );
                }
                break;
            case 102:
                if ($player_deck->countCardInLocation('training') == 0) {
                    throw new BgaUserException( self::_("You do not have any training card in playmat") );
                }
                break;
            case 104:
                $count_myself_forward_players = 0;
                foreach ( $actplayer_playmat as $myselfcard ) {
                    if ( $myselfcard['location_arg'] <= 5 ) {
                        $count_opponent_forward_players++;
                    }
                }
                if ($count_myself_forward_players > 2) {
                    throw new BgaUserException( self::_("You can only have 2 other players in your forward row when Timo is in play") );
                }
                break;
            case 106:
                $count_myself_forward_players = 0;
                foreach ( $actplayer_playmat as $myselfcard ) {
                    if ( $myselfcard['location_arg'] <= 5 ) {
                        $count_myself_forward_players++;
                    }
                }
                if ($count_myself_forward_players < 3) {
                    throw new BgaUserException( self::_("Leo can only be placed if you have 3 or more forward players on the field") );
                }
                break;
            default:
                break;
        }
        // get the active player energy and action number
        $sql = "select player_score, player_action, player_productivity, player_team from player where player_id = $player_id";
        $player = self::getNonEmptyObjectFromDB( $sql );
        if ($player['player_team'] != $card_team && $card_team != "basic") {
            throw new BgaUserException( self::_("This card does not belong to your team") );
        }
        if ($player['player_action'] <= 0) {
            throw new BgaUserException( self::_("You do not have enough action to play this card") );
        }

        // check whether is it empowerment card and the position is occupied by other player
        if ($card_category == 'Player') {
            foreach($actplayer_playmat as $card) {
                if ($card['location_arg'] == $db_position) {
                    $occupied_name = $this->getCardinfoFromCardsInfo($card['type_arg'])['name'];
                    if ($card_type == 60) {
                        $brosNumber = $player_deck->countCardInLocation( 'playmat', $db_position);
                        if ($brosNumber >= 3) {
                            throw new BgaUserException( self::_("This position only allows maximum 3 Marco bros here.") );
                        }
                    } else {
                        throw new BgaUserException( self::_("This position is occupied by {$occupied_name}, you can not place another player here") );
                    }
                }
            }
        } else if ($card_category == 'Training') {
            $allCardsInThisPosition = $this->find_elements_by_key_value($actplayer_playmat, 'location_arg', $db_position);
            if ( count($allCardsInThisPosition) == 0 ) {
                throw new BgaUserException( self::_("This position haven't any player here, the training card can't not be played.") );
            }
        }
        
        // play the card
        // minus the action and productivity of player in this round
        if ($row == "1") {
            if ($player['player_productivity'] < $card_cost) {
                throw new BgaUserException( self::_("You do not have enough productivity to play this card") );
            }
            $card_power = $card_info['power'];
            if ( $card_type == 101 ) { $card_power++; }
            $sql = "
            UPDATE player
            SET player_action = player_action - $comsumed_action, 
            player_productivity = player_productivity - $card_cost,
            player_power = player_power + $card_power
            WHERE player_id = $player_id
            ";
            self::DbQuery( $sql );
        } else if ( $row == "2" ) {
            $card_productivity = $card_info['productivity'];
            if ( $card_type == 101 ) { $card_productivity++; }
            $sql = "
            UPDATE player
            SET player_action = player_action - $comsumed_action, 
            player_productivity = player_productivity + $card_productivity, 
            player_productivity_limit = player_productivity_limit + $card_productivity
            WHERE player_id = $player_id
            ";
            self::DbQuery( $sql );
        }
        // move the card from hand to playmat
        $player_deck->moveCard($card_id, 'playmat', $db_position);
        $allPlayerInThisPosition = $this->find_elements_by_key_value($player_deck->getCardsInLocation('playmat'), 'location_arg', $db_position);
        self::notifyAllPlayers( "playPlayerCard", clienttranslate( '${player_name} plays ${card_name} : ${card_effect}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_info['name'],
            'card_id' => $card_id,
            'card_type' => $card_type,
            'card_effect' => $card_info['function'],
            'row' => $row,
            'col' => $col,
            'allPlayerInThisPosition' => json_encode($this->getAllUniqueTypeArgsInCardLst($allPlayerInThisPosition)),
        ) );
        // update database that what card the active player has played
        // REVIEW - debug : checkPlayingCard
        $this->checkPlayingCard();
        // playing_card updating
        $sql = "UPDATE playing_card SET "
        . "card_id = " . intval($card_id) . ", "
        . "card_type = '" . addslashes($card_info['type']) . "', "
        . "card_type_arg = " . intval($card_info['id']) . ", "
        . "card_launch = TRUE, "
        . "card_status = 'validating', "
        . "disabled = FALSE "
        . "WHERE player_id = " . intval($player_id);
        self::DbQuery( $sql );
        // calculate special case
        // Refresh the player board by using lastest data (Fetch the data from database again this time) 
        $this->updatePlayerBoard($player_id);
        // determine whether need to enter the cardEffect state to do the card effect, if not, just go to playerTurn state and let the player to player next card
        switch ($card_type) {
            case 57:
                $this->gamestate->nextState( "launch" );
                break;
            case 63:
                $this->gamestate->nextState( "launch" );
                break;
            case 64:
                $this->gamestate->nextState( "launch" );
                break;
            case 109:
                $this->gamestate->nextState( "launch" );
                break;
            default:
                // if no special case, update the playing_card disabled to TRUE
                $sql = "UPDATE playing_card SET disabled = TRUE WHERE player_id = $player_id";
                self::DbQuery( $sql );
        }
    }

    public function throwCards( $player_id, $card_ids ) {
        // ANCHOR - throwCards
        // Ensure that the $card_ids is actually an array
        if (!is_array($card_ids)) {
            throw new BgaUserException("Invalid card IDs.");
        }
        // checking the action
        self::checkAction( 'throwCards' );
        $player_id = self::getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
        $player_name = self::getActivePlayerName();
        foreach ($card_ids as $card_id) {
            $this->playCard2Discard($player_id, $card_id, 'hand');
            $card = $player_deck->getCard($card_id);
            $card_name = self::getCardinfoFromCardsInfo($card['type_arg'])['name'];
            self::notifyAllPlayers( "playFunctionCard", clienttranslate( '${player_name} throws ${card_name}' ), array(
                'player_id' => $player_id,
                'player_name' => $player_name,
                'card_name' => $card_name,
                'card_id' => $card_id,
                'card_type' => $card['type_arg'],
            ) );
        }
        $state_name = $this->getStateName();
        switch ($state_name) {
            case 'throwCard':
                $this->gamestate->nextState( "playerEndTurn" );
                break;
            default:
                break;
        }
    }
    public function redcard_redcard() {
        // ANCHOR - redcard_redcard
        self::checkAction( 'redcard_redcard' );
        $player_id = self::getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
        $red_cards = $player_deck->getCardsOfTypeInLocation( 'Function' , 4 , 'hand' );
        $redcard = array_shift($red_cards);
        $sql = "UPDATE playing_card SET card_info = 'redcard' WHERE player_id = $player_id";
        self::DbQuery( $sql );
        $this->playFunctionCard($player_id, $redcard['id'], $redcard['type_arg']);
    }

    public function intercept_counterattack() {
        // ANCHOR - intercept_counterattack
        self::checkAction( 'intercept_counterattack' );
        $player_id = self::getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
        $intercept_cards = $player_deck->getCardsOfTypeInLocation( 'Function' , 5 , 'hand' );
        if (empty($intercept_cards)) {
            throw new BgaUserException( self::_("You do not have any intercept card in hand") );
        }
        $intercept_card = array_shift($intercept_cards);
        $this->playCard2Discard($player_id, $intercept_card['id'], 'hand');
        self::notifyAllPlayers( "playFunctionCard", clienttranslate( '${player_name} intercepts the card' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_id' => $intercept_card['id'],
            'card_type' => $intercept_card['type_arg'],
        ) );
        // UPDATE the player_card database
        // check whether the opponent has counter attack card in hand
        $opponent_deck = $this->getNonActivePlayerDeck($player_id);
        if (empty($opponent_deck->getCardsOfTypeInLocation( 'Function' , 5 , 'hand' ))) {
            // Opponent does not have a counter attack card in hand
            // UPDATE the playing_card database to reverse card_launch and set card_status to "validated"
            $sql = "UPDATE `playing_card` SET `card_launch` = NOT `card_launch`, `card_status` = 'validated'
            WHERE `disabled` = FALSE";
            self::DbQuery($sql);
            $this->gamestate->nextState( "changeActivePlayer_counterattack" );
        } else {
            // Opponent has a counter attack card in hand
            // UPDATE the playing_card database just to reverse card_launch
            $sql = "UPDATE `playing_card` SET `card_launch` = NOT `card_launch` WHERE `disabled` = FALSE";
            self::DbQuery($sql);
            // Go to counterAttack state
            $this->gamestate->nextState("changeActivePlayer_counterattack");
        }
    }
    public function pass_redcard() {
        // ANCHOR - pass_redcard
        self::checkAction( 'pass_redcard' );
        $this->checkPlayingCard();
        $player_id = self::getActivePlayerId();
        $sql  = "UPDATE playing_card SET card_info = 'passredcard' WHERE player_id = $player_id";
        self::DbQuery( $sql );
        $this->gamestate->nextState( "changeActivePlayer_redcard" );
    }
    public function pass_counterattack() {
        // ANCHOR - pass_counterattack
        self::checkAction( 'pass_counterattack' );
        $sql = "UPDATE `playing_card` SET `card_status` = 'validated' WHERE `disabled` = FALSE";
        self::DbQuery($sql);
        $this->gamestate->nextState( "changeActivePlayer_counterattack" );
    }
    public function pass_CardActiveEffect() {
        // ANCHOR - pass_CardActiveEffect
        self::checkAction( 'pass_CardActiveEffect' );
        $this->checkPlayingCard();
        self::notifyPlayer( self::getActivePlayerId(), "terminateTempStock", "", array() );
        $this->gamestate->nextState( "playerTurn" );
    }
    
    public function pass_playerTurn() {
        // ANCHOR - pass_playerTurn
        self::checkAction( 'pass_playerTurn' );
        $this->gamestate->nextState( "throwCard" );
    }

    public function shoot_playerTurn() {
        // ANCHOR - shoot_playerTurn
        self::checkAction( 'shoot_playerTurn' );
        $player_id = self::getActivePlayerId();
        $sql = "SELECT player_power, player_productivity FROM player WHERE player_id = $player_id";
        $player = self::getNonEmptyObjectFromDB( $sql );
        $player_power = $player['player_power'];
        $player_productivity = $player['player_productivity'];
        if ($player_power < 10) {
            throw new BgaUserException( self::_("You do not have enough power to shoot (Please ensure you have at least 10 power for shooting.") );
        }
        if ($player_productivity < 2) {
            throw new BgaUserException( self::_("You do not have enough energy to shoot (Please ensure you have at least 2 energy for shooting.") );
        }
        // for add shooting infomation to the database
        $sql = "UPDATE playing_card SET card_info = 'preredcard' WHERE player_id = $player_id";
        self::DbQuery( $sql );
        $this->gamestate->nextState( "changeActivePlayer_redcard" );
    }

    public function throwCard_throwCard( $player_id, $card_ids ) {
        // ANCHOR - throwCard_throwCard
        self::checkAction( 'throwCard_throwCard' );
        $player_id = self::getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
        $player_handCardNumber = $player_deck->countCardInLocation('hand', $player_id);
        $throwNumber = $player_handCardNumber - 5;
        // if card_ids length is not equal to throwNumber, throw an exception
        if (count($card_ids) != $throwNumber) {
            throw new BgaUserException( self::_("Please ensure that you discard exactly {$throwNumber} card(s) from your hand; selecting more or fewer cards than required is not permitted.") );
        }
        $this->throwCards($player_id, $card_ids);
    }

    public function pass_throwCard() {
        // ANCHOR - pass_throwCard
        self::checkAction( 'pass_throwCard' );
        $this->gamestate->nextState( "playerEndTurn" );
    }

    public function throwCard_CardActiveEffect( $player_id, $card_ids ) {
        // ANCHOR - throwCard_CardActiveEffect
        self::checkAction( 'throwCard_CardActiveEffect' );
        $sql = "SELECT * FROM playing_card WHERE disabled = FALSE";
        $card_effect_info = self::getNonEmptyObjectFromDB( $sql );
        // $card_id = $card_effect_info['card_id'];
        $card_type_arg = $card_effect_info['card_type_arg'];
        $player_deck = $this->getActivePlayerDeck($player_id);
        switch ($card_type_arg) {
            case 1:
                if (count($card_ids) != 1) {
                    throw new BgaUserException( self::_("Please ensure that you discard exactly 1 card from your hand; selecting more or fewer cards than required is not permitted.") );
                }
                $this->throwCards($player_id, $card_ids);
                $this->checkDoubleCard($player_id);
                break;
            case 56:
                if (count($card_ids) != 2) {
                    throw new BgaUserException( self::_("Please ensure that you discard exactly 2 cards from your hand; selecting more or fewer cards than required is not permitted.") );
                }
                $this->throwCards($player_id, $card_ids);
                $this->checkDoubleCard($player_id);
                break;
            case 64:
                if (count($card_ids) != 1) {
                    throw new BgaUserException( self::_("Please ensure that you discard exactly 1 card from your hand; selecting more or fewer cards than required is not permitted.") );
                }
                $this->throwCards($player_id, $card_ids);
                $this->checkDoubleCard($player_id);
                break;
            case 109:
                if (count($card_ids) != 2) {
                    throw new BgaUserException( self::_("Please ensure that you discard exactly 2 cards from your hand; selecting more or fewer cards than required is not permitted.") );
                }
                $this->throwCards($player_id, $card_ids);
                break;
            case 112:
                if (count($card_ids) != 1) {
                    throw new BgaUserException( self::_("Please ensure that you discard exactly 1 card from your hand; selecting more or fewer cards than required is not permitted.") );
                }
                $this->throwCards($player_id, $card_ids);
                $allCardsInDrawDeck = $player_deck->getCardsInLocation('deck');
                self::notifyAllPlayers( "showCardsOnTempStock", "", array(
                    'cards' => $allCardsInDrawDeck,
                    'card_type_arg' => 112,
                ) );
                break;
            default:
                $card_effect = "You can play a card from your hand to the playmat";
        }
    }

    public function eightEffect_CardActiveEffect( $top_items, $bottom_items) {
        // ANCHOR - eightEffect_CardActiveEffect
        self::checkAction( 'eightEffect_CardActiveEffect' );
        $sql = "SELECT * FROM playing_card WHERE disabled = FALSE";
        $playing_card_info = self::getNonEmptyObjectFromDB( $sql );
        $card_type_arg = $playing_card_info['card_type_arg'];
        if ( $card_type_arg != 8 ) {
            throw new BgaUserException( self::_("This card does not have an effect that can be activated") );
        } else {
            $random_pick_cards = json_decode($playing_card_info['card_info'], true);
            foreach ($top_items as $top_item) {
                // Filter $random_pick_cards to find all items with the matching type_arg
                $matches = array_filter($random_pick_cards, function($card) use ($top_item) {
                    return $card['type_arg'] == $top_item['type'];
                });
                
                // If no matching cards, throw an exception
                if (empty($matches)) {
                    throw new BgaUserException(self::_("Invalid top cards selected Error: playeraction Line at 407"));
                }
                
                // Randomly pick one of the matching cards
                $keys = array_keys($matches);
                $random_key = $keys[array_rand($keys)];
                $selected_card = $matches[$random_key];
                
                // Perform your operation with $selected_card here
                $player_deck = $this->getActivePlayerDeck($playing_card_info['player_id']);
                $player_deck->insertCardOnExtremePosition($selected_card['id'], 'deck', true);
                // Now remove the selected item from $random_pick_cards
                unset($random_pick_cards[$random_key]);
            }

            for ($i = 0; $i < count($bottom_items); $i++) {
                // Filter $random_pick_cards to find all items with the matching type_arg
                $matches = array_filter($random_pick_cards, function($card) use ($bottom_items, $i) {
                    return $card['type_arg'] == $bottom_items[$i]['type'];
                });
                
                // If no matching cards, throw an exception
                if (empty($matches)) {
                    throw new BgaUserException(self::_("Invalid bottom cards selected"));
                }
                
                // Randomly pick one of the matching cards
                $keys = array_keys($matches);
                $random_key = $keys[array_rand($keys)];
                $selected_card = $matches[$random_key];
                
                // Perform your operation with $selected_card here
                $player_deck = $this->getActivePlayerDeck($playing_card_info['player_id']);
                $player_deck->insertCardOnExtremePosition($selected_card['id'], 'deck', false);
                // Now remove the selected item from $random_pick_cards
                unset($random_pick_cards[$random_key]);
            }
            // notify player
            self::notifyPlayer( $playing_card_info['player_id'], "terminateTempStock", "", array(
                'player_id' => $playing_card_info['player_id'],
            ) );
            $this->checkDoubleCard($player_id);
        }
    }

    public function swapField_CardActiveEffect( $row, $col ) {
        // ANCHOR - swapField_CardActiveEffect card_id : 11
        self::checkAction( 'swapField_CardActiveEffect' );
        // stage : field
        $player_id = self::getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
        $db_position = $this->encodePlayerLocation($row, $col);
        $player_card = $player_deck->getCardInLocation('playmat', $db_position);
        if (empty($player_card)) {
            throw new BgaUserException( self::_("There is no player in this position") );
        } else {
            foreach ($player_card as $card) {
                $this->playCard2Discard($player_id, $card['id'], 'playmat');
                self::notifyAllPlayers( "movePlayerInPlaymat2Discard", clienttranslate( '${player_name} swaps the player' ), array(
                    'player_id' => $player_id,
                    'player_name' => self::getActivePlayerName(),
                    'card_id' => $card['id'],
                    'card_type' => $card['type_arg'],
                    'row' => $row,
                    'col' => $col,
                ) );
            }
        }
        // update the playing_card status
        $sql = "UPDATE playing_card SET card_info = 'discard' WHERE disabled = FALSE";
        self::DbQuery( $sql );
        // notify player
        // stage : discard pile
        $all_discard_cards = $player_deck->getCardsInLocation('discard');
        $onlyPlayercard = array_filter($all_discard_cards, function($card) {
            return $card['type'] == 'Player';
        });
        self::notifyPlayer( $player_id, "showCardsOnTempStock", "", array(
            'cards' => $onlyPlayercard,
            'card_type_arg' => 11,
        ) );
    }

    public function redCardAfterShoot_CardActiveEffect( $row, $col ) {
        // ANCHOR - swapField_CardActiveEffect card_id : 11
        self::checkAction( 'redCardAfterShoot_CardActiveEffect' );
        // stage : field
        $player_id = self::getActivePlayerId();

        $player_deck = $this->getActivePlayerDeck($player_id);
        $db_position = $this->encodePlayerLocation($row, $col);
        $player_card = $player_deck->getCardInLocation('playmat', $db_position);
        if (empty($player_card)) {
            throw new BgaUserException( self::_("There is no player in this position") );
        } else {
            $training_cards = $player_deck->getCardsOfTypeInLocation( 'Training' , null, 'playmat', $db_position);
            if (empty($training_cards)) {
                $card = array_shift($player_card);
                $this->playCard2Discard($player_id, $card['id'], 'playmat');
                self::notifyAllPlayers( "movePlayerInPlaymat2Discard", clienttranslate( '${player_name} swaps the player' ), array(
                    'player_id' => $player_id,
                    'player_name' => self::getActivePlayerName(),
                    'card_id' => $card['id'],
                    'card_type' => $card['type_arg'],
                    'row' => $row,
                    'col' => $col,
                ) );
            } else {
                $training_card = array_shift($training_cards);
                $this->playCard2Discard($player_id, $training_card['id'], 'playmat');
                self::notifyAllPlayers( "movePlayerInPlaymat2Discard", clienttranslate( '${player_name} swaps the player' ), array(
                    'player_id' => $player_id,
                    'player_name' => self::getActivePlayerName(),
                    'card_id' => $training_card['id'],
                    'card_type' => $training_card['type_arg'],
                    'row' => $row,
                    'col' => $col,
                ) );
                
            }
        }
        // get the playing_card info
        $sql = "SELECT * FROM playing_card WHERE disabled = FALSE";
        $playing_card_info = self::getNonEmptyObjectFromDB( $sql );
        $effect_times = intval($playing_card_info['card_info']);
        $opponent_id = self::getNonActivePlayerId();
        if ( $effect_times <= 0) {
            throw new BgaUserException( self::_("This card does not have an effect that can be activated") );
        } else if ( $effect_times == 2 ) {
            $sql = "UPDATE playing_card SET card_info = '1' WHERE player_id = $player_id";
            self::DbQuery( $sql );
        } else if ( $effect_times == 1 ) {
            $this->updatePlayerBoard($opponent_id);
        }

        $this->updatePlayerBoard($opponent_id);
        $this->gamestate->nextState( "changeActivePlayer_redcard" );
    }

    public function pickPlayerFromPlaymat2Hand_CardActiveEffect( $row, $col ) {
        // ANCHOR - pickPlayerFromPlaymat2Hand_CardActiveEffect card_id : 108
        self::checkAction( 'pickPlayerFromPlaymat2Hand_CardActiveEffect' );
        $player_id = self::getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
        $db_position = $this->encodePlayerLocation($row, $col);
        $player_card = $player_deck->getCardInLocation('playmat', $db_position);
        if (empty($player_card)) {
            throw new BgaUserException( self::_("There is no player in this position") );
        } else {
            foreach ($player_card as $card) {
                $player_deck->moveCard($card['id'], 'hand', $player_id);
                self::notifyAllPlayers( "movePlayerInPlaymat2Hand", clienttranslate( '${player_name} picks the ${card_name} from the field to hand.' ), array(
                    'player_id' => $player_id,
                    'player_name' => self::getActivePlayerName(),
                    'card_id' => $card['id'],
                    'card_name' => self::getCardinfoFromCardsInfo($card['type_arg'])['name'],
                    'card_type' => $card['type_arg'],
                    'row' => $row,
                    'col' => $col,
                ) );
            }
        }
        $this->checkDoubleCard($player_id);
    }

    public function pickPlayerFromDiscardPile_CardActiveEffect( $selected_player ) {
        // ANCHOR - pickPlayerFromDiscardPile_CardActiveEffect
        self::checkAction( 'pickPlayerFromDiscardPile_CardActiveEffect' );
        $player_id = self::getActivePlayerId();
        // check whether the selected player is in the discard pile
        $player_deck = $this->getActivePlayerDeck($player_id);
        $all_discard_cards = $player_deck->getCardsInLocation('discard');
        $selected_player_card = array_filter($all_discard_cards, function($card) use ($selected_player) {
            return $card['type_arg'] == $selected_player;
        });
        if (empty($selected_player_card)) {
            throw new BgaUserException( self::_("The selected player is not in the discard pile") );
        }
        $selected_player_card = array_shift($selected_player_card);
        $player_deck->moveCard($selected_player_card['id'], 'hand', $player_id);
        $selected_player_card_name = self::getCardinfoFromCardsInfo($selected_player_card['type_arg'])['name'];
        self::notifyPlayer( $player_id, "cardDrawn", 'You get the player ${card_name} from your discard pile.', array(
            'cards' => array($selected_player_card),
            'card_name' => $selected_player_card_name,
        ) );
        // end the effect
        self::notifyPlayer( $player_id, "terminateTempStock", "", array() );
        $this->checkDoubleCard($player_id);
    }

    public function pickPlayerFromDrawDeck_CardActiveEffect( $selected_player ) {
        // ANCHOR - pickPlayerFromDrawDeck_CardActiveEffect
        self::checkAction( 'pickPlayerFromDrawDeck_CardActiveEffect' );
        $player_id = self::getActivePlayerId();
        // check whether the selected player is in the discard pile
        $player_deck = $this->getActivePlayerDeck($player_id);
        $all_discard_cards = $player_deck->getCardsInLocation('deck');
        $selected_player_card = array_filter($all_discard_cards, function($card) use ($selected_player) {
            return $card['type_arg'] == $selected_player;
        });
        if (empty($selected_player_card)) {
            throw new BgaUserException( self::_("The selected player is not in your draw deck.") );
        }
        $selected_player_card = array_shift($selected_player_card);
        $player_deck->moveCard($selected_player_card['id'], 'hand', $player_id);
        $selected_player_card_name = self::getCardinfoFromCardsInfo($selected_player_card['type_arg'])['name'];
        self::notifyPlayer( $player_id, "cardDrawn", 'You get the player ${card_name} from your draw deck.', array(
            'cards' => array($selected_player_card),
            'card_name' => $selected_player_card_name,
        ) );
        // end the effect
        self::notifyPlayer( $player_id, "terminateTempStock", "", array() );
        $this->checkDoubleCard($player_id);
    }

    public function redCard_CardActiveEffect( $row, $col ) {
        // ANCHOR - redCard_CardActiveEffect
        self::checkAction( 'redCard_CardActiveEffect' );
        $player_id = self::getActivePlayerId();
        $opponent_deck = $this->getNonActivePlayerDeck($player_id);
        $db_position = $this->encodePlayerLocation($row, $col);
        $opponent_position_cards = $opponent_deck->getCardInLocation('playmat', $db_position);
        if (empty($player_card)) {
            throw new BgaUserException( self::_("There is no player in this position") );
        } else {
            if ( count(find_elements_by_key_value($opponent_position_cards, "type_arg", 12)) >= 1 ) {
                throw new BgaUserException( self::_("You can not red card the player who has the Resillence effect.") );
            }
            foreach ($opponent_position_cards as $card) {
                $this->playCard2Discard($player_id, $card['id'], 'playmat');
                self::notifyAllPlayers( "movePlayerInPlaymat2Discard", clienttranslate( '${player_name} give the red cards to eject the player' ), array(
                    'player_id' => $player_id,
                    'player_name' => self::getActivePlayerName(),
                    'card_id' => $card['id'],
                    'card_type' => $card['type_arg'],
                    'row' => $row,
                    'col' => $col,
                ) );
            }
        }

        $opponent_id = $this->getNonActivePlayerId($player_id);
        $this->updatePlayerAbility($opponent_id);
        // update the playing_card status
        $sql = "SELECT * FROM playing_card WHERE disabled = FALSE";
        $playing_card_info = self::getNonEmptyObjectFromDB( $sql );
        if ( $playing_card_info['card_info'] == 'redcard' ) {
            $sql = "SELECT * FROM player WHERE player_id = $opponent_id";
            $opponent_playerInfo = self::getNonEmptyObjectFromDB( $sql );
            $this->gamestate->nextState( "changeActivePlayer_redcard" );
        } else {
            $this->checkDoubleCard($player_id);
        }
        
    }
}