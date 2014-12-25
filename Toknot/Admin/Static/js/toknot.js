if (typeof TK == 'undefined') {
    "use strict";
    String.prototype.isEmail = function () {
        return /^([a-z0-9+_]|\-|\.)+@(([a-z0-9_]|\-)+\.)+[a-z]{2,6}$/.test(this);
    };
    String.prototype.isCNMoblie = function () {
        return /^(13|15|18)\d{9}$/i.test(this);
    };
    String.prototype.trim = function () {
        return this.replace(/(^\s*)|(\s*$)/g, "");
    };
    String.prototype.strpos = function (needle, offset) {
        var i = (this + '').indexOf(needle, (offset || 0));
        return i === -1 ? false : i;
    };
    String.prototype.ucwords = function () {
        return (str + '').replace(/^([a-z])|\s+([a-z])/g, function ($1) {
            return $1.toUpperCase();
        });
    };
    String.prototype.isWord = function () {
        return /^(A-za-z0-9_)/i.test(this);
    };
    String.prototype.ucfirst = function () {
        var f = this.charAt(0).toUpperCase();
        return f + this.substr(1);
    };
    if (typeof Node == 'undefined') {
        Node = {
            ELEMENT_NODE: 1,
            ATTRIBUTE_NODE: 2,
            TEXT_NODE: 3,
            COMMENT_NODE: 8,
            DOCUMENT_NODE: 9,
            DOCUMENT_FRAGMENT_NODE: 11
        };
    }
    ;
    if (typeof console == 'undefined') {
        console = {};
        console.warn = function (str) {
            throw new Error(str);
        };
        console.log = function (str) {
            throw new Error(str);
        };
    }
    ;
    navigator.IE = typeof ActiveXObject == 'undefined' ? false : true;
    navigator.ugent = navigator.userAgent.toLowerCase()
    navigator.FIREFOX = /gecko/.test(navigator.ugent);
    navigator.WEBKIT = /webkit/.test(navigator.ugent);
    navigator.IEV = navigator.IE && !document.documentMode ? 6 : document.documentMode;
    var TK = {
        doc: window.document,
        bodyNode: window.document.body, //TK.$(ele)方法返回对象的父对象
        isReady: false,
        ugent: navigator.userAgent.toLowerCase(),
        intervalHandle: [],
        timeoutHandle: [],
        cache: {},
        maxZIndex: 0,
        keyList: {
            keyup: [],
            keydown: []
        },
        //document鼠标点击事件回调函数列表
        bodyMouseEventCallFuncitonList: {
            mousedown: [[], [], [], []],
            mouseup: [[], [], [], []],
            mouseover: [],
            mouseout: [],
            click: [[], [], [], []]
        },
        path: (function () {
            if (document.currentScript) {
                return document.currentScript.src;
            }
            try {
                throw new Error("Get Filename");
            } catch (e) {
                if (typeof e.fileName !== 'undefined') {
                    return e.fileName;
                }
            }
            return document.scripts[document.scripts.length - 1].src;
        })(),
        requirePath: 0,
        //窗口滚动事件注册函数列表
        windowScrollEventCallFunctionList: [],
        windowResizeCallFunctionList: [],
        isArray: function (it) {
            return Object.prototype.toString.call(it) === '[object Array]';
        },
        isFunction: function (it) {
            return Object.prototype.toString.call(it) === '[object Function]';
        },
        isNumeric: function (it) {
            return  !isNaN(parseFloat(it)) && isFinite(it);
            ;
        },
        createNode: function (t) {
            return TK.$(TK.doc.createElement(t));
        },
        realpath: function (srcPath) {
            return srcPath.split("?")[0].split("#")[0];
        },
        basename: function (path) {
            path = TK.realpath(path);
            var shash = path.lastIndexOf("/") + 1;
            return path.substring(shash, path.length);
        },
        dirname: function (path) {
            var shash = path.lastIndexOf("/");
            return path.substring(0, shash);
        },
        jsPath: function (cidx) {
            var scripts = TK.doc.scripts, scriptPath;
            if (typeof cidx == "string") {
                var scriptfile, i = 0;
                while (cidx != scriptfile) {
                    scriptPath = TK.realpath(scripts[i].src);
                    scriptfile = TK.basename(scripts[i].src);
                    i++;
                }
            }
            if (!cidx) {
                cidx = 0;
            } else if (TK.isNumeric(cidx)) {
                cidx = parseInt(cidx);
                scriptPath = scripts[cidx].src;
                scriptPath = TK.realpath(scriptPath);
            }
            var shash = scriptPath.lastIndexOf("/");
            if (shash < 0)
                return '';
            return scriptPath.substring(0, shash + 1);
        },
        inputType: {
            INPUT_TEXT: 1,
            INPUT_PASSWORD: 2,
            INPUT_CHECKBOX: 3,
            INPUT_RADIO: 4,
            INPUT_TEXTAREA: 5,
            INPUT_BUTTON: 6,
            INPUT_SUBMIT: 7,
            INPUT_IMAGE: 8,
            INPUT_SELECT: 9
        },
        require: function (fs, options) {
            options = options || 0;
            if (typeof fs === 'string') {
                var requirePath = TK.requirePath || TK.dirname(TK.realpath(TK.path));
                while (true) {
                    if (fs.substring(0, 2) == '..') {
                        requirePath = TK.dirname(requirePath);
                        fs = fs.substring(2, fs.length);
                    } else {
                        break;
                    }
                }
                if (fs.substring(0, 1) != '/')
                    fs = '/' + fs;
                return TK.loadJSFile(requirePath + fs + '.js', options);
            }
        },
        script: function () {
            return TK.doc.getElementsByTagName('script');
        },
        loadJSFile: function (fs, bodyEnd) {
            var f = TK.createNode('script');
            f.setAttribute('type', 'text/javascript');
            f.setAttribute('src', fs);
            try {
                if (bodyEnd) {
                    TK.doc.body.appendChild(f);
                    return f;
                }
                TK.doc.getElementsByTagName('head')[0].appendChild(f);
                f.onerror = function (e) {
                    TK.error(fs + ' Load Failure');
                }
            } catch (e) {
                TK.error(fs + ' Load Failure');
            }
            return f;
        },
        unloadExecList: [],
        eventList: [],
        unload: function (cf) {
            if (typeof cf == 'string') {
                TK.unloadExecMessage = cf;
                return;
            }
            TK.unloadExecList.push(cf);
        },
        unloadExecMessage: null,
        unloadExec: function () {
            if (TK.doc && TK.isReady) {
                TK.doc.onkeydown = null;
                TK.doc.onkeyup = null;
                TK.doc.onmouseup = null;
                TK.doc.onmousedown = null;
                window.onscroll = null;
                if (window.top == window.self) {
                    TK.doc.body.onresize = null;
                }
                for (var es in TK.eventList) {
                    for (var k in TK.eventList[es]) {
                        if (!isNaN(i))
                            TK.$(TK.eventList[es][k].handObj).delListener(es, TK.eventList[es][k]);
                    }
                }
            }
            if (TK.Ajax.XMLHttp)
                TK.Ajax.XMLHttp = null;
            if (TK.Ajax.openInstance.length > 0) {
                for (var a in TK.Ajax.openInstance)
                    if (!isNaN(a))
                        TK.Ajax.openInstance[a].abort();
                TK.Ajax.openInstance = [];
            }
            for (var i = 0; i < TK.unloadExecList.length; i++)
                TK.unloadExecList[i]();
            if (TK.unloadExecMessage)
                return TK.unloadExecMessage;
            delete TK;
        },
        /**
         * 
         * @param {String} msg
         * @returns {void}
         */
        log: function (msg) {
            console.log(msg);
        },
        /**
         * 
         * @param {String} msg
         * @returns {void}
         */
        error: function (msg) {
            console.error(msg);
        },
        init: function () {
            window.onload = function () {
                window.onerror = TK.error;
                if (TK.doc) {
                    TK.bodyNode = window.document.body;
                    TK.doc.onkeydown = TK.keyboardEventCallFunction;
                    TK.doc.onkeyup = TK.keyboardEventCallFunction;
                    TK.doc.onmousedown = TK.mouseClickEventCallFunction;
                    TK.doc.onmouseup = TK.mouseClickEventCallFunction;
                    TK.doc.onmouseover = TK.mouseMoveEventFunction;
                    TK.doc.onmouseout = TK.mouseMoveEventFunction;
                    window.onscroll = TK.windowScrollCallback;
                    if (window.top == window.self) {
                        TK.doc.body.onresize = TK.windowResizeCallback;
                    }
                }
                for (var i in TK.readyFunctionList) {
                    if (isNaN(i))
                        continue;
                    var func = TK.readyFunctionList[i];
                    func();
                }
                TK.isReady = true;
            };
        },
        readyFunctionList: [],
        /**
         * Page load ready after call function
         *
         * @param {type} func
         * @returns {void}
         */
        ready: function (func) {
            TK.readyFunctionList.push(func);
            TK.init();
        },
        getURIHash: function () {
            var hash = window.location.hash.substr(1);
            return hash;
        },
        scrollOffset: function () {
            var YOffset = window.pageYOffset ? window.pageYOffset : TK.doc.body.scrollTop;
            var XOffset = window.pageXOffset ? window.pageXOffset : TK.doc.body.scrollLeft;
            return {
                x: XOffset,
                y: YOffset
            };
        },
        //窗口滚动事件
        windowScrollCallback: function (e) {
            e = e || event;
            for (var i in TK.windowScrollEventCallFunctionList)
                if (!isNaN(i))
                    TK.windowScrollEventCallFunctionList[i].call(TK.windowScrollEventCallFunctionList[i].handObj, e);
        },
        //窗口改变大小事件
        windowResizeCallback: function (e) {
            for (var i in TK.windowResizeCallFunctionList)
                if (!isNaN(i))
                    TK.windowResizeCallFunctionList[i].func(TK.windowResizeCallFunctionList[i].obj);
        },
        //添加鼠标移动事件函数
        addMouseMoveCallFunction: function (func, type) {
            return TK.bodyMouseEventCallFuncitonList[type].push(func);
        },
        //添加document鼠标点击事件
        addMouseClickCallFunction: function (func, type, button) {
            if (!type)
                type = click;
            if (!button)
                button = 3;
            return TK.bodyMouseEventCallFuncitonList[type][button].push(func);
        },
        //document鼠标移动事件回调函数
        mouseMoveEventFunction: function (e) {
            e = e || event;
            fL = TK.bodyMouseEventCallFuncitonList[e.type];
            if (fL.length == 0)
                return;
            if (fL.eventObj == TK.getEventNode(e)) {
                e.eventObj = eventObj;
                for (var i in fL)
                    !isNaN(i) && fL[i](e);
            } else {
                for (var i in fL)
                    !isNaN(i) && fL[i](e);
            }
        },
        //document鼠标点击事件回调函数
        mouseClickEventCallFunction: function (e) {
            e = e || event, fL = TK.bodyMouseEventCallFuncitonList[e.type][e.button], fL = fL.concat(TK.bodyMouseEventCallFuncitonList[e.type][3]);
            if (fL.length == 0)
                return;
            for (var i in fL)
                !isNaN(i) && fL[i](e);
        },
        //document键盘事件回调函数
        keyboardEventCallFunction: function (e) {
            e = e || event, k = e.keyCode, fL = TK.keyList[e.type];
            for (var key in fL)
                key != '' && k == key && fL[k](e);
            if (fL['any'])
                fL['any'](e);
        },
        addKeyListener: function (key, func, type) {
            TK.keyList[type][key] = func;
        },
        delKeyListener: function (key, type) {
            delete TK.keyList[type][key];
        },
        //常用键盘事件注册
        keyboardEventRegisterController: function (obj) {
            return {
                esc: function (func) {
                    obj.key(27, func);
                },
                enter: function (func) {
                    obj.key(13, func);
                },
                tab: function (func) {
                    obj.key(9, func);
                },
                space: function (func) {
                    obj.key(32, func);
                },
                backspace: function (func) {
                    obj.key(8, func);
                },
                up: function (func) {
                    obj.key(38, func);
                },
                down: function (func) {
                    obj.key(40, func);
                },
                left: function (func) {
                    obj.key(37, func);
                },
                right: function (func) {
                    obj.key(39, func);
                },
                any: function (func) {
                    obj.key('any', func);
                },
                key: obj.key
            };
        },
        keyDown: function () {
            this.key = function (key, func) {
                TK.addKeyListener(key, func, 'keydown');
            };
            return TK.keyboardEventRegisterController(this);
        },
        keyUp: function () {
            this.key = function (key, func) {
                TK.addKeyListener(key, func, 'keyup');
            };
            return TK.keyboardEventRegisterController(this);
        },
        //鼠标点击事件注册原型
        addMouseEventController: function (type) {
            return {
                left: function (func) {
                    return TK.addMouseClickCallFunction(func, type, 0) - 1;
                },
                right: function (func) {
                    return TK.addMouseClickCallFunction(func, type, 2) - 1;
                },
                middle: function (func) {
                    return TK.addMouseClickCallFunction(func, type, 1) - 1;
                },
                any: function (func) {
                    return TK.addMouseClickCallFunction(func, type, 3) - 1;
                }
            };
        },
        delMouseEventFunction: function (type, idx, button) {
            if (type == 'mouseover' || type == 'mouseout') {
                delete TK.bodyMouseEventCallFuncitonList[type][idx];
            } else {
                switch (button) {
                    case 'left':
                        button = 0;
                        break;
                    case 'right':
                        button = 2;
                        break;
                    case 'middle':
                        button = 1;
                        break;
                    case 'any':
                    default:
                        button = 3;
                        break;
                }
                delete TK.bodyMouseEventCallFuncitonList[type][button][idx];
            }
        },
        mouseover: function (func, eventObj) {
            func.eventObj = eventObj;
            return TK.addMouseMoveCallFunction(func, 'mouseover') - 1;
        },
        mouseout: function (func, eventObj) {
            return TK.addMouseMoveCallFunction(func, 'mouseout', eventObj) - 1;
        },
        mousedown: function () {
            return TK.addMouseEventController('mousedown');
        },
        mouseup: function () {
            return TK.addMouseEventController('mouseup');
        },
        click: function () {
            return TK.addMouseEventController('click');
        },
        setTimeout: function (func, time) {
            var id = window.setTimeout(func, time);
            TK.timeoutHandle.push(id);
            return id;
        },
        setInterval: function (func, time) {
            var id = window.setInterval(func, time);
            TK.intervalHandle.push(id);
            return id;
        },
        clearTimeout: function (id) {
            window.clearTimeout(id);
            for (var i in TK.timeoutHandle) {
                if (TK.timeoutHandle[i] == id)
                    delete TK.timeoutHandle[i];
            }
        },
        clearInterval: function (id) {
            window.clearInterval(id);
            for (var i in TK.intervalHandle) {
                if (TK.intervalHandle[i] == id)
                    delete TK.intervalHandle[i];
            }
        },
        /**
         * HTML DOM 元素访问函数
         *
         * @argument {mixed} ele 元素标识,目前支持以下标识:
         *              .className   #跟随元素样式名，返回拥有该样式的所有对象的数组
         *              @tagName    @跟随元素标签名，返回所有拥有该标签的对象的数组
         *              %name        %跟随元素的name属性值，返回所有拥有该name值的对象的数组
         *              id           传入没有上面前缀字符的字符串时作为元素ID范围，返回该ID指向对象
         *              ELEMENT_NODE 传入一个元素对象时，将返回TK.$(ELEMENT_NODE)对象
         *
         * @return TK.$(ele) 返回一个封装的元素对象
         *
         * 方法列表
         * TK.$(ele).getIframeBody() 获取iframe元素引用页面的body对象
         * TK.$(ele).getPos() 获取对象坐标数据 返回 {h : 高，w:宽，x:X坐标, y:Y坐标}
         * TK.$(ele).copyNode()  复制元素, 返回TK.$(ele)对象
         * ...........................见方法注释
         */
        $: function (ele) {
            if (!this.upNode)
                this.upNode = TK.bodyNode;
            if (!ele) {
                throw new Error(ele + ' not found');
            }

            var eleType = typeof (ele);
            if (eleType == 'string' && typeof TK.cache[ele] != 'undefined') {
                return TK.cache[ele];
            }
            var that = this;
            switch (eleType) {
                case  'string':
                    var firstWord = ele.substr(0, 1);
                    var param = ele.substr(1);
                    switch (firstWord) {
                        case '.': //样式名
                            return (function (clsName) {
                                var list = Array();
                                var childList = $(that.upNode).getChilds();
                                for (var t in childList)
                                    !isNaN(t) && TK.$(childList[t]).hasClass(clsName) && (list[list.length] = TK.$(childList[t]));
                                return list;
                            })(param);
                        case '@'://标签名
                            return (function (tagName) {
                                var list = Array();
                                var childList = $(that.upNode).getChilds();
                                for (var t in childList)
                                    !isNaN(t) && TK.$(childList[t]).tag == tagName.toLowerCase() && (list[list.length] = TK.$(childList[t]));
                                return list;
                            })(param);
                        case '%'://NAME名
                            return (function (name) {
                                var list = Array();
                                var childList = $(that.upNode).getChilds();
                                for (var t in childList)
                                    !isNaN(t) && childList[t].getAttribute('name') == name && (list[list.length] = TK.$(childList[t]));
                                return list;
                            })(param);
                        default:
                            var __element = document.getElementById(ele);
                            break;
                    }
                    break;
                case 'array':
                    var list = Array();
                    for (var i in ele)
                        !isNaN(i) && (list[list.length] = TK.$(ele[i]));
                    return list;
                    break;
                default:
                    var __element = ele;
                    break;
            }
            if (!__element)
                //throw new Error(ele + ' not found');
                return false;
            if (typeof (__element) != 'object')
                return false;
            if (!__element.nodeType)
                return false;
            if (__element.nodeType != Node.ELEMENT_NODE)
                return false;
            __element.tag = __element.tagName ? __element.tagName.toLowerCase() : false;
            //__element.$ = TK.$;
            //__element.$.bodyNode = __element;
            __element.inputType = (function () {
                if (__element.tag == 'select')
                    return TK.inputType.INPUT_SELECT;
                if (__element.tag == 'textarea')
                    return TK.inputType.INPUT_TEXTAREA;
                if (__element.tag != 'input')
                    return false;
                if (!__element.getAttribute('type'))
                    return false;
                var nodeTypeAtt = __element.getAttribute('type').toLowerCase();
                switch (nodeTypeAtt) {
                    case 'text':
                        return TK.inputType.INPUT_TEXT;
                    case 'password':
                        return TK.inputType.INPUT_PASSWORD;
                    case 'checkbox':
                        return TK.inputType.INPUT_CHECKBOX;
                    case 'radio':
                        return TK.inputType.INPUT_RADIO;
                    case 'button':
                        return TK.inputType.INPUT_BUTTON;
                    case 'submit':
                        return TK.inputType.INPUT_SUBMIT;
                    case 'image':
                        return TK.inputType.INPUT_IMAGE;
                    default:
                        return false;
                }
            })();
            var __extend = {
                $: function () {
                    this.upNode = this;
                    return TK.$.apply(this, arguments);
                },
                getIframeBody: function () {
                    return navigator.IE ? this.TK.doc.body : this.contentDocument.body;
                },
                setAttr: function (att, value) {
                    return this.setAttribute(att, value);
                },
                getAttr: function (att) {
                    return this.getAttribute(att);
                },
                getText: function () {
                    if (typeof (this.textContent) != 'undefined')
                        return this.textContent;
                    return this.innerText;
                },
                setText: function (text) {
                    if (typeof (this.textContent) != 'undefined')
                        this.textContent = text;
                    else
                        this.innerText = text;
                },
                getPos: function () {
                    var y = this.offsetTop;
                    var x = this.offsetLeft;
                    var height = this.offsetHeight;
                    var width = this.offsetWidth;
                    var obj = this;
                    obj = obj.offsetParent;

                    while (obj) {
                        x += obj.offsetLeft;
                        y += obj.offsetTop;
                        obj = obj.offsetParent;
                    }
                    return {
                        'x': x,
                        'y': y,
                        'h': height,
                        'w': width
                    };
                },
                copyNode: function (deep) {
                    return TK.$(this.cloneNode(deep));
                },
                //根据样式名找子元素
                getNodeByCls: function (clsName) {
                    var childList = this.getChilds();
                    var list = Array();
                    for (var t in childList)
                        if (!isNaN(t) && childList[t].hasClass(clsName))
                            list[list.length] = childList[t];
                    return list;
                },
                //根据指定属性及属性值找子元素
                getChildNodeByAttr: function (attr, value) {
                    var childList = this.getChilds();
                    var list = Array();
                    for (var t in childList)
                        if (!isNaN(t) && childList[t].getAttribute(attr) == value)
                            list[list.length] = childList[t];
                    return list;
                },
                //根据指定属性及属性值找上级元素,最多查找到body
                getParentNodeByAttr: function (attr, value) {
                    if (this.parentNode && this.parentNode.nodeType == Node.ELEMENT_NODE) {
                        if (this.parentNode.getAttribute(attr) == value)
                            return TK.$(this.parentNode);
                        else
                            return TK.$(this.parentNode).getParentNodeByAttr(attr, value);
                    }
                    return false;
                },
                getParentNodeByClass: function (value) {
                    if (this.parentNode && this.parentNode.nodeType == Node.ELEMENT_NODE) {
                        if ($(this.parentNode).hasClass(value))
                            return TK.$(this.parentNode);
                        else
                            return TK.$(this.parentNode).getParentNodeByClass(value);
                    }
                    return false;
                },
                //获取第一ELEMENT_NODE子元素
                getFirstNode: function () {
                    var fNode = this.firstChild;
                    while (fNode) {
                        if (fNode.nodeType == Node.ELEMENT_NODE)
                            return TK.$(fNode);
                        fNode = fNode.nextSibling;
                    }
                    return false;
                },
                //获取最后一个ELEMENT_NODE子元素
                getLastNode: function () {
                    var lNode = this.lastChild;
                    while (lNode) {
                        if (lNode.nodeType == Node.ELEMENT_NODE)
                            return TK.$(lNode);
                        lNode = lNode.previousSibling;
                    }
                    return false;
                },
                //检测当前元素是否是参数指定元素的子元素
                isNodeChild: function (parentNode) {
                    if (this.compareDocumentPosition) {
                        return this.compareDocumentPosition(parentNode) == 10;
                    }
                    return parentNode.contains(this);
                },
                //在第一个子元素前插入一个新节点
                unshiftChild: function (new_node) {
                    if (this.firstChild) {
                        return this.insertBefore(new_node, this.firstChild);
                    }
                    return this.appendChild(new_node);
                },
                //根据标签名查找上级元素,最多查找到body
                getParentNodeByTag: function (tagName) {
                    if (this.parentNode) {
                        if (this.parentNode.tagName.toUpperCase() == 'HTML')
                            return false;
                        if (this.parentNode.tagName == tagName.toUpperCase())
                            return TK.$(this.parentNode);
                        else
                            return TK.$(this.parentNode).getParentNodeByTag(tagName);
                    }
                    return false;
                },
                //根据标签名查找子元素
                getSubNodeByTag: function (tagName) {
                    var childList = this.getChilds();
                    var list = Array();
                    for (var t in childList) {
                        if (!isNaN(t) && TK.$(childList[t]).tag == tagName.toLowerCase())
                            list[list.length] = TK.$(childList[t]);
                    }
                    return list;
                },
                //检查是否有指定样式名
                hasClass: function (cls) {
                    var re = new RegExp('(\\s|^)' + cls + '(\\s|$)');
                    return re.test(this.className);
                },
                //移除指定样式名
                removeClass: function (cls) {
                    if (this.className == cls) {
                        return this.className = '';
                    }
                    if (this.hasClass(cls)) {
                        var reg = new RegExp('(\\s|^)' + cls + '(\\s|$)');
                        this.className = this.className.replace(reg, '');
                    }
                },
                replaceClass: function (oldCls, newCls) {
                    if (this.hasClass(oldCls)) {
                        var reg = new RegExp('(\\s|^)' + oldCls + '(\\s|$)');
                        this.className = this.className.replace(reg, ' ' + newCls + ' ').trim();
                    } else {
                        this.addClass(newCls);
                    }
                },
                //添加一个样式名
                addClass: function (cls) {
                    if (!this.hasClass(cls)) {
                        if (this.className != '') {
                            this.className = this.className += ' ' + cls;
                        } else {
                            this.className = cls;
                        }
                    }
                },
                //设置样式名，会替换原有样式
                setClass: function (cls) {
                    this.className = cls;
                },
                //设置style属性值，会替换原有属性值
                setCss: function (value) {
                    if (navigator.IE)
                        return this.style.cssText = value;
                    this.setAttribute('style', value);
                },
                setOpacity: function (num) {
                    num = navigator.IE ? num : num / 100;
                    return navigator.IE ? this.setStyle("filter", "alpha(opacity=" + num + ");") : this.setStyle('opacity', num);
                },
                appendCss: function (value) {
                    if (navigator.IE)
                        return this.style.cssText = this.style.cssText + value;
                    this.setAttribute('style', this.getAttribute('style') + value);
                },
                //获取元素style属性中指定名字的值
                getStyle: function (ns) {
                    ns = this.convStyleName(ns);
                    if (TK.doc.defaultView)
                        return TK.doc.defaultView.getComputedStyle(this, null)[ns];
                    if (this.currentStyle)
                        return this.currentStyle[ns];
                    if (this.style[ns])
                        return this.style[ns];
                    return null;
                },
                convStyleName: function (ns) {
                    var b = ns.strpos('-');
                    if (b && b > 0) {
                        var l = ns.split('-');
                        ns = l[0];
                        for (var i = 1; i < l.length; i++) {
                            ns += l[i].ucfirst();
                        }
                    }
                    return ns;
                },
                //设置一个style属性值
                setStyle: function (ns, value) {
                    ns = this.convStyleName(ns);
                    this.style[ns] = value;
                },
                //绝对定位时，让元素位于顶部
                setOnTop: function () {
                    var index = TK.maxZIndex + 1;
                    TK.maxZIndex = index;
                    this.setStyle('z-index', index);
                },
                //设置元素z-index值
                setZIndex: function (idx) {
                    if (idx > TK.maxZIndex)
                        TK.maxZIndex = idx;
                    this.setStyle('z-index', idx);
                },
                //元素下一个ELEMENT_NODE元素
                nextNode: function () {
                    var nNode = this.nextSibling;
                    while (nNode) {
                        if (nNode.nodeType == Node.ELEMENT_NODE)
                            return TK.$(nNode);
                        nNode = nNode.nextSibling;
                    }
                    return false;
                },
                //元素上一个ELEMENT_NODE元素
                previousNode: function () {
                    var pNode = this.previousSibling;
                    while (pNode) {
                        if (pNode.nodeType == Node.ELEMENT_NODE)
                            return TK.$(pNode);
                        pNode = pNode.previousSibling;
                    }
                    return false;
                },
                delListener: function (e, call_action) {
                    if (e == 'scroll') {
                        for (var i in TK.scrollFuncList) {
                            if (TK.scrollFuncList[i] == call_action) {
                                delete TK.scrollFuncList[i];
                            }
                        }
                    }
                    if (typeof call_action == 'number') {
                        call_action = TK.eventList[e][call_action];
                        delete TK.eventList[e][call_action];
                    }
                    if (this.removeEventListener) {
                        this.removeEventListener(e, call_action, false);
                    } else if (this.detachEvent) {
                        this.detachEvent(e, call_action);
                    } else {
                        this[e] = null;
                    }
                },
                addListener: function (e, call_action) {
                    call_action.handObj = this;
                    var iserr = false;
                    var l = null;
                    switch (e) {
                        case 'scroll':
                            this.scrollOffset = TK.scrollOffset();
                            TK.windowScrollEventCallFunctionList.push(call_action);
                            return;
                        case 'resize':
                            l = {
                                func: call_action,
                                obj: this
                            };
                            if (window.top == window.self) {
                                TK.windowResizeCallFunctionList.push(l);
                            } else if (window.top.X) {
                                window.top.TK.windowResizeCallFunctionList.push(l);
                            }
                            return;
                        case 'error':
                            iserr = true;
                        case 'load':
                            if (navigator.IE && this.tag == 'script') {
                                this.onreadystatechange = function (e) {
                                    if (script.readyState == 'loaded') {
                                        if (iserr)
                                            call_action(e);
                                    } else if (script.readyState == 'complete') {
                                        if (!iserr)
                                            call_action(e);
                                    }
                                };
                                return;
                            }
                            break;
                    }
                    if (typeof TK.eventList[e] == 'undefined')
                        TK.eventList[e] = [];
                    l = TK.eventList[e].length;

                    TK.eventList[e].push(function () {
                        var that = this;
                        this.eventId = l;
                        this.clear = function () {
                            that.delListener(e, that.eventId);
                        };
                        call_action.apply(this, arguments);
                    }
                    );
                    if (this.addEventListener) {
                        this.addEventListener(e, TK.eventList[e][l], false);
                    } else if (this.attachEvent) {
                        this.attachEvent('on' + e, TK.eventList[e][l]);
                    } else {
                        var elementEvent = this[e];
                        this[e] = function () {
                            var callEvent = elementEvent.apply(this, arguments);
                            var actEvent = TK.eventList[e][l].apply(this, arguments);
                            return (callEvent == undefined) ? actEvent : (actEvent == undefined ? TK.eventList[e][l] : (actEvent && TK.eventList[e][l]));
                        };
                    }
                    return l;
                },
                getChilds: function (cache) {
                    var list = Array();
                    var obj = this;
                    //    var cacheData = TK.getCache('getChildsList',obj);
                    //    if(cacheData) {
                    //        return cacheData;
                    //    }

                    var f = obj.getFirstNode();
                    if (f) {
                        list[list.length] = f;
                        var cL = f.getChilds();
                        if (cL && cL.length > 0) {
                            list = list.concat(cL);
                        }
                        var next = f.nextNode();
                        while (next) {
                            list[list.length] = next;
                            var nL = next.getChilds();
                            if (nL && nL.length > 0) {
                                list = list.concat(nL);
                            }
                            next = next.nextNode();
                        }
                    }
                    ;
                    //   TK.setCache(obj,list,'getChildsList');
                    return list;
                },
                //提交表单
                submitForm: function (func, enter) {
                    var eventObj = this;
                    var _submitForm = function (e) {
                        TK.submitForm(eventObj, func);
                    };
                    this.addListener('click', _submitForm);
                    if (enter) {
                        TK.keyDown().enter(_submitForm);
                    }
                },
                toCenterProto: function (eff, spec) {
                    if (this.style.display == 'none')
                        this.style.display = 'block';
                    this.style.position = 'absolute';
                    var objPos = this.getPos();
                    if (typeof spec != 'undefined') {
                        var specPos = spec.getPos ? spec.getPos() : TK.$(spec).getPos();
                    }
                    var pageSize = TK.pageShowSize();
                    var refObjHeight = spec ? specPos.h : pageSize.h;
                    var refObjWeight = spec ? specPos.w : pageSize.w;
                    var YOffset = TK.scrollOffset().y;
                    var XOffset = TK.scrollOffset().x;
                    var topY = refObjHeight / 3 - objPos.h / 2;
                    var leftX = refObjWeight / 2 - objPos.w / 2;
                    if (YOffset > 0)
                        topY = YOffset + topY;
                    if (XOffset > 0)
                        left = XOffset + leftX;
                    if (spec) {
                        topY = topY + specPos.y;
                        leftX = leftX + specPos.x;
                    }
                    if (topY < 0)
                        topY = 0;
                    this.style.left = leftX + 'px';
                    if (eff == 1) {
                        var obj = this;
                        if (objPos.y < YOffset)
                            obj.style.top = YOffset + 'px';
                        if (this.interOffsetEff)
                            TK.clearInterval(this.interOffsetEff);
                        var MoveDown = objPos.y <= topY;
                        var step = Math.abs(topY - objPos.y) / 100;
                        this.interOffsetEff = TK.setInterval(function () {
                            var y = obj.getPos().y;
                            if (y >= topY && !MoveDown) {
                                y = y - step;
                                obj.style.top = y + 'px';
                                return;
                            }
                            if (y <= topY && MoveDown) {
                                y = y + step;
                                obj.style.top = y + 'px';
                                return;
                            }
                            TK.clearInterval(this.interOffsetEff);
                        }, 10);
                    } else {
                        this.style.top = topY + 'px';
                    }
                    return false;
                },
                //让元素对象居中,spec为true标识是否在页面滚动时居中
                toCenter: function (eff, spec) {
                    if (spec)
                        this.addListener('scroll', this.scrollMove);
                    this.toCenterProto(eff, spec);
                },
                scrollOffset: {},
                scrollMove: function (e) {
                    this.toCenterProto(1);
                },
                mousePopNearX: 5,
                mousePopNearY: 5,
                //元素跟随鼠标
                mousePop: function (e) {
                    var mousePos = TK.mousePos(e);
                    var scroll = TK.scrollOffset();
                    this.toPos(mousePos.x + this.mousePopNearX + scroll.x, mousePos.y + this.mousePopNearY + scroll.y);
                },
                //元素跟随指定对象
                byNodePop: function (byObj, direct) {
                    if (!byObj.getPos)
                        byObj = TK.$(byObj);
                    var pop = this;

                    var setPos = function (direct) {
                        var pos = byObj.getPos();
                        var popPos = pop.getPos();
                        var left = 0;
                        switch (direct) {
                            case 1: //位于下侧靠左
                                pop.toPos(pos.x, pos.y + pos.h);
                                return;
                            case 2: //位于下侧靠右
                                pop.toPos(pos.x + pos.w - popPos.w, pos.y + pos.h);
                                return;
                            case 3: //左侧居上
                                pop.toPos(pos.x, pos.y);
                                return;
                            case 4: //右侧居内
                                var w = pop.getStyle('width').replace(/[A-za-z]+/i, '');
                                left = pos.x + pos.w * 1 - w;
                                pop.toPos(pos.x + pos.w - w, pos.y);
                                return;
                            default:  //默认位于右侧居上
                                var pagePos = TK.pageShowSize();
                                left = pos.x + pos.w;
                                if (pos.x + pos.w + popPos.w > pagePos.w) {
                                    left = pos.x - popPos.w;
                                }
                                var t = pos.y;
                                if (pos.y + popPos.h > pagePos.h) {
                                    t = pos.y + pos.h - popPos.h;
                                }
                                if (t < 0)
                                    t = 0;
                                pop.toPos(left, t);
                                return;
                        }
                    };
                    var pmof = function (e) {
                        var overNode = TK.getEventNode(e);
                        if (overNode == byObj || overNode == pop || overNode.isNodeChild(byObj) || overNode.isNodeChild(pop)) {
                            if (pop.style.display == 'none') {
                                pop.style.display = 'block';
                                setPos(direct);
                            }
                            return;
                        }
                        pop.style.display = 'none';
                    };
                    TK.mouseover(pmof, byObj);
                    setPos(direct);
                },
                //放大图片
                maxImg: function (cls, bsrc, altShow, altClose) {
                    if (this.tag != 'img')
                        return;
                    this.setAttribute('title', altShow);
                    this.addListener('click', function (e) {
                        var pPos = TK.pageShowSize();
                        var src = bsrc ? bsrc : TK.getEventNode(e).src;
                        var bg = TK.createNode('div');
                        bg.addClass(cls);
                        var img = TK.createNode('img');
                        img.setAttribute('src', src);
                        img.setAttribute('title', altClose);
                        var hide = function (e) {
                            bg.destroy();
                            img.destroy();
                        };
                        bg.addListener('click', hide);
                        img.addListener('click', hide);
                        var alpha = TK.getOpacityStr(80);
                        bg.setCss('position:absolute;left:0;top:0;width:' + pPos.w + 'px;height:' + pPos.h + 'px;' + alpha);
                        bg.setOnTop();
                        img.setCss('position:absolute;');
                        bg.setOnTop();
                        TK.doc.body.appendChild(img);
                        TK.doc.body.appendChild(bg);
                        img.toCenter();
                        img.setOnTop();
                    });
                },
                //将元素移动到指定坐标
                toPos: function (x, y) {
                    this.style.position = 'absolute';
                    this.setStyle('top', y + 'px');
                    this.setStyle('left', x + 'px');
                    this.setOnTop();
                },
                //元素可移动，down为鼠标按下该元素时可移动,spec为只能在该元素范围内移动
                move: function (down, spec) {
                    var NodeMoveObj = {};
                    var eventCount = 0;
                    NodeMoveObj.pointerNode = down ? down : this;
                    if (!NodeMoveObj.pointerNode.setStyle)
                        NodeMoveObj.pointerNode = TK.$(NodeMoveObj.pointerNode);
                    NodeMoveObj.pointerNode.setStyle('cursor', 'default');
                    this.setStyle('position', 'absolute');
                    NodeMoveObj.moveNode = this;
                    NodeMoveObj.mousedown = false;
                    NodeMoveObj.moveRange = false;
                    if (spec) {
                        var RangePos = typeof spec.getPos == 'undefined' ? TK.$(spec).getPos() : spec.getPos();
                        NodeMoveObj.moveRange = {};
                        NodeMoveObj.moveRange.minX = RangePos.x;
                        NodeMoveObj.moveRange.minY = RangePos.y;
                        NodeMoveObj.moveRange.maxX = RangePos.x + RangePos.w;
                        NodeMoveObj.moveRange.maxY = RangePos.y + RangePos.h;
                    }
                    var mousDown = function (e) {
                        TK.delDefultEvent(e);
                        NodeMoveObj.startPos = NodeMoveObj.moveNode.getPos();
                        NodeMoveObj.mousedown = true;
                        NodeMoveObj.mosePos = TK.mousePos(e);
                        NodeMoveObj.pointerNode.setStyle('cursor', 'move');

                    };
                    var endMove = function (e) {
                        if (TK.getEventNode(e) !== down) {
                            return;
                        }
                        TK.delDefultEvent(e);
                        NodeMoveObj.mousedown = false;
                        NodeMoveObj.pointerNode.setStyle('cursor', 'default');

                    };
                    var endMoveUp = function (e) {
                        NodeMoveObj.mousedown = false;
                        NodeMoveObj.pointerNode.setStyle('cursor', 'default');
                    };
                    var moveNode = function (e) {
                        TK.delDefultEvent(e);
                        if (NodeMoveObj.mousedown == false)
                            return;
                        eventCount++;
                        if (eventCount % 2 === 0) {
                            return;
                        }
                        var mousePrePos = NodeMoveObj.mosePos;
                        NodeMoveObj.mousePos = TK.mousePos(e);
                        var offsetX = NodeMoveObj.mousePos.x - mousePrePos.x;
                        var offsetY = NodeMoveObj.mousePos.y - mousePrePos.y;
                        var moveToX = NodeMoveObj.startPos.x + offsetX;
                        var moveToY = NodeMoveObj.startPos.y + offsetY;
                        if (NodeMoveObj.moveRange != false) {
                            if (NodeMoveObj.moveRange.minX >= moveToX)
                                moveToX = NodeMoveObj.moveRange.minX;
                            if (NodeMoveObj.moveRange.minY >= moveToY)
                                moveToY = NodeMoveObj.moveRange.minY;
                            if (NodeMoveObj.moveRange.maxX <= moveToX + NodeMoveObj.startPos.w)
                                moveToX = NodeMoveObj.moveRange.maxX - NodeMoveObj.startPos.w;
                            if (NodeMoveObj.moveRange.maxY <= moveToY + NodeMoveObj.startPos.h)
                                moveToY = NodeMoveObj.moveRange.maxY - NodeMoveObj.startPos.h;
                        }
                        NodeMoveObj.moveNode.style.top = Math.ceil(moveToY) + 'px';
                        NodeMoveObj.moveNode.style.left = Math.ceil(moveToX) + 'px';
                        return;
                    };
                    down.addListener('mousemove', moveNode);
                    down.addListener('mousedown', mousDown);
                    down.addListener('mouseout', endMove);
                    down.addListener('mouseup', endMoveUp);
                },
                //双击时放大对象，spec为只能放大到该元素范围，part为点击对象,type为true时为单击，否则为双击
                maxsize: function (spec, part, type) {
                    var maxSizeNode = spec ? spec : TK.$(TK.doc.body);
                    var clickNode = part ? TK.$(part) : this;
                    var maxSize = maxSizeNode.getPos ? maxSizeNode.getPos() : TK.$(maxSizeNode).getPos;
                    var initSize = this.getPos();
                    var changeNode = this;
                    var nodeToMaxSize = function (e) {
                        var nodePos = changeNode.getPos();
                        if (nodePos.w < maxSize.w || nodePos.h < maxSize.h) {
                            initSize = changeNode.getPos();
                            changeNode.style.top = maxSize.y + 'px';
                            changeNode.style.left = maxSize.x + 'px';
                            changeNode.style.width = maxSize.w + 'px';
                            changeNode.style.height = maxSize.h + 'px';
                        } else {
                            changeNode.style.top = initSize.y + 'px';
                            changeNode.style.left = initSize.x + 'px';
                            changeNode.style.width = initSize.w + 'px';
                            changeNode.style.height = initSize.h + 'px';
                        }
                    };
                    if (type) {
                        clickNode.addListener('click', nodeToMaxSize);
                    } else {
                        clickNode.addListener('dblclick', nodeToMaxSize);
                    }
                },
                //使元素可修改尺寸,spec为只能在该元素范围内，sens为鼠标灵敏度
                resize: function (sens, spec) {
                    var resizeNodeObj = {};
                    resizeNodeObj.node = this;
                    resizeNodeObj.cursorList = {
                        ltc: 'nw-resize',
                        lbc: 'sw-resize',
                        l: 'w-resize',
                        rbc: 'se-resize',
                        rtc: 'ne-resize',
                        r: 'e-resize',
                        t: 'n-resize',
                        b: 's-resize'
                    };
                    resizeNodeObj.sens = sens ? sens : 10;
                    resizeNodeObj.startResize = false;
                    var setMouseCursor = function (e) {
                        TK.delDefultEvent(e);
                        var nodePos = resizeNodeObj.node.getPos();
                        var mousePos = TK.mousePos(e);
                        var minX = nodePos.x;
                        var minXS = nodePos.x + resizeNodeObj.sens;
                        var minY = nodePos.y;
                        var minYS = nodePos.y + resizeNodeObj.sens;
                        var maxX = nodePos.x + nodePos.w;
                        var maxXS = maxX - resizeNodeObj.sens;
                        var maxY = nodePos.y + nodePos.h;
                        var maxYS = maxY - resizeNodeObj.sens;
                        var mouseX = 0, mouseY = 0;
                        if (mousePos.x >= minX && mousePos.x <= minXS) {
                            var mouseX = 1;
                        } else if (mousePos.x <= maxX && mousePos.x >= maxXS) {
                            var mouseX = 2;
                        } else {
                            var mouseX = 3;
                        }
                        if (mousePos.y >= minY && mousePos.y <= minYS) {
                            var mouseY = 1;
                        } else if (mousePos.y <= maxY && mousePos.y >= maxYS) {
                            var mouseY = 2;
                        } else {
                            var mouseY = 3;
                        }
                        if (mouseY == 1 && mouseX == 1)
                            resizeNodeObj.node.setStyle('cursor', resizeNodeObj.cursorList.ltc);
                        else if (mouseX == 1 && mouseY == 2)
                            resizeNodeObj.node.setStyle('cursor', resizeNodeObj.cursorList.lbc);
                        else if (mouseX == 1 && mouseY == 3)
                            resizeNodeObj.node.setStyle('cursor', resizeNodeObj.cursorList.l);
                        else if (mouseX == 2 && mouseY == 1)
                            resizeNodeObj.node.setStyle('cursor', resizeNodeObj.cursorList.rtc);
                        else if (mouseX == 2 && mouseY == 2)
                            resizeNodeObj.node.setStyle('cursor', resizeNodeObj.cursorList.rbc);
                        else if (mouseX == 2 && mouseY == 3)
                            resizeNodeObj.node.setStyle('cursor', resizeNodeObj.cursorList.r);
                        else if (mouseX == 3 && mouseY == 1)
                            resizeNodeObj.node.setStyle('cursor', resizeNodeObj.cursorList.t);
                        else if (mouseX == 3 && mouseY == 2)
                            resizeNodeObj.node.setStyle('cursor', resizeNodeObj.cursorList.b);
                        else
                            resizeNodeObj.node.setStyle('cursor', 'auto');
                        if (resizeNodeObj.startResize && !(mouseX == 3 && mouseY == 3)) {
                            var mousePrePos = resizeNodeObj.mosePos;
                            resizeNodeObj.mousePos = TK.mousePos(e);
                            var offsetX = resizeNodeObj.mousePos.x - mousePrePos.x;
                            var offsetY = resizeNodeObj.mousePos.y - mousePrePos.y;
                            //var moveToX = resizeNodeObj.startPos.x + offsetX;
                            //var moveToY = resizeNodeObj.startPos.y + offsetY;
                            var moveToX = resizeNodeObj.startPos.x;
                            var width = resizeNodeObj.startPos.w;
                            var height = resizeNodeObj.startPos.h;
                            var moveToY = resizeNodeObj.startPos.y;

                            if (mouseX == 1) {
                                width = resizeNodeObj.startPos.w - offsetX;
                                moveToX = resizeNodeObj.startPos.x + offsetX;
                            } else if (mouseX == 2) {
                                width = resizeNodeObj.startPos.w + offsetX;
                            }
                            if (mouseY == 1) {
                                height = resizeNodeObj.startPos.h - offsetY;
                                moveToY = resizeNodeObj.startPos.y + offsetY;
                            } else if (mouseY == 2) {
                                height = resizeNodeObj.startPos.h + offsetY;
                            }
                            resizeNodeObj.node.style.top = moveToY + 'px';
                            resizeNodeObj.node.style.height = height + 'px';
                            resizeNodeObj.node.style.left = moveToX + 'px';
                            resizeNodeObj.node.style.width = width + 'px';
                        }
                    };
                    function endResizeNode(e) {
                        TK.delDefultEvent(e);
                        resizeNodeObj.node.setStyle('cursor', 'auto');
                        resizeNodeObj.startResize = false;
                    }
                    ;
                    function startResizeNode(e) {
                        TK.delDefultEvent(e);
                        resizeNodeObj.startResize = true;
                        resizeNodeObj.startPos = resizeNodeObj.node.getPos();
                        resizeNodeObj.mosePos = TK.mousePos(e);
                    }
                    ;
                    this.addListener('mousemove', setMouseCursor);
                    this.addListener('mousedown', startResizeNode);
                    this.addListener('mouseup', endResizeNode);
                    this.addListener('mouseout', endResizeNode);
                    if (spec) {
                        spec = spec.addListener ? spec : TK.$(spec);
                        spec.addListener('mouseout', endResizeNode);
                    }
                },
                //隐藏元素，spec为点击该元素隐藏
                close: function (spec) {
                    var clickNode = spec ? (spec.getPos ? spec : TK.$(spec)) : this;
                    clickNode.addListener('click', function (e) {
                        clickNode.style.display = 'none';
                    });
                },
                //隐藏元素，visibility为隐藏后是否保留位置
                hide: function (visibility) {
                    if (visibility) {
                        this.style.visibility = 'hidden';
                    } else {
                        this.style.display = 'none';
                    }
                },
                show: function (visibility) {
                    if (visibility) {
                        this.style.visibility = 'visible';
                    } else {
                        this.style.display = 'block';
                    }
                },
                //销毁元素
                destroy: function () {
                    if (this == window)
                        return;
                    this.parentNode.removeChild(this);
                    delete this;
                }
            };
            for (var fn in __extend) {
                __element[fn] = __extend[fn];
            }
            //TK.cache[ele] = __element;
            return __element;
        },
        //设置光标偏移量
        setCursorOffset: function (offset, start) {
            if (!TK.doc.hasFocus()) {
                return;
            }
            if (start) {
                offset = offset + start;
            }
            var focusNode = TK.getFocusNode();
            if (window.getSelection()) {
                cur = TK.getCursorOffset();
                var s = window.getSelection();
                s.collapse(focusNode, offset);
                return;
            }
            if (TK.doc.createTextRange) {
                var rangeObj = TK.doc.createTextRange();
                rangeObj.collapse(true);
                rangeObj.moveEnd('character', pos);
                rangeObj.moveStart('character', pos);
                rangeObj.select();
            }
        },
        addCursorSelect: function (start, offset) {
            if (!TK.doc.hasFocus()) {
                return false;
            }
            if (window.getSelection()) {
                var s = window.getSelection();
                var focusNode = TK.getFocusNode();
                var range = TK.doc.createRange();
                range.setStart(focusNode, start);
                range.setEnd(focusNode, start + offset);
                range.selectNode(focusNode);
                s.addRange(range);
            }
        },
        //获取当前输入区，光标偏移量
        getCursorOffset: function () {
            if (window.getSelection)
                return window.getSelection().getRangeAt(0).startOffset;
            if (TK.doc.selection) {
                var selectionObj = TK.doc.selection.createRange();
                selectionObj.moveStart('character', -this.value.length);
                return selectionObj.text.length;
            }
            return 0;
        },
        /**
         *  AJAX对象
         *
         *  TK.Ajax.get(url, callFunc) GET方法请求,
         *             url      : string    请求URL
         *             callFunc : function  请求返回回调函数
         *                            callFunc(returnData)回调函数
         *  TK.Ajax.post(url, data, callFunc) POST方法请求
         *             url      : string  请求URL
         *             data     : JSON  请求数据
         *             callFunc : function 请求返回回调函数
         *  TK.Ajax.head(url,callFunc) HEAD方法请求
         *             url      : string  请求URL
         *             callFunc : function 请求返回回调函数
         *                           callFunc(responseHead)
         *  TK.Ajax.put(url, data, callFunc) PUT 方法请求
         *  TK.Ajax.options(url ,callFunc)  OPTIONS 方法请求
         *  TK.Ajax.del(url,callFunc)  DELETE方法请求
         *  TK.Ajax.trace(url,callFunc) TRACE方法请求
         *                           callFunc(responseHead, responseText)
         *  TK.Ajax.file(formObj, callFunc)  上传文件
         *              formObj  : ELEMENT_NODE  上传文件表单
         *              callFunc : function  请求返回回调函数
         *  TK.Ajax.jsonp(url,callFunc)  JSONP请求
         *  TK.Ajax.waitTime : 等待超时时间
         */
        Ajax: {
            XMLHttp: null,
            dataType: 'json', //text, json, xml
            charset: 'utf-8',
            MimeType: 'text/html;charset=utf-8',
            url: null,
            method: null,
            data: null,
            callFunc: [],
            defaultDomain: window.location.host,
            waitTime: 10000,
            outObj: [],
            formObj: null,
            reponseContentType: null,
            messageList: {
                start: '',
                complete: '',
                still: '',
                current: ''
            },
            message: null,
            messageNode: null,
            showTime: 2000,
            hiddenStatus: true,
            openInstance: [],
            openInstanceId: 0,
            setMimeType: function () {
                mime = 'text/html';
                if (TK.Ajax.dataType.toLowerCase() == 'json') {
                    mime = 'text/html';
                } else {
                    mime = 'text/xml';
                }
                TK.Ajax.MimeType = mime + ';charset=' + TK.Ajax.charset;
            },
            setUrl: function (url) {
                if (url.substr(0, 4).toLowerCase() != 'http://' &&
                        url.substr(0, 5).toLowerCase() != 'https://') {
                    var protocol = window.location.protocol == "https:" ? 'https' : 'http';
                    if (url.substr(0, 1) != '/' || url.substr(0, 2) == './') {
                        if (url.substr(0, 2) == './') {
                            url = url.substr(2, url.length);
                        }
                        url = TK.dirname(TK.realpath(window.location.href)) + '/' + url;
                    } else {
                        url = protocol + '://' + TK.Ajax.defaultDomain + url;
                    }
                }
                TK.Ajax.url = url.strpos('?') != false ? url + '&' + TK.Ajax.dataType.toLowerCase() + '=1' : url + '?is_ajax=1';
                TK.Ajax.url += '&t=' + (new Date().getTime());
            },
            del: function (url, callFunc) {
                TK.Ajax.__get(url, callFunc, 'DELETE');
            },
            head: function (url, callFunc) {
                TK.Ajax.__get(url, callFunc, 'HEAD');
            },
            get: function (url, callFunc) {
                TK.Ajax.__get(url, callFunc, 'GET');
            },
            options: function (url, callFunc) {
                TK.Ajax.__get(url, callFunc, 'OPTIONS');
            },
            trace: function (url, callFunc) {
                TK.Ajax.__get(url, callFunc, 'TRACE');
            },
            __get: function (url, callFunc, method) {
                TK.Ajax.init();
                TK.Ajax.setUrl(url);
                TK.Ajax.method = method;
                TK.Ajax.callServer(callFunc);
            },
            put: function (url, data, callFunc) {
                TK.Ajax.init();
                TK.Ajax.setUrl(url);
                TK.Ajax.setData(data);
                TK.Ajax.method = 'PUT';
                TK.Ajax.callServer(callFunc);
            },
            post: function (url, data, callFunc) {
                TK.Ajax.init();
                TK.Ajax.setUrl(url);
                TK.Ajax.setData(data);
                TK.Ajax.method = 'POST';
                TK.Ajax.callServer(callFunc);
            },
            jsonp: function (url, callFunc) {
                var openId = TK.Ajax.openInstanceId;
                TK.Ajax.setUrl(url);
                TK.Ajax.url += '&jsonp=TK.Ajax.callback';
                TK.Ajax.openInstance[openId] = {};
                TK.Ajax.openInstance[openId].url = TK.Ajax.url;
                if (callFunc)
                    TK.Ajax.openInstance[openId].callFunc = callFunc;
                TK.Ajax.openInstance[openId].js = TK.loadJSFile(TK.Ajax.url, true);
                TK.Ajax.openInstance[openId].js.addListener('error', TK.Ajax.jsonperror);
                TK.Ajax.openInstanceId++;
            },
            jsonperror: function (e) {
                var js = TK.getEventNode(e);
                js.destroy();
                console.warn('JSONP Load Error');
            },
            callback: function (reData) {
                var csrc = TK.doc.scripts;
                csrc = csrc[csrc.length - 1];
                for (var i in TK.Ajax.openInstanceId) {
                    var sIns = TK.Ajax.openInstanceId[i];
                    if (sIns.url && sIns.callFunc && sIns.url == csrc) {
                        sIns.callFunc(reData);
                    }
                }
            },
            socket: function (url, openFunc, receiveFunc) {
                var socket = null;
                if (navigator.FIREFOX && typeof (WebSocket) == 'undefined') {
                    socket = new MozWebSocket(url);
                } else if (typeof (WebSocket) == 'undefined') {
                    return false;
                }
                if (url.substr(0, 4).toLowerCase() != 'ws://' && url.substr(0, 5).toLowerCase() != 'wss://') {
                    var protocol = window.location.protocol == "https:" ? 'wss' : 'ws';
                    url = protocol + '://' + TK.Ajax.defaultDomain + url;
                }
                socket = new WebSocket(url);
                socket.onopen = openFunc;
                socket.onmessage = receiveFunc;
                return socket;
            },
            file: function (form, callFunc) {
                var enc = form.getAttribute('enctype');
                if (enc != 'multipart/form-data') {
                    form.setAttribute('enctype', 'multipart/form-data');
                }
                TK.Ajax.setUrl(form.getAttribute('action'));
                form.setAttribute('action', TK.Ajax.url);
                var target_name = 'XAjaxIframe' + TK.rand(10);
                form.setAttribute('target', target_name);
                var upload_target = TK.createNode('iframe');
                upload_target.setAttribute('name', target_name);
                upload_target.setCss('border:none;height:0;width:0;');
                upload_target.setAttribute('frameboder', 'none');
                TK.doc.body.appendChild(upload_target);
                upload_target.addListener('readystatechange', function () {
                    if (document.readyState == 'loaded') {
                        console.warn('Ajax Uplad File Error');
                        return false;
                    }
                    if (document.readyState == 'complete') {
                        var restr = upload_target.getIframeBody().innerHTML;
                        setTimeout(function () {
                            upload_target.destroy();
                        }, 1000);
                        if (restr == '') {
                            callFunc('');
                        }
                        if (TK.Ajax.dataType.toLowerCase() == 'json') {
                            try {
                                var res = JSON.parse(restr);
                            } catch (e) {
                                if (/413/i.test(restr)) {
                                    console.warn('Ajax upload file is Too large');
                                    return 413;
                                }
                                if (/512/i.test(restr)) {
                                    console.warn('Ajax upload file timeout');
                                    return 512;
                                }
                                console.warn('Ajax Upload File response data is not JSON' + e);
                            }
                            try {
                                callFunc(res);
                            } catch (e) {
                                console.warn('Callback Function Error:' + e.message + ' in File ' + e.fileName + ' line ' + e.lineNumber);
                            }
                        } else {
                            try {
                                callFunc(restr);
                            } catch (e) {
                                console.warn('Callback Function Error:' + e.message + ' in File ' + e.fileName + ' line ' + e.lineNumber);
                            }
                        }
                    }
                });
                form.submit();
            },
            setData: function (data) {
                var str = '';
                for (i in data)
                    if (isNaN(i))
                        str += i + '=' + encodeURIComponent(data[i]) + '&';
                TK.Ajax.data = str;
            },
            complete: function () {
                TK.Ajax.message = TK.Ajax.messageList.complete;
                TK.Ajax.showMessageNode();
                TK.setTimeout(TK.Ajax.hiddenMessageNode, TK.Ajax.showTime);
            },
            hiddenMessageNode: function () {
                if (TK.Ajax.hiddenStatus)
                    return;
                if (TK.Ajax.messageNode)
                    TK.Ajax.messageNode.style.display = 'none';
            },
            showMessageNode: function () {
                if (TK.Ajax.hiddenStatus)
                    return;
                if (TK.Ajax.messageNode != null) {
                    TK.Ajax.messageNode.style.display = 'block';
                    TK.Ajax.messageNode.innerHTML = TK.Ajax.message;
                }
            },
            showStatus: function () {
                TK.Ajax.statusObj = TK.setTimeout(function () {
                    TK.Ajax.message = TK.Ajax.messageList.still;
                    TK.Ajax.showMessageNode();
                }, 3000);
            },
            callServer: function (callFunc) {
                if (!TK.Ajax.XMLHttp)
                    return;
                TK.Ajax.message = TK.Ajax.messageList.current;
                TK.Ajax.showMessageNode();
                var openId = TK.Ajax.openInstanceId;
                TK.Ajax.openInstance[openId] = {};
                if (callFunc)
                    TK.Ajax.openInstance[openId].callFunc = callFunc;
                TK.Ajax.openInstanceId++;

                TK.Ajax.openInstance[openId].XMLHttp = TK.Ajax.XMLHttp;
                TK.Ajax.openInstance[openId].XMLHttp.open(TK.Ajax.method, TK.Ajax.url, TK.Ajax.waitTime);
                if (TK.Ajax.method == "POST")
                    TK.Ajax.openInstance[openId].XMLHttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                TK.Ajax.openInstance[openId].XMLHttp.send(TK.Ajax.data);
                TK.Ajax.openInstance[openId].outObj = TK.setTimeout(function () {
                    TK.Ajax.openInstance[openId].XMLHttp.abort();
                    delete TK.Ajax.openInstance[openId];
                    TK.Ajax.complete();
                }, TK.Ajax.waitTime);
                TK.Ajax.openInstance[openId].method = TK.Ajax.method;
                TK.Ajax.showStatus();
                TK.Ajax.openInstance[openId].XMLHttp.onreadystatechange = function () {
                    if (TK.Ajax.openInstance[openId].XMLHttp.readyState == 4) {
                        TK.clearTimeout(TK.Ajax.openInstance[openId].outObj);
                        TK.clearTimeout(TK.Ajax.statusObj);
                        TK.Ajax.complete();

                        if (TK.Ajax.openInstance[openId].method == 'HEAD') {
                            if (TK.Ajax.openInstance[openId].XMLHttp.status == 0) {
                                return TK.Ajax.openInstance[openId].callFunc(0);
                            }

                            var headerStr = TK.Ajax.openInstance[openId].XMLHttp.getAllResponseHeaders();
                            var headerArr = headerStr.split("\r\n");
                            var header = [];
                            for (var h in headerArr) {
                                if (typeof (headerArr[h]) == 'string') {
                                    var fvs = headerArr[h].trim();
                                    if (fvs == '')
                                        continue;
                                    var fv = fvs.split(':');
                                    header[fv[0].trim()] = fv[1].trim();
                                }
                            }

                            TK.Ajax.openInstance[openId].callFunc(header);
                            return null;
                        }
                        if (TK.Ajax.openInstance[openId].method == 'TRACE') {
                            TK.Ajax.openInstance[openId].callFunc(
                                    TK.Ajax.openInstance[openId].XMLHttp.getAllResponseHeaders(),
                                    TK.Ajax.openInstance[openId].XMLHttp.responseText);
                            return null;
                        }
                        //if (TK.Ajax.openInstance[openId].XMLHttp.status == 200) {

                        if (TK.Ajax.openInstance[openId].XMLHttp.status) {
                            var reData = null;
                            switch (TK.Ajax.dataType.toLowerCase()) {
                                case 'xml':
                                    reData = TK.Ajax.openInstance[openId].XMLHttp.responseXML;
                                    break;
                                case 'json':
                                    reData = TK.Ajax.openInstance[openId].XMLHttp.responseText;
                                    if (reData != '') {
                                        try {
                                            reData = JSON.parse(reData);
                                        }
                                        catch (e) {
                                            console.warn('Ajax JSON Parse Error: ' + e + ' in File ' + e.fileName + ' Line ' + e.lineNumber);
                                            return null;
                                        }
                                    }
                                    break;
                                default:
                                    reData = TK.Ajax.openInstance[openId].XMLHttp.responseText;
                                    break;
                            }
                            if (TK.Ajax.openInstance[openId].callFunc) {
                                TK.Ajax.openInstance[openId].callFunc(reData,
                                        TK.Ajax.openInstance[openId].XMLHttp.status);
                                /*try { TK.Ajax.openInstance[openId].callFunc(reData);
                                 } catch(e) {
                                 console.warn('Callback Function Error:'+e.message + ' in File '+e.fileName+' line '+e.lineNumber);
                                 }*/
                            }
                        } else {
                            console.warn('Ajax requset timeout');
                        }
                        delete TK.Ajax.openInstance[openId];
                    }
                };
            },
            init: function () {
                TK.Ajax.setMimeType();
                if (window.XMLHttpRequest) {
                    TK.Ajax.XMLHttp = new XMLHttpRequest();
                    if (TK.Ajax.XMLHttp.overrideMimeType) {
                        TK.Ajax.XMLHttp.overrideMimeType(TK.Ajax.MimeType);
                    }
                } else if (window.ActiveXObject) {
                    var versions = ['Microsoft.XMLHTTP', 'MSXML6.XMLHTTP', 'MSXML5.XMLHTTP', 'MSXML4.XMLHTTP', 'MSXML3.XMLHTTP', 'MSXML2.XMLHTTP', 'MSXML.XMLHTTP'];
                    for (var i = 0; i < versions.length; i++) {
                        try {
                            var ieObject = new ActiveXObject('Microsoft.XMLHTTP');
                            TK.Ajax.XMLHttp = ieObject;
                            break;
                        } catch (e) {
                            TK.Ajax.XMLHttp = false;
                        }
                    }
                } else {
                    alert('Your browser un-support Ajax,Please Update Your browser');
                    TK.Ajax.XMLHttp = false;
                }
            }
        },
        /**
         * 创建一个元素滚动控件
         *
         * @argument {JSON} data 轮换元素数据 {'label' : string  该轮换项标签
         *                                  'link'  : string  链接URL
         *                                  'img'   : string  轮换项图片地址
         *                                  }
         * @argument {ELEMENT_NODE} obj 本控件摆放位置元素
         * @argument {int} type 1为数字列表点击切换,2前进后退切换,3,文字切换,4缩略图列表点击切换
         * @argument {int} eff 轮换效果, 1为渐变轮换,2滑动切换
         * @argument {string} cls 控件内元素样式名前缀, 实际元素会加上以下名字:
         *                                  CarouselMainBox : 控件样式
         *                                  CarouselListDiv : 展示元素清单样式
         *                                  CarouselPreDiv  : 上一个按钮样式
         *                                  CarouselNextDiv : 下一个按钮样式
         *                                  CarouselCurrentSelect : 当前选中元素指示样式
         * @argument {int} waitTime 滚动间隔时间
         *
         * @return ELEMENT_NODE 返回控件元素对象
         */
        carousel: function (data, obj, type, eff, cls, waitTime) {
            var preInter = null;
            var nextInter = null;
            var autoTimeout = null;
            var current = 0;
            var force = false;
            var preOpacity = 100;
            var nextOpacity = 0;
            var changeStatus = false;
            var startCarousel = function (e) {
                if (autoTimeout)
                    TK.clearTimeout(autoTimeout);
                autoTimeout = TK.setTimeout(hideShow, waitTime);
            };
            var hideShow = function () {
                if (changeStatus)
                    return;
                var currentObj = mainDiv.getNodeByAttr('rol', current)[0];
                var next = (current >= itemCount - 1) ? 0 : current * 1 + 1;
                if (force) {
                    if (force == current)
                        return;
                    next = force;
                    force = false;
                }
                current = next;
                var showObj = mainDiv.getNodeByAttr('rol', next)[0];
                if (eff == 2) {
                    mainDiv.setStyle('overflow', 'hidden');
                    var currentPos = currentObj.getPos();
                    currentObj.setStyle('position', 'absolute');
                    //currentObj.setStyle('top',currentPos.y+'px');
                    currentObj.setStyle('left', initPos.x + 'px');
                    var showObjPos = showObj.getPos();
                    showObj.setStyle('visibility', 'hidden');
                    showObj.setStyle('position', 'absolute');
                    showObj.setStyle('display', 'block');
                    // showObj.setStyle('top',currentPos.y+'px');
                    var showObjLeft = initPos.x - initPos.w;
                    showObj.setStyle('left', showObjLeft + 'px');
                    var step = initPos.w / 100;
                    var cLeft = initPos.x;
                    var sLeft = showObj.getPos().x;
                    showObj.setStyle('visibility', 'visible');
                    changeStatus = true;
                    preInter = TK.setInterval(function () {
                        cLeft = cLeft + step;
                        sLeft = sLeft + step;
                        currentObj.setStyle('left', cLeft + 'px');
                        showObj.setStyle('left', sLeft + 'px');
                        if (sLeft >= currentPos.x) {
                            TK.clearInterval(preInter);
                            currentObj.setStyle('visibility', 'hidden');
                            changeStatus = false;
                            startCarousel();
                        }
                    }, 1);
                } else {
                    preInter = TK.setInterval(function () {
                        preOpacity = preOpacity - 10;
                        var opacityStr = TK.getOpacityStr(preOpacity);
                        currentObj.setCss(opacityStr);
                        if (preOpacity <= 0) {
                            TK.clearInterval(preInter);
                            preOpacity = 100;
                            currentObj.setStyle('display', 'none');
                            changeStatus = true;
                            nextInter = TK.setInterval(function () {
                                nextOpacity = nextOpacity + 10;
                                var opacityStr = TK.getOpacityStr(nextOpacity);
                                showObj.setCss(opacityStr);
                                showObj.setStyle('display', 'inline-block');
                                if (nextOpacity >= 100) {
                                    TK.clearInterval(nextInter);
                                    nextOpacity = 0;
                                    changeStatus = false;
                                    startCarousel();
                                }
                            }, 20);
                        }
                    }, 20);
                }
            };
            var stopCarousel = function (e) {
                if (autoTimeout)
                    TK.clearTimeout(autoTimeout);
            };
            var changeItem = function (e) {
                var i = TK.getEventNode(e).getAttribute('rol');
                if (!i)
                    return;
                stopCarousel();
                force = i;
                hideShow();
            };
            var preItem = function (e) {
                stopCarousel();
                force = current <= 0 ? 7 : current--;
                hideShow();
            };
            var nextItem = function (e) {
                stopCarousel();
                hideShow();
            };
            waitTime = waitTime || 3000;
            var boxDiv = TK.createNode('div');
            var mainDiv = boxDiv.copyNode(true);
            mainDiv.addClass(cls + 'CarouselMainBox');
            var a = TK.createNode('a');
            var j = 0;
            for (var i in data) {
                if (isNaN(i))
                    continue;
                var itemA = a.copyNode(true);
                itemA.setStyle('display', 'none');
                itemA.setAttribute('rol', i);
                itemA.setAttribute('href', data[i].link);
                if (data[i].label)
                    itemA.setAttribute('title', data[i].label);
                itemA.innerHTML = '<img src="' + data[i].img + '"/>';
                mainDiv.appendChild(itemA);
                j++;
            }
            mainDiv.getFirstNode().style.display = 'block';
            mainDiv.setStyle('position', 'relative');
            current = 0;
            mainDiv.addListener('mouseover', stopCarousel);
            mainDiv.addListener('mouseout', startCarousel);
            var itemCount = j;
            if (type == 2) {
                var preDiv = boxDiv.copyNode(true);
                preDiv.addClass(cls + 'CarouselPreDiv');
                preDiv.addListener('click', preItem);
                boxDiv.appendChild(preDiv);
                boxDiv.appendChild(mainDiv);
                var nextDiv = boxDiv.copyNode(true);
                nextDiv.addClass(cls + 'CarouselNextDiv');
                nextDiv.addListener('click', nextItem);
                boxDiv.appendChild(nextDiv);
            } else {
                boxDiv.appendChild(mainDiv);
                var listDiv = TK.createNode('div');
                var span = TK.createNode('span');
                listDiv.addClass(cls + 'CarouselListDiv');
                listDiv.addListener('mouseover', changeItem);
                for (var k in data) {
                    if (isNaN(k))
                        continue;
                    var itemSpan = span.copyNode(true);
                    itemSpan.setAttribute('rol', k);
                    switch (type) {
                        case 3:
                            itemSpan.innerHTML = data[k].label;
                            break;
                        case 4:
                            itemSpan.innerHTML = '<img src="' + (data[k].thumb || data[k].img) + '"/>';
                            break;
                        default:
                            itemSpan.innerHTML = k * 1 + 1;
                            break;
                    }
                    listDiv.appendChild(itemSpan);
                }
                listDiv.getFirstNode().addClass(cls + 'CarouselCurrentSelect');
                listDiv.addListener('mouseout', startCarousel);
                boxDiv.appendChild(listDiv);
            }
            boxDiv.addClass(cls);
            obj.appendChild(boxDiv);
            var initPos = mainDiv.getPos();
            startCarousel();
            return boxDiv;
        },
        /**
         *  创建一个简单的具有时效性的信息控件
         *
         *  @argument {string} msg 信息内容
         *  @argument {string} cls 信息提示控件样式
         *  @argument {int} zIndex 信息提示控件 z-index 值
         *  @argument {int} waitTime 默认3000ms,信息提示控件自动超时隐藏毫秒时间
         *
         *  @return box : ELEMENT_NODE   返回控件所在DIV对象
         */
        msgBox: function (msg, cls, zIndex, waitTime) {
            var box = TK.createNode('div');
            box.innerHTML = msg;
            if (cls)
                box.addClass(cls);
            if (zIndex)
                box.setZIndex(zIndex);
            box.setStyle('position', 'absolute');
            TK.doc.body.appendChild(box);
            box.toCenter();
            waitTime = waitTime || 3000;
            TK.setTimeout(function () {
                box.destroy();
            }, waitTime);
            box.setOnTop();
            return box;
        },
        /**
         * 创建一个拥有确定按钮的信息提示控件
         *
         * @argument {string} tit 控件标题信息
         * @argument {string} msg 控件提示信息
         * @argument {function} func 确定按钮后执行的操作
         *                            回调函数原型样式:
         *                              callbackFunciton(event, button);
         *                                  event  : EventObject   点击事件
         *                                  button : boolean       等于true
         * @argument {string} cls 控件内元素样式名前缀,内部实际会跟随以下名字:
         *                          TitleDiv  : 标题栏样式
         *                          MainDiv   : 控件中间主题部分样式
         *                          ButtonDiv : 按钮所在元素样式
         * @argument {boolean} cover 是否显示cover层,默认不显示，true为显示
         * @argument {int} zIndex 控件 z-index 值,如果没有设置将为当前页面最上面
         *
         * @return box : ELEMENT_NODE   返回控件所在DIV对象
         */
        alertBox: function (tit, msg, func, cls, cover, zIndex) {
            return TK.confirmBoxProto(1, tit, msg, func, cls, cover, zIndex);
        },
        /**
         * 创建一个拥有确定与取消按钮的信息提示控件
         *
         * @argument {string} tit 控件标题
         * @argument {string} msg 控件提示信息
         * @argument {function} func 控件点击确定与取消后调用函数,
         *                          回调函数原型样式:
         *                              callbackFunciton(event, button);
         *                                  event  : EventObject   点击事件
         *                                  button : boolean  点击确认按钮为true
         *                                                    否则为 false
         * @argument {string} cls  控件内元素样式名前缀,内部实际会跟随以下名字:
         *                          TitleDiv  : 标题栏样式
         *                          MainDiv   : 控件中间主题部分样式
         *                          ButtonDiv : 按钮所在元素样式
         * @argument {bloolean} cover 是否显示cover层,默认不显示,true为显示
         * @argument {int} zIndex 控件 z-index 值,如果没有设置将为当前页面最上面
         *
         * @return box : ELEMENT_NODE   返回控件所在DIV对象
         */
        confirmBox: function (tit, msg, func, cls, cover, zIndex) {
            return TK.confirmBoxProto(2, tit, msg, func, cls, cover, zIndex);
        },
        /**
         *
         * @param {int} type
         * @param {string} tit
         * @param {string} msg
         * @param {function} func
         * @param {string} cls
         * @param {bloolean} cover
         * @param {int} zIndex
         * @returns {ELEMENT_NODE} box 返回控件所在DIV对象
         */
        confirmBoxProto: function (type, tit, msg, func, cls, cover, zIndex) {
            var box = TK.createNode('div');
            var title = box.copyNode(true);
            var msgDiv = box.copyNode(true);
            var button = box.copyNode(true);
            var okButton = TK.createNode('button');
            if (type == 2) {
                var cancelButton = okButton.copyNode(true);
            }
            if (cls) {
                box.addClass(cls);
                title.addClass(cls + 'TitleDiv');
                msgDiv.addClass(cls + 'MainDiv');
                button.addClass(cls + 'ButtonDiv');
            }
            if (zIndex)
                box.setZIndex(zIndex);
            title.innerHTML = tit;
            msgDiv.innerHTML = msg;
            okButton.innerHTML = '确定';
            if (type == 2) {
                cancelButton.innerHTML = '取消';
                cancelButton.addListener('click', function (e) {
                    if (func)
                        func(e, false);
                    box.destroy();
                    if (cover)
                        TK.hiddenPageCover();
                });
            }
            okButton.addListener('click', function (e) {
                if (func)
                    func(e, true);
                box.destroy();
            });
            button.appendChild(okButton);
            if (type == 2)
                button.appendChild(cancelButton);
            box.appendChild(title);
            box.appendChild(msgDiv);
            box.appendChild(button);
            TK.doc.body.appendChild(box);
            box.move(title);
            box.toCenter();
            if (cover)
                TK.showPageCover();
            box.setOnTop();
            return box;
        },
        time: function () {
            return new Date().getTime();
        },
        repeat: function (str, n) {
            if (n < 1)
                return '';
            var result = '';
            while (n > 0) {
                if (n & 1)
                    result += str;
                n >>= 1, str += str;
            }
            return result;
        },
        /**
         * add leading zero to a specified digits
         *
         * @param {int} num     the number be add leaded
         * @param {int} bit     a specified digits
         * @returns {string}
         */
        preZero: function (num, bit) {
            bit = bit || 2;
            var max = 10 ^ (bit - 1);
            bit = bit - num.toString().length;
            var str = TK.repeat('0', bit);
            return num < max ? str + num : num;
        },
        dateStatic: [0],
        date: function (time, cache) {
            var seconds = '00';
            if (TK.dateStatic[0] == 0) {
                var d = time ? new Date(time) : new Date;
                var month = TK.preZero(d.getMonth() + 1);
                var date = TK.preZero(d.getDate());
                var hours = TK.preZero(d.getHours());
                var minutes = TK.preZero(d.getMinutes());
                var sec = d.getSeconds();
                seconds = TK.preZero(sec);
                TK.dateStatic[0] = 59 - sec;
                TK.dateStatic[1] = d.getFullYear() + '-' + month + '-' + date + ' ' + hours + ':' + minutes + ':';
            } else {
                if (cache) {
                    TK.dateStatic[0]--;
                    sec = 59 - TK.dateStatic[0];
                } else {
                    var d = time ? new Date(time) : new Date;
                    var sec = d.getSeconds();
                    TK.dateStatic[0] = 59 - sec;
                }
                seconds = TK.preZero(sec);
            }
            return  TK.dateStatic[1] + seconds;
        },
        localDate: function () {
            var d = new Date();
            return d.toLocaleDateString() + ' ' + d.toLocaleTimeString();
        },
        rand: function (bit) {
            return Math.random() * (10 ^ bit);
        },
        /**
         * 创建一个具有表单功能的控件
         *
         * @param {string} tit 控件标题信息
         * @param {string} msg  默认提示信息
         * @param {JSON} inputList  表单内input元素清单,select元素将会使用selectDiv控件替代
         *                          单个input元素数据为:
         *                              {'label' : string   input元素标签
         *                               'type'  : string   input元素类型
         *                               'name'  : string   input元素name值
         *                               'value' : string   input元素默认值
         *                               'cls'   : string   input元素直接使用样式,
         *                                          内部实际会跟随以下名字：
         *                                              ItemDiv   : input元素的上一级Div样式
         *                                              ItemLabel : input元素的label元素样式
         *                              }
         *                          上面的单个元素组成JSON数组
         *
         * @param {JSON} buttonList 按钮清单，这里的按钮不是button类型input标签
         *                          单个按钮元素数据为:
         *                              {'label' : string   按钮显示名字,innerHTML值
         *                               'value' : string   按钮值,attributes属性
         *                               'cls'   : string   按钮样式
         *                               'call'  : string/function   按钮点击回调函数名
         *                               'url'   : string   表单提交URL
         *                              }
         *                          由上面的数据组成JSON数组
         *
         * @param {string} cls  控件内元素样式名前缀,内部实际会跟随以下名字:
         *                          TitleDiv  : 标题栏样式
         *                          MainDiv   : 控件中间主题部分样式
         *                          ButtonDiv : 按钮所在元素样式
         *                          MsgDiv    : 提示信息样式名
         *                          CloseDiv  : 关闭按钮样式名
         * @param {boolean} cover  是否显示cover层，true为显示，默认不显示
         * @param {int} zIndex  控件z-index 值，默认在页面最上面
         *
         *
         * @return {ELEMENT_NODE} box  返回控件所在DIV对象
         *
         * 外部可调用方法:
         * <code>
         * box.iHide()       销毁控件
         * box.msg(msg, cls, visibility)     显示提示信息
         *              msg        : string   提示信息内容
         *              cls        : string   提示信息样式名
         *              visibility : boolean  隐藏后是否保留提示信息位置
         *
         * box.submitInput(url, func, validFunc) 提交表单
         *          url       : string    提交表单URL
         *          func      : function  表单提交返回回调函数
         *                        回调函数原型样式：
         *                          callbackFunciton(returnData)
         *                                  returnData : JSON  Ajax返回数据
         *
         *          validFunc : function  表单数据检测回调函数
         *                         回调函数原型样式：
         *                          callbackFunciton(formData, box)
         *                              returnData : JSON 表单数据
         *                                                KEY值为input name值
         *                              box        : ELEMENT_NODE 控件对象
         *                              return boolean 返回false将阻止表单提交,true提交表单
         * </code>
         */
        inputBox: function (tit, msg, inputList, buttonList, cls, cover, zIndex) {
            var box = TK.createNode('div');
            var titleDiv = box.copyNode(true);
            var closeDiv = box.copyNode(true);
            var msgDiv = box.copyNode(true);
            var mainDiv = TK.createNode('div');
            var buttonDiv = box.copyNode(true);
            titleDiv.innerHTML = tit;
            msgDiv.innerHTML = msg;
            if (cls) {
                box.addClass(cls);
                titleDiv.addClass(cls + 'TitleDiv');
                msgDiv.addClass(cls + 'MsgDiv');
                mainDiv.addClass(cls + 'MainDiv');
                buttonDiv.addClass(cls + 'ButtonDiv');
                closeDiv.addClass(cls + 'CloseDiv');
            } else {
                closeDiv.innerHTML = 'X';
                closeDiv.setStyle('float', 'right');
            }
            if (zIndex)
                box.setZIndex(zIndex);
            box.iHide = function () {
                if (cover)
                    TK.hiddenPageCover();
                box.destroy();
            };
            closeDiv.addListener('click', box.hide);
            titleDiv.appendChild(closeDiv);
            box.appendChild(titleDiv);
            box.appendChild(msgDiv);
            var input = TK.createNode('input');
            var button = TK.createNode('button');
            var inputItem = null;
            for (var i in inputList) {
                if (isNaN(i))
                    continue;
                if (inputList[i].type == 'textarea') {
                    inputItem = TK.createNode('textarea');
                    inputItem.setAttribute('name', inputList[i].name);
                    inputItem.innerHTML = inputList[i].value;
                } else if (inputList[i].type == 'select') {
                    inputItem = TK.selectDiv(inputList[i].value, inputList[i].name,
                            '', '', inputList[i].cls);
                } else {
                    inputItem = input.copyNode(true);
                    inputItem.setAttribute('type', inputList[i].type);
                    inputItem.setAttribute('name', inputList[i].name);
                    inputItem.setAttribute('value', inputList[i].value);
                    if (inputList[i].type == 'checkbox' && inputList[i].checked) {
                        inputItem.setAttribute('checked', 'true');
                    }
                }
                if (inputList[i].type != 'hidden') {
                    var inputDiv = TK.createNode('div');
                    var inputLabel = TK.createNode('div');
                    inputLabel.innerHTML = inputList[i].label;
                    inputDiv.appendChild(inputLabel);
                    inputDiv.appendChild(inputItem);
                    if (inputList[i].cls) {
                        inputDiv.addClass(inputList[i].cls + 'ItemDiv');
                        inputLabel.addClass(inputList[i].cls + 'ItemLabel');
                    }
                } else {
                    inputDiv = inputItem;
                }
                if (inputList[i].type != 'select' && inputList[i].cls) {
                    inputItem.addClass(inputList[i].cls);
                }
                mainDiv.appendChild(inputDiv);
            }
            box.appendChild(mainDiv);
            box.submitInput = function (url, func, validFunc) {
                var data = TK.getFormInputData(box);
                if (validFunc) {
                    var objData = JSON.parse(data.data);
                    if (validFunc(objData, box) == false)
                        return;
                }
                TK.Ajax.post(url, data, func);
            };
            box.msg = function (message, cls, visibility) {
                msgDiv.show(visibility);
                msgDiv.innerHTML = message;
                if (cls)
                    msgDiv.addClass(cls);
                setTimeout(function () {
                    msgDiv.hide(visibility);
                }, 2000);
            };
            for (var j in buttonList) {
                if (isNaN(j))
                    continue;
                var bi = button.copyNode(true);
                bi.addClass(buttonList[j].cls);
                bi.innerHTML = buttonList[j].label;
                bi.setAttribute('value', buttonList[j].value);
                if (typeof buttonList[j].call == 'string')
                    eval('var call_func = ' + buttonList[j].call);
                else
                    call_func = buttonList[j].call;
                bi.addListener('click', call_func);
                bi.box = box;
                if (buttonList[j].url)
                    bi.url = buttonList[j].url;
                buttonDiv.appendChild(bi);
            }
            box.appendChild(buttonDiv);
            TK.doc.body.appendChild(box);
            box.move(titleDiv);
            box.toCenter();
            if (cover) {
                TK.showPageCover();
                box.setOnTop();
            }
            return box;
        },
        /**
         * 创建一个下来列表控件
         *
         * @param {JSON} optionList 列表数据
         *                      单个选项所需要的数据:
         *                          {'label'    : string  选项显示名
         *                           'value'    : mixed   选项值
         *                           'disabled' : boolean true时该项不可选，默认可选
         *                           }
         * @param {string} name  控件在表单内的name值
         * @param {function} func  更换选择项后回调函数,可选
         *                              回调函数原型样式：callbackFunciton(value)
         *                                  @value  : mixed  选择的值
         * @param {JSON} def   默认项数据, 数据样式与optionList单项一样,可选
         * @param {string} cls  控件内元素样式名前缀,内部实际会跟随以下名字:
         *                              DefDiv    : 当前显示项外层样式
         *                              DefOption : 当前显示项样式
         *                              SelectOptionDiv : 下拉列表层样式
         *                              Selected  : 下拉列表中选中项样式
         *                              OptionDisable : 不可选项样式
         *                              OptionMouseOver : 鼠标移动到选项上时样式
         *
         * @return {ELEMENT_NODE} box  返回控件元素对象
         */
        selectDiv: function (optionList, name, func, def, cls) {
            var box = TK.createNode('div');
            var defDiv = box.copyNode(true);
            var defOption = box.copyNode(true);
            var listDiv = box.copyNode(true);
            var arrow = box.copyNode('div');
            var boxInput = TK.createNode('input');
            boxInput.type = 'hidden';
            boxInput.name = name;
            boxInput.value = def.value;
            box.appendChild(boxInput);
            box.addClass(cls);
            box.selected = null;
            box.defDiv = defDiv;
            defDiv.addClass(cls + 'DefDiv');
            defOption.addClass(cls + 'DefOption');
            listDiv.addClass(cls + 'SelectOptionDiv');
            listDiv.setCss('position:absolute;z-index:10;max-height:200px;overflow:auto;');
            arrow.setCss('border-color:#000 transparent transparent;border-style:solid dashed dashed;border-width:6px 5px 0;height:0;width:0;cursor:pointer;float:left;');
            defDiv.addListener('click', function (e) {
                if (listDiv.getStyle('display') == 'block') {
                    return listDiv.hide();
                }
                listDiv.style.display = 'block';
                var pos = defDiv.getPos();
                listDiv.setStyle('left', pos.x + 'px');
                var topY = pos.y + pos.h;
                listDiv.setStyle('top', topY + 'px');
            });
            defDiv.appendChild(defOption);
            defDiv.appendChild(arrow);
            box.appendChild(defDiv);
            var span = TK.createNode('div');
            span.setStyle('display', 'block');
            for (var i in optionList) {
                if (!isNaN(i)) {
                    var op = span.copyNode(true);
                    op.setAttribute('value', optionList[i].value);
                    op.setAttribute('rol', 'option');
                    op.innerHTML = optionList[i].label;
                    if (def && optionList[i].value == def.value && optionList[i].label == def.label) {
                        op.addClass(cls + 'Selected');
                        box.selected = op;
                    }
                    if (optionList[i].disabled) {
                        op.addClass(cls + 'OptionDisable');
                        op.setAttribute('disabled', true);
                    } else {
                        op.setStyle('cursor', 'pointer');
                    }
                    listDiv.appendChild(op);
                }
            }
            listDiv.addListener('click', function (e) {
                var op = TK.getEventNode(e);
                if (op.getAttribute('rol') != 'option')
                    return;
                var value = op.getAttribute('value');
                if (op.getAttribute('disabled'))
                    return;
                var label = op.innerHTML;
                op.addClass(cls + 'Selected');
                if (box.selected)
                    box.selected.removeClass(cls + 'Selected');
                box.selected = op;
                defOption.setAttribute('value', value);
                boxInput.value = value;
                defOption.innerHTML = label;
                listDiv.style.display = 'none';
                if (func)
                    func(value);
            });
            listDiv.addListener('mouseover', function (e) {
                var op = TK.getEventNode(e);
                if (op.getAttribute('disabled'))
                    return;
                if (op.getAttribute('rol') == 'option')
                    op.addClass(cls + 'OptionMouseOver');
            });
            listDiv.addListener('mouseout', function (e) {
                var op = TK.getEventNode(e);
                if (op.getAttribute('disabled'))
                    return;
                if (op.getAttribute('rol') == 'option')
                    op.removeClass(cls + 'OptionMouseOver');
            });
            listDiv.hide();
            box.appendChild(listDiv);
            if (def) {
                defOption.setAttribute('value', def.value);
                defOption.innerHTML = def.label;
            }
            var borderColor = defDiv.getStyle('color');
            arrow.setStyle('border-color', borderColor + ' transparent transparent');
            var borderW = defDiv.getStyle('font-size');
            borderW = borderW.replace(/[a-zA-Z]+/i, '');
            borderW = borderW >= 15 ? borderW - 10 : borderW - 5;
            var borderH = borderW - 2;
            arrow.setStyle('border-width', borderW + 'px ' + borderH + 'px 0');
            box.addListener('leftmouse', function (e) {
                listDiv.hide();
            });
            return box;
        },
        /**
         * 获取当前触发事件所在元素对象
         *
         * @e  : EventObject  当前触发事件对象
         */
        getEventNode: function () {
            var obj = navigator.IE ? event.srcElement : arguments[0].target;
            return TK.$(obj);
        },
        getFocusNode: function () {
            var obj = TK.doc.activeElement;
            return obj ? TK.$(obj) : false;
        },
        setCache: function (obj, data, key) {
            var ec = TK.getCache(key, obj);
            if (ec) {
                ec.data = data;
            } else {
                if (!TK.cache[key]) {
                    TK.cache[key] = [];
                    var len = 0;
                } else {
                    var len = TK.cache[key].length - 1;
                }
                TK.cache[key][len] = {};
                TK.cache[key][len].data = data;
                TK.cache[key][len].obj = obj;
            }
        },
        /**
         * 获取当前浏览器可视区域尺寸
         *
         * @return JSON  {h : int 高度
         *                w : int 宽度
         *               }
         */
        pageShowSize: function () {
            var h = navigator.IE ? window.screen.availHeight : document.documentElement.clientHeight;
            var w = navigator.IE ? window.screen.availWidth : document.documentElement.clientWidth;
            return {
                h: h,
                w: w
            };
        },
        getCache: function (hay, need) {
            for (var i in TK.cache[hay]) {
                if (!isNaN(i) && TK.cache[hay][i].obj == need) {
                    return TK.cache[hay][i].data;
                }
                return false;
            }
        },
        delDefultEvent: function (e) {
            if (e.preventDefault)
                return e.preventDefault();
            else
                e.returnValue = false;
        },
        /**
         * 获取当前鼠标事件时，鼠标所在坐标
         *
         * @param {EventObject} e 当前触发事件对象
         *
         * @return {JSON}  鼠标坐标 { x : int   X坐标值
         *                          y : int   Y坐标值
         *                        }
         */
        mousePos: function (e) {
            e = typeof event == 'undefined' ? e : event;
            return {
                x: e.clientX,
                y: e.clientY
            };
        },
        /**
         * 获取兼容性透明度设置样式
         *
         * @param {int} num
         *
         * @return {string}  返回样式字符串
         */
        getOpacityStr: function (num) {
            num = navigator.IE ? num : num / 100;
            return navigator.IE ? "filter:alpha(opacity=" + num + ");" : 'opacity:' + num;
        },
        /**
         * 页面cover对象
         */
        pageCover: null,
        /**
         * 显示cover 元素
         */
        showPageCover: function () {
            var viewSize = TK.pageShowSize();
            var height = TK.doc.body.offsetHeight > viewSize.h ? TK.doc.body.offsetHeight + 15 : viewSize.h;
            var width = TK.doc.body.offsetWidth > viewSize.w ? TK.doc.body.offsetWidth + 15 : viewSize.w;
            if (TK.pageCover != null) {
                TK.pageCover.style.display = 'block';
                TK.pageCover.style.height = height + 'px';
                TK.pageCover.style.width = width + 'px';
                return;
            }
            var alpha = TK.getOpacityStr(20);
            TK.pageCover = TK.createNode('div');
            TK.pageCover.setCss('position:absolute;top:0;left:0;padding:0;margin:0;background-color:#000;' + alpha);
            TK.pageCover.setOnTop();
            TK.doc.body.appendChild(TK.pageCover);
            TK.pageCover.style.display = 'block';
            TK.pageCover.style.height = height + 'px';
            TK.pageCover.style.width = width + 'px';
            TK.pageCover.addListener('resize', TK.resizePageCover);
        },
        resizePageCover: function () {
            var viewSize = TK.pageShowSize();
            var height = TK.doc.body.offsetHeight > viewSize.h ? TK.doc.body.offsetHeight + 15 : viewSize.h;
            var width = TK.doc.body.offsetWidth > viewSize.w ? TK.doc.body.offsetWidth + 15 : viewSize.w;
            TK.pageCover.style.height = height + 'px';
            TK.pageCover.style.width = width + 'px';

        },
        hiddenPageCover: function () {
            TK.pageCover.style.display = 'none';
        },
        /**
         * 获取指定元素对象所包含的所有input或相关表单数据
         *
         * @param {ELEMENT_NODE} frm 指定需要获取的表单对象
         * @param {boolean} disable_no_name  是否屏蔽没有name值的表单,默认屏蔽
         *
         * @return {JSON}   返回一个JSON对象，格式为{ data : formData}
         *                                         formData为一个JSON格式字符串
         */
        getFormInputData: function (frm, disable_no_name) {
            if (!frm.getSubNodeByTag)
                frm = TK.$(frm);
            if (!disable_no_name)
                disable_no_name = true;
            var inputList = frm.getSubNodeByTag('input');
            var formData = {};
            for (var i in inputList) {
                if (isNaN(i))
                    continue;
                var inputEelement = inputList[i];
                var eleType = inputEelement.inputType;
                if (inputEelement.style.display == 'none')
                    continue;
                var key = inputEelement.getAttribute('name');
                if (disable_no_name && !key)
                    continue;
                if ((eleType == TK.inputType.INPUT_CHECKBOX || eleType == TK.inputType.INPUT_RADIO) && inputEelement.checked != true)
                    continue;
                var value = inputEelement.value;
                if (eleType == TK.inputType.INPUT_CHECKBOX) {
                    if (typeof (formData[key]) == 'undefined')
                        formData[key] = Array();
                    formData[key][formData[key].length] = value;
                } else {
                    formData[key] = value;
                }
            }
            var selectList = frm.getSubNodeByTag('select');
            for (var i in selectList) {
                if (isNaN(i))
                    continue;
                var select = selectList[i];
                var key = select.getAttribute('name');
                if (select.style.display == 'none')
                    continue;
                if (disable_no_name && !key)
                    continue;
                formData[key] = select.value;
            }
            var textareaList = frm.getSubNodeByTag('textarea');
            for (var i in textareaList) {
                if (isNaN(i))
                    continue;
                var textarea = textareaList[i];
                var key = textarea.getAttribute('name');
                if (textarea.style.display == 'none')
                    continue;
                if (disable_no_name && !key)
                    continue;
                formData[key] = textarea.value;
            }
            formData = JSON.stringify(formData);
            return {
                data: formData
            };
        },
        /**
         * 自动提交表单,本方法只能提交form标签表单
         *
         * @param {ELEMENT_NODE} ele form标签下的子元素
         * @param {function} callFunc   AJAX提交后返回回调函数
         *                            原型:callbackFunciton(returnData);
         *                                  @returnData : AJAX返回数据
         * @param {function} validFunc   表单数据检测回调函数
         *                            原型:callbackFunciton(objData,formObj)
         *                                  @objData : JSON 表单数据,name为键值
         *                                  @formObj : ELEMENT_NODE 表单对象
         *
         */
        submitForm: function (ele, callFunc, validFunc) {
            if (!ele.getParentNodeByTag)
                ele = TK.$(ele);
            if (ele.tag == 'form') {
                var formObj = ele;
            } else {
                var formObj = ele.getParentNodeByTag('form');
            }
            if (!formObj || formObj.tag != 'form') {
                console.warn('form element not exists');
                return;
            }
            var formAction = formObj.getAttribute('action');
            var formMethod = formObj.getAttribute('method');
            var data = TK.getFormInputData(formObj);
            if (validFunc) {
                var objData = JSON.parse(data.data);
                if (validFunc(objData, formObj) == false)
                    return;
            }
            if (formMethod.toLowerCase() == 'post') {
                TK.Ajax.post(formAction, data, callFunc);
            } else {
                TK.Ajax.setData(data);
                TK.Ajax.get(formAction, callFunc);
            }
        },
        AjaxDebugMessageDiv: null,
        debugInnerHTML: function (html) {
            if (!TK.AjaxDebugMessageDiv)
                TK.AjaxDebugMessageDiv = TK.createNode('div');
            var m = '<h2>Ajax Return Server Debug Message</h2>';
            for (var i in html)
                m += html[i];
            TK.AjaxDebugMessageDiv.innerHTML = m;
            document.body.appendChild(TK.AjaxDebugMessageDiv);
        },
        drawRect: function (x, y, w, h, color) {
            var canvas = TK.createNode('canvas');
            var ctx = canvas.getContext('2d');
            ctx.fillStyle = color;
            ctx.fillRect(x, y, w, h);
            return canvas;
        },
        /**
         * 创建基于canvas 元素的趋势图表
         *
         * @param {string} style
         * @param {JSON} initData
         * @param {int} padding
         * @returns {Boolean}
         */
        drawLineTrends: function (style, initData, padding) {
            var canvas = TK.createNode('canvas');
            canvas.setAttribute('width', style.w + 'px');
            canvas.setAttribute('height', style.h + 'px');
            canvas.popShow = false;
            canvas.popDiv = null;
            if (!canvas.getContext) {
                console.warn('Your browser not support canvas');
                return false;
            }
            var ctx = canvas.getContext('2d');
            padding = padding ? padding : 20;
            //X Y 轴标尺点数
            var ySectionCount = (style.y.max + style.y.min) / style.y.step;
            var xSectionCount = (style.x.max + style.x.min) / style.x.step;
            //X Y 轴长度
            var yHSize = style.h - padding * 2;
            var xWSize = style.w - padding * 2;
            //X Y 轴每块长度
            var ySectionSize = yHSize / ySectionCount;
            var xSectionSize = xWSize / xSectionCount;
            //X Y 每单位长度
            var yDot = ySectionSize / style.y.step;
            var xDot = xSectionSize / style.x.step;
            var yLabel = style.y.label + '(' + style.y.unit + ')';
            var xLabel = style.x.label + '(' + style.x.unit + ')';
            //Y 轴X 坐标
            var yXStart = padding + style.x.min / style.x.step * xSectionSize;
            var xXEnd = xWSize + padding; //X 轴X 结束点
            //X Y 轴 Y轴结束坐标
            var yYEnd = yHSize + padding; //Y 轴 Y方向结束点
            //X 轴Y坐标
            var xYEnd = yHSize + padding - style.y.min / style.y.step * ySectionSize;
            //标准数据坐标轴
            var st = style.x.stantard ? 'x' : 'y';
            var trendLinePointer = Array();
            //create Trend
            var createTrend = function (x, y, st) {
                trendLinePointer = [];
                for (var k in initData) {
                    if (isNaN(k))
                        continue;
                    var prec = initData[k];
                    ctx.beginPath();
                    ctx.strokeStyle = prec.color;
                    ctx.lineWidth = prec.w;
                    var precData = prec.data;
                    trendLinePointer[k] = [];
                    var yPos = null;
                    var xPos = null;
                    if (st == 'x') {
                        yPos = y - precData[0] * yDot;
                        ctx.moveTo(x, yPos);
                        trendLinePointer[k].push([x, yPos]);
                    } else {
                        xPos = x + precData[0] * xDot;
                        trendLinePointer[k].push([xPos, y]);
                        ctx.moveTo(xPos, y);
                    }
                    for (var j in precData) {
                        if (j == 0)
                            continue;
                        if (st == 'x') {
                            xPos = x + j * xSectionSize + xSectionSize;
                            yPos = y - precData[j] * yDot;
                            ctx.lineTo(xPos, yPos);
                        } else {
                            yPos = y - j * ySectionSize - ySectionSize;
                            xPos = x + precData[j] * xDot;
                            ctx.lineTo(xPos, yPos);
                        }
                        trendLinePointer[k].push([xPos, yPos]);
                    }
                    ctx.stroke();
                }
            };
            var initCoord = function () {
                //Y
                ctx.font = style.y.labelFont;
                ctx.fillStyle = style.y.labelColor;
                ctx.fillText(yLabel, 0, padding);
                //X
                ctx.font = style.x.labelFont;
                ctx.fillStyle = style.x.labelColor;
                var xLabelTextMea = ctx.measureText(xLabel);
                var xLabelTextWidth = xLabelTextMea.width;
                ctx.fillText(xLabel, style.w - xLabelTextWidth - padding, yHSize + padding);
                //Y line
                ctx.beginPath();
                ctx.moveTo(yXStart, padding);
                ctx.strokeStyle = style.y.color;
                ctx.lineWidth = style.y.w;
                ctx.lineTo(yXStart, yYEnd);
                ctx.moveTo(yXStart - 5, padding + 10);
                ctx.lineTo(yXStart, padding);
                ctx.lineTo(yXStart + 5, padding + 10);
                for (var i = 1; i < ySectionCount; i++) {
                    var yPos = padding + ySectionSize * i;
                    ctx.moveTo(yXStart, yPos);
                    ctx.lineTo(yXStart + 3, yPos);
                }
                ctx.stroke();
                //X line
                ctx.beginPath();
                ctx.moveTo(padding, xYEnd);
                ctx.strokeStyle = style.x.color;
                ctx.lineWidth = style.x.w;
                ctx.lineTo(xXEnd, xYEnd);
                ctx.moveTo(xXEnd - 10, xYEnd - 5);
                ctx.lineTo(xXEnd, xYEnd);
                ctx.lineTo(xXEnd - 10, xYEnd + 5);
                for (var i = 1; i < xSectionCount; i++) {
                    var xPos = padding + xSectionSize * i;
                    ctx.moveTo(xPos, xYEnd);
                    ctx.lineTo(xPos, xYEnd - 3);
                }
                ctx.stroke();
                createTrend(padding, xYEnd, st);
            };
            var searchNear = function (arr, na) {
                for (var k in arr) {
                    if (arr[k][0] + 5 > na[0] && arr[k][0] - 5 < na[0] &&
                            arr[k][1] + 5 > na[1] && arr[k][1] - 5 < na[1]) {
                        return k;
                    }
                }
                return -1;
            };
            var showPointerPopInfoDiv = function (x, y) {
                if (canvas.popDiv) {
                    canvas.popDiv.style.display = 'block';
                } else {
                    canvas.popDiv = TK.createNode('div');
                    if (style.popClass)
                        canvas.popDiv.addClass(style.popClass);
                    TK.doc.body.appendChild(canvas.popDiv);
                    canvas.popDiv.style.display = 'block';
                }
                canvas.popDiv.innerHTML = x + ',' + y;
            };
            var showPointerPopInfo = function (e) {
                var mPos = TK.mousePos(e);
                var nPos = this.getPos();
                var scroll = TK.scrollOffset();
                var inCtxPos = [mPos.x - nPos.x + scroll.x, mPos.y - nPos.y + scroll.y];
                for (var t in trendLinePointer) {
                    var idx = searchNear(trendLinePointer[t], inCtxPos);
                    if (idx != -1) {
                        break;
                    }
                }
                if (idx != -1) {
                    if (st == 'x') {
                        var xData = idx * style.x.step;
                        var yData = initData[t].data[idx];
                    } else {
                        var yData = idx * style.x.step;
                        var xData = initData[t].data[idx];
                    }
                    canvas.popShow = true;
                    showPointerPopInfoDiv(xData, yData);
                    canvas.popDiv.mousePopNearX = 10;
                    canvas.popDiv.mousePopNearY = -15;
                    canvas.popDiv.mousePop(e);
                } else {
                    if (canvas.popShow == true) {
                        canvas.popShow = false;
                        canvas.popDiv.style.display = 'none';
                    }
                }
            };
            var clearPointerPopInfo = function (e) {
                if (canvas.popDiv) {
                    canvas.popDiv.style.display = 'none';
                }
                canvas.popShow = false;
            };
            canvas.addPoint = function (addData) {
                ctx.save();
                ctx.clearRect(0, 0, style.w, style.h);
                for (var k in addData) {
                    for (var j in addData[k]) {
                        initData[k].data.push(addData[k][j]);
                        var len = st == 'x' ? xSectionCount : ySectionCount;
                        if (initData[k].data.length > len) {
                            initData[k].data.shift();
                        }
                    }
                }
                initCoord();
                ctx.restore();
            };
            canvas.addListener('mousemove', showPointerPopInfo);
            canvas.addListener('mouseout', clearPointerPopInfo);
            initCoord();
            return canvas;
        },
        /**
         * 设置cookie值
         *
         * @argument {string} cn 一个cookie name值
         * @argument {string} v 一个cookie value值
         * @argument {int} ex cookie有效期
         */
        setCookie: function (cn, v, ex) {
            var e = new Date(), n = e.getTime();
            ex = n + ex * 1000;
            e.setTime(ex);
            var cv = escape(v) + "; exs=" + e.toUTCString();
            TK.doc.cookie = cn + "=" + cv;
        },
        /**
         * 获取一个cookie 值
         *
         * @argument {string} cn  cookie name值
         *
         * @return : string  返回cookie value 值,没有将返回null
         */
        getCookie: function (cn) {
            var i, x, y, a = TK.doc.cookie.split(";");
            for (i = 0; i < a.length; i++) {
                x = a[i].substr(0, a[i].indexOf("="));
                y = a[i].substr(a[i].indexOf("=") + 1);
                x = x.replace(/^\s+|\s+$/g, "");
                if (x == cn)
                    return unescape(y);
            }
            return null;
        },
        version: 0.6
    };
    window.onbeforeunload = TK.unloadExec;
    if (typeof $ == 'undefined') {
        window.$ = TK.$;
    }
    if (typeof require == 'undefined') {
        window.require = TK.require;
    }
}
