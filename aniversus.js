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
            
            // Zone control (Playmat)
            this.discardpile_me = new ebg.zone();
            this.discardpile_opponent = new ebg.zone();
            // Zone control (Player Playmat)
            // Create a array of zone objects for player playmat
            this.playerOnPlaymat = {};
            ['me', 'opponent'].forEach((player) => {
                for (var row = 1; row <= 2; row++) {
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
            

            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                
                // TODO: Setting up players boards if needed
            }
            
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
            }
            // setup connect
            dojo.connect( this.playerdeck, 'onChangeSelection', this, 'onPlayerHandSelectionChanged' );

            // playmat setup
            // Discard pile
            this.discardpile_me.create( this, 'discardPile_field_me', this.cardwidth, this.cardheight );
            this.discardpile_opponent.create( this, 'discardPile_field_opponent', this.cardwidth, this.cardheight );
            // player playmat
            // for (var player in ['me', 'opponent']) {
            //     for (var row = 1; row <= 2; row++) {
            //         for (var col = 1; col <= 5; col++) {
            //             this.playerOnPlaymat[player][row][col].create( this, `playerOnPlaymat_${player}_${row}_${col}`, this.cardwidth, this.cardheight );
            //         }
            //     }
            // }

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
            console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */
                
                
            case 'dummmy':
                break;
            }
        },

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

        // 
        


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
        playPlayerCard: function(player_id, card_id, card_type) {
            //jstpl_cardsOnTable = '<div class="js-cardsontable" id="cardsOnTable_${player_id}_${card_id}" style="background-position:-${x}px -${y}px"></div>';
            // init card type to css position mapping, and get the position
            
            const position = this.getCardBackgroundPosition(card_type);
            let x = position.x;
            let y = position.y;
            console.log('playCard', player_id, card_id, card_type, x, y);
            // place the card on the table
            if (player_id != this.player_id) {
                // create card on table
                dojo.place( this.format_block('jstpl_cardsOnTable', {
                    player_id: player_id,
                    card_id: card_id,
                    x: x,
                    y: y
                }), 'playerOnPlaymat_' + 'opponent_' + '1_1');
                // place the card on the player board
                this.placeOnObject('cardsOnTable_' + player_id + '_' + card_id, 'overall_player_board_' + player_id);
                // slide the card to the table
                this.slideToObject( 'cardsOnTable_' + player_id + '_' + card_id , 'playerOnPlaymat_' + 'opponent_' + '1_1' ).play();
            } else {
                
                if ($('myhand_item_' + card_id)) {
                    // create card on table
                    dojo.place( this.format_block('jstpl_cardsOnTable', {
                        player_id: player_id,
                        card_id: card_id,
                        x: x,
                        y: y
                    }), 'playerOnPlaymat_' + 'me_' + '1_1');
                    // place the card on the player board
                    this.placeOnObject('cardsOnTable_' + player_id + '_' + card_id, 'myhand_item_' + card_id);
                    this.playerdeck.removeFromStockById(card_id);
                }
                // slide the card to the table
                this.slideToObject( 'cardsOnTable_' + player_id + '_' + card_id , 'playerOnPlaymat_' + 'me_' + '1_1').play();
            }
            // this.playerdeck.removeFromStockById(card_id);
        },

        playFunctionCard: function(player_id, card_id, card_type) {
            // get the discard pile items
            let discard_pile_items = this.discardpile_me.getAllItems();
            // create card html element and place it to discard pile
            const position = this.getCardBackgroundPosition(card_type);
            var div_id = `discardPile_field_me`;
            dojo.place( this.format_block('jstpl_cardsOnTable', {
                player_id: player_id,
                card_id: card_id,
                ...position
            }), div_id);
            // // place the card on the player board
            this.placeOnObject( 'cardsOnTable_' + player_id + '_' + card_id , 'myhand_item_' + card_id);
            this.playerdeck.removeFromStockById(card_id);
            // slide the card to the table
            this.slideToObject( 'cardsOnTable_' + player_id + '_' + card_id , div_id ).play();
            if (this.discardpile_me.getItemNumber() > 2) {
                this.discardpile_me.removeFromZone(discard_pile_items[0], true, "player_board_" + player_id);
            }
            console.log(discard_pile_items);
            this.discardpile_me.placeInZone( 'cardsOnTable_' + player_id + '_' + card_id , 0 );
        },

        onPlayerHandSelectionChanged : function() {
            var items = this.playerdeck.getSelectedItems();

            if (items.length > 0) {
                if (this.checkAction('playCard', true)) {
                    // Can play a card
                    var card_id = items[0].id;
                    var card_type = items[0].type;
                    var card_info = this.gamedatas.cards_info.find((card) => card.id == card_type);
                    if (card_info.type == 'Function') {
                        // function card
                        console.log(`The player card id : ${card_id} and card type: function is played.`);
                        this.playFunctionCard(this.player_id, card_id, card_type);
                        // this.playerdeck.unselectAll();
                    } else {
                        // trial 
                        for (var row = 1; row <= 2; row++) {
                            for (var col = 1; col <= 5; col++) {
                                var div_id = `playerOnPlaymat_me_${row}_${col}`;
                                dojo.addClass(div_id, 'available');
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
   });             
});
