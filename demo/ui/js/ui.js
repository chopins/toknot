var dop = {
    userLoginStatus : false,
    loginUri  : '/index/checklogin',
    accessUri : '/index/home',
    activeNav : null,
    mainBlockId : 'b-center',
    leftNavGetUri : null,
    leftNavBox : null,
    mainBlock : null,
    pageRight : null,
    userName : null,
    init : function() {
        dop.initUI();
        dop.getUri();
        dop.checkUserLogin();
    },
    initUI : function() {
        dop.mainBlock = X.$(dop.mainBlockId);
        dop.leftNavBox = X.createNode('div');
        dop.leftNavBox.setClass('left-block');
        dop.mainBlock.appendChild(dop.leftNavBox);
        dop.pageRight = X.createNode('div');
        dop.pageRight.setClass('right-block');
        dop.mainBlock.appendChild(dop.pageRight);
        dop.bodyResize();
        X.$().body.addListener('resize',dop.bodyResize);
    },
    bodyResize : function() {
        var size = X.pageShowSize();
        X.doc.body.style.height = size.h+'px';
        X.doc.body.style.width = size.w+'px';
        dop.mainBlock.style.height = (size.h - 50)+'px';
    },
    getUri : function() {
        dop.accessUri = window.location.hash.substr(1);
    },
    setUri : function(uri) {
        window.location.hash = uri;
        dop.uri = uri;
    },
    checkUserLogin : function() {
        X.Ajax.get(dop.loginUri,function(re) {
            if(re.status == 1) {
                dop.leftNavGetUri = re.data;
                dop.showView();
            } else {
                dop.userLoginView(re);
            }
        });
    },
    createLeftNav : function() {
        if(!dop.leftNavGetUri) return;
                var tli= X.createNode('div');
        tli.setClass('left-nav-div');
        X.Ajax.get(dop.leftNavGetUri,function(re) {
            if(re.status == 0) return;
            var list = re.data;
            for(var i in list) {
                if(isNaN(i)) continue;
                var tmp = list[i].split('|');
                var nav = tli.copyNode(tli);
                nav.innerHTML = tmp[0];
                nav.action = tmp[1];
                nav.addListener('click',dop.showRightBlock);
                if(dop.accessUri == tmp[1]) {
                    nav.setClass('left-nav-div-active');
                    dop.activeNav = nav;
                }
                dop.leftNavBox.appendChild(nav);
            }
        });
    },
    showView : function() {
        dop.createLeftNav();
        dop.showRightBlock();
    },
    showRightBlock : function(e) {
        if(dop.activeNav) dop.activeNav.removeClass('left-nav-div-active');
        if(e) {
            var nav = X.getEventNode(e);
            dop.accessUri = nav.action;
            nav.setClass('left-nav-div-active');
            dop.activeNav = nav;
            dop.setUri(nav.action);
        }
        X.Ajax.get(dop.accessUri, function(re) {
            if(re.status == 0) {
                return dop.userLoginView(re);
            } else if(re.status == -1) {
                return X.alertBox(re.data.title,re.message,null,'',1);
            } else {
            }
        });
    },
    userLoginView : function(re) {
        X.inputBox(re.data.title,'',re.data.input,re.data.button,re.data.cls,true,100);
    },
    userLoginAct : function(e) {
        var inputBox = this.box;
        inputBox.submitInput(this.url,function(re) {
            inputBox.msg(re.message);
            if(re.status == 1) {
                box.hide();
                dop.leftNavGetUri = re.data;
                dop.showView();
            }
            return;
        }, function(data, obj) {
            if(!data.username) {
                obj.msg('用户名不能为空');
                return false;
            }
            if(!data.password) {
                obj.msg('密码不能为空');
                return false;
            }
            return true;
        });
    }
};
