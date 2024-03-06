var test = {};

['me', 'opponent'].forEach((player) => {
    test[player] = {};
    for (var row = 1; row <= 2; row++) {
        test[player][row] = {};
        for (var col = 1; col <= 5; col++) {
            console.log(`player : ${player}<${typeof(player)}>, row : ${row}<${typeof(row)}>, col : ${col}<${typeof(col)}>`)
            test[player][row][col] = "KK";
        }
    }
});

console.log(`${test['me']["1"][1]}`);