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
    //初始化后台
    init : function() {
        dop.initUI();
        dop.getUri();
        dop.checkUserLogin();
    },
    //初始化页面
    initUI : function() {
        dop.mainBlock = X.$(dop.mainBlockId);
        dop.leftNavBox = X.createNode('div');
        dop.leftNavBox.addClass('left-block');
        dop.mainBlock.appendChild(dop.leftNavBox);
        dop.pageRight = X.createNode('div');
        dop.pageRight.addClass('right-block');
        dop.mainBlock.appendChild(dop.pageRight);
        dop.bodyResize();
        X.$(document.body).addListener('resize',dop.bodyResize);
    },
    //页面尺寸控制
    bodyResize : function() {
        var size = X.pageShowSize();
        X.doc.body.style.height = size.h+'px';
        X.doc.body.style.width = size.w+'px';
        dop.mainBlock.style.height = (size.h - 100)+'px';
    },
    //获取当前URL中的hash值
    getUri : function() {
        dop.accessUri = window.location.hash.substr(1);
    },
    //设置URL的ACT
    setUri : function(uri) {
        window.location.hash = uri;
        dop.uri = uri;
    },
    //检查用户是否登录
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
    // 创建左侧导航栏
    createLeftNav : function() {
        if(!dop.leftNavGetUri) return;
                var tli= X.createNode('div');
        tli.addClass('left-nav-div');
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
                    nav.addClass('left-nav-div-active');
                    dop.activeNav = nav;
                }
                if(typeof(tmp[2]) != 'undefined') {
                    nav.addClass('left-nav-head');
                }
                dop.leftNavBox.appendChild(nav);
            }
        });
    },
    //显示页面 
    showView : function() {
        dop.createLeftNav();
        dop.showRightBlock();
    },
    //页面的从定向动作操作
    pageAutoAct : function(act,part) {
        if(part == 'page') {
            switch(act) {
                case 'refresh':
                    window.location.href = '/';
                return;
            }
        }
    },
    //显示右侧页面
    showRightBlock : function(e) {
        if(dop.activeNav) dop.activeNav.removeClass('left-nav-div-active');
        if(e) {
            var nav = X.getEventNode(e);
            dop.accessUri = nav.action;
            nav.addClass('left-nav-div-active');
            dop.activeNav = nav;
            dop.setUri(nav.action);
        }
        X.Ajax.get(dop.accessUri, function(re) {
            if(re.status == 0) {
                return dop.userLoginView(re);
            } else if(re.status == -1) {
                return X.alertBox(re.data.title,re.message,null,'',1);
            } else {
                if(re.data) {
                    if(typeof(re.data.act) != 'undefined' && typeof(re.data.part) != 'undefined') {
                        dop.pageAutoAct(re.data.act,re.data.part);
                    } else {
                    }
                }
            }
        });
    },
    //显示登录框
    userLoginView : function(re) {
        X.inputBox(re.data.title,'',re.data.input,re.data.button,re.data.cls,true,100);
    },
    //用户登录操作
    userLoginAct : function(e) {
        var inputBox = this.box;
        inputBox.submitInput(this.url,function(re) {
            if(re.status == 1) {
                inputBox.hide();
                dop.leftNavGetUri = re.data;
                dop.showView();
                return;
            }
            inputBox.msg(re.message, '',true);
            return;
        }, function(data, obj) {
            if(!data.username) {
                obj.msg('用户名不能为空', '',true);
                return false;
            }
            if(!data.password) {
                obj.msg('密码不能为空','',true);
                return false;
            }
            return true;
        });
    }
};
