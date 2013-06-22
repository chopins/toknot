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
TK.ready(function() {
	var method = {
		controllerList: [],
		router: function(request) {
			if (typeof requset === 'undefined') {
				return;
			}
			if (request instanceof Event) {
				var element = TK.getEventNode();
				return method.controller('Event', element);
			}
			if (request == 'URIHash') {
				var map = TK.getURIHash();
				return method.controller('URIHash', map);
			}
			if(request == 'Cookie') {
				
			}
		},
		controller: function(type, request) {
			switch (type) {
				case 'Event':
					break;
				case 'URIHash':

				case 'CallFunction':
					break;
			}
		},
		regiserController: function(controller, file) {
			if (file) {
				TK.loadJSFile(file, true);
			}
			method.controllerList.push(controller);
		},
		view: function() {

		}
	};
	$(document.body).addListener(method.router, 'click');
	method.router('URIHash');
});
