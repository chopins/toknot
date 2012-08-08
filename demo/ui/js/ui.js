var dop = {
    userLoginStatus : false,
    loginUri  : '/index/checklogin',
    accessUri : '/index/home',
    channelUri : '/channel/call',
    epollUri : '/channel/poll',
    epollFreqTime : 5000,
    activeNav : null,
    mainBlockId : 'b-center',
    leftNavGetUri : null,
    leftNavBox : null,
    rightBar : null,
    mainBlock : null,
    pageRight : null,
    userName : null,
    loginStatus : false,
    pageReady : 0,
    notifyPool : null,
    lastModified : 0,
    sock : null,
    epollSt : 0,
    enableAct : [],
    sid : '',
    //初始化后台
    init : function() {
        dop.initUI();
        dop.getUri();
        dop.checkUserLogin();
        dop.start = X.time();
        if(typeof(XSID) != 'undefined') dop.sid = XSID;
    },
    //初始化页面
    initUI : function() {
        dop.mainBlock = X.$(dop.mainBlockId);
        dop.leftNavBox = X.createNode('div');
        dop.leftNavBox.addClass('left-block');
        dop.leftNavBox.hide();
        dop.mainBlock.appendChild(dop.leftNavBox);
        dop.pageRight = X.createNode('div');
        dop.pageRight.addClass('right-block');
        dop.mainBlock.appendChild(dop.pageRight);
        dop.bodyResize();
        X.$(document.body).addListener('resize',dop.bodyResize);
    },
    notifyUpdate : function (header) {
        var accessData = {};
        accessData['act'] = '/channel/get';
        accessData['modified_time'] = header['DATA_MODIFIED'];
        dop.request(accessData);
    },
    ajaxHeadEpoll : function() {
        X.clearTimeout(dop.epollSt);
        var sendHeader = [{k:'Client-Modified', v:dop.lastModified}];
        X.Ajax.waitTime = 30000;
        X.Ajax.hpost(dop.epollUri,sendHeader,[],function(state, header) {
            if(state != 4) return;
            if(header == 0) {
                dop.epollFreqTime += 3000;
                X.setTimeout(dop.ajaxHeadEpoll,dop.epollFreqTime);
                return;
            }
            if(header['AUTHORIZATION'] == 'nologin') {
                dop.loginStatus = false;
                return dop.userLoginView(re);
            } else {
                if(header['DATA_MODIFIED'] > dop.lastModified) {
                    dop.lastModified = header['DATA_MODIFIED'];
                    dop.notifyUpdate(header);
                }
                dop.epollSt = X.setTimeout(dop.ajaxHeadEpoll, dop.epollFreqTime);
                return;
            }
        }, function(retext) {});
    },
    sendToken : function() {
    },
    epoll : function() {
        //dop.sock = X.Ajax.socket(dop.epollUri, dop.sendToken, dop.notifyUpdate);
        dop.sock = false;
        if(dop.pageReady < 2) {
            X.setTimeout(dop.epoll,1000);
            return;
        }
        if(!dop.sock) {
            dop.ajaxHeadEpoll();
        }
    },
    request : function(accessData) {
        if(!accessData) accessData = {act:dop.accessUri};
        X.Ajax.post(dop.channelUri,accessData, function(re) {
            if(re.status == 0 && re.data == 'nologin') {
                dop.loginStatus = false;
                return dop.userLoginView(re);
            } else if(re.status == -1) {
                return X.alertBox(re.data.title,re.message,null,'',1);
            } else {
                if(re.data && typeof(re.data.act) != 'undefined' && typeof(re.data.part) != 'undefined') {
                    dop.pageAutoAct(re.data.act,re.data.part);
                } else if(re.status == 1) {
                    if(dop.pageReady == 0) {
                        dop.pageReady = 1;
                    } else if(dop.pageReady == 1) {
                        dop.pageReady = 2;
                    }
                    dop.createRightBlock(re);
                }
            }
        });
    },
    //页面尺寸控制
    bodyResize : function() {
        var size = X.pageShowSize();
        X.doc.body.style.height = size.h+'px';
        X.doc.body.style.width = size.w+'px';
        dop.pageRight.style.width = (size.w - 260) + 'px';
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
        X.Ajax.get(dop.loginUri+'?'+dop.sid,function(re) {
            if(re.status == -1) {
                X.alertBox('错误','Cookie被禁用',function(){
                    window.location.reload();
                    },'b-input-box',true);
                return;
            }
            if(re.status == 1) {
                dop.leftNavGetUri = re.data;
                dop.loginStatus = true;
                dop.showView();
                dop.epoll();
            } else {
                dop.userLoginView(re);
            }
        });
    },
    // 创建左侧导航栏
    createLeftNav : function() {
        dop.leftNavBox.show();
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
            if(dop.pageReady == 0) {
                dop.pageReady = 1;
            } else if(dop.pageReady == 1) {
                dop.pageReady = 2;
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
        dop.request();
    },
    /**
     * 创建页面右侧横向导航
     * 
     * opreate_nav 功能页面标签数据
     * 数据格式:  {Lable_name|action_uri,....}
     *
     *  
     */
    createRightTabNav : function(opreate_nav) {
        var tabNavBlock = X.createNode('div');
        tabNavBlock.setClass('b-right-tab-block');
        var tabNavNodeContainer = X.createNode('div');
        tabNavNodeContainer.setClass('b-right-tab-container');
        var tabNavNode = X.createNode('div');
        tabNavNode.setClass('b-right-tab-nav');
        for(var i in opreate_nav) {
            if(isNaN(i)) continue;
            var menu = opreate_nav[i].split('|');
            var item = tabNavNode.copyNode(true);
            item.innerHTML = menu[0];
            item.act = menu[1];
            if(menu[1] == dop.accessUri) {
                item.addClass('b-right-tab-nav-active');
            }
            tabNavNodeContainer.appendChild(item);
        }
        tabNavBlock.appendChild(tabNavNodeContainer);
        dop.pageRight.appendChild(tabNavBlock);
    },

    /**
     *  创建整个右侧页面
     *
     *  re.message 为右侧页面标题
     *  re.data.opreate_nav 为页面导航
     *  re.data.table_data  为页面正文数据
     *      table_data      包括以下字段
     *            type      数据类型，可能值为 table
     *            data      数据实体
     *
     */
    createRightBlock : function(re) {
        if(dop.rightBar == null) {
            dop.rightBar = X.createNode('div');
            dop.rightBar.setClass('b-right-block-bar');
            dop.pageRight.appendChild(dop.rightBar);
        }
        dop.rightBar.innerHTML = re.message;
        if(re.data.opreate_nav) {
            dop.createRightTabNav(re.data.opreate_nav);
        }
        if(re.data.table_data) {
            if(re.data.table_data.type == 'table') {
                dop.createRightBlockTable(re.data.table_data);
            } else {
            }
        }
    },
    createRightBlockTable : function(table_data) {
        var rightTable = X.createNode('div');
        var itemProto = X.createNode('div');
        itemProto.setClass('b-right-block-table-item');
        var optBtnDiv = X.createNode('div');
        optBtnDiv.setClass('b-right-block-table-item-btn');
        optBtnDiv.innerHTML = '全部发布|发布|停止发布|发布日志|SVN日志|查看项目文件';
        var summaryInfo = X.createNode('div');
        summaryInfo.setClass('b-right-block-table-item-summary');
        summaryInfo.innerHTML = 'SVN最新版本|最新发布时间';
        for(var i in table_data.data) {
            var item = itemProto.copyNode(true);
            item.innerHTML = table_data.data[i];
            var btnDiv = optBtnDiv.copyNode(true);
            var summary = summaryInfo.copyNode(true);
            item.appendChild(btnDiv);
            item.appendChild(summary);
            rightTable.appendChild(item);
        }
        dop.pageRight.appendChild(rightTable);
    },

    //显示登录框
    userLoginView : function(re) {
        X.inputBox(re.data.title,'',re.data.input,re.data.button,re.data.cls,true,100);
    },
    //用户登录操作
    userLoginAct : function(e) {
        var btn = X.getEventNode(e);
        var inputBox = btn.parentBox;
        inputBox.submitInput(btn.url,function(re) {
            if(re.status == 1) {
                inputBox.iHide();
                dop.leftNavGetUri = re.data;
                dop.showView();
                dop.epoll();
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
