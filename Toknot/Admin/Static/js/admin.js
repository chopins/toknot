function updateTimeBar(serverTime) {
    var timeBar = TK.$('time-bar');
    timeBar.innerHTML = TK.date(serverTime);
    var setTime = function() {
        serverTime = serverTime + 1000;
        timeBar.innerHTML = TK.date(serverTime);
        setTimeout(setTime, 1000);
    };
    setTimeout(setTime, 1000);
}