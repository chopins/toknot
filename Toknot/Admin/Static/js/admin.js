function updateTimeBar(serverTime) {
    var timeBar = document.getElementById('time-bar');
    var date = new Date(serverTime);
    var month = date.getMonth() + 1;
    month = month > 9 ? month : '0' + month;
    var YMD = date.getFullYear() + '-' + month + '-' + date.getDate();
    var setTime = function() {
        serverTime = serverTime + 1000;
        date = new Date(serverTime);
        var seconds = date.getSeconds();
        seconds = seconds > 9 ? seconds : '0' + seconds;
        timeBar.innerHTML = YMD + ' ' + date.getHours() + ':' + date.getMinutes() + ':' + seconds;
        setTimeout(setTime, 1000);
    };
    setTimeout(setTime, 1000);
}