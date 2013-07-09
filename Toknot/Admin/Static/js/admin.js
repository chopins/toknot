var init = {
	name: 'init',
	requestType: 'Init',
	controller: {
		updateTimeBar: function() {
			var timeBar = TK.$('time-bar');
			var localTime = TK.time();
			timeBar.innerHTML = TK.date(localTime);
			var setTime = function() {
				localTime = localTime + 1000;
				timeBar.innerHTML = TK.date(localTime, true);
				setTimeout(setTime, 1000);
			};
			setTimeout(setTime, 1000);
		}
	}
};
var ievent = {
	name : 'IEvent',
	requestType: 'IEvent',
	controller : {

	}
};

TK.ready(function() {
	var method = {
		controllerList: {
			Init : [],
			Cookie : [],
			URIHash : [],
			IEvent : []
		},
		router: function(request) {
			if (typeof requset === 'undefined') {
				return;
			}
			if (request instanceof Event) {
				var element = TK.getEventNode();
				return method.controller('IEvent', element);
			}
			if (request == 'URIHash') {
				var map = TK.getURIHash();
				return method.controller('URIHash', map);
			}
			if (request == 'Cookie') {

			}
			if (request == 'Init') {
				method.controllerList.Init.controller();	
			}
		},
		controller: function(type, request) {
			switch (type) {
				case 'IEvent':
					method.controllerList.IEvent.controller(type,request);
					break;
				case 'URIHash':

				case 'CallFunction':
					break;
			}
		},
		regiserController: function(controller, file) {
			if(typeof(controller.requestType) == 'undefined') {
				return;	
			}
			if(typeof(controller.controller)== 'undefined') {
				return;
			}
			if (file) {
				TK.loadJSFile(file, true);
			}
			var type = controller.requestType;
			method.controllerList[type].push(controller);
		}
	};
	method.regiserController(init);
	method.regiserController(ievent);
	$(document.body).addListener('click', method.router);
	$(document.body).addListener('scroll', method.router);
	method.router('Init');
});
