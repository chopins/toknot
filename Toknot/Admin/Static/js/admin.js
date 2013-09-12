var init = {
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
    },
    setPageLoadAction : function() {
        TK.Ajax.dataType = 'html';
        clickEvent.currentNav = TK.$('left-index-nav');
        clickEvent.currentNavPontiner = clickEvent.currentNav.getLastNode();
    },
    bindMenuList : function() {
        TK.$('user-menu').byNodePop(TK.$('user-name'),1);
    },
    bindEvent : function() {
//        TK.$('add-manage-btn').addListener('click', clickEvent.showAddManagePage);
        TK.$('left-nav-tree').addListener('click',clickEvent.manageMenuAction);
        TK.$('user-menu').addListener('click',clickEvent.userMenuAction);
        TK.$('top-panel').addListener('click',clickEvent.globalBtnAction);
        TK.$('left-index-nav').addListener('click',clickEvent.gotoIndexAction);
    }
};

var clickEvent = {
    currentNav : null,
    currentNavPontiner : null,
    preActionUrl : '/Index',
    currentActionUrl : '/Index',
    saveFunction : null,
    deleteFunction : null,
    updatePageHtml : function(action) {
        clickEvent.preActionUrl = clickEvent.currentActionUrl;
        clickEvent.currentActionUrl = action;
        TK.Ajax.get(action,function(html) {
            TK.$('right-page-box').innerHTML = html;
        });
    },
    globalBtnAction : function(e) {
        var clickNode = TK.getEventNode(e);
        if(clickNode.hasClass('opreate-btn')) {

        } else if(clickNode.parentNode.hasClass('opreate-btn')) {
            clickNode = clickNode.parentNode;
        } else {
            return false;
        }
        var action = clickNode.getAttribute('action');
        var actionUrl = TK.$('right-page-box').getAttribute(action);
        if(action == 'return') {
            return clickEvent.updatePageHtml(clickEvent.preActionUrl);
        } else if(action == 'save' && clickEvent.saveFunction != null) {
            return clickEvent.saveFunction();
        } else if(action == 'delete' && clickEvent.deleteFunction != null) {
            return clickEvent.deleteFunction();
        }else if(actionUrl) {
            return clickEvent.updatePageHtml(actionUrl);
        }
    },
    userMenuAction : function(e) {
        var clickNode = TK.getEventNode(e);
        var action = clickNode.getAttribute('action');
        var refresh = clickNode.getAttribute('refresh');
        if(action) {
            if(refresh && refresh == 'true') {
                window.location.href = action;
            }
            clickEvent.updatePageHtml(action);
        }
    },
    showAddManagePage : function(e) {
        clickEvent.updatePageHtml('AddManage');
    },
    changeCurrentAction : function(clickNode) {
        clickEvent.currentNav.removeClass('current-nav-page');
        clickNode.addClass('current-nav-page');
        clickNode.appendChild(clickEvent.currentNavPontiner);
        clickEvent.currentNav = clickNode;
        var action = clickNode.getAttribute('action');
        if(action) {
            clickEvent.updatePageHtml(action);
        }
    },
    gotoIndexAction : function(e) {
        var clickNode = TK.getEventNode(e);
        clickEvent.changeCurrentAction(clickNode);
    },
    manageMenuAction : function(e) {
        var clickNode = TK.getEventNode(e);
        if(clickNode.hasClass('current-nav-page')) {
            return;
        }
        if(clickNode.hasClass('nav-cat')) {
            if(clickNode.hasClass('icon-down-open')) {
                clickNode.replaceClass('icon-down-open', 'icon-right-open');
                clickNode.nextNode().hide();
            } else {
                clickNode.replaceClass('icon-right-open', 'icon-down-open');
                clickNode.nextNode().show();
            }
            return;
        }
        if(clickNode.hasClass('sub-nav-tree-li')) {
            clickEvent.changeCurrentAction(clickNode);
        }
    }

};

var TKRouter = {
    importModule : function(file) {
        TK.loadJSFile(file, true);
    },
    controllerList : [],
    register : function(func) {
        this.controllerList.push(func);
    },
    importMethod : function(obj) {
        for(var i in obj) {
            this.register(obj[i]);
        }
    },
    Init : function() {
        for(var i in this.controllerList) {
            this.controllerList[i]();
        }
    }
};

TK.ready(function() {
    TKRouter.importMethod(init);
    TKRouter.Init();
});
