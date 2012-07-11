if(typeof X == 'undefined') {
String.prototype.isEmail = function() {
    return /^([a-z0-9+_]|\-|\.)+@(([a-z0-9_]|\-)+\.)+[a-z]{2,6}$/.test(this);
};
String.prototype.isMoblie = function() {
    return /^(13|15|18)\d{9}$/i.test(this);
};
String.prototype.trim = function() {
    return this.replace(/(^\s*)|(\s*$)/g,"");
};
String.prototype.strpos = function(needle,offset) {
    var i = (this + '').indexOf(needle, (offset || 0));
    return i === -1 ? false : i;
};
String.prototype.ucwords = function() {
    return (str + '').replace(/^([a-z])|\s+([a-z])/g, function ($1) {
        return $1.toUpperCase();
    });
};
String.prototype.ucfirst = function() {
    var f = this.charAt(0).toUpperCase();
    return f + this.substr(1);
};
Array.prototype.last = function() {
    return this.length >0 && this[this.length-1];
};
if(typeof Node == 'undefined') {
    Node = {
        ELEMENT_NODE:1,
        ATTRIBUTE_NODE:2,
        TEXT_NODE:3,
        COMMENT_NODE:8,
        DOCUMENT_NODE:9,
        DOCUMENT_FRAGMENT_NODE:11
    }
}
var debug = true;
if(typeof console == 'undefined' && debug) {
    console = {};
    console.warn = function(str){throw new Error(str);};
    console.log = function(str) {};
} else if(console && !debug) {
    console.warn = function(str) {};
    console.log = function(str) {};
};
navigator.IE =typeof ActiveXObject == 'undefined' ? false : true;
navigator.FIREFOX = /(firefox)/i.test(navigator.userAgent.toLowerCase());
navigator.WEBKIT = /(Webkit)/i.test(navigator.userAgent.toLowerCase());
navigator.IEV = navigator.IE && ! document.documentMode ? 6 : document.documentMode;
//if(navigator.IEV <8) alert('Not Support IE'+navigator.IEV+', Please upgrade to IE8 or lastest');
var X = {
doc : window.document,
isReady : false,
ugent : navigator.userAgent.toLowerCase(),
intervarlHandle : [],
timeoutHandle : [],
cache : {},
mouseX : 0,
mouseY : 0,
maxZIndex : 0,
keyList : {keyup:[],keydown:[]},

    //document鼠标点击事件回调函数列表
bMECFL : {
    mousedown : [[],[],[],[]],
    mouseup : [[],[],[],[]],
    mouseover : [],
    mouseout : [],
    mousemove : [],
    click : [[],[],[],[]]
},
    //窗口滚动事件注册函数列表
wSFL : [],
wRSFL : [],
createNode : function(t) {return X.$(X.doc.createElement(t));},
jsPath  : function(){
        return X.doc.scripts[0].src.substring(0,X.doc.scripts[0].src.lastIndexOf("/")+1);
},
inputType : {INPUT_TEXT:1,INPUT_PASSWORD:2,INPUT_CHECKBOX:3,
            INPUT_RADIO:4,INPUT_TEXTAREA:5,INPUT_BUTTON:6,INPUT_SUBMIT:7,
            INPUT_IMAGE:8,INPUT_SELECT:9},
loadJSON : function() {
    typeof(JSON) === 'undefined' && X.loadJSFile(X.jsPath()+'json.js');
},
loadJSFile : function(fs) {
    var f = X.createNode('script');
    f.setAttribute('type','text/javascript');
    f.setAttribute('src', fs);
    X.doc.getElementsByTagName('head')[0].appendChild(f);
},
unloadExecList : [],
eventList : [],
unload : function(cf) {
    if(typeof cf == 'string') {
        console.log(cf);
        X.unloadExecMessage = cf;
        return;
    }
    X.unloadExecList.push(cf);
},
unloadExecMessage : null,
unloadExec : function() {
    if(X.doc && X.isReady) {
        X.doc.onkeydown = null;
        X.doc.onkeyup = null;
        X.doc.onmouseup = null;
        X.doc.onmousedown = null;
        X.doc.onmousemove = null;
        window.onscroll = null;
        if(window.top == window.self) {
            X.doc.body.onresize = null;
        }
        for(var es in X.eventList) {
            for(var k in X.eventList[es]) {
                if(!isNaN(i)) 
                X.$(X.eventList[es][k].handObj).delListener(es,X.eventList[es][k]);
            }
        }
    }
    if(X.Ajax.XMLHttp) X.Ajax.XMLHttp = null;
    if(X.Ajax.openInstance.length>0) {
        for(var a in X.Ajax.openInstance) if(!isNaN(a)) X.Ajax.openInstance[a].abort();
        X.Ajax.openInstance = [];
    }
    for(var i=0;i<X.unloadExecList.length;i++) X.unloadExecList[i]();
    if(X.unloadExecMessage) return X.unloadExecMessage;
},
ready  : function() {
    X.loadJSON();
    if(X.doc) {
        X.doc.onkeydown = X.kEF;
        X.doc.onkeyup = X.kEF;
        X.doc.onmousedown = X.bMECF;
        X.doc.onmouseup = X.bMECF;
        X.doc.onmouseover = X.mMEF;
        X.doc.onmouseout = X.mMEF;
        X.doc.onmousemove = X.mMEF;
        window.onscroll = X.wSF;
        X.mousemove(function(e) {
            var mp = X.mousePos(e);
            X.mouseX = mp.x;
            X.mouseY = mp.y;
        });
        if(window.top == window.self) {
            X.doc.body.onresize = X.wRSF;
        }
    }
    X.isReady = true;
},
scrollOffset : function() {
    var YOffset = window.pageYOffset ? window.pageYOffset:X.doc.body.scrollTop;
    var XOffset = window.pageXOffset ? window.pageXOffset:X.doc.body.scrollLeft;
    return {x:XOffset,y:YOffset};
},
    //窗口滚动事件
wSF : function(e) {
    e = e || event;
    for(var i in X.wSFL) if(!isNaN(i)) X.wSFL[i].call(X.wSFL[i].handObj,e);
},
    //窗口改变大小事件
wRSF : function(e) {
    for(var i in X.wRSFL) if(!isNaN(i)) X.wRSFL[i].func(X.wRSFL[i].obj);
},
    //添加鼠标移动事件函数
aMMECF : function(func,type) {
    X.bMECFL[type].push(func);
},
    //添加document鼠标点击事件
aBMECF : function(func,type,button) {
    if(!type) type = click;
    if(typeof(button) == 'undefined') button = 3;
    X.bMECFL[type][button].push(func);
},
    //document鼠标移动事件回调函数
mMEF : function(e) {
    X.delDefultEvent(e);
    e = e||event;
    var fL = X.bMECFL[e.type];
    for(var i in fL) {
        if(!isNaN(i)) {
            if(fL[i].eventObj) e.eventObj = fL[i].eventObj;
            fL[i](e);
        }
    }
},
    //document鼠标点击事件回调函数
bMECF : function(e) {
    e = e || event;
    var button = e.button;
    if(navigator.IE && navigator.IEV <9) {
        if(button == 1) button = 0;
        if(button == 4) button = 1;
    }
    var fL = X.bMECFL[e.type][button];
    for(var i in fL) {
        if(!isNaN(i)) {
            if(fL[i].eventObj) e.eventObj = fL[i].eventObj;
            fL[i](e);
        }
    }
},
    //document键盘事件回调函数
kEF : function(e) {
    e = e || event,k = e.keyCode,fL = X.keyList[e.type];
    for(var key in fL) key != '' && k == key && fL[k](e);
},
addKeyListener : function(key,func,type) {X.keyList[type][key] = func;},
delKeyListener : function(key,type) {delete X.keyList[type][key];},
    //常用键盘事件注册
dKEP : function(obj) {
    return {
    esc : function(func) {obj.key(27,func);},
    enter : function(func) {obj.key(13,func);},
    tab : function(func) {obj.key(9,func);},
    space : function(func) {obj.key(32,func);},
    backspace : function(func) {obj.key(8,func);},
    up : function(func) {obj.key(38,func);},
    down : function(func) {obj.key(40,func);},
    left : function(func) {obj.key(37,func);},
    right : function(func) {obj.key(39,func);},
    key : obj.key
    };
},
    //鼠标点击事件注册原型
dMEP : function(type) {
    return {left : function(func,eventObj) { if(eventObj)func.eventObj = eventObj;X.aBMECF(func,type,0)},
    right : function(func,eventObj) {if(eventObj)func.eventObj = eventObj;X.aBMECF(func,type,2)},
    middle : function(func,eventObj) {if(eventObj)func.eventObj = eventObj;X.aBMECF(func,type,1)},
    any : function(func,eventObj) {if(eventObj)func.eventObj=eventObj;X.aBMECF(func,type,3)}
    };
},
mousemove : function(func, eventObj) {
    if(eventObj) func.eventObj = eventObj;
    X.aMMECF(func,'mousemove');
},
mouseover : function(func, eventObj) {
    if(eventObj) func.eventObj = eventObj;
    X.aMMECF(func,'mouseover');
},
mouseout : function(func, eventObj) {
    if(eventObj) func.eventObj = eventObj;
    X.aMMECF(func,'mouseout');
},
setTimeout : function(func,time) {
    var id = window.setTimeout(func,time)
    X.timeoutHandle.push(id);
    return id;
},
setInterval : function(func, time) {
    var id = window.setInterval(func, time);
    X.intervarlHandle.push(id);
    return id;
},
clearTimeout : function(id) {
    window.clearTimeout(id);
    for(var i in X.timeoutHandle) {
        if(X.timeoutHandle[i] == id) delete X.timeoutHandle[i];
    }
},
clearInterval : function(id) {
    window.clearInterval(id);
    var i = X.timeoutHandle.indexOf(id)
    if(i>-1) delete X.timeoutHandle[i];
},
$ : function(ele) {
    if(!ele) console.warn('X.$(ele) param is empty, ele value is '+ele);
    var eleType = typeof(ele);
    switch(eleType) {
         case  'string':
            var firstWord = ele.split(0,1);
            var param = ele.split(1,ele.length);
            switch(firstWord) {
                case '#': //样式名
                    return (function(clsName) {
                        var list = Array();
                        var childList = X.$(X.doc.body).getChilds();
                        for(var t in childList) !isNaN(t) && X.$(childList[t]).hasClass(clsName) && (list[list.length] = X.$(childList[t]));
                        return list;
                        })(param);
                case '@'://标签名
                    return (function (tagName) {
                        var list = Array();
                        var childList = X.$(X.doc.body).getChilds();
                        for(var t in childList) !isNaN(t) && X.$(childList[t]).tag == tagName.toLowerCase() && (list[list.length] = X.$(childList[t]));
                        return list;
                    })(param);
                case '%'://NAME名
                    return (function(name) {
                        var list = Array();
                        var childList = X.$(X.doc.body).getChilds();
                        for(var t in childList) !isNaN(t) && childList[t].getAttr('name') == name && (list[list.length] = X.$(childList[t]));
                        return list;
                    })(param);
                default:
                    var __element = X.doc.getElementById(ele);
                    break;
                }
            break;
            case 'array':
                var list = Array();
                for(var i in ele) !isNaN(i) && (list[list.length] = X.$(ele[i]));
                return list;
            break;
            default:
                var __element = ele;
            break;
            }
            if(!__element) return false;
            if(typeof(__element) != 'object') return false;
            if(!__element.nodeType) return false;
            if(__element.nodeType != Node.ELEMENT_NODE) return false;
            __element.tag = __element.tagName ? __element.tagName.toLowerCase() : false;
            __element.inputType = (function() {
                    if(__element.tag == 'select') return X.inputType.INPUT_SELECT;
                    if(__element.tag == 'textarea') return X.inputType.INPUT_TEXTAREA;
                    if(__element.tag != 'input') return false;
                    if(!__element.getAttribute('type')) return false;
                    var nodeTypeAtt = __element.getAttribute('type').toLowerCase();
                    switch(nodeTypeAtt) {
                        case 'text': return X.inputType.INPUT_TEXT;
                        case 'password': return X.inputType.INPUT_PASSWORD;
                        case 'checkbox': return X.inputType.INPUT_CHECKBOX;
                        case 'radio': return X.inputType.INPUT_RADIO;
                        case 'button': return X.inputType.INPUT_BUTTON;
                        case 'submit': return X.inputType.INPUT_SUBMIT;
                        case 'image': return X.inputType.INPUT_IMAGE;
                        default: return false;
                    }
                })();
                if(typeof(__element.innerText) == 'undefined') {
                    __element.__defineSetter__('innerText',function(text) {__element.textContent = text;});
                    __element.__defineGetter__('innerText',function() { return __element.textContent;});
                }
                if(typeof(__element.setCapture) == 'undefined') {
                    __element.setCapture = function() {window.captureEvents(Event.MOUSEMOVE);}
                }
                if(typeof(__element.releaseCapture) == 'undefined') {
                    __element.releaseCapture = function() {window.captureEvents(Event.MOUSEMOVE);}
                }
                var __extend = {
                getIframeBody : function() {
                    var ifBody = this.contentDocument ? this.contentDocument.body : this.contentWindow.document.body;
                    return X.$(ifBody);
                },
                getHorSize : function() {
                    return this.getStyleNum('padding-top',2) + this.getStyleNum('padding-bottom',2) +this.getStyleNum('margin-bottom',2)+this.getStyleNum('margin-top',2)+this.getStyleNum('border-bottom-width',2)+this.getStyleNum('border-top-width',2);
                },
                getVerSize : function() {
                    return this.getStyleNum('padding-left',2)+this.getStyleNum('padding-right',2)+this.getStyleNum('margin-left',2)+this.getStyleNum('margin-right',2)+this.getStyleNum('border-left-width',2)+this.getStyleNum('border-right-width',2);
                },
                getStyleNum : function(n,l) {
                    if(typeof(l) == 'undefined') l = 2;
                    var s = this.getStyle(n);
                    return s.substr(0,s.length-l) *1;
                },
                getPos : function() {
                    var y = this.offsetTop;
                    var x = this.offsetLeft;
                    var height = this.offsetHeight;
                    var width = this.offsetWidth;
                    var obj = this;
                    if(navigator.IE && navigator.IEV <8) {
                        while(obj = obj.offsetParent) {    
                            x += obj.offsetLeft;    
                            y += obj.offsetTop;    
                        }
                    } else {
                        while(obj = obj.parentOffset) {    
                            x += obj.offsetLeft;    
                            y += obj.offsetTop;    
                        }
                    }
                    return {'x':x,'y':y,'h':height,'w':width};
                },
                copyNode : function(deep) {
                    return X.$(this.cloneNode(deep));
                },
                getNodeByCls : function(clsName) {
                    var childList = this.getChilds();
                    var list = Array();
                    for(var t in childList)
                        if(!isNaN(t) && childList[t].hasClass(clsName)) list[list.length] = childList[t];
                    return list;
                },
                getChildNodeByAttr : function(attr,value) {
                    var childList = this.getChilds();
                    var list = Array();
                    for(var t in childList) {
                        if(!isNaN(t) && childList[t].getAttr(attr) == value) {
                            list[list.length] = childList[t];
                        }
                    }
                    return list;
                },
                getAttr : function(attr) {
                    if(this.getAttribute(attr)) return this.getAttribute(attr);
                    if(attr == 'class') return this.className;
                    return null;
                },
                getParentNodeByAttr : function(attr,value) {
                    if(this.parentNode && this.parentNode.nodeType == Node.ELEMENT_NODE) {
                        if(this.parentNode.getAttr(attr) == value) return X.$(this.parentNode);
                        else return X.$(this.parentNode).getParentNodeByAttr(attr,value);
                    }
                    return false;
                },
                getFirstNode : function() {
                    var fNode = this.firstChild;
                    while(fNode) {
                        if(fNode.nodeType == Node.ELEMENT_NODE) return X.$(fNode);
                        fNode = fNode.nextSibling;
                    }
                    return false;
                },
                getLastNode : function() {
                    var lNode = this.lastChild;
                    while(lNode) {
                        if(lNode.nodeType == Node.ELEMENT_NODE) return X.$(lNode);
                        lNode =lNode.previousSibling;
                    }
                    return false;
                },

                isNodeChild : function(parentNode) {
                    if(this.compareDocumentPosition) {
                        return this.compareDocumentPosition(parentNode) == 10;
                    }
                    return parentNode.contains(this);
                },
                unshiftChild : function(new_node) {
                    if(this.firstChild) {
                        return this.insertBefore(new_node,this.firstChild);
                    }
                    return this.appendChild(new_node);
                },
                getParentNodeByTag : function(tagName) {
                    if(this.parentNode) {
                        if(this.parentNode.tagName == tagName.toUpperCase()) return X.$(this.parentNode);
                        else return X.$(this.parentNode).getParentNodeByTag(tagName);
                    }
                    return false;
                },
                getSubNodeByTag : function(tagName) {
                    var childList = this.getChilds();
                    var list = Array();
                    for(var t in childList) {
                        if(!isNaN(t) && X.$(childList[t]).tag == tagName.toLowerCase())
                            list[list.length] = X.$(childList[t]);
                    }
                    return list;
                },
                hasClass : function(cls) {
                    var re = new RegExp('(\\s|^)'+cls+'(\\s|$)');
                    return re.test(this.className);
                },
                removeClass : function(cls) {
                    if (this.hasClass(cls)) {
                        var reg = new RegExp('(\\s|^)'+cls+'(\\s|$)');
                        this.className=this.className.replace(reg,'');
                    }
                },
                setClass : function(cls) {
                    if(!this.hasClass(cls)) {
                        if(this.className !='') {
                            this.className = this.className+=' '+cls;
                        } else {
                            this.className = cls;
                        }
                    }
                },
                setCss : function(value) {
                    if(navigator.IE) return this.style.cssText = value;
                    this.setAttribute('style',value);
                },
                getStyle : function(ns) {
                    ns = this.convStyleName(ns);
                    if(this.style[ns]) return this.style[ns];
                    if(this.currentStyle) return this.currentStyle[ns];
                    if(X.doc.defaultView) return X.doc.defaultView.getComputedStyle(this,null)[ns];
                    return false;
                },
                convStyleName : function(ns) {
                    var b = ns.strpos('-');
                    if(b && b>0) {
                        var l = ns.split('-');
                        ns = l[0];
                        for(var i=1;i<l.length;i++) {
                            ns+=l[i].ucfirst();
                        }
                    }
                    return ns;
                },
                setStyle : function(ns,value) {
                    ns = this.convStyleName(ns);
                    this.style[ns] = value;
                },
                setOnTop : function() {
                    var index = X.maxZIndex + 1;    
                    X.maxZIndex = index;
                    this.setStyle('z-index',index);
                },
                setZIndex : function(idx) {
                    if(idx > X.maxZIndex) X.maxZIndex = idx;
                    this.setStyle('z-index',idx);
                },
                nextNode : function() {
                    var nNode = this.nextSibling;
                    while(nNode) {
                        if(nNode.nodeType == Node.ELEMENT_NODE) return X.$(nNode);
                        nNode = nNode.nextSibling;
                    }
                    return false;
                },
                previousNode : function() {
                    var pNode = this.previousSibling;
                    while(pNode) {
                        if(pNode.nodeType == Node.ELEMENT_NODE) return X.$(pNode);
                        pNode = pNode.previousSibling;
                    }
                    return false;
                },
                delListener : function(e,call_action) {
                    if(e == 'scroll') {
                        for(var i in X.scrollFuncList) {
                            if(X.scrollFuncList[i] == call_action) {
                                delete X.scrollFuncList[i];
                            }
                        }
                    }
                    if(typeof call_action == 'number') {
                        call_action = X.eventList[e][call_action];
                    }
                    if(this.delEventListener) {
                        this.delEventListener(e,call_action,false);
                    } else if(this.detachEvent) {
                        this.detachEvent(e,call_action);
                    } else {
                        this[e] = null;
                    }
                },
                addListener : function(e,call_action) {
                    //console.warn('addEventListener Element '+ this + ' Function is ' + call_action);
                    call_action.handObj = this;
                    if(e == 'scroll') {
                        this.scrollOffset = X.scrollOffset();
                        X.wSFL.push(call_action);   
                        return;
                    }
                    if(e == 'resize') {
                        var l = {func:call_action,obj:this};
                        if(window.top == window.self) {
                            X.wRSFL.push(l);
                        } else if(window.top.X){
                            window.top.X.wRSFL.push(l);
                        }
                        return;
                    }
                    if(typeof X.eventList[e] == 'undefined') X.eventList[e] = [];
                    var l = X.eventList[e].push(call_action) - 1;
                    if(this.addEventListener) {
                        this.addEventListener(e,X.eventList[e][l] , false);
                    } else if(this.attachEvent) {
                        this.attachEvent('on'+e, X.eventList[e][l]);
                    } else {
                        var elementEvent = this[e];
                        this[e] = function() {
                            var callEvent = elementEvent.apply(this,arguments);
                            var actEvent = X.eventList[e][l].apply(this,arguments);
                            return (callEvent == undefined) ? actEvent : (actEvent ==undefined ? X.eventList[e][l] : (actEvent && X.eventList[e][l]));
                        }
                    }
                    return l;
                },
                getChilds : function(cache) {
                    var list = Array();
                    var obj = this;
                //    var cacheData = X.getCache('getChildsList',obj);
                //    if(cacheData) {
                //        return cacheData;
                //    }
                    var f = obj.getFirstNode();
                    if(f) {
                        list[list.length] = f;
                        var cL = f.getChilds();
                        if(cL && cL.length>0) {
                            list = list.concat(cL);
                        }
                        var next=f.nextNode();
                        while(next) {
                            list[list.length] = next;
                            var nL = next.getChilds();
                            if(nL && nL.length>0) {
                                list = list.concat(nL);
                            }
                            next = next.nextNode();
                        }
                    };
                 //   X.setCache(obj,list,'getChildsList');
                    return list;
                },
                toCenterProto : function(eff,spec) {
                    if(this.style.display = 'none') this.style.display = 'block';
                    this.style.position = 'absolute';
                    var objPos = this.getPos();
                    if(typeof spec != 'undefined') {
                        var specPos = spec.getPos ? spec.getPos() : X.$(spec).getPos();
                    }
                    var pageSize = X.pageShowSize();
                    var refObjHeight = spec ? specPos.h : pageSize.h;
                    var refObjWeight = spec ? specPos.w : pageSize.w;
                    var YOffset = X.scrollOffset().y;
                    var XOffset = X.scrollOffset().x;
                    var topY = refObjHeight/3-objPos.h/2;
                    var leftX = refObjWeight/2-objPos.w/2;
                    if(YOffset>0) topY = YOffset + topY;
                    if(XOffset>0) left = XOffset + leftX;
                    if(spec) {
                        topY = topY + specPos.y;
                        leftX = leftX + specPos.x;
                    }
                    if(topY <0) topY =0;
                    this.style.left = leftX+'px';
                    if(eff == 1) {
                        var obj = this;
                        if(objPos.y<YOffset) obj.style.top = YOffset+'px';
                        if(this.interOffsetEff) X.clearInterval(this.interOffsetEff);
                        var MoveDown = objPos.y<=topY;
                        var step = Math.abs(topY-objPos.y)/100;
                        this.interOffsetEff = X.setInterval(function(){
                                var y = obj.getPos().y;
                                if(y>=topY && !MoveDown) {y=y-step;obj.style.top = y+'px';return;}
                                if(y<=topY && MoveDown){y=y+step;obj.style.top = y+'px';return;}
                                X.clearInterval(this.interOffsetEff);
                                },10);
                    } else {
                        this.style.top = topY+'px';
                    } 
                    return false;
                },
                toCenter : function(eff,spec) {
                    if(spec) this.addListener('scroll',this.scrollMove);
                    this.toCenterProto(eff,spec);
                },
                scrollOffset : {},
                scrollMove : function(e) {
                    this.toCenterProto(1);
                },
                mousePop : function(e) {
                    var mousePos = X.mousePos(e);
                    this.toPos(mousePos.x+5,mousePos.y+5);
                },
                byNodePop : function(byObj,direct) {
                    if(!byObj.getPos) byObj = X.$(byObj);
                    var pop = this;
                    var overpop = false;
                    var setPos = function(direct) {
                        var pos = byObj.getPos();
                        var popPos = pop.getPos(); 
                        switch(direct) {
                        case 1: //位于下侧
                            pop.toPos(pos.x,pos.y+pos.h);
                        return;
                        case 3: //右侧内
                            var w = pop.getStyle('width').replace(/[A-za-z]+/i,'');
                            var left = pos.x+pos.w * 1-w;
                            pop.toPos(pos.x+pos.w-w,pos.y);
                            return;
                        default:  //默认位于右侧
                            var pagePos = X.pageShowSize();
                            var left = pos.x + pos.w;
                            if(pos.x+pos.w + popPos.w> pagePos.w) {
                                left = pos.x-popPos.w;
                            }
                            var t = pos.y;
                            if(pos.y+popPos.h > pagePos.h) {
                                t = pos.y+pos.h - popPos.h;
                            }
                            if(t < 0) t = 0;
                            pop.toPos(left, t);
                            return;
                        }
                    };
                    var pmof = function(e) {
                        var overNode = X.getEventNode(e);
                        if(overNode == byObj || overNode == pop || overNode.isNodeChild(byObj) || overNode.isNodeChild(pop)) {
                            if(pop.isHidden == true) {
                                pop.style.display = 'block';
                                pop.isHidden = false;
                                setPos(direct);
                            }
                            return;
                        }
                        if(pop.isHidden == false) {
                            pop.style.display = 'none';
                            pop.isHidden = true;
                        }
                    };
                    this.style.display = 'none';
                    this.isHidden = true;
                    X.mouseover(pmof, byObj);
                    setPos(direct);
                },
                maxImg : function(cls,bsrc) {
                    if(this.tag != 'img') return;
                    this.setAttribute('title','点击图片查看大图');
                    this.addListener('click',function(e) {
                        var pPos = X.pageShowSize();
                        pPos.w+=X.scrollOffset().x;
                        pPos.h+=X.scrollOffset().y;
                        var src = bsrc ? bsrc : X.getEventNode(e).src;
                        var bg = X.createNode('div');
                        bg.setClass(cls);
                        var img = X.createNode('img');
                        img.setAttribute('src',src);
                        img.setAttribute('title','点击关闭查看大图');
                        var hide = function(e) {bg.destroy();img.destroy();};
                        bg.addListener('click',hide);
                        img.addListener('click',hide);
                        var alpha = X.getOpacityStr(80);
                        bg.setCss('position:absolute;left:0;top:0;width:'+pPos.w+'px;height:'+pPos.h+'px;'+alpha);
                        bg.setOnTop();
                        img.setCss('position:absolute;');
                        bg.setOnTop();
                        X.doc.body.appendChild(img);
                        X.doc.body.appendChild(bg);
                        img.toCenter();
                        img.setOnTop();
                    });
                },
                toPos : function(x,y) {
                    this.style.position = 'absolute';
                    this.setStyle('top',y+'px');
                    this.setStyle('left',x+'px');
                    this.setOnTop();
                },
                move : function(down,spec) {
                    var NodeMoveObj = {};
                    NodeMoveObj.pointerNode = down ? down : this;
                    if(!NodeMoveObj.pointerNode.setStyle)  NodeMoveObj.pointerNode = X.$(NodeMoveObj.pointerNode);
                    NodeMoveObj.pointerNode.setStyle('cursor','default');
                    this.setStyle('position','absolute');
                    NodeMoveObj.moveNode = this;
                    NodeMoveObj.mousedown = false;
                    NodeMoveObj.moveRange = false;
                    NodeMoveObj.execId = null;
                    if(spec) {
                        var RangePos = typeof spec.getPos == 'undefined' ? X.$(spec).getPos() : spec.getPos();
                        NodeMoveObj.moveRange = {};
                        NodeMoveObj.moveRange.minX = RangePos.x;
                        NodeMoveObj.moveRange.minY = RangePos.y;
                        NodeMoveObj.moveRange.maxX = RangePos.x+RangePos.w;
                        NodeMoveObj.moveRange.maxY = RangePos.y+RangePos.h;
                    }
                    X.mousedown.left(function(e) {
                        var downNode = X.getEventNode(e);
                        if(e.eventObj && e.eventObj == down && downNode == down) {
                            X.delDefultEvent(e);
                            if(NodeMoveObj.execId) clearTimeout(NodeMoveObj.execId);
                            NodeMoveObj.startPos = NodeMoveObj.moveNode.getPos();
                            NodeMoveObj.mousedown = true;
                            NodeMoveObj.mosePos = X.mousePos(e);
                            NodeMoveObj.offsetX = Math.round(NodeMoveObj.mosePos.x - NodeMoveObj.startPos.x);
                            NodeMoveObj.offsetY = Math.round(NodeMoveObj.mosePos.y - NodeMoveObj.startPos.y);
                            //NodeMoveObj.execId = setTimeout(moveNode,10);
                            moveNode();
                        }
                    },down);
                    X.mouseup.left(function(e) {
                        X.delDefultEvent(e);
                        NodeMoveObj.mousedown = false;
                        if(NodeMoveObj.execId) clearTimeout(NodeMoveObj.execId);
                    });
                    var moveNode = function() {
                        if(NodeMoveObj.mousedown == false) return;
                        clearTimeout(NodeMoveObj.execId);
                        var moveToX = Math.round(X.mouseX- NodeMoveObj.offsetX);
                        var moveToY = Math.round(X.mouseY - NodeMoveObj.offsetY);
                        if(NodeMoveObj.moveRange != false) {
                            if(NodeMoveObj.moveRange.minX >= moveToX) moveToX = NodeMoveObj.moveRange.minX;
                            if(NodeMoveObj.moveRange.minY >= moveToY) moveToY = NodeMoveObj.moveRange.minY;
                            if(NodeMoveObj.moveRange.maxX <= moveToX+NodeMoveObj.startPos.w) moveToX = NodeMoveObj.moveRange.maxX - NodeMoveObj.startPos.w;
                            if(NodeMoveObj.moveRange.maxY <= moveToY+NodeMoveObj.startPos.h) moveToY = NodeMoveObj.moveRange.maxY - NodeMoveObj.startPos.h;
                        } else {
                            if(moveToX <=0 ) moveToX = 0;
                            if(moveToY <=0) moveToY =0;
                        }
                        NodeMoveObj.execId = setTimeout(moveNode,10);
                        NodeMoveObj.moveNode.toPos(moveToX,moveToY);
                        //console.log(X.time());
                        return;
                    };
                },
                maxsize : function(spec,part) {
                    var maxSizeNode = spec ? spec : X.$(X.doc.body);
                    var clickNode = part ? X.$(part) : this;
                    var maxSize = maxSizeNode.getPos ? maxSizeNode.getPos() : X.$(maxSizeNode).getPos;
                    var initSize = this.getPos();
                    var changeNode = this;
                    var nodeToMaxSize = function(e) {
                        var nodePos = changeNode.getPos();
                        if(nodePos.w < maxSize.w||nodePos.h < maxSize.h) {
                            initSize = changeNode.getPos();
                            changeNode.style.top = maxSize.y+'px';
                            changeNode.style.left = maxSize.x+'px';
                            changeNode.style.width = maxSize.w+'px';
                            changeNode.style.height = maxSize.h+'px';
                        } else {
                            changeNode.style.top = initSize.y+'px';
                            changeNode.style.left = initSize.x+'px';
                            changeNode.style.width = initSize.w+'px';
                            changeNode.style.height = initSize.h+'px';
                        }
                    };
                    clickNode.addListener('dblclick',nodeToMaxSize);
                },
                resize : function(sens,spec) {
                    var resizeNodeObj = {};
                    resizeNodeObj.node = this;
                    resizeNodeObj.cursorList = {ltc:'nw-resize',lbc:'sw-resize',l:'w-resize',
                                                rbc:'se-resize',rtc:'ne-resize',r:'e-resize',
                                                t:'n-resize',b:'s-resize'};
                    resizeNodeObj.sens = sens ? sens : 10;
                    resizeNodeObj.startResize = false;
                    var setMouseCursor = function(e) {
                        X.delDefultEvent(e);
                        var nodePos = resizeNodeObj.node.getPos();
                        var mousePos = X.mousePos(e);
                        var minX = nodePos.x;
                        var minXS = nodePos.x + resizeNodeObj.sens;
                        var minY = nodePos.y;
                        var minYS = nodePos.y + resizeNodeObj.sens;
                        var maxX = nodePos.x+nodePos.w;
                        var maxXS = maxX- resizeNodeObj.sens;
                        var maxY = nodePos.y+nodePos.h;
                        var maxYS = maxY - resizeNodeObj.sens;
                        var mouseX = 0,mouseY = 0;
                        if(mousePos.x>=minX && mousePos.x<=minXS) {
                            var mouseX = 1;
                        } else if(mousePos.x<=maxX && mousePos.x>=maxXS) {
                            var mouseX = 2;
                        } else {
                            var mouseX = 3;
                        }
                        if(mousePos.y>=minY && mousePos.y<=minYS) {
                            var mouseY =1;
                        } else if(mousePos.y<=maxY && mousePos.y>=maxYS) {
                            var mouseY =2;
                        } else {
                            var mouseY =3;
                        }
                        if(mouseY == 1 && mouseX ==1) resizeNodeObj.node.setStyle('cursor',resizeNodeObj.cursorList.ltc);
                        else if(mouseX == 1 && mouseY == 2) resizeNodeObj.node.setStyle('cursor',resizeNodeObj.cursorList.lbc);
                        else if(mouseX == 1 && mouseY == 3) resizeNodeObj.node.setStyle('cursor',resizeNodeObj.cursorList.l);
                        else if(mouseX == 2 && mouseY == 1) resizeNodeObj.node.setStyle('cursor',resizeNodeObj.cursorList.rtc);
                        else if(mouseX == 2 && mouseY == 2) resizeNodeObj.node.setStyle('cursor',resizeNodeObj.cursorList.rbc);
                        else if(mouseX == 2 && mouseY == 3) resizeNodeObj.node.setStyle('cursor',resizeNodeObj.cursorList.r);
                        else if(mouseX == 3 && mouseY == 1) resizeNodeObj.node.setStyle('cursor',resizeNodeObj.cursorList.t);
                        else if(mouseX == 3 && mouseY == 2) resizeNodeObj.node.setStyle('cursor',resizeNodeObj.cursorList.b);
                        else resizeNodeObj.node.setStyle('cursor','auto');
                        if(resizeNodeObj.startResize && !(mouseX == 3 && mouseY ==3)) {
                            var mousePrePos = resizeNodeObj.mosePos;
                            resizeNodeObj.mousePos = X.mousePos(e);
                            var offsetX = resizeNodeObj.mousePos.x - mousePrePos.x;
                            var offsetY = resizeNodeObj.mousePos.y - mousePrePos.y;
                            //var moveToX = resizeNodeObj.startPos.x + offsetX;
                            //var moveToY = resizeNodeObj.startPos.y + offsetY;
                            var moveToX = resizeNodeObj.startPos.x;
                            var width = resizeNodeObj.startPos.w;
                            var height = resizeNodeObj.startPos.h;
                            var moveToY = resizeNodeObj.startPos.y;
                            if(mouseX==1) {
                                var width = resizeNodeObj.startPos.w - offsetX;
                                var moveToX = resizeNodeObj.startPos.x + offsetX;
                            } else if(mouseX == 2) {
                                var width = resizeNodeObj.startPos.w+offsetX;
                            }
                            if(mouseY ==1) {
                                var height = resizeNodeObj.startPos.h - offsetY;
                                var moveToY = resizeNodeObj.startPos.y + offsetY;
                            } else if(mouseY == 2) {
                                var height = resizeNodeObj.startPos.h + offsetY;
                            }
                            resizeNodeObj.node.style.top = moveToY+'px';
                            resizeNodeObj.node.style.height = height+'px';
                            resizeNodeObj.node.style.left = moveToX+'px';
                            resizeNodeObj.node.style.width = width+'px';
                        }
                    };
                    function endResizeNode(e) {
                        X.delDefultEvent(e);
                        resizeNodeObj.node.setStyle('cursor','auto');
                        resizeNodeObj.startResize = false;
                    };
                    function startResizeNode(e) {
                        X.delDefultEvent(e);
                        resizeNodeObj.startResize = true;
                        resizeNodeObj.startPos = resizeNodeObj.node.getPos();
                        resizeNodeObj.mosePos = X.mousePos(e);
                    };
                    this.addListener('mousemove',setMouseCursor);
                    this.addListener('mousedown',startResizeNode);
                    this.addListener('mouseup',endResizeNode);
                    this.addListener('mouseout',endResizeNode);
                    if(spec) {
                        spec = spec.addListener ? spec : X.$(spec);
                        spec.addListener('mouseout',endResizeNode);
                    }
                },
                close : function(spec) {
                    var clickNode = spec ? (spec.getPos ? spec : X.$(spec)) : this;
                    clickNode.addListener('click',function(e) { clickNode.style.display = 'none'});
                },
                hide : function() {
                    this.style.display = 'none';
                },
                destroy : function() {
                    var t = this;
                    this.parentNode.removeChild(this);
                    delete t;
                },
                getCursorOffset : function() {
                    if(this.selectionStart) return this.selectionStart;
                    if(X.doc.selection) {
                        var selectionObj = X.doc.selection.createRange();
                        selectionObj.moveStart ('character', - this.value.length);
                        return selectionObj.text.length;
                    }
                    return 0;
                },
                setCursorOffset : function(offset,start) {
                    var start = 0;
                    if(X.doc.hasFocus() && this == X.getFocusNode()) start = this.getCursorOffset();
                    else this.focus();
                    var pos = start+offset;
                    if(this.setSelectionRange) this.setSelectionRange(pos,pos);
                    if(this.createTextRange) {
                        var rangeObj = this.createTextRange();
                        rangeObj.collapse(true);
                        rangeObj.moveEnd('character', pos);
                        rangeObj.moveStart('character', pos);
                        rangeObj.select();
                        this.focus();
                    }
                }
            };
            for(var fn in __extend) __element[fn] = __extend[fn];
            return __element;
        },
        Ajax : {
            XMLHttp :  null,
            dataType : 'json',
            charset : 'utf-8',
            MimeType : 'text/html;charset=utf-8',
            url : null,
            method : null,
            data : null,
            callFunc : [],
            defaultDomain : 'http://'+window.location.host,
            rewriteTag : '/?',
            waitTime : 10000,
            outObj : [],
            formObj : null,
            messageList : {start:'',complete:'',still:'',current:''},
            message : null,
            messageNode : null,
            showTime : 2000,
            hiddenStatus : true,
            openInstance : [],
            openInstanceId : 0,
            setMimeType : function() {
                if(X.Ajax.dataType.toLowerCase() == 'json') {
                    var mime = 'text/html';
                } else {
                    var mime = 'text/xml';
                };
                X.Ajax.MimeType = mime + ';charset='+X.Ajax.charset;
            },
            setUrl : function(url) {
                var h = url.substr(url, 4);
                if(h.toLowerCase() != 'http') {
                    url = X.Ajax.defaultDomain + url;
                };
                X.Ajax.url = url.strpos('?') != false ? url+'&is_ajax=1' : url+'?is_ajax=1';
                X.Ajax.url+= '&t='+(new Date().getTime());
            },
            get : function(url, callFunc) {
                X.Ajax.init();
                X.Ajax.setUrl(url);
                X.Ajax.method = 'GET';
                var openId = X.Ajax.openInstanceId;
                if(callFunc) X.Ajax.callFunc[openId] = callFunc;
                X.Ajax.openInstanceId++;
                X.Ajax.callServer(openId);
            },
            post : function(url, data, callFunc) {
                X.Ajax.init();
                X.Ajax.setUrl(url);
                X.Ajax.setData(data);
                X.Ajax.method  = 'POST';
                var openId = X.Ajax.openInstanceId;
                if(callFunc) X.Ajax.callFunc[openId] = callFunc;
                X.Ajax.openInstanceId++;
                X.Ajax.callServer(openId);
            },
            file : function(form, callFunc) {
                var enc = form.getAttr('enctype');
                if(enc != 'multipart/form-data') {
                    form.setAttribute('enctype','multipart/form-data');
                }
                X.Ajax.setUrl(form.getAttr('action'));
                form.setAttribute('action',X.Ajax.url);
                var target_name = 'XAjaxIframe'+X.time();
                form.setAttribute('target',target_name);
                var upload_target = X.createNode('iframe');
                upload_target.setAttribute('name',target_name);
                upload_target.setCss('border:none;height:0px;width:0px;');
                upload_target.setAttribute('frameboder','none');
                X.doc.body.appendChild(upload_target);
                if(navigator.IEV < 8){ upload_target.contentWindow.name = target_name;}
                var isSubmit = false;
                upload_target.addListener('load',function() {
                    if(isSubmit == false) return;
                    var restr = upload_target.getIframeBody().innerHTML;
                 //   setTimeout(function(){if(isSubmit==true) {upload_target.destroy();}},1000);
                    if(restr == '') {
                        console.warn('Ajax Upload File response data is empty');
                        return 403;
                    }
                    if(X.Ajax.dataType.toLowerCase() == 'json') {
                        try{
                            var res = JSON.parse(restr);
                        } catch(e) {
                            if(/413/i.test(restr)) {
                                console.warn('X.Ajax upload file is Too large');
                                return 413;
                            }
                            if(/512/i.test(restr)) {
                                console.warn('X.Ajax upload file timeout');
                                return 512;
                            }
                            console.warn('Ajax Upload File response data is not JSON'+e);
                        }
                        callFunc(res);
                        return;
                        try{ callFunc(res);}catch(e) {
                            if(navigator.IE) {
                                e.message = e.description;
                                e.lineNumber =  e.number;
                            }
                            console.warn('Callback Function Error:'+e.message + ' in File '+e.fileName+' line '+e.lineNumber);
                        }
                    } else {
                        callFunc(restr);
                        return;
                        try{callFunc(restr);}catch(e) {
                            if(navigator.IE) {
                                e.message = e.description;
                                e.lineNumber =  e.number;
                            }
                            console.warn('Callback Function Error:'+e.message + ' in File '+e.fileName+' line '+e.lineNumber);
                        }
                    }
                });
                form.submit();
                isSubmit = true;
            },
            setData : function(data) {
                var str = '';
                for(i in data) if(isNaN(i)) str += i+'='+encodeURIComponent(data[i])+'&';
                X.Ajax.data = str;
            },
            complete : function() {
                X.Ajax.message = X.Ajax.messageList.complete;
                X.Ajax.showMessageNode();
                X.setTimeout(X.Ajax.hiddenMessageNode,X.Ajax.showTime);
            },

            hiddenMessageNode : function() {
                if(X.Ajax.hiddenStatus) return;
                if(X.Ajax.messageNode) X.Ajax.messageNode.style.display = 'none';
            },
            showMessageNode : function() {
                if(X.Ajax.hiddenStatus) return;
                if(X.Ajax.messageNode != null) {
                    X.Ajax.messageNode.style.display = 'block';
                    X.Ajax.messageNode.innerHTML = X.Ajax.message;
                }
            },
            showStatus : function() {
                X.Ajax.statusObj = X.setTimeout(function() {X.Ajax.message = X.Ajax.messageList.still;X.Ajax.showMessageNode();},3000);
            },
            callServer : function(openId) {
                if(!X.Ajax.XMLHttp) return;
                X.Ajax.message = X.Ajax.messageList.current;
                X.Ajax.showMessageNode();
                X.Ajax.openInstance[openId] = X.Ajax.XMLHttp;
                X.Ajax.openInstance[openId].open(X.Ajax.method, X.Ajax.url,X.Ajax.waitTime);
                if (X.Ajax.method == "POST") X.Ajax.openInstance[openId].setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                X.Ajax.openInstance[openId].send(X.Ajax.data);
                X.Ajax.outObj[openId] = X.setTimeout(function(){
                    X.Ajax.openInstance[openId].abort();
                    delete X.Ajax.openInstance[openId];
                    X.Ajax.complete();}, X.Ajax.waitTime);
                X.Ajax.showStatus();
                X.Ajax.openInstance[openId].onreadystatechange = function() {
                    if (X.Ajax.openInstance[openId].readyState == 4) {
                        X.clearTimeout(X.Ajax.outObj[openId]);
                        X.clearTimeout(X.Ajax.statusObj);
                        X.Ajax.complete();
                        if(X.Ajax.openInstance[openId].status == 200) {
                            if (X.Ajax.callFunc) {
                                if(X.Ajax.dataType.toLowerCase() == 'json') {
                                    //try{
                                        if(X.Ajax.openInstance[openId].responseText == '') {
                                            X.Ajax.callFunc[openId](X.Ajax.openInstance[openId].responseText);
                                        } else {
                                            var reJSON = JSON.parse(X.Ajax.openInstance[openId].responseText);
                                            X.Ajax.callFunc[openId](reJSON);
                                            return;
                                            try {
                                                X.Ajax.callFunc[openId](reJSON);
                                            } catch(e) {
                                                console.warn('Callback Function Error:'+e.message + ' in File '+e.fileName+' line '+e.lineNumber);
                                            }
                                            if(reJSON.debug) {
                                                X.debugInnerHTML(reJSON.debug);
                                            }
                                        }
                                        /*
                                    } catch(e) {
                                        console.warn('Ajax response data is not JSON'+e);
                                    }*/
                                } else {
                                    X.Ajax.callFunc(X.Ajax.openInstance[openId].responseXML); 
                                }
                            }
                        } else {
                            if(X.Ajax.openInstance[openId].status == 0) {
                                console.warn('Ajax requset timeout');
                                //alert('网络超时，请稍后在试');
                            }
                        }
                        delete X.Ajax.openInstance[openId];
                    }
                }
            },
            init : function() {
                X.Ajax.setMimeType();
                if(window.XMLHttpRequest) {
                    X.Ajax.XMLHttp = new XMLHttpRequest();
                    if(X.Ajax.XMLHttp.overrideMimeType) {
                        X.Ajax.XMLHttp.overrideMimeType(X.Ajax.MimeType);
                    }
                } else if(window.ActiveXObject) {
                    var versions = ['Microsoft.XMLHTTP','MSXML6.XMLHTTP', 'MSXML5.XMLHTTP', 'MSXML4.XMLHTTP', 'MSXML3.XMLHTTP', 'MSXML2.XMLHTTP', 'MSXML.XMLHTTP'];
                    for(var i=0;i<versions.length;i++) {
                        try{
                            var ieObject = new ActiveXObject('Microsoft.XMLHTTP');
                            X.Ajax.XMLHttp = ieObject;
                            break;
                        } catch(e) {
                            X.Ajax.XMLHttp = false;
                        }
                    }
                } else {
                    alert('Your browser un-support Ajax,Please Update Your browser');
                    X.Ajax.XMLHttp = false;
                }
            }
        },
        carousel : function(data,obj,type,eff,cls,waitTime) {
            var preInter = null;
            var nextInter = null;
            var autoTimeout = null;
            var current = 0;
            var force = false;
            var preOpacity = 100;
            var nextOpacity = 0;
            var changeStatus = false;
            var startCarousel = function(e) {
                if(autoTimeout) X.clearTimeout(autoTimeout);
                autoTimeout = X.setTimeout(hideShow,waitTime);
            };
            var hideShow = function() {
                if(changeStatus) return;
                var currentObj = mainDiv.getNodeByAttr('rol',current)[0];
                var next = (current >= itemCount-1) ? 0 : current*1+1;
                if(force) {
                    if(force == current) return;
                    next = force;force = false;
                }
                current = next;
                var showObj = mainDiv.getNodeByAttr('rol',next)[0];
                if(eff == 2) {
                    mainDiv.setStyle('overflow','hidden');
                    var currentPos = currentObj.getPos();
                    currentObj.setStyle('position','absolute');
                    //currentObj.setStyle('top',currentPos.y+'px');
                    currentObj.setStyle('left',initPos.x+'px');
                    var showObjPos = showObj.getPos();
                    showObj.setStyle('visibility','hidden');
                    showObj.setStyle('position','absolute');
                    showObj.setStyle('display','block');
                   // showObj.setStyle('top',currentPos.y+'px');
                    var showObjLeft = initPos.x - initPos.w;
                    showObj.setStyle('left',showObjLeft+'px');
                    var step = initPos.w/100;
                    var cLeft = initPos.x;
                    var sLeft = showObj.getPos().x;
                    showObj.setStyle('visibility','visible');
                    changeStatus = true;
                    preInter = X.setInterval(function() {
                        cLeft = cLeft + step;
                        sLeft = sLeft + step;
                        currentObj.setStyle('left',cLeft+'px');
                        showObj.setStyle('left',sLeft+'px');
                        if(sLeft >= currentPos.x) {
                            X.clearInterval(preInter);
                            currentObj.setStyle('visibility','hidden');
                            changeStatus = false;
                            startCarousel();
                        }
                    },1);
                } else {
                    preInter = X.setInterval(function() {
                    preOpacity = preOpacity-10;
                    var opacityStr = X.getOpacityStr(preOpacity)
                    currentObj.setCss(opacityStr);
                    if(preOpacity <= 0) {
                        X.clearInterval(preInter);
                        preOpacity = 100;
                        currentObj.setStyle('display','none');
                        changeStatus = true;
                        nextInter = X.setInterval(function() {
                            nextOpacity = nextOpacity+10;
                            var opacityStr = X.getOpacityStr(nextOpacity);
                            showObj.setCss(opacityStr);
                            showObj.setStyle('display','inline-block');
                            if(nextOpacity>=100) {
                                X.clearInterval(nextInter);
                                nextOpacity = 0;
                                changeStatus = false;
                                startCarousel();
                            }
                        },20);
                    }
                    },20);
                }
            }
            var stopCarousel = function(e) {
                if(autoTimeout) X.clearTimeout(autoTimeout);
            };
            var changeItem = function(e) {
                var i = X.getEventNode(e).getAttr('rol');
                if(!i) return;
                stopCarousel();
                force = i;
                hideShow();
            };
            var preItem = function(e) {
                stopCarousel();
                force = current<=0 ? 7 : current--;
                hideShow();
            };
            var nextItem = function(e) {
                stopCarousel();
                hideShow();
            };
            waitTime = waitTime || 3000;
            var boxDiv = X.createNode('div');
            var mainDiv = boxDiv.copyNode(true);
            mainDiv.setClass(cls+'CarouselMainBox');
            var a = X.createNode('a');
            var j = 0;
            for(var i in data) {
                if(isNaN(i)) continue;
                var itemA = a.copyNode(true);
                itemA.setStyle('display','none');
                itemA.setAttribute('rol',i);
                itemA.setAttribute('href',data[i].link);
                if(data[i].label) itemA.setAttribute('title',data[i].label);
                itemA.innerHTML = '<img src="'+data[i].img+'"/>';
                mainDiv.appendChild(itemA);
                j++;
            }
            mainDiv.getFirstNode().style.display = 'block';
            mainDiv.setStyle('position','relative');
            current = 0;
            mainDiv.addListener('mouseover',stopCarousel);
            mainDiv.addListener('mouseout',startCarousel);
            var itemCount = j;
            if(type == 2) {
                var preDiv = boxDiv.copyNode(true);
                preDiv.setClass(cls+'CarouselPreDiv');
                preDiv.addListener('click',preItem);
                boxDiv.appendChild(preDiv);
                boxDiv.appendChild(mainDiv);
                var nextDiv = boxDiv.copyNode(true);
                nextDiv.setClass(cls+'CarouselNextDiv');
                nextDiv.addListener('click',nextItem);
                boxDiv.appendChild(nextDiv);
            } else {
                boxDiv.appendChild(mainDiv);
                var listDiv = X.createNode('div');
                var span = X.createNode('span');
                listDiv.setClass(cls+'CarouselListDiv');
                listDiv.addListener('mouseover',changeItem);
                for(var k in data) {
                    if(isNaN(k)) continue;
                    var itemSpan = span.copyNode(true);
                    itemSpan.setAttribute('rol',k);
                    switch(type) {
                        case 3: itemSpan.innerHTML = data[k].label;break;
                        case 4: itemSpan.innerHTML ='<img src="'+ (data[k].thumb || data[k].img)+'"/>';break;
                        default: itemSpan.innerHTML = k*1 + 1;break;
                    }
                    listDiv.appendChild(itemSpan);
                }
                listDiv.getFirstNode().setClass(cls+'CarouselCurrentSelect');
                listDiv.addListener('mouseout',startCarousel);
                boxDiv.appendChild(listDiv);
            }
            boxDiv.setClass(cls);
            obj.appendChild(boxDiv);
            var initPos = mainDiv.getPos();
            startCarousel();
            return boxDiv;
        },
        msgBox : function(msg,cls,zIndex,waitTime) {
            var box = X.createNode('div');
            box.innerHTML = msg;
            if(cls) box.setClass(cls);
            if(zIndex) box.setZIndex(zIndex);
            box.setStyle('position','absolute');
            X.doc.body.appendChild(box);
            box.toCenter();
            var waitTime = waitTime || 3000;
            X.setTimeout(function(){box.destroy();},waitTime);
            box.setOnTop();
            return box;
        },
        alertBox : function(tit,msg,func, cls,cover,zIndex) {
            return X.confirmBoxProto(1,tit,msg,func,cls,cover,zIndex);
        },
        confirmBox : function(tit,msg, func,cls,cover,zIndex) {
            return X.confirmBoxProto(2,tit,msg,func,cls,cover,zIndex);
        },
        confirmBoxProto : function(type,tit,msg,func,cls,cover,zIndex) {
            var box = X.createNode('div');
            var title = box.copyNode(true);
            var msgDiv = box.copyNode(true);
            var button = box.copyNode(true);
            var okButton = X.createNode('button');
            if(type == 2) {
                var cancelButton = okButton.copyNode(true);
            }
            if(cls) {
                box.setClass(cls);
                title.setClass(cls+'TitleDiv');
                msgDiv.setClass(cls+'MainDiv');
                button.setClass(cls+'ButtonDiv');
            }
            if(zIndex) box.setZIndex(zIndex);
            title.innerHTML = tit;
            msgDiv.innerHTML = msg;
            okButton.innerHTML = '确定';
            if(type == 2) {
                cancelButton.innerHTML = '取消';
                cancelButton.addListener('click',function(e) {if(func) func(e,false);box.destroy();
                    if(cover) X.hiddenPageCover();
                });
            }
            okButton.addListener('click',function(e){if(func) func(e,true);box.destroy();});
            button.appendChild(okButton);
            if(type==2) button.appendChild(cancelButton);
            box.appendChild(title);
            box.appendChild(msgDiv);
            box.appendChild(button);
            X.doc.body.appendChild(box);
            box.move(title);
            box.toCenter();
            if(cover) X.showPageCover();
            box.setOnTop();
            return box;
        },
        time : function() {
            return new Date().getTime();
        },
        inputBox : function(tit,msg,inputList,buttonList,cls,cover,zIndex) {
            var box = X.createNode('div');
            var titleDiv = box.copyNode(true);
            var closeDiv = box.copyNode(true);
            var msgDiv = box.copyNode(true);
            var mainDiv = X.createNode('div');
            var buttonDiv = box.copyNode(true);
            titleDiv.innerHTML = tit;
            msgDiv.innerHTML = msg;
            if(cls) {
                box.setClass(cls);
                titleDiv.setClass(cls+'TitleDiv');
                msgDiv.setClass(cls+'MsgDiv');
                mainDiv.setClass(cls+'MainDiv');
                buttonDiv.setClass(cls+'ButtonDiv');
                closeDiv.setClass(cls+'CloseDiv');
            } else {
                closeDiv.innerHTML = 'X';
                closeDiv.setStyle('float','right');
            }
            if(zIndex) box.setZIndex(zIndex);
            closeDiv.addListener('click',function(e) {box.destroy();
                if(cover) X.hiddenPageCover();
            });
            titleDiv.appendChild(closeDiv);
            box.appendChild(titleDiv);
            box.appendChild(msgDiv);
            var input = X.createNode('input');
            var button = X.createNode('button');
            for(var i in inputList) {
                if(isNaN(i)) continue;
                if(inputList[i].type == 'textarea') {
                    var inputItem = X.createNode('textarea');
                    inputItem.setAttribute('name',inputList[i].name);
                    inputItem.innerHTML = inputList[i].value;
                } else {
                    var inputItem = input.copyNode(true);
                    inputItem.setAttribute('type',inputList[i].type);
                    inputItem.setAttribute('name',inputList[i].name);
                    inputItem.setAttribute('value',inputList[i].value);
                    if(inputList[i].type =='checkbox' && inputList[i].checked) {
                        inputItem.setAttribute('checked','true');
                    }
                }
                if(inputList[i].type != 'hidden') {
                    var inputDiv = X.createNode('div');
                    var inputLabel = X.createNode('div');
                    inputLabel.innerHTML = inputList[i].label;
                    inputDiv.appendChild(inputLabel);
                    inputDiv.appendChild(inputItem);
                    if(inputList[i].cls) {
                        inputDiv.setClass(inputList[i].cls+'ItemDiv');
                        inputLabel.setClass(inputList[i].cls+'ItemLabel');
                    }
                } else {
                    inputDiv = inputItem;
                }
                if(inputList[i].cls) {
                    inputItem.setClass(inputList[i].cls);
                }
                mainDiv.appendChild(inputDiv);
            }
            box.appendChild(mainDiv);
            for(var j in buttonList) {
                if(isNaN(j)) continue;
                var bi = button.copyNode(true);
                bi.setClass(buttonList[j].cls);
                bi.innerHTML = buttonList[j].label;
                bi.setAttribute('value',buttonList[j].value);
                bi.addListener('click',buttonList[j].call);
                buttonDiv.appendChild(bi);
            }
            box.appendChild(buttonDiv);
            X.doc.body.appendChild(box);
            box.move(titleDiv);
            box.toCenter();
            if(cover) {
                X.showPageCover();
                box.setOnTop();
            }
            box.message = msgDiv;
            return box;
        },
        selectDiv : function(optionList,func,def,cls) {
            var box = X.createNode('div');
            var defDiv = box.copyNode(true);
            var defOption = box.copyNode(true);
            var listDiv = box.copyNode(true);
            var arrow = box.copyNode('div');
            box.setClass(cls);
            box.selected = null;
            box.defDiv = defDiv;
            defDiv.setClass(cls+'DefDiv');
            defOption.setClass(cls+'DefOption');
            listDiv.setClass(cls+'SelectOptionDiv');
            listDiv.setCss('position:absolute;z-index:10;max-height:200px;overflow:auto;');
            arrow.setCss('border-color:#000 transparent transparent;border-style:solid dashed dashed;border-width:6px 5px 0;height:0;width:0;cursor:pointer;float:left;line-height:0px;');
            defDiv.addListener('click',function(e) {
                if(listDiv.getStyle('display') == 'block') {
                    return listDiv.hide();
                }
                listDiv.style.display = 'block';
                var pos = defDiv.getPos();
                listDiv.setStyle('left',pos.x+'px');
                var topY = pos.y+pos.h;
                listDiv.setStyle('top', topY+'px');
                var dPos = box.getPos();
                listDiv.setStyle('width',dPos.w+'px');
            });
            defDiv.appendChild(defOption);
            defDiv.appendChild(arrow);
            box.appendChild(defDiv);
            var span = X.createNode('div');
            span.setStyle('display','block');
            for(var i in optionList) {
                if(optionList.hasOwnProperty(i)) {
                    var op = span.copyNode(true);
                    op.setAttribute('value',optionList[i].value);
                    op.setAttribute('rol','option');
                    op.innerHTML = optionList[i].label;
                    if(def && optionList[i].value == def.value && optionList[i].label == def.label) {
                        op.setClass(cls+'Selected');
                        box.selected = op;
                    }
                    if(optionList[i].disabled) {
                        op.setClass(cls+'OptionDisable');
                        op.setAttribute('disabled',true);
                    } else {
                        op.setStyle('cursor','pointer');
                    }
                    listDiv.appendChild(op);
                }
            }
            listDiv.addListener('click',function(e){
                var op = X.getEventNode(e);
                if(op.getAttr('rol') != 'option') return;
                var value = op.getAttr('value');
                if(op.getAttr('disabled')) return;
                var label = op.innerHTML;
                op.setClass(cls+'Selected');
                if(box.selected) box.selected.removeClass(cls+'Selected');
                box.selected = op;
                defOption.setAttribute('value',value);
                defOption.innerHTML = label;
                listDiv.style.display = 'none';
                func(value);
            });
            listDiv.addListener('mouseover',function(e) {
                var op = X.getEventNode(e);
                if(op.getAttr('disabled')) return;
                if(op.getAttr('rol') == 'option') op.setClass(cls+'OptionMouseOver');
            });
            listDiv.addListener('mouseout',function(e) {
                var op = X.getEventNode(e);
                if(op.getAttr('disabled')) return;
                if(op.getAttr('rol') == 'option') op.removeClass(cls+'OptionMouseOver');
            });
            listDiv.hide();
            box.appendChild(listDiv);
            defOption.setAttribute('value',def.value);
            defOption.innerHTML = def.label;
            var borderColor = defDiv.getStyle('color');
            arrow.setStyle('border-color',borderColor+' transparent transparent');
            var borderW = defDiv.getStyle('font-size');
            borderW = borderW.replace(/[a-zA-Z]+/i,'');
            borderW = borderW >= 15 ? borderW - 10 : borderW - 5;
            var borderH = borderW - 2;
            arrow.setStyle('border-width',borderW+'px '+borderH+'px 0');
            box.addListener('leftmouse',function(e) {listDiv.hide();});
            return box;
        },
        getEventNode : function(e) {
            var obj = navigator.IE ? event.srcElement : arguments[0].target;
            return X.$(obj);
        },
        getFocusNode : function(e) {
            var obj = X.doc.activeElement;
            return obj ? X.$(obj) : false;
        },
        setCache : function(obj,data,key) {
            var ec = X.getCache(key,obj);
            if(ec) {
                ec.data = data;
            } else {
                if(!X.cache[key]) {
                    X.cache[key] = [];
                    var len = 0;
                } else {
                    var len = X.cache[key].length -1;
                }
                X.cache[key][len] = {};
                X.cache[key][len].data = data;
                X.cache[key][len].obj = obj;
            }
        },
        pageShowSize : function() {
            var h = navigator.IE ? window.screen.availHeight : document.documentElement.clientHeight;
            var w = navigator.IE ? window.screen.availWidth : document.documentElement.clientWidth;
            if((navigator.FIREFOX || navigator.IE) && document.body.scrollBottom >0) {
                w = w -25;
            }
            return {h:h,w:w}
        },
        getCache : function(hay,need) {
            for(var i in X.cache[hay]) {
                if(!isNaN(i)&& X.cache[hay][i].obj == need) {
                    return X.cache[hay][i].data;
                }
                return false;
            }
        },
        delDefultEvent : function(e) {
            e = typeof event == 'undefined' ? e : event;
            if(e.preventDefault) return e.preventDefault();
            else e.returnValue = false;
        },
        mousePos : function(e) {
            e = typeof event == 'undefined' ? e : event;
            return {"x":e.clientX,"y":e.clientY};
        },
        getOpacityStr : function(num) {
            num = navigator.IE ? num : num/100;
            return navigator.IE ? "filter:alpha(opacity="+num+");" : 'opacity:'+num;
        },
        pageCover : null,
        showPageCover : function() {
            var viewSize = X.pageShowSize();
            var height = X.doc.body.offsetHeight > viewSize.h ? X.doc.body.offsetHeight+15 : viewSize.h;
            var width = X.doc.body.offsetWidth > viewSize.w ? X.doc.body.offsetWidth+15 : viewSize.w;
            var soff = X.scrollOffset();
            height = height + soff.y;
            width = width + soff.x;
            if(X.pageCover != null) {
                X.pageCover.style.display = 'block';
                X.pageCover.style.height = height+'px';
                X.pageCover.style.width = width +'px';
                return;
            }
            var alpha = X.getOpacityStr(50);
            X.pageCover = X.createNode('div');
            X.pageCover.setCss('position:absolute;top:0;left:0;padding:0;margin:0;background-color:#909090;'+alpha);
            X.pageCover.setOnTop();
            X.doc.body.appendChild(X.pageCover);
            X.pageCover.style.display = 'block';
            X.pageCover.style.height = height+'px';
            X.pageCover.style.width = width +'px';
        },
        getFormInputData : function(frm) {
            if(!frm.getSubNodeByTag) frm = X.$(frm);
            var inputList = frm.getSubNodeByTag('input');
            var formData = {};
            for(var i in inputList) {
                if(isNaN(i)) continue;
                var inputEelement = inputList[i];
                var eleType = inputEelement.inputType;
                if(inputEelement.style.display == 'none') continue;
                if((eleType == X.inputType.INPUT_CHECKBOX || eleType == X.inputType.INPUT_RADIO) && inputEelement.checked != true) continue;
                var key = inputEelement.getAttr('name');
                var value = inputEelement.value;
                if(key == '') continue;
                if(eleType == X.inputType.INPUT_CHECKBOX) {
                    if(typeof(formData[key]) == 'undefined') formData[key] = Array();
                    formData[key][formData[key].length] = value;
                } else {
                    formData[key] = value;
                }
            }
            var selectList = frm.getSubNodeByTag('select');
            for(var i in selectList) {
                if(isNaN(i)) continue;
                var select = selectList[i];
                var key = select.getAttr('name');
                if(select.style.display == 'none') continue;
                if(key == '') continue;
                formData[key] = select.value;
            }
            var textareaList = frm.getSubNodeByTag('textarea');
            for(var i in textareaList) {
                if(isNaN(i)) continue;
                var textarea = textareaList[i];
                var key = textarea.getAttr('name');
                if(textarea.style.display == 'none') continue;
                if(key == '') continue;
                formData[key] = textarea.value;
            }
            formData = JSON.stringify(formData);
            return {data:formData};
        },
        submitForm : function(ele,callFunc) {
            if(!ele.getParentNodeByTag) ele = X.$(ele);
            var formObj = ele.getParentNodeByTag('form');
            var formObj = ele.tag == 'form' ?  ele : formObj;
            if(!formObj || formObj.tag != 'form') {
                console.warn('form element not exists');
                return;
            }
            var formAction = formObj.getAttr('action');
            var formMethod = formObj.getAttr('method');
            var data = X.getFormInputData(formObj);
            if(formMethod.toLowerCase() == 'post') {
                X.Ajax.post(formAction,data,callFunc);
            } else {
                X.Ajax.setData(data);
                X.Ajax.get(formAction,callFunc);
            }
        },
        hiddenPageCover : function() {
            X.pageCover.style.display = 'none';
        },
        AjaxDebugMessageDiv : null,
        debugInnerHTML : function(html) {
            if(!X.AjaxDebugMessageDiv)  X.AjaxDebugMessageDiv = X.createNode('div');
            var m = '<h2>Ajax Return Server Debug Message</h2>';
            for(var i in html) m+=html[i];
            X.AjaxDebugMessageDiv.innerHTML = m;
            document.body.appendChild(X.AjaxDebugMessageDiv);
        },
        setCookie : function(cn, v, ex) {
            var e= new Date(),n = e.getTime();
            ex = n + ex *1000;
            e.setTime(ex);
            var cv=escape(v) + "; exs="+e.toUTCString();
            X.doc.cookie=cn + "=" + cv;
        },
        getCookie : function(cn) {
            var i,x,y,a=X.doc.cookie.split(";");
            for (i=0;i<a.length;i++) {
                x=a[i].substr(0,a[i].indexOf("="));
                y=a[i].substr(a[i].indexOf("=")+1);
                x=x.replace(/^\s+|\s+$/g,"");
                if (x==cn) return unescape(y);
            }
            return null;
        },
        version : 0.4
};
X.mousedown = (function() { return X.dMEP('mousedown')})();
X.mouseup = (function() {return X.dMEP('mouseup')})();
X.keyDown = (function(){ this.key = function(key,func) {X.addKeyListener(key,func,'keydown');};
    return X.dKEP(this);
})();
X.keyUp = (function() {
    this.key = function(key,func) {X.addKeyListener(key,func,'keydown');};
    return X.dKEP(this);
})();
window.onbeforeunload = X.unloadExec;
}
