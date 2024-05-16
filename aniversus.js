/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Aniversus implementation : © Leo Cheung <leocheung1718@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * aniversus.js
 *
 * Aniversus user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

function reloadCss() {
	var links = document.getElementsByTagName("link");
	for (var cl in links) {
		var link = links[cl];
		if (link.rel === "stylesheet" && link.href.includes("99999")) {
			var index = link.href.indexOf("?timestamp=");
			var href = link.href;
			if (index >= 0) {
				href = href.substring(0, index);
			}

			link.href = href + "?timestamp=" + Date.now();

			console.log("reloading " + link.href);
		}
	}
}

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock",
    "ebg/zone",
    g_gamethemeurl + "modules/aniversus_other.js",
    
],
function (dojo, declare) {
    
    return declare("bgagame.aniversus", ebg.core.gamegui, {
        
        constructor: function(){
            console.log('aniversus constructor');
            // declare card width and height
            this.cardwidth = 124;
            this.cardheight = 174;
            
            this.other = new bgagame.other();
            // Zone control (Player Playmat)
            // Create a array of zone objects for player playmat
            this.playerOnPlaymat = {};
            ['me', 'opponent'].forEach((player) => {
                this.playerOnPlaymat[player] = {};
                this.playerOnPlaymat[player]['discardpile'] = new ebg.zone();
                for (var row = 1; row <= 2; row++) {
                    this.playerOnPlaymat[player][row] = {};
                    for (var col = 1; col <= 5; col++) {
                        this.playerOnPlaymat[player][row][col] = new ebg.zone();
                    }
                }
            });
            // OnClickMethod store
            this.onClickMethod = {};
            this.onClickMethod['playerOnPlaymat'] = {};

            // Shooting roll dice area -------------------------------------------------------------------------------- 
            // this.placeJstplSection("rolldice-area", "roll-dice", this.other.rollDice_html_content);
            // dojo.connect($('roll'), 'onclick', this, () => {this.other.rollDice();});
            // Shooting roll dice area -------------------------------------------------------------------------------- 
        },
        
        /*
            setup:
            // SECTION Setup Part
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////
            ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
            // ANCHOR Setting up player boards
            this.playerCounter = {};
            for( let player_id in gamedatas.players )
            {
                // player board counter
                var player = gamedatas.players[player_id];
                this.playerCounter[player_id] = {};
                var player_board_div = $('player_board_'+ player_id);
                dojo.place( this.format_block('jstpl_player_board', {"player_id" : player_id}), player_board_div );
                this.playerCounter[player_id]['productivity'] = new ebg.counter();
                this.playerCounter[player_id]['productivity'].create( `player_productivity_${player_id}` );
                this.playerCounter[player_id]['productivity'].setValue(player.player_productivity);
                this.playerCounter[player_id]['action'] = new ebg.counter();
                this.playerCounter[player_id]['action'].create( `player_action_${player_id}` );
                this.playerCounter[player_id]['action'].setValue(player.player_action);
                this.playerCounter[player_id]['power'] = new ebg.counter();
                this.playerCounter[player_id]['power'].create( `player_power_${player_id}` );
                this.playerCounter[player_id]['power'].setValue(player.player_power);
                this.playerCounter[player_id]['handCardNumber'] = new ebg.counter();
                this.playerCounter[player_id]['handCardNumber'].create( `player_hand_${player_id}` );
                this.playerCounter[player_id]['handCardNumber'].setValue(gamedatas['hand_card_number'][player_id]);
                this.playerCounter[player_id]['deckCardNumber'] = new ebg.counter();
                this.playerCounter[player_id]['deckCardNumber'].create( `player_deck_${player_id}` );
                this.playerCounter[player_id]['deckCardNumber'].setValue(gamedatas['deck_card_number'][player_id]);
                // shooting number
                let shootNum_text = '';
                player.shooting_number = [...new Set(player.shooting_number)].sort((a, b) => a - b);
                for (let i = 0; i < player.shooting_number.length; i++) {
                    if ( Number(player.shooting_number[i]) <= 12 ) {
                        shootNum_text += `${player.shooting_number[i]}, `;
                    }
                }
                shootNum_text = shootNum_text.slice(0, -2)
                $(`player_shootNum_${player_id}`).textContent = shootNum_text;
                if (player_id == this.player_id) {
                    this.playerCounter[player_id]['productivity_playmat'] = new ebg.counter();
                    this.playerCounter[player_id]['productivity_playmat'].create( `playermat_productivity_me` );
                    this.playerCounter[player_id]['productivity_playmat'].setValue(player.player_productivity);
                    this.playerCounter[player_id]['power_playmat'] = new ebg.counter();
                    this.playerCounter[player_id]['power_playmat'].create( `playermat_power_me` );
                    this.playerCounter[player_id]['power_playmat'].setValue(player.player_power);
                } else {
                    this.playerCounter[player_id]['productivity_playmat'] = new ebg.counter();
                    this.playerCounter[player_id]['productivity_playmat'].create( `playermat_productivity_opponent` );
                    this.playerCounter[player_id]['productivity_playmat'].setValue(player.player_productivity);
                    this.playerCounter[player_id]['power_playmat'] = new ebg.counter();
                    this.playerCounter[player_id]['power_playmat'].create( `playermat_power_opponent` );
                    this.playerCounter[player_id]['power_playmat'].setValue(player.player_power);
                }
                if (player_id == this.player_id) {
                // status counter
                this.playerCounter[player_id]['status_cannotdraw'] = new ebg.counter();
                this.playerCounter[player_id]['status_cannotdraw'].create( "status_cannotdraw_counter" );
                this.playerCounter[player_id]['status_cannotdraw'].setValue(gamedatas['player_status']['cannotdraw']);
                if (gamedatas['player_status']['cannotdraw'] == 0) {
                    dojo.addClass('cannotdraw_container', 'status_noeffect')
                } else {
                    dojo.removeClass('cannotdraw_container', 'status_noeffect')
                }
                this.playerCounter[player_id]['status_suspension'] = new ebg.counter();
                this.playerCounter[player_id]['status_suspension'].create( "status_suspension_counter" );
                this.playerCounter[player_id]['status_suspension'].setValue(gamedatas['player_status']['suspension']);
                if (gamedatas['player_status']['suspension'] == 0) {
                    dojo.addClass('suspension_container', 'status_noeffect')
                } else {
                    dojo.removeClass('suspension_container', 'status_noeffect')
                }
                this.playerCounter[player_id]['status_actionup'] = new ebg.counter();
                this.playerCounter[player_id]['status_actionup'].create( "status_actionup_counter" );
                this.playerCounter[player_id]['status_actionup'].setValue(gamedatas['player_status']['actionup']);
                if (gamedatas['player_status']['actionup'] == 0) {
                    dojo.addClass('actionup_container', 'status_noeffect')
                } else {
                    dojo.removeClass('actionup_container', 'status_noeffect')
                }
                this.playerCounter[player_id]['status_energydeduct'] = new ebg.counter();
                this.playerCounter[player_id]['status_energydeduct'].create( "status_energydeduct_counter" );
                this.playerCounter[player_id]['status_energydeduct'].setValue(gamedatas['player_status']['energydeduct']);
                if (gamedatas['player_status']['energydeduct'] == 0) {
                    dojo.addClass('energydeduct_container', 'status_noeffect')
                } else {
                    dojo.removeClass('energydeduct_container', 'status_noeffect')
                }
                this.playerCounter[player_id]['status_comeback'] = new ebg.counter();
                this.playerCounter[player_id]['status_comeback'].create( "status_comeback_counter" );
                this.playerCounter[player_id]['status_comeback'].setValue(gamedatas['player_status']['comeback']);
                if (gamedatas['player_status']['comeback'] == 0) {
                    dojo.addClass('comeback_container', 'status_noeffect')
                } else {
                    dojo.removeClass('comeback_container', 'status_noeffect')
                }
                }

            }
            // playerboard ToolTip
            this.addTooltipToClass('element_productivity_token', _('<b>Productivity</b>'), '');
            this.addTooltipToClass('element_action_token', _('<b>Action</b>'), '');
            this.addTooltipToClass('element_power_token', _('<b>Power</b>'), '');
            this.addTooltipToClass('element_hand_token', _('<b>Hand Card Number(s)</b>'), '');
            this.addTooltipToClass('element_draw_token', _('<b>Draw Deck Number(s)</b>'), '');
            this.addTooltipToClass('element_shootNum_token', _('<b>Goal Number(s)</b>'), '');
            this.addTooltipToClass('status_cannotdraw_token', _('<b>You cannot draw cards next round</b>'), '');
            this.addTooltipToClass('status_suspension_token', _('<b>You skip 1 round</b>'), '');
            this.addTooltipToClass('status_actionup_token', _('<b>You have 2 more actions next round</b>'), '');
            this.addTooltipToClass('status_energydeduct_token', _('<b>Your productivities to be deducted by 2 next round</b>'), '');
            this.addTooltipToClass('status_comeback_token', _('<b>You gain 2 productivities and draw 2 more cards next round</b>'), '');
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////
            ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
            // ANCHOR Set up your game interface here, according to "gamedatas"
            // Player hand Setup
            // player hand
            this.playerdeck = this.setNewCardStock('myhand', 1, 'onPlayerHandSelectionChanged', 5, false);
            this.playerdeck.extraClasses='rounded';
            // show hand
            for ( let i in this.gamedatas.hand) {
                var card = this.gamedatas.hand[i];
                this.getCard2hand(card);
            }
            
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////
            ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
            // ANCHOR Discard pile setup
            this.playerOnPlaymat['me']['discardpile'].create( this, 'discardPile_field_me', this.cardwidth, this.cardheight );
            this.playerOnPlaymat['opponent']['discardpile'].create( this, 'discardPile_field_opponent', this.cardwidth, this.cardheight );
            // player playmat setup
            for (var player of ['me', 'opponent']) {
                for (var row = 1; row <= 2; row++) {
                    for (var col = 1; col <= 5; col++) {
                        this.playerOnPlaymat[player][row][col].create( this, `playerOnPlaymat_${player}_${row}_${col}`, this.cardwidth, this.cardheight );
                        this.playerOnPlaymat[player][row][col].setPattern('diagonal');
                        this.playerOnPlaymat[player][row][col].item_margin = 5;
                    }
                }
            }
            // ANCHOR Add cards to the playmat and discard pile
            for ( let player_id in this.gamedatas.players ) {
                if (player_id == this.player_id) {
                    //// Add cards to the Discard pile
                    //// Get the top three cards in the discard pile
                    let topThreeDiscardPile = this.gamedatas['players'][player_id]['discardpile'].slice(0, 3);
                    // we just need to get first three cards in the discard pile
                    topThreeDiscardPile.forEach((card) => {
                        this.getJstplCard(player_id, card.id, card.type_arg, 'player_board_' + player_id, 'discardPile_field_me', true);
                        this.playerOnPlaymat['me']['discardpile'].placeInZone( `discardOnTable_${player_id}_${card.id}`, 0 );
                        this.addTooltipHtml(`discardOnTable_${player_id}_${card.id}`, this.getTooltipHtml(card.type_arg));
                    });
                    ////
                    //// Add cards to my playmat

                    $group_me_playmat = this.groupAllSamePositionPlayerFromCardLst(this.gamedatas['players'][player_id]['playmat']);
                    Object.values($group_me_playmat).forEach((positionLst) => {
                        positionLst.forEach((card) => {
                            let card_position = this.decodePlayerLocation(card.location_arg);
                            this.getJstplCard(player_id, card.id, card.type_arg, 'player_board_' + player_id, `playerOnPlaymat_me_${card_position.row}_${card_position.col}`);
                            this.playerOnPlaymat['me'][card_position.row][card_position.col].placeInZone( 'cardsOnTable_' + player_id + '_' + card.id, 0 );
                        });
                        this.addTooltipHtml(`cardsOnTable_${player_id}_${positionLst[positionLst.length - 1].id}`, this.joinTooltipHtml(this.getAllUniqueTypeArgsInCardLst(positionLst)));
                    });
                } else {
                    //// Opponent Discard pile
                    let topThreeDiscardPile_opponent = this.gamedatas['players'][player_id]['discardpile'].slice(0, 3);
                    topThreeDiscardPile_opponent.forEach((card) => {
                        this.getJstplCard(player_id, card.id, card.type_arg, 'player_board_' + player_id, 'discardPile_field_opponent', true);
                        this.playerOnPlaymat['opponent']['discardpile'].placeInZone( `discardOnTable_${player_id}_${card.id}`, 0 );
                        this.addTooltipHtml(`discardOnTable_${player_id}_${card.id}`, this.getTooltipHtml(card.type_arg));
                    });
                    //// Opponent playmat
                    //// Add cards to the playmat

                    $group_opponent_playmat = this.groupAllSamePositionPlayerFromCardLst(this.gamedatas['players'][player_id]['playmat']);
                    Object.values($group_opponent_playmat).forEach((positionLst) => {
                        positionLst.forEach((card) => {
                            let card_position = this.decodePlayerLocation(card.location_arg);
                            this.getJstplCard(player_id, card.id, card.type_arg, 'player_board_' + player_id, `playerOnPlaymat_opponent_${card_position.row}_${card_position.col}`);
                            this.playerOnPlaymat['opponent'][card_position.row][card_position.col].placeInZone( 'cardsOnTable_' + player_id + '_' + card.id, 0 );
                        });
                        this.addTooltipHtml(`cardsOnTable_${player_id}_${positionLst[positionLst.length - 1].id}`, this.joinTooltipHtml(this.getAllUniqueTypeArgsInCardLst(positionLst)));
                    });
                }
            }
            // player Ability setup
            for (let [PlaymatInfoId, PlaymatInfoValue] of Object.entries(this.gamedatas['playerAbility'])) {
                if (PlaymatInfoId == this.player_id) {
                    for (let [key, value] of Object.entries(PlaymatInfoValue['playmatInfo'])) {
                        if (value['active'] == false) {
                            let position = this.decodePlayerLocation(key);
                            let unactive_cards = dojo.query(`#playerOnPlaymat_me_${position.row}_${position.col} .js-cardsontable`);
                            unactive_cards.forEach((card) => {
                                dojo.addClass(card, 'ineffective');
                            });
                        }
                    }
                } else {
                    for (let [key, value] of Object.entries(PlaymatInfoValue['playmatInfo'])) {
                        if (value['active'] == false) {
                            let position = this.decodePlayerLocation(key);
                            let unactive_cards = dojo.query(`#playerOnPlaymat_opponent_${position.row}_${position.col} .js-cardsontable`);
                            unactive_cards.forEach((card) => {
                                dojo.addClass(card, 'ineffective');
                            });
                        }
                    }
                
                }
            }
            // $opplaymat = this.gamedatas['playerAbility'][this.player_id]['playmatInfo'];
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////
            // ANCHOR special case setup
            const gamestate = this.gamedatas.gamestate_name;
            const my_playing_card = this.gamedatas.playing_card[this.player_id];
            switch (gamestate) {
                case 'cardActiveEffect':
                    if (my_playing_card['disabled'] == false) {
                        switch (Number(my_playing_card['card_type_arg'])) {
                            case 4:
                                this.notif_removePlaymatClickAvailable({});
                                this.playerdeck.setSelectionMode(0);
                                for (let row = 1; row <= 2; row++) {
                                    for (let col = 1; col <= 5; col++) {
                                        var div_id = `playerOnPlaymat_opponent_${row}_${col}`; 
                                        if ( dojo.query(`.js-cardsontable`, div_id).length != 0) {
                                            dojo.addClass(div_id, 'available');
                                            this.onClickMethod['playerOnPlaymat'][`${row}_${col}`] = dojo.connect($(div_id), 'onclick', this, () => this.onRedCard_CardActiveEffect(row, col));
                                        }
                                    }
                                }
                                break;
                            case 8:
                                this.notif_showCardsOnTempStock({args: {'card_type_arg': 8, 'cards': JSON.parse(my_playing_card['card_info']), 'card_id': my_playing_card['card_id']}});
                                break;
                            case 11:
                                if (my_playing_card['card_info'] == "field" ) {
                                    this.playerdeck.setSelectionMode(0);
                                    this.notif_removePlaymatClickAvailable({});
                                    if (args.player_id == this.player_id) {
                                        for (let row = 1; row <= 2; row++) {
                                            for (let col = 1; col <= 5; col++) {
                                                var div_id = `playerOnPlaymat_me_${row}_${col}`; 
                                                if ( dojo.query(`.js-cardsontable`, div_id).length != 0) {
                                                    dojo.addClass(div_id, 'available');
                                                    this.onClickMethod['playerOnPlaymat'][`${row}_${col}`] = dojo.connect($(div_id), 'onclick', this, () => this.onSwapField_CardActiveEffect(row, col));
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    this.notif_showCardsOnTempStock({args: {'card_type_arg': 11, 'cards': JSON.parse(my_playing_card['card_info'])}});
                                }
                                break;
                            case 57:
                                this.notif_showCardsOnTempStock({args: {'card_type_arg': 57, 'cards': JSON.parse(my_playing_card['card_info'])}});
                                break;
                            case 108:
                                this.notif_removePlaymatClickAvailable({});
                                this.playerdeck.setSelectionMode(0);
                                if (args.player_id == this.player_id) {
                                    for (let row = 1; row <= 2; row++) {
                                        for (let col = 1; col <= 5; col++) {
                                            var div_id = `playerOnPlaymat_me_${row}_${col}`; 
                                            if ( dojo.query(`.js-cardsontable`, div_id).length != 0) {
                                                dojo.addClass(div_id, 'available');
                                                this.onClickMethod['playerOnPlaymat'][`${row}_${col}`] = dojo.connect($(div_id), 'onclick', this, () => this.onPickPlayerFromPlaymat2Hand_CardActiveEffect(row, col));
                                            }
                                        }
                                    }
                                }
                                break;
                            case 112:
                                this.notif_showCardsOnTempStock({args: {'card_type_arg': 112, 'cards': JSON.parse(my_playing_card['card_info'])}});
                                this.playerdeck.setSelectionMode(0);
                                break;
                            case 401:
                                this.notif_removePlaymatClickAvailable({});
                                this.playerdeck.setSelectionMode(0);
                                if (this.player_id == this.getActivePlayerId()) {
                                    for (let row = 1; row <= 2; row++) {
                                        for (let col = 1; col <= 5; col++) {
                                            var div_id = `playerOnPlaymat_opponent_${row}_${col}`; 
                                            if ( dojo.query(`.js-cardsontable`, div_id).length != 0) {
                                                dojo.addClass(div_id, 'available');
                                                this.onClickMethod['playerOnPlaymat'][`${row}_${col}`] = dojo.connect($(div_id), 'onclick', this, () => this.onRedCardAfterShoot_CardActiveEffect(row, col));
                                            }
                                        }
                                    }
                                }   
                                break;
                            case 402:
                                this.notif_removePlaymatClickAvailable({});
                                this.playerdeck.setSelectionMode(0);
                                if (this.player_id == this.getActivePlayerId()) {
                                    for (let row = 1; row <= 2; row++) {
                                        for (let col = 1; col <= 5; col++) {
                                            let div_id = `playerOnPlaymat_me_${row}_${col}`; 
                                            if ( dojo.query(`.js-cardsontable`, div_id).length != 0) {
                                                dojo.addClass(div_id, 'available');
                                                this.onClickMethod['playerOnPlaymat'][`${row}_${col}`] = dojo.connect($(div_id), 'onclick', this, () => this.onThrowPlayer_CardActiveEffect(row, col));
                                            }
                                        }
                                    }
                                }
                                break;
                            case 40512:
                                this.notif_showCardsOnTempStock({args: {'card_type_arg': 40512, 'cards': JSON.parse(my_playing_card['card_info'])}});
                                break;
                            default:
                                break;
                        }
                    }
                    break;
                default:
                    break;
            }
            ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
            // ANCHOR Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();
            // CSS setup
            dojo.addClass('page-title', 'page-title-css')
            // end of css setup
            console.log( "Ending game setup" );
        },
        // !SECTION setup

        ///////////////////////////////////////////////////
        //// Game & client states
        // SECTION onEnteringState
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+ stateName );
            // Call appropriate method
            var methodName = "onEnteringState_" + stateName;
            if (this[methodName] !== undefined) {
                console.log(`Calling ${methodName}, args: ${this.safeStringify(args.args)}`);
                this[methodName](args.args);
            }
        },
        // ANCHOR cardEffect state
        onEnteringState_cardEffect: function(args) {
            console.log('cardEffect state: the enterfunction is called');
            console.log(args);
            // let card_type_arg = Number(args.card_type_arg);
            // switch (card_type_arg) {
            //     default:
            //         console.log('The card type is other');
            //         break;
            // }
        },
        // ANCHOR playerTurn state
        onEnteringState_playerTurn: function(args) {
            console.log('playerTurn state: the enterfunction is called');
            console.log(args);
            // set playerdeck selection mode
            this.playerdeck.setSelectionMode(1);
        },
        // ANCHOR cardActiveEffect state
        onEnteringState_cardActiveEffect: function(args) {
            console.log('cardActiveEffect state: the enterfunction is called');
            console.log(args);
            let card_type_arg = Number(args.card_type_arg);
            switch (card_type_arg) {
                case 1:
                    this.playerdeck.setSelectionMode(1);
                    break;
                case 4:
                    this.playerdeck.setSelectionMode(0);
                    const row = 1;
                    if (args.player_id == this.player_id) {
                        for (let col = 1; col <= 5; col++) {
                            var div_id = `playerOnPlaymat_opponent_${row}_${col}`; 
                            if ( dojo.query(`.js-cardsontable`, div_id).length != 0) {
                                dojo.addClass(div_id, 'available');
                                this.onClickMethod['playerOnPlaymat'][`${row}_${col}`] = dojo.connect($(div_id), 'onclick', this, () => this.onRedCard_CardActiveEffect(row, col));
                            }
                        }
                    }   
                    break;
                case 8:
                    this.playerdeck.setSelectionMode(0);
                    break;
                case 11:
                    this.playerdeck.setSelectionMode(0);
                    if (args.player_id == this.player_id) {
                        for (let row = 1; row <= 2; row++) {
                            for (let col = 1; col <= 5; col++) {
                                var div_id = `playerOnPlaymat_me_${row}_${col}`; 
                                if ( dojo.query(`.js-cardsontable`, div_id).length != 0) {
                                    dojo.addClass(div_id, 'available');
                                    this.onClickMethod['playerOnPlaymat'][`${row}_${col}`] = dojo.connect($(div_id), 'onclick', this, () => this.onSwapField_CardActiveEffect(row, col));
                                }
                            }
                        }
                    }
                    break;
                case 56:
                    this.playerdeck.setSelectionMode(2);
                    break;
                case 57:
                    this.playerdeck.setSelectionMode(0);
                    break;
                case 64:
                    this.playerdeck.setSelectionMode(1);
                    break;
                case 108:
                    this.playerdeck.setSelectionMode(0);
                    if (args.player_id == this.player_id) {
                        for (let row = 1; row <= 2; row++) {
                            for (let col = 1; col <= 5; col++) {
                                var div_id = `playerOnPlaymat_me_${row}_${col}`; 
                                if ( dojo.query(`.js-cardsontable`, div_id).length != 0) {
                                    dojo.addClass(div_id, 'available');
                                    this.onClickMethod['playerOnPlaymat'][`${row}_${col}`] = dojo.connect($(div_id), 'onclick', this, () => this.onPickPlayerFromPlaymat2Hand_CardActiveEffect(row, col));
                                }
                            }
                        }
                    }
                    break;
                case 109:
                    this.playerdeck.setSelectionMode(2);
                    break;
                case 112:
                    this.playerdeck.setSelectionMode(2);
                    break;
                case 401:
                    this.playerdeck.setSelectionMode(0);
                    if (args.player_id == this.player_id) {
                        for (let row = 1; row <= 2; row++) {
                            for (let col = 1; col <= 5; col++) {
                                let div_id = `playerOnPlaymat_opponent_${row}_${col}`; 
                                if ( dojo.query(`.js-cardsontable`, div_id).length != 0) {
                                    dojo.addClass(div_id, 'available');
                                    this.onClickMethod['playerOnPlaymat'][`${row}_${col}`] = dojo.connect($(div_id), 'onclick', this, () => this.onRedCardAfterShoot_CardActiveEffect(row, col));
                                }
                            }
                        }
                    }
                    break;
                case 402:
                    this.playerdeck.setSelectionMode(0);
                    this.notif_removePlaymatClickAvailable({});
                    if (args.player_id == this.player_id) {
                        for (let row = 1; row <= 2; row++) {
                            for (let col = 1; col <= 5; col++) {
                                let div_id = `playerOnPlaymat_me_${row}_${col}`; 
                                if ( dojo.query(`.js-cardsontable`, div_id).length != 0) {
                                    dojo.addClass(div_id, 'available');
                                    this.onClickMethod['playerOnPlaymat'][`${row}_${col}`] = dojo.connect($(div_id), 'onclick', this, () => this.onThrowPlayer_CardActiveEffect(row, col));
                                }
                            }
                        }
                    }
                    break;
                case 40512:
                    this.playerdeck.setSelectionMode(0);
                    break;
                default:
                    console.log('The card type is other');
                    break;
            }
        },
        // ANCHOR counterattack state
        onEnteringState_counterattack: function(args) {
            console.log('counterattack state: the enterfunction is called');
            console.log(args);
            this.playerdeck.setSelectionMode(0);
            
        },
        // ANCHOR throwCard state
        onEnteringState_throwCard: function(args) {
            console.log('throwCard state: the enterfunction is called');
            console.log(args);
            this.playerdeck.setSelectionMode(2);
        },

        // ANCHOR shoot state
        onEnteringState_shoot: function(args) {
            console.log('shoot state: the enterfunction is called');
            console.log(args);
            this.placeJstplSection("rolldice-area", "roll-dice", this.other.rollDice_html_content);
        },
        // ANCHOR redcard state
        onEnteringState_redcard: function(args) {
            console.log('redcard state: the enterfunction is called');
            console.log(args);
            this.playerdeck.setSelectionMode(0);
        },
        // ANCHOR skill state
        onEnteringState_skill: function(args) {
            console.log('skill state: the enterfunction is called');
            console.log(args);
            this.playerdeck.setSelectionMode(0);
        },
        // !SECTION onEnteringState
        // ------------------------------------- End of onEnteringState -------------------------------------------- //

        //SECTION - onLeavingState
        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            /* Example:
            case 'myGameState':
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                break;
           */
            case 'cardActiveEffect':
                break;
            case 'shoot':
                dojo.destroy('roll-dice');
                break;
            case 'dummmy':
                break;
            }            
        }, 
        // !SECTION - onLeavingState
        ///////////////////////////////////////////////////
        // SECTION - onUpdateActionButtons
        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: ' + stateName );
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
                case 'cardActiveEffect':
                    this.addActionButton( 'cardActiveEffect_btn_throw', _('Discard'), 'onThrowCard_CardActiveEffect' );
                    let button_list = JSON.parse(args.button_list);
                    if (!(button_list.includes(1))) {
                        dojo.addClass('cardActiveEffect_btn_throw', 'none');
                    }
                    break;
                case 'playerTurn':
                    this.addActionButton( 'playerTurn_btn_play', _('Play'), 'onPlayCard_PlayerTurn' );
                    this.addActionButton( 'playerTurn_btn_throwPlayer', _('Remove'), 'onThrowPlayer_playerTurn' );
                    this.addActionButton( 'playerTurn_btn_shoot', _('Shoot'), 'onShoot_PlayerTurn' );
                    this.addActionButton( 'playerTurn_btn_pass', _('Pass'), 'onPass_PlayerTurn' );
                    this.addActionButton( 'playerTurn_btn_skill', _('Animal Skill'), 'onSkill_playerTurn', null, false, 'red' );
                    if (this.playerCounter[this.player_id]['action'].getValue() == 0) {
                        dojo.addClass('playerTurn_btn_play', 'disabled');
                        dojo.addClass('playerTurn_btn_throwPlayer', 'disabled');
                    }
                    if (this.playerCounter[this.player_id]['power'].getValue() < 10) {
                        dojo.addClass('playerTurn_btn_shoot', 'disabled');
                    }
                    break;
                case 'counterattack':
                    this.addActionButton( 'counterattack_btn_intercept', _('Intercept'), 'onIntercept_counterattack' );
                    this.addActionButton( 'counterattack_btn_pass', _('Pass'), 'onPass_counterattack' );
                    break;
                case 'redcard':
                    this.addActionButton( 'redcard_btn_redcard', _('Red Card'), 'onRedCard_redcard' );
                    this.addActionButton( 'redcard_btn_pass', _('Pass'), 'onPass_redcard' );
                    if (args.redcardnumber <= 0) {
                        dojo.addClass('redcard_btn_redcard', 'disabled');
                    }
                    break;
                case 'throwCard':
                    this.addActionButton( 'throwCard_btn_throw', _('Discard'), 'onThrowCard_throwCard' );
                    this.addActionButton( 'throwCard_btn_pass', _('Pass'), 'onPass_throwCard' );
                    if (args.thrownumber > 0) {
                        dojo.addClass('throwCard_btn_pass', 'disabled');
                    } else {
                        dojo.addClass('throwCard_btn_throw', 'disabled');
                    }
                    break;
                case 'skill':
                    const team = args.player_team;
                    const player_status = JSON.parse(args.player_status);
                    console.log(player_status);
                    if ( team == 'cat' ) {
                        this.addActionButton( 'skill_btn_cat_power', _('Power Up'), 'onCatPowerUp_skill' );
                        this.addActionButton( 'skill_btn_cat_productivity', _('Productivity Up'), 'onCatProductivityUp_skill' );
                        if ( player_status.includes(40512) ) {
                            dojo.addClass('skill_btn_cat_power', 'disabled');
                        }
                        if ( player_status.includes(40513) ) {
                            dojo.addClass('skill_btn_cat_productivity', 'disabled');
                        }   
                    } else if ( team == 'squirrel' ) {
                        this.addActionButton( 'skill_btn_squirrel_lookAt', _('Deck Peek'), 'onSquirrelLookAt_skill' );
                        this.addActionButton( 'skill_btn_squirrel_search', _('Deck Search'), 'onSquirrelSearch_skill' );
                        if ( player_status.includes(405) ) {
                            dojo.addClass('skill_btn_squirrel_search', 'disabled');
                        }
                    
                    }
                    this.addActionButton( 'skill_btn_back', _('Back'), 'onBack_skill' );
                    dojo.addClass($('skill_btn_back'), 'bgabutton_blackAndWheat')
                }
            }
        },        
        // !SECTION - onUpdateActionButtons
        ///////////////////////////////////////////////////
        //// Utility methods
        // SECTION - Utility methods
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */
        // ANCHOR attachToNewParentNoDestroy
        /**
         * This method will attach mobile to a new_parent without destroying, unlike original attachToNewParent which destroys mobile and
         * all its connectors (onClick, etc)
         */
        attachToNewParentNoDestroy: function (mobile_in, new_parent_in, relation, place_position) {

            const mobile = $(mobile_in);
            const new_parent = $(new_parent_in);

            var src = dojo.position(mobile);
            if (place_position)
                mobile.style.position = place_position;
            dojo.place(mobile, new_parent, relation);
            mobile.offsetTop;//force re-flow
            var tgt = dojo.position(mobile);
            var box = dojo.marginBox(mobile);
            var cbox = dojo.contentBox(mobile);
            var left = box.l + src.x - tgt.x;
            var top = box.t + src.y - tgt.y;

            mobile.style.position = "absolute";
            mobile.style.left = left + "px";
            mobile.style.top = top + "px";
            box.l += box.w - cbox.w;
            box.t += box.h - cbox.h;
            mobile.offsetTop;//force re-flow
            return box;
        },
        // ANCHOR placeJstplSection
        placeJstplSection: function(parent_id, id, content) { 
            html = this.format_block('jstpl_content', { id: id, content: content }); 
            dojo.place(html,parent_id); 
        },
        safeStringify: function(obj) {
        const seen = new WeakSet();
            return JSON.stringify(obj, (key, value) => {
                if (typeof value === "object" && value !== null) {
                if (seen.has(value)) {
                    // Circular reference found, discard key
                    return;
                }
                seen.add(value);
                }
                return value;
            })
        },
        // ANCHOR getCardBackgroundPosition
        // This function returns the css background position of a card
        getCardBackgroundPosition: function(card_type) {
            const type2css = this.gamedatas['card_type_arg2css_position'];
            const position = type2css[card_type];
            const card_width = 124; const card_height = 174;
            const columns = 10;
            var row = Math.floor(position / columns);
            var column = (position % columns);
            var x = column * card_width;
            var y = row * card_height;
            return {x: -x, y: -y};
        },

        getToolTipBackgroundPosition: function(card_type) {
            const type2css = this.gamedatas['card_type_arg2css_position'];
            const position = type2css[card_type];
            const card_width = 230; const card_height = 321;
            const columns = 10;
            var row = Math.floor(position / columns);
            var column = (position % columns);
            var x = column * card_width;
            var y = row * card_height;
            return {x: -x, y: -y};
        },

        // ANCHOR getCard2hand
        getCard2hand: function(card) {
            this.playerdeck.addToStockWithId(Number(card.type_arg), card.id);
            this.addTooltipHtml('myhand_item_' + card.id, this.getTooltipHtml(Number(card.type_arg)));
        },
        // ANCHOR getJstplCard
        getJstplCard: function(player_id, card_id, card_type, from, to, discard = false) {
            const position = this.getCardBackgroundPosition(card_type);
            let discard_id = `discardOnTable_${player_id}_${card_id}`;
            let table_id = `cardsOnTable_${player_id}_${card_id}`;
            var cardOnTable_id = discard ? discard_id : table_id;
            // create card on table
            dojo.place( this.format_block('jstpl_cardsOnTable', {
                card_id: cardOnTable_id,
                ...position
            }), to);
            // add ToolTip to Class
            // dojo.addClass(cardOnTable_id, `cardsOnTable_type_${card_type}`)
            // this.addTooltipHtmlToClass(`cardsOnTable_type_${card_type}`, this.getTooltipHtml(Number(card_type)));
            // place the card on the player board
            this.placeOnObject(cardOnTable_id, from);
            // slide the card to the table
            this.attachToNewParent(cardOnTable_id, to);
            this.addTooltipToClass('cardsOnTable_type_' + card_type, this.getTooltipHtml(Number(card_type)));
            // this.addTooltipHtml(cardOnTable_id, this.getTooltipHtml(Number(card_type)));
        },
        // ANCHOR getTooltipHtml
        getTooltipHtml: function(card_id) {
            var card = this.gamedatas.cards_info.find((card) => Number(card.id) == Number(card_id));
            var card_name = card.name;
            var card_type = card.type;
            var card_cost = card.cost;
            var card_productivity = (card_type == "Function") ? "\u{FF3C}" : card.productivity;
            var card_power = (card_type == "Function") ? "\u{FF3C}" : card.power;
            var card_description = (card.function == "") ? "\u{FF3C}" : card.function;
            var position = this.getToolTipBackgroundPosition(card_id);
            return this.format_block('jstpl_cardToolTip', {
                card_name: card_name,
                card_type: card_type,
                card_cost: card_cost,
                card_productivity: card_productivity,
                card_power: card_power,
                card_description: card_description,
                ... position
            });
        },
        // ANCHOR joinTooltipHtml
        joinTooltipHtml: function(tooltip_lst) {
            if (tooltip_lst.length == 1) {
                return this.getTooltipHtml(Number(tooltip_lst[0]));
            }
            var tooltip = '';
            tooltip_lst.forEach((item) => {
                tooltip += this.getTooltipHtml(Number(item));
                tooltip += '<hr>';
            });
            return tooltip;
        },

        // ANCHOR ajaxcallwrapper
        ajaxcallwrapper: function(action, args, handler) {
            if (!args) args = {}; // this allows to skip args parameter for action which do not require them
                
            args.lock = true; // this allows to avoid rapid action clicking which can cause race condition on server
            if (this.checkAction(action)) { // this does all the proper check that player is active and action is declared
                this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", args, // this is mandatory fluff 
                    this, (result) => { },  // success result handler is empty - it is never needed
                    handler); // this is real result handler - it called both on success and error, it has optional param  "is_error" - you rarely need it
                }
        },
        // Usage: 
        // this.ajaxcallwrapper('pass'); // no args
        // this.ajaxcallwrapper('playCard', {card: card_id}); // with args

        // ANCHOR setNewCardStock
        setNewCardStock: function( div_id, selectionMode, onSelectionChangedMethod, card_margin = 13, overlap = false ) {
            // Player hand Setup
            // player hand
            var newstock = new ebg.stock(); // new stock object for hand
            newstock.create( this, $( div_id ), this.cardwidth, this.cardheight );
            // config stock object
            newstock.image_items_per_row = 10;
            newstock.centerItems = true; // Center items (actually i don't know what it does)
            // this.playerdeck.apparenceBorderWidth = '2px'; // Change border width when selected
            newstock.setSelectionMode( selectionMode ); // Allow only one card to be selected
            newstock.setSelectionAppearance('class'); // Add a class to selected
            newstock.item_margin = card_margin; // Add margin between cards
            if (overlap) {
                newstock.item_margin = 0;
                newstock.horizontal_overlap = 95;
            }
            newstock.autowidth = true;
            // Create cards types:
            // addItemType(type: number, weight: number, image: string, image_position: number )
            this.gamedatas.cards_info.forEach((key) => {
                newstock.addItemType(Number(key.id), 1, 
                g_gamethemeurl + "img/sprite_sheet.png", Number(key.css_position));
                    
            });
            // setup connect
            dojo.connect( newstock, 'onChangeSelection', this, onSelectionChangedMethod );

            return newstock;
        },

        // ANCHOR groupAllSamePositionPlayerFromCardLst
        groupAllSamePositionPlayerFromCardLst: function(card_lst) {
            let grouped = {};
            // First, create groups based on 'location_arg'
            Object.values(card_lst).forEach((item) => {
                let locationArg = item.location_arg;
                if (!grouped[locationArg]) {
                    grouped[locationArg] = [];
                }
                grouped[locationArg].push(item);
            });
        
            // Define an order for the types
            const typeOrder = {
                'Player': 1,
                'Training': 2,
                'Function': 3
            };
        
            // Sort each group by the predefined type order, with a fallback to 'id' comparison
            Object.keys(grouped).forEach((key) => {
                grouped[key].sort((a, b) => {
                    return (typeOrder[a.type] || 4) - (typeOrder[b.type] || 4) 
                           || a.id.localeCompare(b.id);
                });
            });
        
            return grouped;
        },

        // ANCHOR getAllUniqueTypeArgsInCardLst
        getAllUniqueTypeArgsInCardLst: function(cards) {
            let typeArgs = new Set(); // Use a Set to keep track of unique values
            cards.forEach((card) => {
                if ('type_arg' in card) {
                    typeArgs.add(card.type_arg);
                }
            });
            return [...typeArgs]; // Convert the set back to an array
        },
        ///////////////////////////////////////////////////
        // ANCHOR decodePlayerLocation
        decodePlayerLocation: function(location_arg) {
            location_arg = Number(location_arg);
            const num_columns = 5;
            const row = Math.floor((location_arg - 1) / num_columns) + 1;
            const column = (location_arg - 1) % num_columns + 1;
            return { row: row, col: column };
        },

        // ANCHOR setupPlayerSelector
        setupPlayerSelector: function(player_character, onClickFunction) {
            for (let row = 1; row <= 2; row++) {
                for (let col = 1; col <= 5; col++) {
                    var div_id = `playerOnPlaymat_${player_character}_${row}_${col}`; 
                    if ( dojo.query(`.js-cardsontable`, div_id).length != 0) {
                        dojo.addClass(div_id, 'available');
                        this.onClickMethod['playerOnPlaymat'][`${row}_${col}`] = dojo.connect($(div_id), 'onclick', this, () => onClickFunction(row, col));
                    }
                }
            }
        },
        // !SECTION - Utility methods


        ///////////////////////////////////////////////////
        // SECTION Player's action
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */
        ///////////////////////////////////////////////////
        // ANCHOR onClickPlayPlayerCard
        onClickPlayPlayerCard: function(card_id, card_type, row, col) {
            for (let row = 1; row <= 2; row++) {
                for (let col = 1; col <= 5; col++) {
                    var div_id = `playerOnPlaymat_me_${row}_${col}`;
                    dojo.disconnect(this.onClickMethod['playerOnPlaymat'][`${row}_${col}`]);
                    this.playerdeck.unselectAll();
                }
            }
            this.ajaxcallwrapper('playPlayerCard', {
                "card_id": card_id,
                "card_type": card_type,
                "player_id": this.getActivePlayerId(),
                "row": row,
                "col": col
            });
        },
        // ANCHOR onPlayCard_PlayerTurn
        onPlayCard_PlayerTurn : function(evt) {
            var items = this.playerdeck.getSelectedItems();
            if (items.length > 0) {
                var card_id = items[0].id;
                var card_type = items[0].type;
                var card_info = this.gamedatas.cards_info.find((card) => card.id == card_type);
                // Can play a card
                if (card_info.type == 'Function') {
                    // function card
                    console.log(`The player card id : ${card_id} and card type: function is played.`);
                    this.ajaxcallwrapper('playFunctionCard', {
                        "card_id": card_id,
                        "card_type": card_type,
                        "player_id": this.getActivePlayerId()
                    });
                } else {
                    for (let row = 1; row <= 2; row++) {
                        for (let col = 1; col <= 5; col++) {
                            var div_id = `playerOnPlaymat_me_${row}_${col}`;
                            dojo.addClass(div_id, 'available');
                            this.onClickMethod['playerOnPlaymat'][`${row}_${col}`] = dojo.connect($(div_id), 'onclick', this, () => this.onClickPlayPlayerCard(card_id, card_type, row, col));
                        }
                    }
                }
            }
        },
        // ANCHOR onPlayerHandSelectionChanged
        onPlayerHandSelectionChanged : function( evt ) {
            if (!this.isCurrentPlayerActive()) {
                this.playerdeck.unselectAll();
                return;
            }
            var items = this.playerdeck.getSelectedItems();
            if (items.length > 0) {
                console.log("The player selected the card: ", items);
            } else {
                // Remove the selection for user to play the player to playmat
                for (var row = 1; row <= 2; row++) {
                    for (var col = 1; col <= 5; col++) {
                        var div_id = `playerOnPlaymat_me_${row}_${col}`;
                        dojo.removeClass(div_id, 'available');
                        dojo.disconnect(this.onClickMethod['playerOnPlaymat'][`${row}_${col}`]);
                    }
                }
                
            }
        },
        // ANCHOR onThrowCard_CardActiveEffect
        onThrowCard_CardActiveEffect: function(evt) {
            dojo.stopEvent(evt);
            var items = this.playerdeck.getSelectedItems();
            let player_id = this.getActivePlayerId();
            if (items.length > 0) {
                var items_ids = items.map((item) => Number(item.id));
                console.log("items_ids: ", items_ids);
                // make to check the action
                this.ajaxcallwrapper('throwCard_CardActiveEffect', {
                    "player_id": player_id,
                    "card_ids": JSON.stringify(items_ids),
                });
            }
        },
        // ANCHOR onPass_CardActiveEffect
        onPass_CardActiveEffect: function(evt) {
            dojo.stopEvent(evt);
            this.ajaxcallwrapper('pass_CardActiveEffect');
        },
        // ANCHOR onEightEffect_CardActiveEffect:
        onEightEffect_CardActiveEffect: function(evt) {
            dojo.stopEvent(evt);
            var top_items = this.tempstock.getSelectedItems();
            if (top_items.length == 0 ) { 
                var top_items = [];
            }
            var unselected_items = this.tempstock.getAllItems().filter((item) => !top_items.includes(item));
            this.ajaxcallwrapper('eightEffect_CardActiveEffect', {
                "top_items": JSON.stringify(top_items),
                "bottom_items": JSON.stringify(unselected_items)
            });
        },

        // ANCHOR onGetCard_CardActiveEffect
        onGetCard_CardActiveEffect: function(evt) {
            dojo.stopEvent(evt);
            var items = this.tempstock.getSelectedItems();
            if (items.length > 0) {
                var items_ids = items.map((item) => Number(item.id));
                this.ajaxcallwrapper('getCard_CardActiveEffect', {
                    "card_ids": JSON.stringify(items_ids)
                });
            }
        },
        // ANCHOR onSwapField_CardActiveEffect
        onSwapField_CardActiveEffect: function( row, col ) {
            this.ajaxcallwrapper('swapField_CardActiveEffect', {
                "row": row,
                "col": col
            });
        },
        // ANCHOR onThrowPlayer_CardActiveEffect
        onThrowPlayer_CardActiveEffect: function( row, col ) {
            this.ajaxcallwrapper('throwPlayer_CardActiveEffect', {
                "row": row,
                "col": col
            });
        },
        // ANCHOR onRedCardAfterShoot_CardActiveEffect
        onRedCardAfterShoot_CardActiveEffect: function( row, col ) {
            for (let row = 1; row <= 2; row++) {
                for (let col = 1; col <= 5; col++) {
                    var div_id = `playerOnPlaymat_opponent_${row}_${col}`;
                    dojo.removeClass(div_id, 'available');
                    dojo.disconnect(this.onClickMethod['playerOnPlaymat'][`${row}_${col}`]);
                }
            }
            this.ajaxcallwrapper('redCardAfterShoot_CardActiveEffect', {
                "row": row,
                "col": col
            });
        },
        
        // ANCHOR onPickPlayerFromPlaymat2Hand_CardActiveEffect
        onPickPlayerFromPlaymat2Hand_CardActiveEffect: function( row, col ) {
            this.ajaxcallwrapper('pickPlayerFromPlaymat2Hand_CardActiveEffect', {
                "row": row,
                "col": col
            });
        },
                

        // ANCHOR onPickPlayerFromDiscardPile_CardActiveEffect
        onPickPlayerFromDiscardPile_CardActiveEffect: function(evt) {
            dojo.stopEvent(evt);
            var items = this.tempstock.getSelectedItems();
            if (items.length > 0) {
                var selected_player = items[0];
                this.ajaxcallwrapper('pickPlayerFromDiscardPile_CardActiveEffect', {
                    "selected_player": JSON.stringify(selected_player)
                });
            }
        },
        // ANCHOR onPickCardFromDeck2Hand_CardActiveEffect
        onPickCardFromDeck2Hand_CardActiveEffect: function(evt) {
            dojo.stopEvent(evt);
            var items = this.tempstock.getSelectedItems();
            if (items.length > 0) {
                var items_ids = items.map((item) => Number(item.id));
                this.ajaxcallwrapper('pickCardFromDeck2Hand_CardActiveEffect', {
                    "card_ids": JSON.stringify(items_ids)
                });
            }
        },
        // ANCHOR onRedCard_CardActiveEffect
        onRedCard_CardActiveEffect: function(row, col) {
            this.ajaxcallwrapper('redCard_CardActiveEffect', {
                "row": row,
                "col": col
            });
        },
        // ANCHOR onShoot_PlayerTurn
        onShoot_PlayerTurn: function(evt) {
            dojo.stopEvent(evt);
            this.ajaxcallwrapper('shoot_playerTurn');
        },
        // ANCHOR onPass_PlayerTurn
        onPass_PlayerTurn: function(evt) {
            dojo.stopEvent(evt);
            this.ajaxcallwrapper('pass_playerTurn');
        },
        // ANCHOR onThrowPlayer_PlayerTurn
        onThrowPlayer_playerTurn: function(evt) {
            dojo.stopEvent(evt);
            this.ajaxcallwrapper('throwPlayer_playerTurn');
        },

        // ANCHOR onIntercept_counterattack
        onIntercept_counterattack: function(evt) {
            dojo.stopEvent(evt);
            this.ajaxcallwrapper('intercept_counterattack');
        },
        // ANCHOR onPass_counterattack
        onPass_counterattack: function(evt) {
            dojo.stopEvent(evt);
            this.ajaxcallwrapper('pass_counterattack');
        },

        // ANCHOR onThrowCard_throwCard
        onThrowCard_throwCard: function(evt) {
            dojo.stopEvent(evt);
            var player_id = this.getActivePlayerId();
            var items = this.playerdeck.getSelectedItems();
            if (items.length > 0) {
                var items_ids = items.map((item) => Number(item.id));
                // make to check the action
                this.ajaxcallwrapper('throwCard_throwCard', {
                    "player_id": player_id,
                    "card_ids": JSON.stringify(items_ids),
                });
            }
        },
        // ANCHOR onThrowCard_pass
        onPass_throwCard: function(evt) {
            dojo.stopEvent(evt);
            this.ajaxcallwrapper('pass_throwCard');
        },

        // ANCHOR onRedCard_redcard
        onRedCard_redcard: function(evt) {
            dojo.stopEvent(evt);
            for (let row = 1; row <= 2; row++) {
                for (let col = 1; col <= 5; col++) {
                    var div_id = `playerOnPlaymat_opponent_${row}_${col}`;
                    dojo.removeClass(div_id, 'available');
                    dojo.disconnect(this.onClickMethod['playerOnPlaymat'][`${row}_${col}`]);
                }
            }
            this.ajaxcallwrapper('redcard_redcard');
        },
        // ANCHOR onPass_redcard
        onPass_redcard: function(evt) {
            dojo.stopEvent(evt);
            this.ajaxcallwrapper('pass_redcard');
        },

        // ANCHOR onSkill_playerTurn
        onSkill_playerTurn: function(evt) {
            dojo.stopEvent(evt);
            this.ajaxcallwrapper('skill_playerTurn');
        },
        // ANCHOR onCatPowerUp_skill
        onCatPowerUp_skill: function(evt) {
            dojo.stopEvent(evt);
            this.ajaxcallwrapper('catPowerUp_skill');
        },
        // ANCHOR onCatProductivityUp_skill
        onCatProductivityUp_skill: function(evt) {
            dojo.stopEvent(evt);
            this.ajaxcallwrapper('catProductivityUp_skill');
        },
        // ANCHOR onSquirrelLookAt_skill
        onSquirrelLookAt_skill: function(evt) {
            dojo.stopEvent(evt);
            this.ajaxcallwrapper('squirrelLookAt_skill');
        },
        // ANCHOR onSquirrelSearch_skill
        onSquirrelSearch_skill: function(evt) {
            dojo.stopEvent(evt);
            this.ajaxcallwrapper('squirrelSearch_skill');
        },
        // ANCHOR onBack_skill
        onBack_skill: function(evt) {
            dojo.stopEvent(evt);
            this.ajaxcallwrapper('back_skill');
        },

        // !SECTION Player's action
        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications
        // SECTION Notifications
        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your aniversus.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            // here, associate your game notifications with local methods
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 
            // Action Notifications
            dojo.subscribe('playFunctionCard', this, "notif_playFunctionCard");
            this.notifqueue.setSynchronous('playFunctionCard', 1500);
            dojo.subscribe('updatePlayerBoard', this, "notif_updatePlayerBoard");
            dojo.subscribe('playPlayerCard', this, "notif_playPlayerCard");
            this.notifqueue.setSynchronous('playPlayerCard', 1500);
            dojo.subscribe('cardDrawn', this, "notif_cardDrawn");
            this.notifqueue.setSynchronous('cardDrawn', 500);
            dojo.subscribe('cardThrown', this, "notif_cardThrown");
            this.notifqueue.setSynchronous('cardThrown', 500);
            dojo.subscribe('shoot_roll', this, "notif_shoot_roll");
            this.notifqueue.setSynchronous('shoot_roll', 8000);
            dojo.subscribe('showCardsOnTempStock', this, "notif_showCardsOnTempStock");
            this.notifqueue.setSynchronous('showCardsOnTempStock', 1000);
            dojo.subscribe('terminateTempStock', this, "notif_terminateTempStock");
            this.notifqueue.setSynchronous('terminateTempStock', 500);
            dojo.subscribe('movePlayerInPlaymat2Discard', this, "notif_movePlayerInPlaymat2Discard");
            this.notifqueue.setSynchronous('movePlayerInPlaymat2Discard', 500);
            dojo.subscribe('movePlayerInPlaymat2Hand', this, "notif_movePlayerInPlaymat2Hand");
            this.notifqueue.setSynchronous('movePlayerInPlaymat2Hand', 500);
            dojo.subscribe('unselectAll', this, "notif_unselectAll");
            dojo.subscribe('removePlaymatClickAvailable', this, "notif_removePlaymatClickAvailable");
            dojo.subscribe('addIneffectiveCard', this, "notif_addIneffectiveCard");
            dojo.subscribe('removeIneffectiveCard', this, "notif_removeIneffectiveCard");
            dojo.subscribe('enableShootBtnPlayerTurn', this, "notif_enableShootBtnPlayerTurn")
            dojo.subscribe('catSkillUpdateShootingNumber', this, "notif_catSkillUpdateShootingNumber");
            // broadcast notifications
            dojo.subscribe('broadcast', this, "notif_broadcast");
            this.notifqueue.setSynchronous('broadcast', 2000);
        },  

        // from this point and below, you can write your game notifications handling methods
        // This Notification is called when the shooting is successful
        // SECTION ACTION NOTIFICATIONS
        // ANCHOR updatePlayerBoard
        notif_updatePlayerBoard: function( notif ) {
            console.log(`**** Notification: updatePlayerBoard `)
            console.log(notif);
            const player_id = String(notif.args.player_id);
            const player_productivity = Number(notif.args.player_productivity);
            const player_action = Number(notif.args.player_action);
            const player_score = Number(notif.args.player_score);
            const player_power = Number(notif.args.player_power);
            const player_handCardNumber = Number(notif.args.player_handCardNumber);
            const player_deckCardNumber = Number(notif.args.player_deckCardNumber);
            const cannotdraw = Number(notif.args.cannotdraw);
            const suspension = Number(notif.args.suspension);
            const actionup = Number(notif.args.actionup);
            const energydeduct = Number(notif.args.energydeduct);
            const comeback = Number(notif.args.comeback);
            var shootNum_lst = notif.args.shootNum_lst;
            // Update all information in the player board
            this.playerCounter[player_id]['productivity'].toValue(player_productivity);
            this.playerCounter[player_id]['productivity_playmat'].toValue(player_productivity);
            this.playerCounter[player_id]['action'].toValue(player_action);
            this.scoreCtrl[player_id].setValue(player_score);
            this.playerCounter[player_id]['power'].toValue(player_power);
            this.playerCounter[player_id]['power_playmat'].toValue(player_power);
            this.playerCounter[player_id]['handCardNumber'].toValue(player_handCardNumber);
            this.playerCounter[player_id]['deckCardNumber'].toValue(player_deckCardNumber);
            if ( player_id == this.player_id ) {
                this.playerCounter[player_id]['status_cannotdraw'].toValue(cannotdraw);
                if (cannotdraw == 0) {
                    dojo.addClass('cannotdraw_container', 'status_noeffect')
                } else {
                    dojo.removeClass('cannotdraw_container', 'status_noeffect')
                }
                this.playerCounter[player_id]['status_suspension'].toValue(suspension);
                if (suspension == 0) {
                    dojo.addClass('suspension_container', 'status_noeffect')
                } else {
                    dojo.removeClass('suspension_container', 'status_noeffect')
                }
                this.playerCounter[player_id]['status_actionup'].toValue(actionup);
                if (actionup == 0) {
                    dojo.addClass('actionup_container', 'status_noeffect')
                } else {
                    dojo.removeClass('actionup_container', 'status_noeffect')
                }
                this.playerCounter[player_id]['status_energydeduct'].toValue(energydeduct);
                if (energydeduct == 0) {
                    dojo.addClass('energydeduct_container', 'status_noeffect')
                } else {
                    dojo.removeClass('energydeduct_container', 'status_noeffect')
                }
                this.playerCounter[player_id]['status_comeback'].toValue(comeback);
                if (comeback == 0) {
                    dojo.addClass('comeback_container', 'status_noeffect')
                } else {
                    dojo.removeClass('comeback_container', 'status_noeffect')
                }
            }
            // update shooting number list
            shootNum_lst = [...new Set(shootNum_lst)].sort((a, b) => a - b);
            let shootNum_text = '';
            for (let i = 0; i < shootNum_lst.length; i++) {
                if ( Number(shootNum_lst[i]) <= 12 ) {
                    shootNum_text += `${shootNum_lst[i]}, `;
                }
            }
            shootNum_text = shootNum_text.slice(0, -2);
            $(`player_shootNum_${player_id}`).textContent = shootNum_text;
        },
        // ANCHOR movePlayerInPlaymat2Discard
        notif_movePlayerInPlaymat2Discard: function(notif) {
            console.log(`**** Notification: movePlayerInPlaymat2Discard `)
            console.log(notif);
            const player_id = notif.args.player_id;
            const card_id = notif.args.card_id;
            const card_type = notif.args.card_type;
            const row = notif.args.row;
            const col = notif.args.col;
            if (player_id == this.player_id) {
                // this.getJstplCard(player_id, card_id, card_type, card_div, 'discardPile_field_me');
                this.getJstplCard(player_id, card_id, card_type, `playerOnPlaymat_me_${row}_${col}`, 'discardPile_field_me', true);
                this.playerOnPlaymat['me'][row][col].removeFromZone('cardsOnTable_' + player_id + '_' + card_id, true, 'discardPile_field_me');
                dojo.style(`playerOnPlaymat_me_${row}_${col}`, 'height', 'auto');
                if (this.playerOnPlaymat['me']['discardpile'].getItemNumber() > 2) {
                    const discard_pile_items = this.playerOnPlaymat['me']['discardpile'].getAllItems();
                    this.playerOnPlaymat['me']['discardpile'].removeFromZone(discard_pile_items[0], true, "player_board_" + player_id);
                }
                this.playerOnPlaymat['me']['discardpile'].placeInZone( `discardOnTable_${player_id}_${card_id}` , 0 );
            } else {
                // this.getJstplCard(player_id, card_id, card_type, card_div, 'discardPile_field_opponent');
                this.getJstplCard(player_id, card_id, card_type, `playerOnPlaymat_opponent_${row}_${col}`, 'discardPile_field_me', true);
                this.playerOnPlaymat['opponent'][row][col].removeFromZone('cardsOnTable_' + player_id + '_' + card_id, true, 'discardPile_field_opponent');
                dojo.style(`playerOnPlaymat_opponent_${row}_${col}`, 'height', 'auto')
                if (this.playerOnPlaymat['opponent']['discardpile'].getItemNumber() > 2) {
                    const discard_pile_items = this.playerOnPlaymat['opponent']['discardpile'].getAllItems();
                    this.playerOnPlaymat['opponent']['discardpile'].removeFromZone(discard_pile_items[0], true, "player_board_" + player_id);
                }
                this.playerOnPlaymat['opponent']['discardpile'].placeInZone( `discardOnTable_${player_id}_${card_id}` , 0 );
            }
            this.addTooltipHtml(`discardOnTable_${player_id}_${card_id}`, this.getTooltipHtml(Number(card_type)));
        },
        // ANCHOR movePlayerInPlaymat2Hand
        notif_movePlayerInPlaymat2Hand: function(notif) {
            console.log(`**** Notification: movePlayerInPlaymat2Hand `)
            console.log(notif);
            const player_id = notif.args.player_id;
            const card_id = notif.args.card_id;
            const card_type = notif.args.card_type;
            const row = notif.args.row;
            const col = notif.args.col;
            if (player_id == this.player_id) {
                this.playerdeck.addToStockWithId(card_type, card_id);
                this.playerOnPlaymat['me'][row][col].removeFromZone(`cardsOnTable_${player_id}_${card_id}`, true, `myhand_item_${card_id}`);
            } else {
                this.playerOnPlaymat['opponent'][row][col].removeFromZone(`cardsOnTable_${player_id}_${card_id}`, true, `player_board_${player_id}`);
            }
        },
        // ANCHOR playFunctionCard
        // This Notification is called when a function card is played
        notif_playFunctionCard: function( notif ) {
            console.log(`**** Notification: playFunctionCard `)
            console.log(notif);
            // Input: player_id, card_id, card_type
            const player_id = notif.args.player_id;
            const card_id = notif.args.card_id;
            const card_type = notif.args.card_type;
            const unixtime = notif.args.time;
            if (player_id == this.player_id) {
                this.getJstplCard(player_id, card_id, card_type, 'myhand_item_' + card_id, 'discardPile_field_me', true);
                this.playerdeck.removeFromStockById(card_id);
                if (this.playerOnPlaymat['me']['discardpile'].getItemNumber() > 2) {
                    const discard_pile_items = this.playerOnPlaymat['me']['discardpile'].getAllItems();
                    this.playerOnPlaymat['me']['discardpile'].removeFromZone(discard_pile_items[0], true, "player_board_" + player_id);
                }
                this.playerOnPlaymat['me']['discardpile'].placeInZone( `discardOnTable_${player_id}_${card_id}` , 0 );
            } else {
                this.getJstplCard(player_id, card_id, card_type, 'player_board_' + player_id, 'discardPile_field_opponent', true);
                if (this.playerOnPlaymat['opponent']['discardpile'].getItemNumber() > 2) {
                    const discard_pile_items = this.playerOnPlaymat['opponent']['discardpile'].getAllItems();
                    this.playerOnPlaymat['opponent']['discardpile'].removeFromZone(discard_pile_items[0], true, "player_board_" + player_id);
                }
                this.playerOnPlaymat['opponent']['discardpile'].placeInZone( `discardOnTable_${player_id}_${card_id}` , 0 );
            }
            this.addTooltipHtml(`discardOnTable_${player_id}_${card_id}`, this.getTooltipHtml(Number(card_type)));
            if ( notif.args.time != null ) {
                this.addTooltipHtml('logcard_' + unixtime + '_' + card_type, this.getTooltipHtml(Number(card_type)));
            }
        },
        // ANCHOR playPlayerCard
        notif_playPlayerCard: function( notif ){
            console.log(`**** Notification: playPlayerCard `)
            console.log(notif);
            // get all args
            const player_id = notif.args.player_id;
            const card_id = notif.args.card_id;
            const card_type = notif.args.card_type;
            const row = notif.args.row;
            const col = notif.args.col;
            const allPlayerInThisPositionJson = notif.args.allPlayerInThisPosition;
            // opponent part
            const allPlayerInThisPosition = JSON.parse(allPlayerInThisPositionJson);
            if (this.player_id != player_id) {
                this.getJstplCard(player_id, card_id, card_type, 'player_board_' + player_id, `playerOnPlaymat_opponent_${row}_${col}`);
                this.playerOnPlaymat['opponent'][row][col].placeInZone('cardsOnTable_' + player_id + '_' + card_id, 0);
            } else if (this.player_id == player_id) {
                // me part
                if ($('myhand_item_' + card_id)) {
                    this.getJstplCard(player_id, card_id, card_type, 'myhand_item_' + card_id, `playerOnPlaymat_me_${row}_${col}`);
                    this.playerdeck.removeFromStockById(card_id);
                    console.log(`This handled about the position of row: ${row} and col: ${col}`)
                    this.playerOnPlaymat['me'][row][col].placeInZone('cardsOnTable_' + player_id + '_' + card_id, 0);
                    for (let row = 1; row <= 2; row++) {
                        for (let col = 1; col <= 5; col++) {
                            var div_id = `playerOnPlaymat_me_${row}_${col}`;
                            dojo.removeClass(div_id, 'available');
                        }
                    }
                    this.playerdeck.unselectAll();
                }
            }
            this.addTooltipHtml('cardsOnTable_' + player_id + '_' + card_id, this.joinTooltipHtml(allPlayerInThisPosition));
            if ( notif.args.time != null ) {
                this.addTooltipHtml('logcard_' + notif.args.time + '_' + card_type, this.getTooltipHtml(Number(card_type)));
            }
        },
        // ANCHOR cardDrawn
        // This Notification is called when a card is drawn
        notif_cardDrawn: function( notif ) {
            console.log(`**** Notification: cardDrawn `)
            console.log(notif);
            const cards = notif.args.cards;
            for (let i in cards) {
                var card = cards[i];
                this.getCard2hand(card);
            }
        },
        // This Notification is called when a card is thrown
        // ANCHOR cardThrown
        notif_cardThrown: function( notif ) {
            console.log(`**** Notification: cardThrown `)
            console.log(notif);
            const player_id = notif.args.player_id;
            const card_id = notif.args.card_id;
            const card_type_arg = notif.args.card_type_arg;
            this.getJstplCard(player_id, card_id, card_type_arg, 'myhand_item_' + card_id, 'discardPile_field_me', true);
            this.playerOnPlaymat['me']['discardpile'].placeInZone( `discardOnTable_${player_id}_${card_id}`, 0);
            this.playerdeck.removeFromStockById(Number(card_id));
        },

        // ANCHOR shoot_roll
        notif_shoot_roll: function( notif ) {
            console.log(`**** Notification: shoot_roll `)
            console.log(notif);
            const player_id = notif.args.player_id;
            // show the roll dice
            this.other.rollDice(notif.args.diceOne, notif.args.diceTwo);
            let diceTotal = Number(notif.args.diceOne) + Number(notif.args.diceTwo);
            dojo.place(this.format_block('jstpl_rollValue', {
                player_id: player_id,
                rollValue: diceTotal
            }), "roll_result");
            dojo.addClass('roll_result', 'roll-result');
        },
        // ANCHOR showCardsOnTempStock
        notif_showCardsOnTempStock: function( notif ) {
            console.log(`**** Notification: showCardsOnTempStock `)
            console.log(notif);
            const cards = notif.args.cards;
            const card_type_arg = notif.args.card_type_arg;
            var message;
            var button_text;
            var selected_mode = 0;
            switch (card_type_arg) {
                case 8:
                    const card_id = notif.args.card_id;
                    if ( card_id == 4058 ) {
                        message = "Select the cards that you want to place on top of your opponent's draw deck; the remaining cards will be placed at the bottom."
                    } else {
                        message = 'Select the cards that you want to place on top of your draw deck; the remaining cards will be placed at the bottom.';
                    }
                    button_text = 'Confirm';
                    selected_mode = 2;
                    break;
                case 11:
                    message = 'Select a card from your discard pile and put it in your hand';
                    button_text = 'Confirm';
                    selected_mode = 1;
                    break;
                case 57:
                    message = "Select 2 cards from your discard pile and put them in your hand"
                    button_text = 'Confirm';
                    selected_mode = 1;
                    break;
                case 112:
                    message = "Select 2 cards from your draw deck and this card will be put on your hand"
                    button_text = 'Confirm';
                    selected_mode = 2;
                    break;
                case 40512:
                    message = "Select 2 cards from your draw desk and put them in your hand"
                    button_text = 'Confirm';
                    selected_mode = 2;
                    break;
                default:
                    message = "Please define the message for this card type"
                    button_text = 'Confirm';
                    selected_mode = 0;
            }
            dojo.place(this.format_block('jstpl_tempCardStock', {
                'message': message,
                'buttonText': button_text
            }), 'tempstock-area');
            this.tempstock = this.setNewCardStock('tempCardStock', selected_mode, 'onPlayerHandSelectionChanged');
            this.tempstock.extraClasses='rounded';
            dojo.addClass('tempstock-area', 'whiteblock');
            for (let i in cards) {
                var card = cards[i];
                this.tempstock.addToStockWithId(Number(card.type_arg), card.id);
                this.addTooltipHtml('tempstock_item_' + card.id, this.getTooltipHtml(Number(card.type_arg)));
            }
            switch (card_type_arg) {
                case 8:
                    dojo.connect($('tempStockButton'), 'onclick', this, 'onEightEffect_CardActiveEffect');
                    break;
                case 11:
                    dojo.connect($('tempStockButton'), 'onclick', this, 'onPickPlayerFromDiscardPile_CardActiveEffect');
                    break;
                case 57:
                    dojo.connect($('tempStockButton'), 'onclick', this, 'onGetCard_CardActiveEffect');
                    break;
                case 112:
                    dojo.connect($('tempStockButton'), 'onclick', this, 'onPickCardFromDeck2Hand_CardActiveEffect');
                    break;
                case 40512:
                    dojo.connect($('tempStockButton'), 'onclick', this, 'onPickCardFromDeck2Hand_CardActiveEffect');
                    break;
                default:
                    break;
            }
        },

        // ANCHOR terminateTempStock
        notif_terminateTempStock: function( notif ) {
            console.log(`**** Notification: terminateTempStock `)
            console.log(notif);
            this.tempstock = null;
            dojo.destroy('tempStock');
            dojo.removeClass('tempstock-area', 'whiteblock');
            this.notif_removePlaymatClickAvailable({});
        },

        // ANCHOR removePlaymatClickAvailable
        notif_removePlaymatClickAvailable: function( ) {
            console.log(`**** Notification: removePlaymatClickAvailable `)
            for (let row = 1; row <= 2; row++) {
                for (let col = 1; col <= 5; col++) {
                    const me_div_id = `playerOnPlaymat_me_${row}_${col}`;
                    const opponent_div_id = `playerOnPlaymat_opponent_${row}_${col}`;
                    if ( this.onClickMethod['playerOnPlaymat'][`${row}_${col}`] ?? null != null) {
                        dojo.disconnect(this.onClickMethod['playerOnPlaymat'][`${row}_${col}`]);
                    }
                    dojo.removeClass(me_div_id, 'available');
                    dojo.removeClass(opponent_div_id, 'available');
                }
            }
        },

        // ANCHOR unselectAll
        notif_unselectAll: function( notif ) {
            console.log(`**** Notification: unselectAll `)
            this.playerdeck.unselectAll();
        },

        // ANCHOR addIneffectiveCard
        notif_addIneffectiveCard: function( notif ) {
            console.log(`**** Notification: addIneffectiveCard `)
            console.log(notif);
            const row = notif.args.row;
            const col = notif.args.col;
            const player_id = notif.args.player_id;
            if ( player_id == this.player_id ) {
                const allCardsInThisPosition = dojo.query(`#playerOnPlaymat_me_${row}_${col} .js-cardsontable`);
                for (let i = 0; i < allCardsInThisPosition.length; i++) {
                    dojo.addClass(allCardsInThisPosition[i], 'ineffective');
                }
            } else {
                const allCardsInThisPosition = dojo.query(`#playerOnPlaymat_opponent_${row}_${col} .js-cardsontable`);
                for (let i = 0; i < allCardsInThisPosition.length; i++) {
                    dojo.addClass(allCardsInThisPosition[i], 'ineffective');
                }
            }
        },

        // ANCHOR removeIneffectiveCard
        notif_removeIneffectiveCard: function( notif ) {
            console.log(`**** Notification: removeIneffectiveCard `)
            console.log(notif);
            const row = notif.args.row;
            const col = notif.args.col;
            const player_id = notif.args.player_id;
            if ( player_id == this.player_id ) {
                const allCardsInThisPosition = dojo.query(`#playerOnPlaymat_me_${row}_${col} .js-cardsontable`);
                if (allCardsInThisPosition.length > 0) {
                    for (let i = 0; i < allCardsInThisPosition.length; i++) {
                        dojo.removeClass(allCardsInThisPosition[i], 'ineffective');
                    }
                }
            } else {
                const allCardsInThisPosition = dojo.query(`#playerOnPlaymat_opponent_${row}_${col} .js-cardsontable`);
                if (allCardsInThisPosition.length > 0) {
                    for (let i = 0; i < allCardsInThisPosition.length; i++) {
                        dojo.removeClass(allCardsInThisPosition[i], 'ineffective');
                    }
                }
            }
        },
        // ANCHOR enableShootBtnPlayerTurn
        notif_enableShootBtnPlayerTurn: function( notif ) {
            console.log(`**** Notification: enableShootBtnPlayerTurn `)
            console.log(notif);
            dojo.removeClass('playerTurn_btn_shoot', 'disabled');
        },

        notif_catSkillUpdateShootingNumber: function( notif ) {
            console.log(`**** Notification: catSkillUpdateShootingNumber `)
            console.log(notif);
            const player_id = notif.args.player_id;
            var shootNum_lst = notif.args.shootNum_lst;
            let shootNum_text = '';
            shootNum_lst = [...new Set(shootNum_lst)].sort((a, b) => a - b);
            for (let i = 0; i < shootNum_lst.length; i++) {
                if ( Number(shootNum_lst[i]) <= 12 ) {
                    shootNum_text += `${shootNum_lst[i]}, `;
                }
            }
            shootNum_text = shootNum_text.slice(0, -2);
            $(`player_shootNum_${player_id}`).textContent = shootNum_text;
        },
        // !SECTION ACTION NOTIFICATIONS
        // ------------------------------------- End of ACTION Notifications -------------------------------------------- //
        // SECTION BROADCAST NOTIFICATIONS
        //ANCHOR broadcast
        notif_broadcast: function(notif) {
            console.log(`**** Notification: broadcast `)
            console.log(notif);
            const message = notif.args.message;
            console.log(`The message is: ${message}`)

            const type = notif.args.type;
            console.log(`The type is: ${type}`)
            this.showMessage(message, type);
        },
        
        // !SECTION BROADCAST NOTIFICATIONS
        // !SECTION Notifications
   });             
});
