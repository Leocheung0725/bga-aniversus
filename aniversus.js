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
    "ebg/zone"
    
],
function (dojo, declare) {
    
    return declare("bgagame.aniversus", ebg.core.gamegui, {
        
        constructor: function(){
            console.log('aniversus constructor');
            // declare card width and height
            this.cardwidth = 124;
            this.cardheight = 174;
            
            
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
            // The card pixel width and height is 472x656
            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;
        },
        
        /*
            setup:
            
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
            // Setting up player boards
            this.playerCounter = {};
            for( let player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                this.playerCounter[player_id] = {};
                // TODO: Setting up players boards if needed
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
            }

            /////////////////////////////////////////////////////////////////////////////////////////////////////////////
            ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
            // TODO: Set up your game interface here, according to "gamedatas"
            // Player hand Setup
            // player hand
            this.playerdeck = new ebg.stock(); // new stock object for hand
            this.playerdeck.create( this, $('myhand'), this.cardwidth, this.cardheight );
            // config stock object
            this.playerdeck.image_items_per_row = 10;
            this.playerdeck.centerItems = true; // Center items (actually i don't know what it does)
            // this.playerdeck.apparenceBorderWidth = '2px'; // Change border width when selected
            this.playerdeck.setSelectionMode(1); // Allow only one card to be selected
            // Create cards types:
            // addItemType(type: number, weight: number, image: string, image_position: number )

            this.gamedatas.cards_info.forEach((key) => {
                this.playerdeck.addItemType(Number(key.id), 1, 
                    "https://novbeestoragejp.blob.core.windows.net/bga-aniversus-img/sprite_sheet.png", Number(key.css_position));
                    
            });
            console.log(this.playerdeck);
            // show hand
            for ( var i in this.gamedatas.hand) {
                var card = this.gamedatas.hand[i];
                this.playerdeck.addToStockWithId(Number(card.type_arg), card.id);
                this.addTooltipHtml('myhand_item_' + card.id, this.getTooltipHtml(Number(card.type_arg)));
            }
            // setup connect
            dojo.connect( this.playerdeck, 'onChangeSelection', this, 'onPlayerHandSelectionChanged' );
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////
            ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
            // playmat setup
            // Discard pile
            this.playerOnPlaymat['me']['discardpile'].create( this, 'discardPile_field_me', this.cardwidth, this.cardheight );
            this.playerOnPlaymat['opponent']['discardpile'].create( this, 'discardPile_field_opponent', this.cardwidth, this.cardheight );
            // player playmat
            for (var player of ['me', 'opponent']) {
                for (var row = 1; row <= 2; row++) {
                    for (var col = 1; col <= 5; col++) {
                        this.playerOnPlaymat[player][row][col].create( this, `playerOnPlaymat_${player}_${row}_${col}`, this.cardwidth, this.cardheight );
                        this.playerOnPlaymat[player][row][col].setPattern('diagonal');
                        this.playerOnPlaymat[player][row][col].item_margin = 5;
                    }
                }
            }
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////
            ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },


        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+ stateName );
            
        //     switch( stateName )
        //     {
            
        //     /* Example:
            
        //     case 'myGameState':
            
        //         // Show some HTML block at this game state
        //         dojo.style( 'my_html_block_id', 'display', 'block' );
                
        //         break;
        //    */
                
                
        //     case 'dummmy':
        //         break;
        //     }
            // Call appropriate method
            var methodName = "onEnteringState_" + stateName;
            if (this[methodName] !== undefined) {
                console.log(`Calling ${methodName}, args: ${this.safeStringify(args.args)}`);
                this[methodName](args.args);
            }
        },

        // onEnteringState_dummmy: function(args) {
        //     console.log('Entering state: dummy');
        //     console.log(args);
        // },
        // -------------------------------------                        -------------------------------------------- //
        // ------------------------------------- End of onEnteringState -------------------------------------------- //


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
                
                
            case 'dummmy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */
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

        getJstplCard: function(player_id, card_id, card_type, from, to) {
            const position = this.getCardBackgroundPosition(card_type);
            // create card on table
            dojo.place( this.format_block('jstpl_cardsOnTable', {
                player_id: player_id,
                card_id: card_id,
                ...position
            }), to);
            // place the card on the player board
            this.placeOnObject('cardsOnTable_' + player_id + '_' + card_id, from);
            // slide the card to the table
            this.slideToObject( 'cardsOnTable_' + player_id + '_' + card_id , to).play();
        },

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


        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */
        // function playCardOnTable( player_id, card_id, card_type, card_type_arg) {
        //     // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        //     // self::checkAction( 'playCardOnTable' ); 
        //     // Add your game logic to play a card there 
            
        //     dojo.place(this.format_block( ($card_type == "function") ? 'jstpl' : 'dkf',  ),
        // );
    
        //     // In any case: move it to its final destination
        //     this.slideToObject('cardontable_' + player_id, 'playertablecard_' + player_id).play();
        // }
        playPlayerCard: function(player_id, card_id, card_type, row, col, evt) {
            dojo.stopEvent( evt );
            // init card type to css position mapping, and get the position
            const position = this.getCardBackgroundPosition(card_type);
            // place the card on the table
            if (player_id != this.player_id) {
                // create card on table
                dojo.place( this.format_block('jstpl_cardsOnTable', {
                    player_id: player_id,
                    card_id: card_id,
                    ...position
                }), 'playerOnPlaymat_opponent_' + `${row}_${col}`);
                // place the card on the player board
                this.placeOnObject('cardsOnTable_' + player_id + '_' + card_id, "player_board_" + player_id);
                // slide the card to the table
                this.slideToObject( 'cardsOnTable_' + player_id + '_' + card_id , 'playerOnPlaymat_opponent_' + `${row}_${col}` ).play();
            } else {
                if ($('myhand_item_' + card_id)) {
                    // create card on table
                    dojo.place( this.format_block('jstpl_cardsOnTable', {
                        player_id: player_id,
                        card_id: card_id,
                        ...position
                    }), `playerOnPlaymat_me_${row}_${col}`);
                    // place the card on the player board
                    this.placeOnObject('cardsOnTable_' + player_id + '_' + card_id, 'myhand_item_' + card_id);
                    this.playerdeck.removeFromStockById(card_id);
                    // slide the card to the table
                    this.slideToObject( 'cardsOnTable_' + player_id + '_' + card_id , `playerOnPlaymat_me_${row}_${col}`).play();
                    console.log(`This handled about the position of row: ${row} and col: ${col}`)
                    this.playerOnPlaymat['me'][row][col].placeInZone('cardsOnTable_' + player_id + '_' + card_id, 0);
                    for (var row = 1; row <= 2; row++) {
                        for (var col = 1; col <= 5; col++) {
                            var div_id = `playerOnPlaymat_me_${row}_${col}`;
                            dojo.removeClass(div_id, 'available');
                        }
                    }
                    this.playerdeck.unselectAll();
                    for (var row = 1; row <= 2; row++) {
                        for (var col = 1; col <= 5; col++) {
                            console.log(`The playerOnPlaymat['me'][${row}][${col}] has ${this.playerOnPlaymat['me'][row][col].getItemNumber()} items.`);
                        }
                    }
                    return;
                }
            }
            // this.playerdeck.removeFromStockById(card_id);
        },



        onPlayerHandSelectionChanged : function(evt) {
            var items = this.playerdeck.getSelectedItems();
            let card_id = items[0].id;
            let card_type = items[0].type;
            let card_info = this.gamedatas.cards_info.find((card) => card.id == card_type);
            if (items.length > 0) {
                if (this.checkAction('playCard', true)) {
                    // Can play a card
                    if (card_info.type == 'Function') {
                        // function card
                        console.log(`The player card id : ${card_id} and card type: function is played.`);
                        if (this.isCurrentPlayerActive()) {
                            this.ajaxcallwrapper('playFunctionCard', {
                                "card_id": card_id,
                                "card_type": card_type,
                                "player_id": this.player_id
                            });
                        }
                        
                        // this.playerdeck.unselectAll();
                    } else {
                        // trial 
                        for (let row = 1; row <= 2; row++) {
                            for (let col = 1; col <= 5; col++) {
                                var div_id = `playerOnPlaymat_me_${row}_${col}`;
                                dojo.addClass(div_id, 'available');
                                dojo.connect($(div_id), 'onclick', this, (evt) => {this.playPlayerCard(this.player_id, card_id, card_type, row, col, evt)});
                            }
                        }
                        console.log(`The player card id : ${card_id} and card type: player is played.`)
                        // this.playPlayerCard(this.player_id, card_id, card_type);
                        
                    }
                    // this.playerdeck.unselectAll();
                } else {
                    // Can't play a card
                    this.playerdeck.unselectAll();
                }
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

        /* Example:
        
        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/aniversus/aniversus/myAction.html", { 
                                                                    lock: true, 
                                                                    myArgument1: arg1, 
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 }, 
                         this, function( result ) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );        
        },        
        
        */

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your aniversus.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            // TODO: here, associate your game notifications with local methods
            
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 

            dojo.subscribe('playFunctionCard', this, "notif_playFunctionCard");
            dojo.subscribe('updatePlayerBoard', this, "notif_updatePlayerBoard");
        },  
        
        // TODO: from this point and below, you can write your game notifications handling methods
        
        /*
        Example:
        
        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
            
            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
            
            // TODO: play the card in the user interface.
        },    
        
        */
        // This Notification is called when the shooting is successful
        notif_updatePlayerBoard: function( notif ) {
            console.log(`**** Notification: updatePlayerBoard `)
            console.log(notif);
            const player_id = notif.args.player_id;
            const player_productivity = notif.args.player_productivity;
            const player_action = notif.args.player_action;
            const score = notif.args.player_score;
            // Update all information in the player board
            this.playerCounter[player_id]['productivity'].toValue(player_productivity);
            this.playerCounter[player_id]['action'].toValue(player_action);
            this.scoreCtrl[player_id].toValue(score);
        },

        // This Notification is called when a function card is played
        notif_playFunctionCard: function( notif ) {
            console.log(`**** Notification: playFunctionCard `)
            console.log(notif);
            const player_id = notif.args.player_id;
            const card_id = notif.args.card_id;
            const card_type = notif.args.card_type;
            if (player_id == this.player_id) {
                this.getJstplCard(player_id, card_id, card_type, 'myhand_item_' + card_id, 'discardPile_field_me');
                this.playerdeck.removeFromStockById(card_id);
                if (this.playerOnPlaymat['me']['discardpile'].getItemNumber() > 2) {
                    this.playerOnPlaymat['me']['discardpile'].removeFromZone(discard_pile_items[0], true, "player_board_" + player_id);
                }
                this.playerOnPlaymat['me']['discardpile'].placeInZone( 'cardsOnTable_' + player_id + '_' + card_id , 0 );
            } else {
                this.getJstplCard(player_id, card_id, card_type, 'player_board_' + player_id, 'discardPile_field_opponent');
                if (this.playerOnPlaymat['opponent']['discardpile'].getItemNumber() > 2) {
                    this.playerOnPlaymat['opponent']['discardpile'].removeFromZone(discard_pile_items[0], true, "player_board_" + player_id);
                }
                this.playerOnPlaymat['opponent']['discardpile'].placeInZone( 'cardsOnTable_' + player_id + '_' + card_id , 0 );
            }
        },
   });             
});
