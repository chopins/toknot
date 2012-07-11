#使用本库前需建议调用的方法    
X.ready() 此方法将初始化键盘事件,Body对象点击事件,如果浏览器不支持JSON对象,将加载JSON对象   


#A.实用方法    
  * 1.创建对象 X.createNode('tagName') 返回一个对象  
  * 2.取消当前事件的原始处理方法 X.delDefaultEvent(e);  
  * 3.当前事件触发的鼠标位置 X.mousePos(e);  
  * 4.增加scroll事件,当滑块滚动时触发,回调函数中的this为被注册对象  
  * 5.获取当前时间戳 X.time();  
  * 6.获取透明度设置样式字符串 X.getOpacityStr(num) num为透明度,0-100,从透明到不透明  
  * 7.设置cookie X.setCookie(c_name,value,expire) c_name为cookie名,value值,expire为有效期,当前时间为起点后的时间过期,单位秒  
  * 8.获取cookie X.getCookie(c_name) c_name为cookie名  
  * 9.注册键盘事件 X.addKeyListener(key,func) key为按键编号, func为触发执行函数,一个按键只能注册一个函数,运行本函数前需要执行X.ready();  
  * 10.删除键盘事件 X.delKeyListener(key) key为注册键盘事件时的按键编号  
  * 12.获取当前鼠标当前选择文本 X.getCursorSelectText() 获取当前鼠标选择的所有文本信息  
  
--------------------------------------------------------------  
#B.节点对象访问方法:  
  * 1.节点ID访问 X.$('ID_value') 返回一个对象  
  * 2.节点name属性 X.$('%name_value') 返回一个数组,数组内的对象的name属性值为指定值  
  * 3.节点样式 X.$('#className_value') 返回一个数组  
  * 4.节点标签名 X.$('@tagName_value') 返回一个数组  
  * 5.节点对象, X.$(obj) 返回扩展后的该对象  
  
----------------------------------------------------------------  
#C.对象操作函数:  
首先对象必须按上A中方法获取:Obj  
  * 1.使对象可移动 Obj.move(down,spec), down 是当鼠标按下的对象,此时可移动,未设置时是Obj, spec是 Obj只能在该对象范围内移动,未设置是不限制  
  * 2.使对象可改变大小 Obj.resize(spec,sens) spec 是Obj只能改变到该对象大小,spens 鼠标提示可改变大小的灵敏度,默认10px  
  * 3.最大化对象 Obj.maxsize(spec,part) spec 是当点击该对象触发,未设置时是Obj, part是最大化该对象的范围,未设置时是body  
  * 4.让对象居中 Obj.toCenter(eff,spec) eff是效果,1是滑动,否则无, spec是在某对象的居中,无是body  
  * 5.对象标签名 Obj.tag 该值为小写标签名  
  * 6.返回Input,select,textarea标签类型 Obj.getInputType() 非input.select,textarea标签将返回false,否则返回X.inputType定义的值  
  * 7.返回对象绝对坐标及宽高 Obj.getPos() 这是一个对象,格式如{x:NUM,y:NUM,h:NUM,w:NUM}  
  * 8.返回对象的子对象为指定class的对象列表 Obj.getNodeByCls(cls)  
  * 9.返回对象第一个非文本对象 Obj.getFirstNode()  
  * 10.返回对象最后一个非文本对象 Obj.getLastNode()  
  * 11.返回当前对象的为指定标签的第一个上级对象 Obj.getParentNodeByTag(tagNmae) tagName为需要找的标签名  
  * 12.返回当前对象的为指定标签的子对象列表 Obj.getSubNodeByTag(tagName) tagName为需要找的标签名,返回一个数组,深度获取  
  * 13.对象是否有指定样式名 Obj.hasClass(cls) cls为样式名  
  * 14.移除对象的指定样式 Obj.removeClass(cls) cls为需要移除的样式名  
  * 15.给对象添加样式 Obj.setClass(cls) cls为需要添加的样式名  
  * 16.批量增加样式属性 Obj.setCss(str) str为属性CSS字符串  
  * 17.获取一个样式属性值 Obj.getStyle(ns) ns为属性名  
  * 18.添加一个样式属性 Obj.setStyle(ns,value) ns为属性名,value属性值  
  * 19.当前对象的下一个非文本对象 Obj.nextNode()  
  * 20.当前对象的上一个非文本对象 Obj.previousNode()  
  * 21.给对象注册一个事件 Obj.addListener(e,call_function) e 为事件类型,call_function为事件触发函数  
  * 22.删除一个已经注册的事件 Obj.delListener(e,call_funcation)  
  * 23.获取对象的所有子对象,Obj.getChilds()  
  * 24.隐藏对象 Obj.hide()  
  * 25.销毁对象 Obj.destroy()  
  * 26.关闭对象 Obj.close(spec) spec为点击该对象后关闭Obj,如果未设置,将在点击Obj后关闭  
  * 27.拷贝读西 Obj.copyNode(deep) deep是否深度拷贝  
  * 28.获取iframe内的body对象 Obj.getIframeBody()  
  * 29.当前节点是否属于另一个节点的子节点 Obj.isNodeChild(parent) Obj是否是parent的子节点  
  * 30.获取光标偏移量 Obj.getCursorOffset() Obj必须为可输入对象,返回相对与指定对象首个文本字符光标的偏移字符数  
  * 31.设置光标偏移量 Obj.setCursorOffset(offset,start) Obj必须为指定可输入对象, offset为偏移字符数,、  
start为文本首字符计算的开始处,如果没有默认为当前光标处,如果光标不在当前对象,将从首字符开始,成功返回true,否则false  
  * 32.选择指定长度文本 Obj.cursorSelectText(len,start) Obj为指定对象,len为选择的字符长度,  
start为文本首字符开始处,如果没有设置默认为当前光标位置,但是当光标不在指定对象将从当前节点的首个文本字符开始返回选择文本  

----------------------------------------------------------------------------------------------------  


#D.Ajax请求,兼容性考虑,执行Ajax操作前需要执行X.ready();  
  * 1.get请求 X.Ajax.get(url, call_func) url为请求url, call_func为回调函数  
  * 2.post请求 X.Ajax.post(url,data,func) url为请求url, data为post数据,格式为JSON数据, func为回调函数  
  * 3.上传文件 X.Ajax.file(form,func) form为提交表单,func为回调函数  
 
---------------------------------------------------------------------------------------------------------------------  

#E.控件  
  * 1.普通消息提示 X.msgBox(msg, cls, zIndex, waitTime) msg提示信息文字, cls控件样式, zIndex控件z-index属性值, waitTome显示时间,返回该控件DOM对象  
  * 2.确定提示消息组件 X.alertBox(tit, msg,func,cls,cover,zIndex) tit提示控件标题栏文字,msg提示信息文字,返回该控件DOM对象  
func确定后回调函数,会传两个值第一个点击事件,第二个为布尔true  
cls控件样式,该样式引伸出以下样式: cls+'TitleDiv'(定义标题栏样式),cls+'MainDiv'(主题部分样式),cls+'ButtonDiv'(按钮所在div样式)  
cover是否显示遮照,zIndex 控件z-index属性值,  
  * 3.确认提示消息控件 X.confirmBox(tit,msg,func,cls,cover,zIndex) 参数及样式同2, func回调函数会传两个参数,第一个数点击事件,第二个是确定(true)或取消(false)  
返回该控件对象  
  * 4.输入性表单控件 X.inputBox(tit,msg,inputList,buttonList,cls,cover,zIndex) tit为该控件标题文字,msg为该控件提示文字,返回该控件对象  
inputList为input节点参数表,一个JSON对象,包括以下属性  
label(标签文字),type(input类型名),name(input的name属性值),value(input的默认value值),cls(input的样式名)  
buttonList为该控件上的按钮列表,一个JSON对象,包括以下属性  
label(按钮文字),cls(该按钮的样式),value(该按钮的值), call(该按钮点击后触发的事件)  
  * 5.下拉框控件 X.selectDiv(optionList,func,cls) optionList为option项清单,JSON对象,包括以下属性:value(项值),label(项目显示文字),disabled=true(该项不可选)  
func属性改变时调用回调函数,将会传入当前选中项值,cls为样式名,引申样式cls+'OptionSelected',标识已选择项目,鼠标滑动样式cls+'OptionMouseOver',  
cls+'DefOption'默认选择框样式,cls+'DefDiv'控件默认显示部分样式,cls+'SelectOptionDiv'  
  * 6.轮播控件 X.carousel(data,obj,type,eff,cls,waitTime) type为类型(1为数字列表点击切换,2前进后退切换,3,文字切换,4缩略图列表点击切换) obj轮播所处对象,  
cls为样式根据不同情况引申出cls+'CarouselMainBox'主体部分样式,cls+'CarouselListDiv'清单部分,  
cls+'CarouselPreDiv'上一个,cls+'CarouselNextDiv'下一个,cls+'CarouselCurrentSelect'  
eff效果1为渐变切换,2滑动切换,waitTime等待时间,data为数据,JSON格式,每项包括label:图片说明,link链接,img图片地址  
  
