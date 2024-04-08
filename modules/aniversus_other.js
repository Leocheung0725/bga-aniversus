define([
    "dojo", "dojo/_base/declare"
], function( dojo, declare )
{
return declare("bgagame.other", null, { // null here if we don't want to inherit from anything
        constructor: function(){
            this.rollDice_html_content = 
            `<div class="rolldice-main">
                <div class="rolldice-game">
                <div class="rolldice-container">
                <div id='dice1' class="dice dice-one">
                    <div id="dice-one-side-one" class='side one'>
                    <div class="dot one-1"></div>
                    </div>
                    <div id="dice-one-side-two" class='side two'>
                    <div class="dot two-1"></div>
                    <div class="dot two-2"></div>
                    </div>
                    <div id="dice-one-side-three" class='side three'>
                    <div class="dot three-1"></div>
                    <div class="dot three-2"></div>
                    <div class="dot three-3"></div>
                    </div>
                    <div id="dice-one-side-four" class='side four'>
                    <div class="dot four-1"></div>
                    <div class="dot four-2"></div>
                    <div class="dot four-3"></div>
                    <div class="dot four-4"></div>
                    </div>
                    <div id="dice-one-side-five" class='side five'>
                    <div class="dot five-1"></div>
                    <div class="dot five-2"></div>
                    <div class="dot five-3"></div>
                    <div class="dot five-4"></div>
                    <div class="dot five-5"></div>
                    </div>
                    <div id="dice-one-side-six" class='side six'>
                    <div class="dot six-1"></div>
                    <div class="dot six-2"></div>
                    <div class="dot six-3"></div>
                    <div class="dot six-4"></div>
                    <div class="dot six-5"></div>
                    <div class="dot six-6"></div>
                    </div>
                </div>
                </div>
                <div class="rolldice-container">
                <div id='dice2' class="dice dice-two">
                    <div id="dice-two-side-one" class='side one'>
                    <div class="dot one-1"></div>
                    </div>
                    <div id="dice-two-side-two" class='side two'>
                    <div class="dot two-1"></div>
                    <div class="dot two-2"></div>
                    </div>
                    <div id="dice-two-side-three" class='side three'>
                    <div class="dot three-1"></div>
                    <div class="dot three-2"></div>
                    <div class="dot three-3"></div>
                    </div>
                    <div id="dice-two-side-four" class='side four'>
                    <div class="dot four-1"></div>
                    <div class="dot four-2"></div>
                    <div class="dot four-3"></div>
                    <div class="dot four-4"></div>
                    </div>
                    <div id="dice-two-side-five" class='side five'>
                    <div class="dot five-1"></div>
                    <div class="dot five-2"></div>
                    <div class="dot five-3"></div>
                    <div class="dot five-4"></div>
                    <div class="dot five-5"></div>
                    </div>
                    <div id="dice-two-side-six" class='side six'>
                    <div class="dot six-1"></div>
                    <div class="dot six-2"></div>
                    <div class="dot six-3"></div>
                    <div class="dot six-4"></div>
                    <div class="dot six-5"></div>
                    <div class="dot six-6"></div>
                    </div>
                </div> 
                </div>
                </div>
            </div>`;
        
        },

        // <div id='roll' class="roll-button"><button class="rolldice-button">Roll dice!</button></div>

        rollDice: function() {
            
            // Generate random dice rolls
            var diceOne = Math.floor(Math.random() * 6) + 1;
            var diceTwo = Math.floor(Math.random() * 6) + 1;

            // Update the dice visuals using Dojo
            for (var i = 1; i <= 6; i++) {
                dojo.removeClass('dice1', 'show-' + i);
                if (diceOne === i) {
                    dojo.addClass('dice1', 'show-' + i);
                }
            }
        
            for (var k = 1; k <= 6; k++) {
                dojo.removeClass('dice2', 'show-' + k);
                if (diceTwo === k) {
                    dojo.addClass('dice2', 'show-' + k);
                }
            }
        },

    });
        
});
