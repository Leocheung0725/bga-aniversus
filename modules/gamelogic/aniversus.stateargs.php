<?php
trait AniversusStateArgs {
    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state arguments
    ////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */
    // ANCHOR argCardEffect
    function argCardEffect()
    {
        // it must return the array
        // fetch playing_card table to get the card id and card type by active player id
        $sql = "SELECT * FROM playing_card WHERE disabled = FALSE";
        $playing_card_info = self::getNonEmptyObjectFromDB( $sql );
        return array(
            'card_id' => $playing_card_info['card_id'],
            'card_type' => $playing_card_info['card_type'],
            'card_type_arg' => $playing_card_info['card_type_arg'],
            'player_id' => $playing_card_info['player_id'],
            'card_status' => $playing_card_info['card_status'],
        );
    }
    // ANCHOR argCardActiveEffect
    function argCardActiveEffect()
    {
        // it must return the array
        // fetch playing_card table to get the card id and card type by active player id
        $sql = "SELECT * FROM playing_card WHERE disabled = FALSE";
        $playing_card_info = self::getNonEmptyObjectFromDB( $sql );
        // throw button: 1
        $button_list = array();
        switch ($playing_card_info['card_type_arg']) {
            case 1:
                $message = "must discard a card from the hand";
                $button_list[] = 1;
                break;
            case 4:
                $message = "can eject 1 opponent forward player from the field";
                break;
            case 8:
                $message = "may look at the top 5 cards from the draw deck, then put them back in any order either on top of or at the bottom of the draw deck.";
                if ( $playing_card_info['card_id'] == 4058 )
                {
                    $message = "may look at the top 4 cards from the draw deck, then put them back in any order either on top of or at the bottom of the draw deck.";
                }
                break;
            case 11:
                $message = "select 1 player from the field to exchange with a player from the discard pile";
                break;
            case 56:
                $message = "must discard 2 cards from the hand";
                $button_list[] = 1;
                break;
            case 57:
                $message = "may search 1 card from the discard pile and put them in hand";
                break;
            case 64:
                $message = "must discard 1 card from the hand";
                $button_list[] = 1;
                break;
            case 108:
                $message = "pick 1 player from the field to your hand.";
                break;
            case 109:
                $message = "discard 2 cards from the hand.";
                $button_list[] = 1;
                break;
            case 112:
                $message = "discard 1 hand card to search for 2 cards from the draw deck and put to the hand.";
                $button_list[] = 1;
                break;
            case 401:
                $message = "can eject 2 player or training card from the opponent field";
                break;
            case 402:
                $message = "may dismiss a player from the field";
                break;
            case 40512:
                $message = "may search 2 cards from the draw deck";
                break;
            default:
                $message = "No message set yet for this card type arg";
        }
        $button_list = json_encode($button_list);
        return array(
            'card_id' => $playing_card_info['card_id'],
            'card_type' => $playing_card_info['card_type'],
            'card_type_arg' => $playing_card_info['card_type_arg'],
            'player_id' => $playing_card_info['player_id'],
            'card_status' => $playing_card_info['card_status'],
            'message' => $message,
            'button_list' => $button_list,
        );
    }
    // ANCHOR argThrowCard
    function argThrowCard()
    {
        // it must return the array
        $player_id = self::getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
        $player_handCardNumber = $player_deck->countCardInLocation('hand', $player_id);
        $throwNumber = $player_handCardNumber - 5;
        if ($throwNumber <= 0) {
            $message = "don't have to discard any cards from hand";
        } else {
            $message = "must discard {$throwNumber} card(s) from hand";
        }
        return array(
            "message" => $message,
            "thrownumber" => $throwNumber,  
        );
    }
    // ANCHOR argRedCard
    function argRedCard()
    {
        // it must return the array
        $player_id = self::getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
        // check whether the opponent player have red card in hand or not (card id: 4 )
        $red_card = $player_deck->getCardsOfTypeInLocation( 'Function' , 4 , 'hand' );
        $redCardNumber = count($red_card);
        return array(
            "redcardnumber" => $redCardNumber,
        );
    }
    // ANCHOR argSkill
    function argSkill()
    {
        // it must return the array
        $player_id = self::getActivePlayerId();
        $sql = "SELECT player_status, player_team, player_id from player WHERE player_id = $player_id";
        $player = self::getNonEmptyObjectFromDB( $sql );
        // $player_status = json_decode($player['player_status'], true);
        // $used_skill = in_array(405, $player_status);
        $player_team = $player['player_team'];
        return array(
            "player_team" => $player_team,
            "player_status" => $player['player_status'],
        );
    }

    function argCounterattack()
    {
        $player_id = self::getActivePlayerId();
        $sql = "SELECT * FROM playing_card WHERE disabled = FALSE";
        $playing_card_info = self::getNonEmptyObjectFromDB( $sql );
        $playcard_player_id = $playing_card_info['player_id'];
        $playcard_player_name = self::getPlayerNameById($playing_card_info['player_id']);
        $card = self::getCardinfoFromCardsInfo($playing_card_info['card_type_arg']);
        $card_name = $card['name'];
        if ( $player_id == $playcard_player_id ) {
            $card_name = "Intercept";
            $opponent_id = $this->getNonActivePlayerId($player_id);
            $playcard_player_name = self::getPlayerNameById($opponent_id);
        }
        return array(
            "playcard_player" => $playcard_player_name,
            "card_name" => $card_name,
        );
    }
}