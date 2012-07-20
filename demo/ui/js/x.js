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
String.prototype.isWord = function() {
    return /^(A-za-z0-9_)/i.test(this);
};
String.prototype.ucfirst = function() {
    var f = this.charAt(0).toUpperCase();
    return f + this.substr(1);
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
var X = {
doc : window.document,
xPNode : window.document.body, //X.$(ele)方法返回对象的父对象
isReady : false,
ugent : navigator.userAgent.toLowerCase(),
intervarlHandle : [],
timeoutHandle : [],
cache : {},
maxZIndex : 0,
keyList : {keyup:[],keydown:[]},

    //document鼠标点击事件回调函数列表
bMECFL : {
    mousedown : [[],[],[],[]],
    mouseup : [[],[],[],[]],
    mouseover : [],
    mouseout : [],
    click : [[],[],[],[]]
},
    //窗口滚动事件注册函数列表
wSFL : [],
wRSFL : [],
IEV : navigator.IE && ! document.documentMode ? 6 : document.documentMode,
createNode : function(t) {return X.$(X.doc.createElement(t));},
FIREFOX : /.*{firefox}\/([\w.]+).*/.test(this.ugent),
WEBKIT : /(Webkit)/i.test(this.ugent),
jsPath  : function(){
    var scripts = X.doc.scripts;
    var cidx = scripts.length - 1;
    var shash = scripts[cidx].src.lastIndexOf("/");
    if(shash < 0) return '';
    return scripts[cidx].src.substring(0,shash+1);
},
inputType : {INPUT_TEXT:1,INPUT_PASSWORD:2,INPUT_CHECKBOX:3,
            INPUT_RADIO:4,INPUT_TEXTAREA:5,INPUT_BUTTON:6,INPUT_SUBMIT:7,
            INPUT_IMAGE:8,INPUT_SELECT:9},
loadJSON : function() {
    typeof(JSON) === 'undefined' && X.loadJSFile(X.jsPath()+'json.js');
},
loadJSFile : function(fs, bodyEnd) {
    var f = X.createNode('script');
    f.setAttribute('type','text/javascript');
    f.setAttribute('src', fs);
    if(bodyEnd) {
        X.doc.body.appendChild(f);
        return f;
    }
    X.doc.getElementsByTagName('head')[0].appendChild(f);
    return f;
},
unloadExecList : [],
eventList : [],
unload : function(cf) {
    if(typeof cf == 'string') {
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
error : function(msg,url , line) {
},
init  : function() {
    window.onload = function() {
    window.onerror = X.error;
    X.loadJSON();
    if(X.doc) {
        X.doc.onkeydown = X.kEF;
        X.doc.onkeyup = X.kEF;
        X.doc.onmousedown = X.bMECF;
        X.doc.onmouseup = X.bMECF;
        X.doc.onmouseover = X.mMEF;
        X.doc.onmouseout = X.mMEF;
        window.onscroll = X.wSF;
        if(window.top == window.self) {
            X.doc.body.onresize = X.wRSF;
        }
    }
    for(var i in X.readyFunctionList) {
        if(isNaN(i)) continue;
        var func = X.readyFunctionList[i];
        func();
    }
    X.isReady = true;
    }
},
readyFunctionList : [],
ready : function(func) {
    X.readyFunctionList.push(func);
    X.init();
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
    if(!button) button = 3;
    X.bMECFL[type][button].push(func);
},
    //document鼠标移动事件回调函数
mMEF : function(e) {
    e = e||event;fL = X.bMECFL[e.type];
    if(fL.eventObj == X.getEventNode(e)) {
        e.eventObj = eventObj;
        for(var i in fL) !isNaN(i) && fL[i](e);
    } else {
        for(var i in fL) !isNaN(i) && fL[i](e);
    }
},
    //document鼠标点击事件回调函数
bMECF : function(e) {
    e = e || event,fL = X.bMECFL[e.type][e.button],fL = fL.concat(X.bMECFL[e.type][3]);
    for(var i in fL) !isNaN(i) && fL[i](e);
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
keyDown : function(){
    this.key = function(key,func) {X.addKeyListener(key,func,'keydown');};
    return X.dKEP(this);
},
keyUp : function() {
    this.key = function(key,func) {X.addKeyListener(key,func,'keydown');};
    return X.dKEP(this);
},
    //鼠标点击事件注册原型
dMEP : function(type) {
    return {left : function(func) {X.aBMECF(func,type,0)},
    right : function(func) {X.aBMECF(func,type,2)},
    middle : function(func) {X.aBMECF(func,type,1)},
    any : function(func) {X.aBMECF(func,type,3)}
    };
},
mouseover : function(func, eventObj) {
    func.eventObj = eventObj;
    X.aMMECF(func,'mouseover');
},
mouseout : function(func, eventObj) {
    X.aMMECF(func,'mouseout',eventObj);
},
mousedown : function() {
    return X.dMEP('mousedown');
},
mouseup : function() {
    return X.dMEP('mouseup'); 
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
/**
 * HTML DOM 元素访问函数
 *
 * @ele : mixed   元素标识,目前支持以下标识:
 *              #className   #跟随元素样式名，返回拥有该样式的所有对象的数组
 *              @tagName     @跟随元素标签名，返回所有拥有该标签的对象的数组
 *              %name        %跟随元素的name属性值，返回所有拥有该name值的对象的数组
 *              id           传入没有上面前缀字符的字符串时作为元素ID范围，返回该ID指向对象
 *              ELEMENT_NODE 传入一个元素对象时，将返回X.$(ELEMENT_NODE)对象
 *
 * @return X.$(ele) 返回一个封装否的元素对象
 *
 * 方法列表
 * X.$(ele).getIframeBody() 获取iframe元素引用页面的body对象
 * X.$(ele).getPos() 获取对象坐标数据 返回 {h : 高，w:宽，x:X坐标, y:Y坐标}
 * X.$(ele).copyNode()  复制元素, 返回X.$(ele)对象
 * ...........................见方法注释
 */
$ : function(ele) {
    if(!this.xPNode) this.xPNode = X.xPNode;
    if(!ele) {
        console.warn('error');
        return false;
    }
    var eleType = typeof(ele);
    switch(eleType) {
         case  'string':
            var firstWord = ele.split(0,1);
            var param = ele.split(1,ele.length);
            switch(firstWord) {
                case '#': //样式名
                    return (function(clsName) {
                        var list = Array();
                        var childList = X.$(this.xPNode).getChilds();
                        for(var t in childList) !isNaN(t) && X.$(childList[t]).hasClass(clsName) && (list[list.length] = X.$(childList[t]));
                        return list;
                        })(param);
                case '@'://标签名
                    return (function (tagName) {
                        var list = Array();
                        var childList = X.$(this.xPNode).getChilds();
                        for(var t in childList) !isNaN(t) && X.$(childList[t]).tag == tagName.toLowerCase() && (list[list.length] = X.$(childList[t]));
                        return list;
                    })(param);
                case '%'://NAME名
                    return (function(name) {
                        var list = Array();
                        var childList = X.$(this.xPNode).getChilds();
                        for(var t in childList) !isNaN(t) && childList[t].getAttribute('name') == name && (list[list.length] = X.$(childList[t]));
                        return list;
                    })(param);
                default:
                    var __element = document.getElementById(ele);
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
            __element.$ = X.$;
            __element.$.xPNode = __element;
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
            var __extend = {
                getIframeBody : function() {
                    return navigator.IE ? this.X.doc.body : this.contentDocument.body;
                },
                getPos : function() {
                    var y = this.offsetTop;
                    var x = this.offsetLeft;
                    var height = this.offsetHeight;
                    var width = this.offsetWidth;
                    var obj = this;
                    while(obj = obj.parentOffset) {    
                        x += obj.offsetLeft;    
                        y += obj.offsetTop;    
                    }
                    return {'x':x,'y':y,'h':height,'w':width};
                },
                copyNode : function(deep) {
                    return X.$(this.cloneNode(deep));
                },
                //根据样式名找子元素
                getNodeByCls : function(clsName) {
                    var childList = this.getChilds();
                    var list = Array();
                    for(var t in childList)
                        if(!isNaN(t) && childList[t].hasClass(clsName)) list[list.length] = childList[t];
                    return list;
                },
                //根据指定属性及属性值找子元素
                getChildNodeByAttr : function(attr,value) {
                    var childList = this.getChilds();
                    var list = Array();
                    for(var t in childList) if(!isNaN(t) && childList[t].getAttribute(attr) == value) list[list.length] = childList[t];
                    return list;
                },
                //根据指定属性及属性值找上级元素,直到到达body为止
                getParentNodeByAttr : function(attr,value) {
                    if(this.parentNode && this.parentNode.nodeType == Node.ELEMENT_NODE) {
                        if(this.parentNode.getAttribute(attr) == value) return X.$(this.parentNode);
                        else return X.$(this.parentNode).getParentNodeByAttr(attr,value);
                    }
                    return false;
                },
                //获取第一ELEMENT_NODE子元素
                getFirstNode : function() {
                    var fNode = this.firstChild;
                    while(fNode) {
                        if(fNode.nodeType == Node.ELEMENT_NODE) return X.$(fNode);
                        fNode = fNode.nextSibling;
                    }
                    return false;
                },
                //获取最后一个ELEMENT_NODE子元素
                getLastNode : function() {
                    var lNode = this.lastChild;
                    while(lNode) {
                        if(lNode.nodeType == Node.ELEMENT_NODE) return X.$(lNode);
                        lNode =lNode.previousSibling;
                    }
                    return false;
                },

                //检测当前元素是否是参数指定元素的子元素
                isNodeChild : function(parentNode) {
                    if(this.compareDocumentPosition) {
                        return this.compareDocumentPosition(parentNode) == 10;
                    }
                    return parentNode.contains(this);
                },
                //在第一个子元素前插入一个新节点
                unshiftChild : function(new_node) {
                    if(this.firstChild) {
                        return this.insertBefore(new_node,this.firstChild);
                    }
                    return this.appendChild(new_node);
                },
                //根据标签名查找上级元素,直到到达body
                getParentNodeByTag : function(tagName) {
                    if(this.parentNode) {
                        if(this.parentNode.tagName.toUpperCase() == 'HTML') return false;
                        if(this.parentNode.tagName == tagName.toUpperCase()) return X.$(this.parentNode);
                        else return X.$(this.parentNode).getParentNodeByTag(tagName);
                    }
                    return false;
                },
                //根据标签名查找子元素
                getSubNodeByTag : function(tagName) {
                    var childList = this.getChilds();
                    var list = Array();
                    for(var t in childList) {
                        if(!isNaN(t) && X.$(childList[t]).tag == tagName.toLowerCase())
                            list[list.length] = X.$(childList[t]);
                    }
                    return list;
                },
                //检查是否有指定样式名
                hasClass : function(cls) {
                    var re = new RegExp('(\\s|^)'+cls+'(\\s|$)');
                    return re.test(this.className);
                },
                //移除指定样式名
                removeClass : function(cls) {
                    if (this.hasClass(cls)) {
                        var reg = new RegExp('(\\s|^)'+cls+'(\\s|$)');
                        this.className=this.className.replace(reg,'');
                    }
                },
                //添加一个样式名
                addClass : function(cls) {
                    if(!this.hasClass(cls)) {
                        if(this.className !='') {
                            this.className = this.className+=' '+cls;
                        } else {
                            this.className = cls;
                        }
                    }
                },
                //设置样式名，会替换原有样式
                setClass : function(cls) {
                    this.className = cls;
                },
                //设置style属性值，会替换原有属性值
                setCss : function(value) {
                    if(navigator.IE) return this.style.cssText = value;
                    this.setAttribute('style',value);
                },
                //获取元素style属性中指定名字的值
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
                //设置一个style属性值
                setStyle : function(ns,value) {
                    ns = this.convStyleName(ns);
                    this.style[ns] = value;
                },
                //绝对定位时，让元素位于顶部
                setOnTop : function() {
                    var index = X.maxZIndex + 1;    
                    X.maxZIndex = index;
                    this.setStyle('z-index',index);
                },
                //设置元素z-index值
                setZIndex : function(idx) {
                    if(idx > X.maxZIndex) X.maxZIndex = idx;
                    this.setStyle('z-index',idx);
                },
                //元素下一个ELEMENT_NODE元素
                nextNode : function() {
                    var nNode = this.nextSibling;
                    while(nNode) {
                        if(nNode.nodeType == Node.ELEMENT_NODE) return X.$(nNode);
                        nNode = nNode.nextSibling;
                    }
                    return false;
                },
                //元素上一个ELEMENT_NODE元素
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
                    var iserr = false;
                    switch(e) {
                        case 'scroll':
                        this.scrollOffset = X.scrollOffset();
                        X.wSFL.push(call_action);   
                        return;
                        case 'resize':
                        var l = {func:call_action,obj:this};
                        if(window.top == window.self) {
                            X.wRSFL.push(l);
                        } else if(window.top.X){
                            window.top.X.wRSFL.push(l);
                        }
                        return;
                        case 'error':
                        var iserr = true;
                        case 'load':
                        if(navigator.IE && this.tag == 'script') {
                            this.onreadystatechange = function(e) {
                                if(script.readyState == 'loaded') {
                                    if(iserr) call_action(e);
                                } else if(script.readyState == 'complete') {
                                    if(!iserr) call_action(e);
                                }
                            }
                            return;
                        }
                        break;
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
                //提交表单
                submitForm : function(func, enter) {
                    var eventObj = this;
                    var _submitForm = function(e) {
                        X.submitForm(eventObj,func);
                    };
                    this.addListener('click',_submitForm);
                    if(enter) {
                        X.keyDown().enter(_submitForm);
                    }
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
                //让元素对象剧中,spec为true标识是否在页面滚动时剧中
                toCenter : function(eff,spec) {
                    if(!spec) this.addListener('scroll',this.scrollMove);
                    this.toCenterProto(eff,spec);
                },
                scrollOffset : {},
                scrollMove : function(e) {
                    this.toCenterProto(1);
                },
                mousePopNearX : 5,
                mousePopNearY : 5,
                //元素跟随鼠标
                mousePop : function(e) {
                    var mousePos = X.mousePos(e);
                    var scroll = X.scrollOffset();
                    this.toPos(mousePos.x+this.mousePopNearX +scroll.x,mousePos.y+this.mousePopNearY+scroll.y);
                },
                //元素跟随指定对象
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
                            if(pop.style.display == 'none') {
                                pop.style.display = 'block';
                                setPos(direct);
                            }
                            return;
                        }
                        pop.style.display = 'none';
                    } 
                //    this.style.display = 'block';
                    X.mouseover(pmof, byObj);
                    setPos(direct);
                },
                //放大图片
                maxImg : function(cls,bsrc) {
                    if(this.tag != 'img') return;
                    this.setAttribute('title','点击图片查看大图');
                    this.addListener('click',function(e) {
                        var pPos = X.pageShowSize();
                        var src = bsrc ? bsrc : X.getEventNode(e).src;
                        var bg = X.createNode('div');
                        bg.addClass(cls);
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
                //将元素移动到指定坐标
                toPos : function(x,y) {
                    this.style.position = 'absolute';
                    this.setStyle('top',y+'px');
                    this.setStyle('left',x+'px');
                    this.setOnTop();
                },
                //元素可移动，down为鼠标按下该元素时可移动,spec为只能在该元素范围内移动
                move : function(down,spec) {
                    var NodeMoveObj = {};
                    NodeMoveObj.pointerNode = down ? down : this;
                    if(!NodeMoveObj.pointerNode.setStyle)  NodeMoveObj.pointerNode = X.$(NodeMoveObj.pointerNode);
                    NodeMoveObj.pointerNode.setStyle('cursor','default');
                    this.setStyle('position','absolute');
                    NodeMoveObj.moveNode = this;
                    NodeMoveObj.mousedown = false;
                    NodeMoveObj.moveRange = false;
                    if(spec) {
                        var RangePos = typeof spec.getPos == 'undefined' ? X.$(spec).getPos() : spec.getPos();
                        NodeMoveObj.moveRange = {};
                        NodeMoveObj.moveRange.minX = RangePos.x;
                        NodeMoveObj.moveRange.minY = RangePos.y;
                        NodeMoveObj.moveRange.maxX = RangePos.x+RangePos.w;
                        NodeMoveObj.moveRange.maxY = RangePos.y+RangePos.h;
                    }
                    var mousDown = function(e) {
                        X.delDefultEvent(e);
                        NodeMoveObj.startPos = NodeMoveObj.moveNode.getPos();
                        NodeMoveObj.mousedown = true;
                        NodeMoveObj.mosePos = X.mousePos(e);
                    };
                    var endMove = function(e) {
                        X.delDefultEvent(e);
                        NodeMoveObj.mousedown = false;
                    };
                    var moveNode = function(e) {
                        X.delDefultEvent(e);
                        if(NodeMoveObj.mousedown == false) return;
                        var mousePrePos = NodeMoveObj.mosePos;
                        NodeMoveObj.mousePos = X.mousePos(e);
                        var offsetX = NodeMoveObj.mousePos.x - mousePrePos.x;
                        var offsetY = NodeMoveObj.mousePos.y - mousePrePos.y;
                        var moveToX = NodeMoveObj.startPos.x + offsetX;
                        var moveToY = NodeMoveObj.startPos.y + offsetY;
                        if(NodeMoveObj.moveRange != false) {
                            if(NodeMoveObj.moveRange.minX >= moveToX) moveToX = NodeMoveObj.moveRange.minX;
                            if(NodeMoveObj.moveRange.minY >= moveToY) moveToY = NodeMoveObj.moveRange.minY;
                            if(NodeMoveObj.moveRange.maxX <= moveToX+NodeMoveObj.startPos.w) moveToX = NodeMoveObj.moveRange.maxX - NodeMoveObj.startPos.w;
                            if(NodeMoveObj.moveRange.maxY <= moveToY+NodeMoveObj.startPos.h) moveToY = NodeMoveObj.moveRange.maxY - NodeMoveObj.startPos.h;
                        }
                        NodeMoveObj.moveNode.style.top = moveToY+'px';
                        NodeMoveObj.moveNode.style.left = moveToX+'px';
                        return;
                    };
                    down.addListener('mousemove',moveNode);
                    down.addListener('mousedown',mousDown);
                    down.addListener('mouseout',endMove);
                    down.addListener('mouseup',endMove);
                },
                //双击时放大对象，spec为只能放大到该元素范围，part为点击对象,type为true时为单击，否则为双击
                maxsize : function(spec,part, type) {
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
                    if(type) {
                        clickNode.addListener('click',nodeToMaxSize);
                    } else {
                        clickNode.addListener('dblclick',nodeToMaxSize);
                    }
                },
                //使元素可修改尺寸,spec为只能在该元素范围内，sens为鼠标灵敏度
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
                //隐藏元素，spec为点击该元素隐藏
                close : function(spec) {
                    var clickNode = spec ? (spec.getPos ? spec : X.$(spec)) : this;
                    clickNode.addListener('click',function(e) { clickNode.style.display = 'none'});
                },
                //隐藏元素，visibility为隐藏后是否保留位置
                hide : function(visibility) {
                    if(visibility) {
                        this.style.visibility = 'hidden';
                    } else {
                        this.style.display = 'none';
                    }
                },
                show : function(visibility) {
                    if(visibility) {
                        this.style.visibility  = 'visible';
                    } else {
                        this.style.display = 'block';
                    }
                },
                //销毁元素
                destroy : function() {
                    this.parentNode.removeChild(this);
                    delete this;
                },
                //获取当前输入区，光标偏移量
                getCursorOffset : function() {
                    if(this.selectionStart) return this.selectionStart;
                    if(X.doc.selection) {
                        var selectionObj = X.doc.selection.createRange();
                        selectionObj.moveStart ('character', - this.value.length);
                        return selectionObj.text.length;
                    }
                    return 0;
                },
                //设置光标偏移量
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
        /**
         *  AJAX对象
         *
         *  X.Ajax.get(url, callFunc) GET方法请求, 
         *             url      : string    请求URL
         *             callFunc : function  请求返回回调函数
         *                            callFunc(returnData)回调函数
         *  X.Ajax.post(url, data, callFunc) POST方法请求
         *             url      : string  请求URL
         *             data     : JSON  请求数据
         *             callFunc : function 请求返回回调函数
         *  X.Ajax.head(url,callFunc) HEAD方法请求
         *             url      : string  请求URL
         *             callFunc : function 请求返回回调函数
         *                           callFunc(responseHead)
         *  X.Ajax.put(url, data, callFunc) PUT 方法请求
         *  X.Ajax.options(url ,callFunc)  OPTIONS 方法请求
         *  X.Ajax.del(url,callFunc)  DELETE方法请求
         *  X.Ajax.trace(url,callFunc) TRACE方法请求 
         *                           callFunc(responseHead, responseText)
         *  X.Ajax.file(formObj, callFunc)  上传文件
         *              formObj  : ELEMENT_NODE  上传文件表单
         *              callFunc : function  请求返回回调函数
         *  X.Ajax.jsonp(url,callFunc)  JSONP请求
         *  X.Ajax.waitTime : 等待超时时间
         */
        Ajax : {
            XMLHttp :  null,
            dataType : 'json',
            charset : 'utf-8',
            MimeType : 'text/html;charset=utf-8',
            url : null,
            method : null,
            data : null,
            callFunc : [],
            defaultDomain : window.location.host,
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
                if(url.substr(0,4).toLowerCase() != 'http://' &&
                        url.substr(0,5).toLowerCase() != 'https://') {
                    var protocol = window.location.protocol=="https:" ? 'https':'http';
                    url = protocol+'://'+X.Ajax.defaultDomain + url;
                };
                X.Ajax.url = url.strpos('?') != false ? url+'&is_ajax=1' : url+'?is_ajax=1';
                X.Ajax.url+= '&t='+(new Date().getTime());
            },
            
            del : function(url, callFunc) {
                X.Ajax.__get(url,callFunc,'DELETE');
            },
            head : function(url, callFunc) {
                X.Ajax.__get(url,callFunc,'HEAD');
            },
            get : function(url , callFunc) {
                X.Ajax.__get(url,callFunc,'GET');
            },
            options : function(url, callFunc) {
                X.Ajax.__get(url,callFunc,'OPTIONS');
            },
            trace : function(url, callFunc) {
                X.Ajax.__get(url,callFunc,'TRACE');
            },
            __get : function(url, callFunc, method) {
                X.Ajax.init();
                X.Ajax.setUrl(url);
                X.Ajax.method = method;
                X.Ajax.callServer(callFunc);
            },
            put : function(url ,data, callFunc) {
                X.Ajax.init();
                X.Ajax.setUrl(url);
                X.Ajax.setData(data);
                X.Ajax.method  = 'PUT';
                X.Ajax.callServer(callFunc);
            },
            post : function(url, data, callFunc) {
                X.Ajax.init();
                X.Ajax.setUrl(url);
                X.Ajax.setData(data);
                X.Ajax.method  = 'POST';
                X.Ajax.callServer(callFunc);
            },
            jsonp : function(url, callFunc) {
                X.Ajax.setUrl(url);
                X.Ajax.url += '&jsonp=X.Ajax.callback';
                X.Ajax.openInstance[openId] = {};
                X.Ajax.openInstance[openId].url = X.Ajax.url;
                if(callFunc) X.Ajax.openInstance[openId].callFunc = callFunc;
                X.Ajax.openInstanceId++;
                X.Ajax.openInstance[openId].js = X.loadJSFile(X.Ajax.url, true);
                X.Ajax.openInstanceId[openId].js.addListener('error',X.Ajax.jsonperror);
            },
            jsonperror : function(e) {
                var js = X.getEventNode(e);
                js.destroy();
                console.warn('JSONP Load Error');
            },
            callback : function(reData) {
                var csrc = X.doc.scripts;
                csrc = csrc[csrc.length -1];
                for(var i in X.Ajax.openInstanceId) {
                    var sIns = X.Ajax.openInstanceId[i];
                    if(sIns.url && sIns.callFunc && sIns.url == csrc) {
                        sIns.callFunc(reData);
                    }
                }
            },
            socket : function(url, openFunc, receiveFunc) {
                if(navigator.FIREFOX && typeof(WebSocket) == 'undefined') {
                    var socket =  new MozWebSocket(url);
                } else if(typeof(WebSocket) == 'undefined') {
                    return false;
                }
                if(url.substr(0,4).toLowerCase() != 'ws://' && url.substr(0,5).toLowerCase() != 'wss://') {
                    var protocol = window.location.protocol=="https:" ? 'wss':'ws';
                    url = protocol+'://'+ X.Ajax.defaultDomain + url;
                }
                var socket = new WebSocket(url);
                socket.onopen = openFunc;
                socket.onmessage = receiveFunc;
                return socket;
            },
            file : function(form, callFunc) {
                var enc = form.getAttribute('enctype');
                if(enc != 'multipart/form-data') {
                    form.setAttribute('enctype','multipart/form-data');
                }
                X.Ajax.setUrl(form.getAttribute('action'));
                form.setAttribute('action',X.Ajax.url);
                var target_name = 'XAjaxIframe'+X.time();
                form.setAttribute('target',target_name);
                var upload_target = X.createNode('iframe');
                upload_target.setAttribute('name',target_name);
                upload_target.setCss('border:none;height:0;width:0;');
                upload_target.setAttribute('frameboder','none');
                X.doc.body.appendChild(upload_target);
                upload_target.addListener('readystatechange',function() {
                    if(document.readyState == 'loaded') {
                        console.warn('Ajax Uplad File Error');
                        return false;
                    }
                    if(document.readyState == 'complete') {
                    var restr = upload_target.getIframeBody().innerHTML;
                    setTimeout(function(){upload_target.destroy();},1000);
                    if(restr == '') {
                        callFunc('');
                    }
                    if(X.Ajax.dataType.toLowerCase() == 'json') {
                        try{
                        var res = JSON.parse(restr);
                        } catch(e) {
                            if(/413/i.test(restr)) {
                                console.warn('Ajax upload file is Too large');
                                return 413;
                            }
                            if(/512/i.test(restr)) {
                                console.warn('Ajax upload file timeout');
                                return 512;
                            }
                            console.warn('Ajax Upload File response data is not JSON'+e);
                        }
                        try{ callFunc(res);}catch(e) {
                            console.warn('Callback Function Error:'+e.message + ' in File '+e.fileName+' line '+e.lineNumber);
                        }
                    } else {
                        try{callFunc(restr);}catch(e) {
                            console.warn('Callback Function Error:'+e.message + ' in File '+e.fileName+' line '+e.lineNumber);
                        }
                    }
                }});
                form.submit();
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
            callServer : function(callFunc) {
                if(!X.Ajax.XMLHttp) return;
                X.Ajax.message = X.Ajax.messageList.current;
                X.Ajax.showMessageNode();
                var openId = X.Ajax.openInstanceId;
                X.Ajax.openInstance[openId] = {};
                if(callFunc) X.Ajax.openInstance[openId].callFunc = callFunc;
                X.Ajax.openInstanceId++;

                X.Ajax.openInstance[openId].XMLHttp = X.Ajax.XMLHttp;
                X.Ajax.openInstance[openId].XMLHttp.open(X.Ajax.method, X.Ajax.url,X.Ajax.waitTime);
                if (X.Ajax.method == "POST") 
                    X.Ajax.openInstance[openId].XMLHttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                X.Ajax.openInstance[openId].XMLHttp.send(X.Ajax.data);
                X.Ajax.openInstance[openId].outObj = X.setTimeout(function(){
                                                            X.Ajax.openInstance[openId].XMLHttp.abort();
                                                            delete X.Ajax.openInstance[openId];
                                                            X.Ajax.complete();
                                                    }, X.Ajax.waitTime);
                X.Ajax.openInstance[openId].method = X.Ajax.method ;
                X.Ajax.showStatus();
                X.Ajax.openInstance[openId].XMLHttp.onreadystatechange = function() {
                    if (X.Ajax.openInstance[openId].XMLHttp.readyState == 4) {
                        X.clearTimeout(X.Ajax.openInstance[openId].outObj);
                        X.clearTimeout(X.Ajax.statusObj);
                        X.Ajax.complete();
                        if(X.Ajax.openInstance[openId].method == 'HEAD') {
                            if(X.Ajax.openInstance[openId].XMLHttp.status == 0) {
                                return X.Ajax.openInstance[openId].callFunc(0);
                            }
                            var headerStr = X.Ajax.openInstance[openId].XMLHttp.getAllResponseHeaders();
                            var headerArr = headerStr.split("\r\n");
                            var header = [];
                            for(var h in headerArr) {
                                if(typeof(headerArr[h]) == 'string') {
                                    var fvs = headerArr[h].trim();
                                    if(fvs == '') continue;
                                    var fv = fvs.split(':');
                                    header[fv[0].trim()] = fv[1].trim();
                                }
                            }
                            X.Ajax.openInstance[openId].callFunc(header);
                            return;
                        }
                        if(X.Ajax.openInstance[openId].method == 'TRACE') {
                            X.Ajax.openInstance[openId].callFunc(
                                    X.Ajax.openInstance[openId].XMLHttp.getAllResponseHeaders(),
                                    X.Ajax.openInstance[openId].XMLHttp.responseText);
                            return;
                        }
                        if(X.Ajax.openInstance[openId].XMLHttp.status == 200) {
                            switch(X.Ajax.dataType.toLowerCase()) {
                                case 'xml':
                                    var reData = X.Ajax.openInstance[openId].XMLHttp.responseXML;
                                break;
                                case 'json':
                                    var reData = X.Ajax.openInstance[openId].XMLHttp.responseText;
                                    if(reData != '') {
                                        try{ var reData = JSON.parse(reData); }
                                        catch(e) {
                                            console.warn('Ajax JSON Parse Error: '+e + ' in File '+e.fileName + ' Line '+e.lineNumber);
                                            return;
                                        }
                                    }
                                break;
                                default:
                                    var reData = X.Ajax.openInstance[openId].XMLHttp.responseText;
                                break;
                            }
                            if(X.Ajax.openInstance[openId].callFunc) {
                                X.Ajax.openInstance[openId].callFunc(reData); 
                                /*try { X.Ajax.openInstance[openId].callFunc(reData); 
                                } catch(e) {
                                console.warn('Callback Function Error:'+e.message + ' in File '+e.fileName+' line '+e.lineNumber);
                                }*/
                            }
                        } else {
                            console.warn('Ajax requset timeout');
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
        /**
         * 创建一个元素滚动控件 
         *
         * @data    : JSON    轮换元素数据 {'label' : string  该轮换项标签
         *                                  'link'  : string  链接URL
         *                                  'img'   : string  轮换项图片地址
         *                                  }
         * @obj     : ELEMENT_NODE 本控件摆放位置元素
         * @type    : int     1为数字列表点击切换,2前进后退切换,3,文字切换,4缩略图列表点击切换
         * @eff     : int     轮换效果, 1为渐变轮换,2滑动切换
         * @cls     : string  控件内元素样式名前缀, 实际元素会加上以下名字:
         *                                  CarouselMainBox : 控件样式
         *                                  CarouselListDiv : 展示元素清单样式
         *                                  CarouselPreDiv  : 上一个按钮样式
         *                                  CarouselNextDiv : 下一个按钮样式
         *                                  CarouselCurrentSelect : 当前选中元素指示样式
         * @waitTime: int     滚动间隔时间
         *
         * @return ELEMENT_NODE 返回控件元素对象
         */
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
                var i = X.getEventNode(e).getAttribute('rol');
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
            mainDiv.addClass(cls+'CarouselMainBox');
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
                preDiv.addClass(cls+'CarouselPreDiv');
                preDiv.addListener('click',preItem);
                boxDiv.appendChild(preDiv);
                boxDiv.appendChild(mainDiv);
                var nextDiv = boxDiv.copyNode(true);
                nextDiv.addClass(cls+'CarouselNextDiv');
                nextDiv.addListener('click',nextItem);
                boxDiv.appendChild(nextDiv);
            } else {
                boxDiv.appendChild(mainDiv);
                var listDiv = X.createNode('div');
                var span = X.createNode('span');
                listDiv.addClass(cls+'CarouselListDiv');
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
                listDiv.getFirstNode().addClass(cls+'CarouselCurrentSelect');
                listDiv.addListener('mouseout',startCarousel);
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
         *  @msg : string       信息内容
         *  @cls : string       信息提示控件样式
         *  @zIndex : int       信息提示控件 z-index 值
         *  @waitTime : int     默认3000ms,信息提示控件自动超时隐藏毫秒时间
         *
         *  @return box : ELEMENT_NODE   返回控件所在DIV对象
         */
        msgBox : function(msg,cls,zIndex,waitTime) {
            var box = X.createNode('div');
            box.innerHTML = msg;
            if(cls) box.addClass(cls);
            if(zIndex) box.setZIndex(zIndex);
            box.setStyle('position','absolute');
            X.doc.body.appendChild(box);
            box.toCenter();
            var waitTime = waitTime || 3000;
            X.setTimeout(function(){box.destroy();},waitTime);
            box.setOnTop();
            return box;
        },
        /**
         * 创建一个拥有确定按钮的信息提示控件
         *
         * @tit : string        控件标题信息
         * @msg : string        控件提示信息
         * @func  : function    确定按钮后执行的操作 
         *                          回调函数原型样式:
         *                              callbackFunciton(event, button);
         *                                  event  : EventObject   点击事件
         *                                  button : boolean       等于true
         * @cls : string        控件内元素样式名前缀,内部实际会跟随以下名字:
         *                          TitleDiv  : 标题栏样式
         *                          MainDiv   : 控件中间主题部分样式
         *                          ButtonDiv : 按钮所在元素样式
         * @cover : boolean     是否显示cover层,默认不显示，true为显示
         * @zIndex : int        控件 z-index 值,如果没有设置将为当前页面最上面
         *
         * @return box : ELEMENT_NODE   返回控件所在DIV对象
         */
        alertBox : function(tit,msg,func, cls,cover,zIndex) {
            return X.confirmBoxProto(1,tit,msg,func,cls,cover,zIndex);
        },
        /**
         * 创建一个拥有确定与取消按钮的信息提示控件 
         *
         * @tit  : string       控件标题
         * @msg  : string       控件提示信息
         * @func : function     控件点击确定与取消后调用函数, 
         *                          回调函数原型样式:
         *                              callbackFunciton(event, button);
         *                                  event  : EventObject   点击事件
         *                                  button : boolean  点击确认按钮为true
         *                                                    否则为 false
         * @cls  : string       控件内元素样式名前缀,内部实际会跟随以下名字:
         *                          TitleDiv  : 标题栏样式
         *                          MainDiv   : 控件中间主题部分样式
         *                          ButtonDiv : 按钮所在元素样式
         * @cover  : bloolean   是否显示cover层,默认不显示,true为显示
         * @zIndex : int        控件 z-index 值,如果没有设置将为当前页面最上面
         *
         *  @return box : ELEMENT_NODE   返回控件所在DIV对象
         */
        confirmBox : function(tit,msg, func,cls,cover,zIndex) {
            return X.confirmBoxProto(2,tit,msg,func,cls,cover,zIndex);
        },
        /**
         * 本函数为上面两个控件原型 
         */
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
                box.addClass(cls);
                title.addClass(cls+'TitleDiv');
                msgDiv.addClass(cls+'MainDiv');
                button.addClass(cls+'ButtonDiv');
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
        /**
         * 创建一个具有表单功能的控件  
         *
         * @tit : string        控件标题信息
         * @msg : string        默认提示信息
         * @inputList : JSON    表单内input元素清单,select元素将会使用selectDiv控件替代
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
         * @buttonList : JSON   按钮清单，这里的按钮不是button类型input标签
         *                          单个按钮元素数据为:
         *                              {'label' : string   按钮显示名字,innerHTML值
         *                               'value' : string   按钮值,attributes属性
         *                               'cls'   : string   按钮样式
         *                               'call'  : string/function   按钮点击回调函数名
         *                               'url'   : string   表单提交URL
         *                              }
         *                          由上面的数据组成JSON数组
         * 
         * @cls  : string       控件内元素样式名前缀,内部实际会跟随以下名字:
         *                          TitleDiv  : 标题栏样式
         *                          MainDiv   : 控件中间主题部分样式
         *                          ButtonDiv : 按钮所在元素样式
         *                          MsgDiv    : 提示信息样式名
         *                          CloseDiv  : 关闭按钮样式名
         * @cover : boolean     是否显示cover层，true为显示，默认不显示
         * @zIndex  : int       控件z-index 值，默认在页面最上面
         *
         *
         * @return box : ELEMENT_NODE   返回控件所在DIV对象
         *
         * 外部可调用方法:
         * @box.iHide()       销毁控件
         * @box.msg(msg, cls, visibility)     显示提示信息
         *              msg        : string   提示信息内容
         *              cls        : string   提示信息样式名 
         *              visibility : boolean  隐藏后是否保留提示信息位置 
         *
         * @box.submitInput(url, func, validFunc) 提交表单
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
         *                              @return boolean 返回false将阻止表单提交,true提交表单
         */
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
                box.addClass(cls);
                titleDiv.addClass(cls+'TitleDiv');
                msgDiv.addClass(cls+'MsgDiv');
                mainDiv.addClass(cls+'MainDiv');
                buttonDiv.addClass(cls+'ButtonDiv');
                closeDiv.addClass(cls+'CloseDiv');
            } else {
                closeDiv.innerHTML = 'X';
                closeDiv.setStyle('float','right');
            }
            if(zIndex) box.setZIndex(zIndex);
            box.iHide = function() {
                if(cover) X.hiddenPageCover();
                box.destroy();
            };
            closeDiv.addListener('click',box.hide);
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
                } else if(inputList[i].type == 'select') {
                    inputItem = X.selectDiv(inputList[i].value, inputList[i].name,
                                            '','',inputList[i].cls);
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
                        inputDiv.addClass(inputList[i].cls+'ItemDiv');
                        inputLabel.addClass(inputList[i].cls+'ItemLabel');
                    }
                } else {
                    inputDiv = inputItem;
                }
                if(inputList[i].type != 'select' && inputList[i].cls) {
                    inputItem.addClass(inputList[i].cls);
                }
                mainDiv.appendChild(inputDiv);
            }
            box.appendChild(mainDiv);
            box.submitInput = function(url, func, validFunc) {
                var data = X.getFormInputData(box);
                if(validFunc) {
                    var objData = JSON.parse(data.data);
                    if(validFunc(objData, box) == false) return;
                }
                X.Ajax.post(url,data,func);
            };
            box.msg =function(message, cls, visibility) {
                msgDiv.show(visibility);
                msgDiv.innerHTML = message;
                if(cls) msgDiv.addClass(cls);
                setTimeout(function() {
                    msgDiv.hide(visibility);
                },2000);
            }
            for(var j in buttonList) {
                if(isNaN(j)) continue;
                var bi = button.copyNode(true);
                bi.addClass(buttonList[j].cls);
                bi.innerHTML = buttonList[j].label;
                bi.setAttribute('value',buttonList[j].value);
                if(typeof buttonList[j].call == 'string') eval('var call_func = '+buttonList[j].call)
                else call_func = buttonList[j].call;
                bi.addListener('click',call_func);
                bi.box = box;
                if(buttonList[j].url) bi.url = buttonList[j].url;
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
            return box;
        },
        /**
         * 创建一个下来列表控件 
         *
         * @optionList  : JSON      列表数据
         *                      单个选项所需要的数据:
         *                          {'label'    : string  选项显示名
         *                           'value'    : mixed   选项值
         *                           'disabled' : boolean true时该项不可选，默认可选
         *                           }
         * @name        : 控件在表单内的name值 
         * @func        : function  更换选择项后回调函数,可选
         *                              回调函数原型样式：callbackFunciton(value)
         *                                  @value  : mixed  选择的值
         * @def         : JSON      默认项数据, 数据样式与optionList单项一样,可选
         * @cls         : string    控件内元素样式名前缀,内部实际会跟随以下名字:
         *                              DefDiv    : 当前显示项外层样式 
         *                              DefOption : 当前显示项样式
         *                              SelectOptionDiv : 下拉列表层样式
         *                              Selected  : 下拉列表中选中项样式
         *                              OptionDisable : 不可选项样式 
         *                              OptionMouseOver : 鼠标移动到选项上时样式
         * 
         * @return box ELEMENT_NODE 返回控件元素对象 
         */
        selectDiv : function(optionList,name,func,def,cls) {
            var box = X.createNode('div');
            var defDiv = box.copyNode(true);
            var defOption = box.copyNode(true);
            var listDiv = box.copyNode(true);
            var arrow = box.copyNode('div');
            var boxInput = X.createNode('input');
            boxInput.type = 'hidden';
            boxInput.name = name;
            boxInput.value = def.value;
            box.appendChild(boxInput);
            box.addClass(cls);
            box.selected = null;
            box.defDiv = defDiv;
            defDiv.addClass(cls+'DefDiv');
            defOption.addClass(cls+'DefOption');
            listDiv.addClass(cls+'SelectOptionDiv');
            listDiv.setCss('position:absolute;z-index:10;max-height:200px;overflow:auto;');
            arrow.setCss('border-color:#000 transparent transparent;border-style:solid dashed dashed;border-width:6px 5px 0;height:0;width:0;cursor:pointer;float:left;');
            defDiv.addListener('click',function(e) {
                if(listDiv.getStyle('display') == 'block') {
                    return listDiv.hide();
                }
                listDiv.style.display = 'block';
                var pos = defDiv.getPos();
                listDiv.setStyle('left',pos.x+'px');
                var topY = pos.y+pos.h;
                listDiv.setStyle('top', topY+'px');
            });
            defDiv.appendChild(defOption);
            defDiv.appendChild(arrow);
            box.appendChild(defDiv);
            var span = X.createNode('div');
            span.setStyle('display','block');
            for(var i in optionList) {
                if(!isNaN(i)) {
                    var op = span.copyNode(true);
                    op.setAttribute('value',optionList[i].value);
                    op.setAttribute('rol','option');
                    op.innerHTML = optionList[i].label;
                    if(def && optionList[i].value == def.value && optionList[i].label == def.label) {
                        op.addClass(cls+'Selected');
                        box.selected = op;
                    }
                    if(optionList[i].disabled) {
                        op.addClass(cls+'OptionDisable');
                        op.setAttribute('disabled',true);
                    } else {
                        op.setStyle('cursor','pointer');
                    }
                    listDiv.appendChild(op);
                }
            }
            listDiv.addListener('click',function(e){
                var op = X.getEventNode(e);
                if(op.getAttribute('rol') != 'option') return;
                var value = op.getAttribute('value');
                if(op.getAttribute('disabled')) return;
                var label = op.innerHTML;
                op.addClass(cls+'Selected');
                if(box.selected) box.selected.removeClass(cls+'Selected');
                box.selected = op;
                defOption.setAttribute('value',value);
                boxInput.value = value;
                defOption.innerHTML = label;
                listDiv.style.display = 'none';
                if(func) func(value);
            });
            listDiv.addListener('mouseover',function(e) {
                var op = X.getEventNode(e);
                if(op.getAttribute('disabled')) return;
                if(op.getAttribute('rol') == 'option') op.addClass(cls+'OptionMouseOver');
            });
            listDiv.addListener('mouseout',function(e) {
                var op = X.getEventNode(e);
                if(op.getAttribute('disabled')) return;
                if(op.getAttribute('rol') == 'option') op.removeClass(cls+'OptionMouseOver');
            });
            listDiv.hide();
            box.appendChild(listDiv);
            if(def) {
                defOption.setAttribute('value',def.value);
                defOption.innerHTML = def.label;
            }
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
        
        /**
         * 获取当前触发事件所在元素对象
         *
         * @e  : EventObject  当前触发事件对象
         */
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
        /**
         * 获取当前浏览器可视区域尺寸 
         *
         * @return JSON  {h : int 高度
         *                w : int 宽度
         *               }
         */
        pageShowSize : function() {
            var h = navigator.IE ? window.screen.availHeight : document.documentElement.clientHeight;
            var w = navigator.IE ? window.screen.availWidth : document.documentElement.clientWidth;
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
            if(e.preventDefault) return e.preventDefault();
            else e.returnValue = false;
        },

        /**
         * 获取当前鼠标事件时，鼠标所在坐标
         *
         * @e  : EventObject  当前触发事件对象
         *
         * @return JSON  鼠标坐标 { x : int   X坐标值
         *                          y : int   Y坐标值
         *                        }
         */
        mousePos : function(e) {
            e = typeof event == 'undefined' ? e : event;
            return {x:e.clientX,y:e.clientY};
        },
        /**
         * 获取兼容性透明度设置样式 
         *
         * @num 
         *
         * @return  string  返回样式字符串
         */
        getOpacityStr : function(num) {
            num = navigator.IE ? num : num/100;
            return navigator.IE ? "filter:alpha(opacity="+num+");" : 'opacity:'+num;
        },
        /**
         * 页面cover对象 
         */
        pageCover : null,
        /**
         * 显示cover 元素  
         */
        showPageCover : function() {
            var viewSize = X.pageShowSize();
            var height = X.doc.body.offsetHeight > viewSize.h ? X.doc.body.offsetHeight+15 : viewSize.h;
            var width = X.doc.body.offsetWidth > viewSize.w ? X.doc.body.offsetWidth+15 : viewSize.w;
            if(X.pageCover != null) {
                X.pageCover.style.display = 'block';
                X.pageCover.style.height = height+'px';
                X.pageCover.style.width = width +'px';
                return;
            }
            var alpha = X.getOpacityStr(20);
            X.pageCover = X.createNode('div');
            X.pageCover.setCss('position:absolute;top:0;left:0;padding:0;margin:0;background-color:#000;'+alpha);
            X.pageCover.setOnTop();
            X.doc.body.appendChild(X.pageCover);
            X.pageCover.style.display = 'block';
            X.pageCover.style.height = height+'px';
            X.pageCover.style.width = width +'px';
        },
        hiddenPageCover : function() {
            X.pageCover.style.display = 'none';
        },

        /**
         * 获取指定元素对象所包含的所有input或相关表单数据 
         *  
         * @frm  :  ELEMENT_NODE   指定需要获取的表单对象
         * @disable_no_name : boolean  是否屏蔽没有name值的表单,默认屏蔽 
         *
         * @return JSON   返回一个JSON对象，格式为{ data : formData}
         *                                         formData为一个JSON格式字符串
         */
        getFormInputData : function(frm, disable_no_name) {
            if(!frm.getSubNodeByTag) frm = X.$(frm);
            if(!disable_no_name) disable_no_name = true;
            var inputList = frm.getSubNodeByTag('input');
            var formData = {};
            for(var i in inputList) {
                if(isNaN(i)) continue;
                var inputEelement = inputList[i];
                var eleType = inputEelement.inputType;
                if(inputEelement.style.display == 'none') continue;
                var key = inputEelement.getAttribute('name');
                if(disable_no_name && !key) continue;
                if((eleType == X.inputType.INPUT_CHECKBOX || eleType == X.inputType.INPUT_RADIO) && inputEelement.checked != true) continue;
                var value = inputEelement.value;
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
                var key = select.getAttribute('name');
                if(select.style.display == 'none') continue;
                if(disable_no_name && !key) continue;
                formData[key] = select.value;
            }
            var textareaList = frm.getSubNodeByTag('textarea');
            for(var i in textareaList) {
                if(isNaN(i)) continue;
                var textarea = textareaList[i];
                var key = textarea.getAttribute('name');
                if(textarea.style.display == 'none') continue;
                if(disable_no_name && !key) continue;
                formData[key] = textarea.value;
            }
            formData = JSON.stringify(formData);
            return {data:formData};
        },
        /**
         * 自动提交表单,本方法只能提交form标签表单 
         *
         * @ele      : ELEMENT_NODE  form标签下的子元素
         * @callFunc : function      AJAX提交后返回回调函数
         *                            原型:callbackFunciton(returnData);
         *                                  @returnData : AJAX返回数据
         * @validFunc: function      表单数据检测回调函数
         *                            原型:callbackFunciton(objData,formObj)
         *                                  @objData : JSON 表单数据,name为键值 
         *                                  @formObj : ELEMENT_NODE 表单对象
         *
         */
        submitForm : function(ele,callFunc, validFunc) {
            if(!ele.getParentNodeByTag) ele = X.$(ele);
            if(ele.tag == 'form') {
                var formObj = ele;
            } else {
                var formObj = ele.getParentNodeByTag('form');
            }
            if(!formObj || formObj.tag != 'form') {
                console.warn('form element not exists');
                return;
            }
            var formAction = formObj.getAttribute('action');
            var formMethod = formObj.getAttribute('method');
            var data = X.getFormInputData(formObj);
            if(validFunc) {
                var objData = JSON.parse(data.data);
                if(validFunc(objData, formObj) == false) return;
            }
            if(formMethod.toLowerCase() == 'post') {
                X.Ajax.post(formAction,data,callFunc);
            } else {
                X.Ajax.setData(data);
                X.Ajax.get(formAction,callFunc);
            }
        },
        AjaxDebugMessageDiv : null,
        debugInnerHTML : function(html) {
            if(!X.AjaxDebugMessageDiv)  X.AjaxDebugMessageDiv = X.createNode('div');
            var m = '<h2>Ajax Return Server Debug Message</h2>';
            for(var i in html) m+=html[i];
            X.AjaxDebugMessageDiv.innerHTML = m;
            document.body.appendChild(X.AjaxDebugMessageDiv);
        },
        drawRect : function(x,y,w,h,color) {
            var canvas = X.createNode('canvas');
            var ctx = canvas.getContext('2d');
            ctx.fillStyle = color;
            ctx.fillRect(x,y,w,h);
            return canvas;
        },

        /**
         * 创建基于canvas 元素的趋势图表 
         */
        drawLineTrends : function(style,initData, padding) {
            var canvas = X.createNode('canvas');
            canvas.setAttribute('width',style.w+'px');
            canvas.setAttribute('height',style.h+'px');
            canvas.popShow = false;
            canvas.popDiv = null;
            if(!canvas.getContext) {
                console.warn('Your browser not support canvas');
                return false;
            }
            var ctx = canvas.getContext('2d');
            padding  = padding ? padding : 20;
            //X Y 轴标尺点数
            var ySectionCount = (style.y.max + style.y.min)/style.y.step;
            var xSectionCount = (style.x.max + style.x.max)/style.x.step;
            //X Y 轴长度 
            var yHSize = style.h - padding * 2;
            var xWSize = style.w - padding * 2;
            //X Y 轴每块长度
            var ySectionSize = yHSize/ySectionCount;
            var xSectionSize = xWSize/xSectionCount;
            //X Y 每单位长度
            var yDot = ySectionSize/style.y.step;
            var xDot = xSectionSize/style.x.step;
            var yLabel = style.y.label + '('+style.y.unit+')';
            var xLabel = style.x.label+'('+style.x.unit+')';
           //Y 轴X 坐标
            var yXStart = padding + style.x.min/style.x.step * xSectionSize;
            var xXEnd = xWSize+padding; //X 轴X 结束点
            //X Y 轴 Y轴结束坐标
            var yYEnd = yHSize + padding; //Y 轴 Y方向结束点
            //X 轴Y坐标
            var xYEnd = yHSize + padding - style.y.min/style.y.step * ySectionSize;
            //标准数据坐标轴
            var st = style.x.stantard ? 'x' : 'y';
            var trendLinePointer = Array();
            //create Trend
            var createTrend = function(x,y,st) {
                trendLinePointer = [];
                for(var k in initData) {
                    if(isNaN(k)) continue;
                    var prec = initData[k];
                    ctx.beginPath();
                    ctx.strokeStyle = prec.color;
                    ctx.lineWidth = prec.w;
                    var precData = prec.data;
                    trendLinePointer[k] = [];
                    var yPos = null;
                    var xPos = null;
                    if(st == 'x') {
                        yPos = y - precData[0] * yDot;
                        ctx.moveTo(x , yPos);
                        trendLinePointer[k].push([x,yPos]);
                    } else {
                        xPos = x+precData[0]*xDot;
                        trendLinePointer[k].push([xPos,y]);
                        ctx.moveTo(xPos ,y);
                    }
                    for(var j in precData) {
                        if(j ==0) continue;
                        if(st == 'x') {
                            xPos = x + j * xSectionSize + xSectionSize;
                            yPos = y - precData[j] * yDot;
                            ctx.lineTo(xPos, yPos);
                        } else {
                            yPos = y - j  * ySectionSize - ySectionSize;
                            xPos = x + precData[j] * xDot;
                            ctx.lineTo(xPos,yPos);
                        }
                        trendLinePointer[k].push([xPos,yPos]);
                    }
                    ctx.stroke();
                }
            };
            var initCoord = function() {
                //Y
                ctx.font = style.y.labelFont;
                ctx.fillStyle = style.y.labelColor;
                ctx.fillText(yLabel,0,padding);
                //X
                ctx.font = style.x.labelFont;
                ctx.fillStyle = style.x.labelColor;
                var xLabelTextMea = ctx.measureText(xLabel);
                var xLabelTextWidth = xLabelTextMea.width;
                ctx.fillText(xLabel,style.w - xLabelTextWidth - padding,yHSize+padding);
                //Y line
                ctx.beginPath();
                ctx.moveTo(yXStart ,padding);
                ctx.strokeStyle = style.y.color;
                ctx.lineWidth = style.y.w;
                ctx.lineTo(yXStart ,yYEnd);
                ctx.moveTo(yXStart - 5,padding + 10);
                ctx.lineTo(yXStart, padding);
                ctx.lineTo(yXStart + 5,padding +10);
                for(var i=1;i<ySectionCount;i++) {
                    var yPos = padding + ySectionSize * i;
                    ctx.moveTo(yXStart, yPos);
                    ctx.lineTo(yXStart+3, yPos);
                }
                ctx.stroke();
                //X line
                ctx.beginPath();
                ctx.moveTo(padding,xYEnd);
                ctx.strokeStyle = style.x.color;
                ctx.lineWidth = style.x.w;
                ctx.lineTo(xXEnd, xYEnd);
                ctx.moveTo(xXEnd-10,xYEnd-5);
                ctx.lineTo(xXEnd, xYEnd);
                ctx.lineTo(xXEnd-10,xYEnd+5);
                for(var i=1;i<xSectionCount;i++) {
                    var xPos = padding+ xSectionSize * i;
                    ctx.moveTo(xPos,xYEnd);
                    ctx.lineTo(xPos,xYEnd -3);
                }
                ctx.stroke();
                createTrend(padding,xYEnd,st);
            };
            var searchNear = function(arr, na) {
                for(var k in arr) {
                    if(arr[k][0] + 5 > na[0] && arr[k][0] - 5 < na[0] &&
                        arr[k][1] +5>na[1] && arr[k][1] -5 <na[1]) {
                        return k;
                    }
                }
                return -1;
            };
            var showPointerPopInfoDiv = function(x,y) {
                if(canvas.popDiv) {
                    canvas.popDiv.style.display = 'block';
                } else {
                    canvas.popDiv = X.createNode('div');
                    if(style.popClass) canvas.popDiv.addClass(style.popClass);
                    X.doc.body.appendChild(canvas.popDiv);
                    canvas.popDiv.style.display = 'block';
                }
                canvas.popDiv.innerHTML = x +','+y;
            };
            var showPointerPopInfo = function(e) {
                var mPos = X.mousePos(e);
                var nPos = this.getPos();
                var scroll = X.scrollOffset();
                var inCtxPos = [mPos.x - nPos.x + scroll.x,mPos.y - nPos.y+scroll.y];
                for(var t in trendLinePointer) {
                    var idx = searchNear(trendLinePointer[t],inCtxPos);
                    if(idx != -1) {
                        break;
                    }
                }
                if(idx != -1) {
                    if(st == 'x') {
                        var xData = idx * style.x.step;
                        var yData = initData[t].data[idx];
                    } else {
                        var yData = idx * style.x.step;
                        var xData = initData[t].data[idx];
                    }
                    canvas.popShow = true;
                    showPointerPopInfoDiv(xData,yData);
                    canvas.popDiv.mousePopNearX = 10;
                    canvas.popDiv.mousePopNearY = -15;
                    canvas.popDiv.mousePop(e);
                } else {
                    if(canvas.popShow == true) {
                        canvas.popShow = false;
                        canvas.popDiv.style.display = 'none';
                    }
                }
            };
            var clearPointerPopInfo = function(e) {
                if(canvas.popDiv) {
                    canvas.popDiv.style.display = 'none';
                }
                canvas.popShow = false;
            };
            canvas.addPoint = function(addData) {
                ctx.save();
                ctx.clearRect(0,0,style.w,style.h);
                for(var k in addData) {
                    for(var j in addData[k]) {
                        initData[k].data.push(addData[k][j]);
                        var len = st == 'x' ? xSectionCount : ySectionCount;
                        if(initData[k].data.length>len) {
                            initData[k].data.shift();
                        }
                    }
                }
                initCoord();
                ctx.restore();
            };
            canvas.addListener('mousemove',showPointerPopInfo);
            canvas.addListener('mouseout',clearPointerPopInfo);
            initCoord();
            return canvas;
        },

        /**
         * 设置cookie值 
         *
         * @cn  : string  一个cookie name值
         * @v   : string  一个cookie value值
         * @ex  : int     cookie有效期
         */
        setCookie : function(cn, v, ex) {
            var e= new Date(),n = e.getTime();
            ex = n + ex *1000;
            e.setTime(ex);
            var cv=escape(v) + "; exs="+e.toUTCString();
            X.doc.cookie=cn + "=" + cv;
        },
        /**
         * 获取一个cookie 值 
         * 
         * @cn : string  cookie name值
         * 
         * @return : string  返回cookie value 值,没有将返回null
         */
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
        version : 0.5
};
window.onbeforeunload = X.unloadExec;
}
