/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Aniversus implementation : © <Your name here> <Your email address here>
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
            // add reload Css debug button ( for development ) //////////////////////////////////////////////////////////
            ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
            var parent = document.querySelector('.debug_section');
            if (parent) {
                var butt = dojo.create('a', { class: 'bgabutton bgabutton_gray', innerHTML: "Reload CSS" }, parent);
                dojo.connect(butt, 'onclick', () => reloadCss());
            }
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////
            ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
            // ANCHOR Setting up player boards
            this.playerCounter = {};
            for( let player_id in gamedatas.players )
            {
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
                this.playerCounter[player_id]['handCardNumber'].create( `player_drawDeck_${player_id}` );
                this.playerCounter[player_id]['handCardNumber'].setValue(gamedatas.hand_card_number[player_id]);
            }

            /////////////////////////////////////////////////////////////////////////////////////////////////////////////
            ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
            // ANCHOR Set up your game interface here, according to "gamedatas"
            // Player hand Setup
            // player hand
            this.playerdeck = this.setNewCardStock('myhand', 1, 'onPlayerHandSelectionChanged');
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
                        this.getJstplCard(player_id, card.id, card.type_arg, 'player_board_' + player_id, 'discardPile_field_me');
                        this.playerOnPlaymat['me']['discardpile'].placeInZone( 'cardsOnTable_' + player_id + '_' + card.id, 0 );
                    });
                    ////
                    //// Add cards to my playmat
                    this.gamedatas['players'][player_id]['playmat'].forEach((card) => {
                        let card_position = this.decodePlayerLocation(card.location_arg);
                        this.getJstplCard(player_id, card.id, card.type_arg, 'player_board_' + player_id, `playerOnPlaymat_me_${card_position.row}_${card_position.col}`);
                        this.playerOnPlaymat['me'][card_position.row][card_position.col].placeInZone( 'cardsOnTable_' + player_id + '_' + card.id, 0 );
                    });
                    
                } else {
                    //// Opponent Discard pile
                    let topThreeDiscardPile_opponent = this.gamedatas['players'][player_id]['discardpile'].slice(0, 3);
                    topThreeDiscardPile_opponent.forEach((card) => {
                        this.getJstplCard(player_id, card.id, card.type_arg, 'player_board_' + player_id, 'discardPile_field_opponent');
                        this.playerOnPlaymat['opponent']['discardpile'].placeInZone( 'cardsOnTable_' + player_id + '_' + card.id, 0 );
                    });
                    //// Opponent playmat
                    //// Add cards to the playmat
                    this.gamedatas['players'][player_id]['playmat'].forEach((card) => {
                        let card_position = this.decodePlayerLocation(card.location_arg);
                        this.getJstplCard(player_id, card.id, card.type_arg, 'player_board_' + player_id, `playerOnPlaymat_opponent_${card_position.row}_${card_position.col}`);
                        this.playerOnPlaymat['opponent'][card_position.row][card_position.col].placeInZone( 'cardsOnTable_' + player_id + '_' + card.id, 0 );
                    });
                }
            }
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////
            // special case setup
            const gamestate = this.gamedatas.gamestate_name;
            switch (gamestate) {
                case 'cardActiveEffect':
                    if (this.gamedatas.playing_card[this.player_id]['disabled'] == 0 && this.gamedatas.playing_card[this.player_id]['card_type_arg'] == 8) {
                        this.notif_showCardsOnTempStock({args: {'card_type_arg': 8, 'cards': JSON.parse(this.gamedatas.playing_card[this.player_id]['card_info'])}});
                    }
                    break;
                default:
                    break;
            }
            ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
            // ANCHOR Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

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
                    for (let row = 1; row <= 2; row++) {
                        for (let col = 1; col <= 5; col++) {
                            var div_id = `playerOnPlaymat_opponent_${row}_${col}`; 
                            if ( dojo.query(`.js-cardsontable`, div_id).length != 0) {
                                dojo.addClass(div_id, 'available');
                                this.onClickMethod['playerOnPlaymat'][`${row}_${col}`] = dojo.connect($(div_id), 'onclick', this, () => this.onRedCard_redcard());
                            }
                        }
                    }
                    break;
                case 8:
                    this.playerdeck.setSelectionMode(0);
                    break;
                case 11:
                    this.playerdeck.setSelectionMode(0);
                    for (let row = 1; row <= 2; row++) {
                        for (let col = 1; col <= 5; col++) {
                            var div_id = `playerOnPlaymat_me_${row}_${col}`; 
                            if ( dojo.query(`.js-cardsontable`, div_id).length != 0) {
                                dojo.addClass(div_id, 'available');
                                this.onClickMethod['playerOnPlaymat'][`${row}_${col}`] = dojo.connect($(div_id), 'onclick', this, () => this.onSwapField_CardActiveEffect(row, col));
                            }
                        }
                    }
                    break;
                case 56:
                    this.playerdeck.setSelectionMode(2);
                    break;
                case 64:
                    this.playerdeck.setSelectionMode(1);
                    break;
                case 108:
                    this.playerdeck.setSelectionMode(0);
                    for (let row = 1; row <= 2; row++) {
                        for (let col = 1; col <= 5; col++) {
                            var div_id = `playerOnPlaymat_me_${row}_${col}`; 
                            if ( dojo.query(`.js-cardsontable`, div_id).length != 0) {
                                dojo.addClass(div_id, 'available');
                                this.onClickMethod['playerOnPlaymat'][`${row}_${col}`] = dojo.connect($(div_id), 'onclick', this, () => this.onSwapField_CardActiveEffect(row, col));
                            }
                        }
                    }
                    break;
                case 109:
                    this.playerdeck.setSelectionMode(2);
                    break;
                case 112:
                    this.playerdeck.setSelectionMode(1);
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
                    this.addActionButton( 'cardActiveEffect_btn_throw', _('Throw'), 'onThrowCard_CardActiveEffect' );
                    this.addActionButton( 'cardActiveEffect_btn_pass', _('Pass'), 'onPass_CardActiveEffect' );
                    let button_list = JSON.parse(args.button_list);
                    if (!(button_list.includes(1))) {
                        dojo.addClass('cardActiveEffect_btn_throw', 'disabled');
                    }
                    break;
                case 'playerTurn':
                    this.addActionButton( 'playerTurn_btn_play', _('Play'), 'onPlayCard_PlayerTurn' );
                    this.addActionButton( 'playerTurn_btn_pass', _('Pass'), 'onPass_PlayerTurn' );
                    this.addActionButton( 'playerTurn_btn_shoot', _('Shoot'), 'onShoot_PlayerTurn' );
                    if (this.playerCounter[this.player_id]['action'].getValue() == 0) {
                        dojo.addClass('playerTurn_btn_play', 'disabled');
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
                    break;
                case 'throwCard':
                    this.addActionButton( 'throwCard_btn_throw', _('Throw'), 'onThrowCard_throwCard' );
                    this.addActionButton( 'throwCard_btn_pass', _('Pass'), 'onPass_throwCard' );
                    if (args.thrownumber > 0) {
                        dojo.addClass('throwCard_btn_pass', 'disabled');
                    } else {
                        dojo.addClass('throwCard_btn_throw', 'disabled');
                    }
                    break;
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

        // ANCHOR getCard2hand
        getCard2hand: function(card) {
            this.playerdeck.addToStockWithId(Number(card.type_arg), card.id);
            this.addTooltipHtml('myhand_item_' + card.id, this.getTooltipHtml(Number(card.type_arg)));
        },
        // ANCHOR getJstplCard
        getJstplCard: function(player_id, card_id, card_type, from, to) {
            const position = this.getCardBackgroundPosition(card_type);
            // create card on table
            dojo.place( this.format_block('jstpl_cardsOnTable', {
                player_id: player_id,
                card_id: card_id,
                ...position
            }), to);
            var cardOnTable_id = `cardsOnTable_${player_id}_${card_id}`
            // place the card on the player board
            this.placeOnObject($(cardOnTable_id), from);
            // slide the card to the table
            this.attachToNewParent(cardOnTable_id, to);
        },
        // ANCHOR getTooltipHtml
        getTooltipHtml: function(card_id) {
            var card = this.gamedatas.cards_info.find((card) => card.id == card_id);
            var card_name = card.name;
            var card_type = card.type;
            var card_cost = card.cost;
            var card_productivity = (card_type == "Function") ? "NA" : card.productivity;
            var card_power = (card_type == "Function") ? "NA" : card.power;
            var card_description = (card.function == "") ? "NA" : card.function;
            return this.format_block('jstpl_cardToolTip', {
                card_name: card_name,
                card_type: card_type,
                card_cost: card_cost,
                card_productivity: card_productivity,
                card_power: card_power,
                card_description: card_description
            });
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
        setNewCardStock: function( div_id, selectionMode, onSelectionChangedMethod ) {
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
            newstock.item_margin = 13; // Add margin between cards
            // Create cards types:
            // addItemType(type: number, weight: number, image: string, image_position: number )

            this.gamedatas.cards_info.forEach((key) => {
                newstock.addItemType(Number(key.id), 1, 
                    "https://novbeestoragejp.blob.core.windows.net/bga-aniversus-img/sprite_sheet.png", Number(key.css_position));
                    
            });
            // setup connect
            dojo.connect( newstock, 'onChangeSelection', this, onSelectionChangedMethod );

            return newstock;
        },
        ///////////////////////////////////////////////////
        decodePlayerLocation: function(location_arg) {
            location_arg = Number(location_arg);
            const num_columns = 5;
            const row = Math.floor((location_arg - 1) / num_columns) + 1;
            const column = (location_arg - 1) % num_columns + 1;
            return { row: row, col: column };
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
                    this.onClickMethod['playerOnPlaymat'] = {};
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
            dojo.stopEvent(evt);
            this.ajaxcallwrapper('swapField_CardActiveEffect', {
                "row": row,
                "col": col
            });
        },
        // ANCHOR onPickPlayerFromPlaymat2Hand_CardActiveEffect
        onPickPlayerFromPlaymat2Hand_CardActiveEffect: function(evt) {
            dojo.stopEvent(evt);
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
            this.ajaxcallwrapper('redCard_redcard');
        },
        // ANCHOR onPass_redcard
        onPass_redcard: function(evt) {
            dojo.stopEvent(evt);
            this.ajaxcallwrapper('pass_redcard');
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
            this.notifqueue.setSynchronous('playFunctionCard', 1000);
            dojo.subscribe('updatePlayerBoard', this, "notif_updatePlayerBoard");
            dojo.subscribe('playPlayerCard', this, "notif_playPlayerCard");
            dojo.subscribe('cardDrawn', this, "notif_cardDrawn");
            dojo.subscribe('cardThrown', this, "notif_cardThrown");
            dojo.subscribe('shoot_roll', this, "notif_shoot_roll");
            this.notifqueue.setSynchronous('shoot_roll', 5000);
            dojo.subscribe('showCardsOnTempStock', this, "notif_showCardsOnTempStock");
            dojo.subscribe('terminateTempStock', this, "notif_terminateTempStock");
            dojo.subscribe('movePlayerInPlaymat2Discard', this, "notif_movePlayerInPlaymat2Discard");
            dojo.subscribe('movePlayerInPlaymat2Hand', this, "notif_movePlayerInPlaymat2Hand");
            // broadcast notifications
            dojo.subscribe('broadcast', this, "notif_broadcast");
        },  
        notif_broadcast: function(notif) {
            console.log(`**** Notification: broadcast `)
            console.log(notif);
        },
        // from this point and below, you can write your game notifications handling methods
        // This Notification is called when the shooting is successful
        // SECTION ACTION NOTIFICATIONS
        // ANCHOR updatePlayerBoard
        notif_updatePlayerBoard: function( notif ) {
            console.log(`**** Notification: updatePlayerBoard `)
            console.log(notif);
            const player_id = notif.args.player_id;
            const player_productivity = notif.args.player_productivity;
            const player_action = notif.args.player_action;
            const score = notif.args.player_score;
            const player_power = notif.args.player_power;
            const player_handCardNumber = notif.args.player_handCardNumber;
            // Update all information in the player board
            this.playerCounter[player_id]['productivity'].toValue(player_productivity);
            this.playerCounter[player_id]['action'].toValue(player_action);
            this.scoreCtrl[player_id].toValue(score);
            this.playerCounter[player_id]['power'].toValue(player_power);
            this.playerCounter[player_id]['handCardNumber'].toValue(player_handCardNumber);
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
                this.playerOnPlaymat['me'][row][col].removeFromZone('cardsOnTable_' + player_id + '_' + card_id, true, "discardPile_field_me");
                this.getJstplCard(player_id, card_id, card_type, `playerOnPlaymat_me_${row}_${col}`, 'discardPile_field_me');
                this.addTooltipHtml($('cardsOnTable_' + player_id + '_' + card_id), this.getTooltipHtml(Number(card_type)));
            } else {
                this.playerOnPlaymat['opponent'][row][col].removeFromZone('cardsOnTable_' + player_id + '_' + card_id, true, "discardPile_field_opponent");
                this.getJstplCard(player_id, card_id, card_type, `playerOnPlaymat_opponent_${row}_${col}`, 'discardPile_field_opponent');
                this.addTooltipHtml($('cardsOnTable_' + player_id + '_' + card_id), this.getTooltipHtml(Number(card_type)));
            }
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
                this.playerOnPlaymat['me'][row][col].removeFromZone('cardsOnTable_' + player_id + '_' + card_id, true, `myhand_item_${card_id}`);
                this.getJstplCard(player_id, card_id, card_type, `playerOnPlaymat_me_${row}_${col}`, `myhand_item_${card_id}`);
                this.addTooltipHtml($('cardsOnTable_' + player_id + '_' + card_id), this.getTooltipHtml(Number(card_type)));
            } else {
                this.playerOnPlaymat['opponent'][row][col].removeFromZone('cardsOnTable_' + player_id + '_' + card_id, true, `myhand_item_${card_id}`);
                this.getJstplCard(player_id, card_id, card_type, `playerOnPlaymat_opponent_${row}_${col}`, `myhand_item_${card_id}`);
                this.addTooltipHtml($('cardsOnTable_' + player_id + '_' + card_id), this.getTooltipHtml(Number(card_type)));
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
            console.log(`card name : ${notif.args.card_name} and player name : ${notif.args.player_name}`)
            if (player_id == this.player_id) {
                this.getJstplCard(player_id, card_id, card_type, 'myhand_item_' + card_id, 'discardPile_field_me');
                this.playerdeck.removeFromStockById(card_id);
                if (this.playerOnPlaymat['me']['discardpile'].getItemNumber() > 2) {
                    this.playerOnPlaymat['me']['discardpile'].removeFromZone(discard_pile_items[0], true, "player_board_" + player_id);
                }
                this.playerOnPlaymat['me']['discardpile'].placeInZone( 'cardsOnTable_' + player_id + '_' + card_id , 0 );
                this.addTooltipHtml($('cardsOnTable_' + player_id + '_' + card_id ), this.getTooltipHtml(Number(card_type)));

            } else {
                this.getJstplCard(player_id, card_id, card_type, 'player_board_' + player_id, 'discardPile_field_opponent');
                if (this.playerOnPlaymat['opponent']['discardpile'].getItemNumber() > 2) {
                    this.playerOnPlaymat['opponent']['discardpile'].removeFromZone(discard_pile_items[0], true, "player_board_" + player_id);
                }
                this.playerOnPlaymat['opponent']['discardpile'].placeInZone( 'cardsOnTable_' + player_id + '_' + card_id , 0 );
                this.addTooltipHtml($('cardsOnTable_' + player_id + '_' + card_id ), this.getTooltipHtml(Number(card_type)));
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
            console.log(`The player id: ${player_id} and card id: ${card_id} and card type: ${card_type} and row: ${row} and col: ${col}`)
            // opponent part
            if (this.player_id != player_id) {
                this.getJstplCard(player_id, card_id, card_type, 'player_board_' + player_id, `playerOnPlaymat_opponent_${row}_${col}`);
                this.playerOnPlaymat['opponent'][row][col].placeInZone('cardsOnTable_' + player_id + '_' + card_id, 0);
                this.addTooltipHtml($('cardsOnTable_' + player_id + '_' + card_id), this.getTooltipHtml(Number(card_type)));
            } else if (this.player_id == player_id) {
                // me part
                if ($('myhand_item_' + card_id)) {
                    this.getJstplCard(player_id, card_id, card_type, 'myhand_item_' + card_id, `playerOnPlaymat_me_${row}_${col}`);
                    this.playerdeck.removeFromStockById(card_id);
                    console.log(`This handled about the position of row: ${row} and col: ${col}`)
                    this.playerOnPlaymat['me'][row][col].placeInZone('cardsOnTable_' + player_id + '_' + card_id, 0);
                    this.addTooltipHtml($('cardsOnTable_' + player_id + '_' + card_id), this.getTooltipHtml(Number(card_type)));
                    for (let row = 1; row <= 2; row++) {
                        for (let col = 1; col <= 5; col++) {
                            var div_id = `playerOnPlaymat_me_${row}_${col}`;
                            dojo.removeClass(div_id, 'available');
                        }
                    }
                    this.playerdeck.unselectAll();
                }
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
            this.getJstplCard(player_id, card_id, card_type_arg, 'myhand_item_' + card_id, 'discardPile_field_me');
            this.playerOnPlaymat['me']['discardpile'].placeInZone('cardsOnTable_' + player_id + '_' + card_id, 0);
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
                    message = 'Select the cards that you want to put on the top of your draw deck. other cards would be put on the bottom.';
                    button_text = 'Confirm';
                    selected_mode = 2;
                    break;
                case 11:
                    message = 'Select a card from your discard pile and put it in your hand.';
                    button_text = 'Confirm';
                    selected_mode = 1;
                    break;
                case 57:
                    message = "Select 3 cards from your discard pile and put them in your hand."
                    button_text = 'Confirm';
                    selected_mode = 2;
                default:
                    break;
            }
            dojo.place(this.format_block('jstpl_tempCardStock', {
                'message': message,
                'buttonText': button_text
            }), 'tempstock-area');
            this.tempstock = this.setNewCardStock('tempCardStock', selected_mode, 'onPlayerHandSelectionChanged');
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
            for (let row = 1; row <= 2; row++) {
                for (let col = 1; col <= 5; col++) {
                    var div_id = `playerOnPlaymat_me_${row}_${col}`;
                    if ( this.onClickMethod['playerOnPlaymat'][`${row}_${col}`] ?? null != null) {
                        dojo.disconnect(this.onClickMethod['playerOnPlaymat'][`${row}_${col}`]);
                    }
                    dojo.removeClass(div_id, 'available');
                    this.onClickMethod['playerOnPlaymat'] = {};
                }
            }
        },
        // !SECTION ACTION NOTIFICATIONS
        // ------------------------------------- End of ACTION Notifications -------------------------------------------- //
        // SECTION BROADCAST NOTIFICATIONS



        // !SECTION BROADCAST NOTIFICATIONS
        // !SECTION Notifications
   });             
});
