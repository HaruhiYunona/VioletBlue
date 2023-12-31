/**
 * script.js
 * 
 * @package script.js
 * @author HaruhiYunona
 * @version 1.0.0
 * @link https://github.com/HaruhiYunona
 * 
 */


/**
 * 获取GET参数
 * @param {text} name 参数名
 */
const $_GET = (name) => {
    let reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
    let r = window.location.search.substr(1).match(reg);
    if (r != null) return unescape(r[2]);
    return null;
}



/** 
 * 设置cookie
 * @param {text} name cookie的名字
 * @param {text} value cokkie的值
 * @param {text} time cookie在本地保存的时间
 */
const setCookie = (name, value, time) => {
    let exp = new Date();
    exp.setTime(exp.getTime() + time * 1000);
    document.cookie = name + "=" + escape(value) + ";path=/;expires=" + exp.toGMTString();
}



/** 
 * 读取cookie
 * @param {text} name cookie的名字
 * @returns text
 */
const $_COOKIE = (name) => {
    let arr, reg = new RegExp("(^| )" + name + "=([^;]*)(;|$)");
    if (arr = document.cookie.match(reg))
        return unescape(arr[2]);
    else
        return null;
}



/**
 * 时间戳
 * @returns int
 */
const timeStample = () => {
    let tmp = Date.parse(new Date()).toString();
    return tmp.substr(0, 10);
}



/**
 * 日期
 * @returns text
 */
const dateTime = () => {
    let date = new Date();
    let y = date.getFullYear();
    let m = date.getMonth() + 1;
    m = m < 10 ? '0' + m : m;
    let d = date.getDate();
    d = d < 10 ? '0' + d : d;
    let h = date.getHours();
    h = h < 10 ? '0' + h : h;
    let minute = date.getMinutes();
    minute = minute < 10 ? '0' + minute : minute;
    let second = date.getSeconds();
    second = second < 10 ? '0' + second : second;
    return y + '-' + m + '-' + d + ' ' + h + ':' + minute + ':' + second;
}


/**
 * urlencode参数
 * @param {*} str 
 * @returns 
 */
const urlencode = (str) => {
    str = (str + '').toString();
    return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').
        replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+');
}




/**
 * POST请求函数 
 * @param {string} 指向接口
 * @param {array} 请求数组
 * @returns json
 */
const post = (aim, array = {}) => {
    return fetch(aim, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: queryParse(array)
    }).then((response) => response.json());
}



/**
 * 请求内容解析函数
 * @param {array} query 请求数组
 * @returns 请求字符串
 */
const queryParse = (query) => {
    let queryText = "";
    for (let key in query) {
        queryText += `${key}=${urlencode(query[key])}&`;
    }
    return queryText.slice(0, -1);
}



/**
 * 批量应用id对应的的innerHTML
 * @param {array} id DOM id
 * @param {array} value 应用值
 */
const useValue = (id, value) => {
    if (id != '' && id != undefined) {
        for (let i = 0; i < id.length; i++) {
            document.getElementById(id[i]).innerHTML = (value[i] != undefined && value[i] != '') ? value[i] : '';
        }
    }
}



/**
 * 批量应用id对应的的value
 * @param {array} id DOM id
 * @param {array} value 应用值
 */
const useVar = (id, value) => {
    if (id != '' && id != undefined) {
        for (let i = 0; i < id.length; i++) {
            document.getElementById(id[i]).value = (value[i] != undefined && value[i] != '') ? value[i] : '';
        }
    }
}



/**
 * tag点击函数
 * @param {int} index DOM索引
 */
const tagClick = (index) => {
    document.getElementsByClassName('type_selected')[0].setAttribute('class', 'type');
    document.getElementsByClassName('type')[index].setAttribute('class', 'type_selected');
    flashList(document.getElementsByClassName('type_selected')[0].innerHTML, 1, true);
    document.getElementById('type').value = document.getElementsByClassName('type_selected')[0].innerHTML;
    document.getElementById('page').value = 1;
}



/**
 * 判断复制还是去编辑页
 * @param {array} song 歌曲名
 * @param {array} uid 歌曲uid
 */
const aimto = (song, uid) => {
    passWd = $_COOKIE('passWd');
    if (passWd == null || passWd == undefined) {
        copyToClipboard('点歌 ' + song + '');
    } else {
        window.opener = null;
        window.open('./admin/edit.php?uid=' + uid, '_blank');
        window.close();
    }
}



/**
 * 列表渲染
 * @param {array} response 列表数组
 */
const listView = (response) => {
    let songLength = response.content.length;
    let htmSong = '';
    let oldHtm = document.getElementById('songs_list').innerHTML;
    for (let i = 0; songLength > i; i++) {
        let typeLength = response.content[i].type.length;
        let htmType = ''
        for (let n = 0; typeLength > n; n++) {
            htmType = htmType + '<span class="info_tag">' + response.content[i].type[n] + '</span>';
        }
        htmSong = htmSong + '<li class="song_item' + ((response.content[i].notice != '') ? ' song_item_claim"' : '"') + ' onClick="aimto(\'' + response.content[i].song + '\',\'' + response.content[i].uid + '\')" id="song_' + response.content[i].uid + '"><div class="song_info">' + response.content[i].song + ' - ' + response.content[i].singer + '</div>' + ((response.content[i].notice != '') ? '<span class="claim">' + response.content[i].notice + '</span>' : '') + '<div class="info_tag_area">' + htmType + '</div></li>';
    }
    document.getElementById('songs_list').innerHTML = oldHtm + htmSong;
    document.getElementById('songs_list').removeEventListener('scroll', scrollList);
    document.getElementById('songs_list').addEventListener('scroll', scrollList);
}



/**
 * 刷新列表 
 * @param {*} type 类型
 * @param {int} page 页号
 * @param {boolean} clear 是否清空
 */
const flashList = (type, page, clear) => {
    if (clear == true) {
        document.getElementById('songs_list').innerHTML = '';
        scrollFlag = 0;
    }
    post('./api/getSongList', { tag: type, page: page }).then((response) => {
        if (response.code == 1) {
            listView(response);
            scrollFlag = 0;
        }
    });
}



/**
 * 滚动到底自动加载列表
 */
const scrollList = () => {
    if (document.getElementById('songs_list').scrollTop + document.getElementById('songs_list').clientHeight + 10 >= document.getElementById('songs_list').scrollHeight) {
        if (scrollFlag == 0) {
            let type = document.getElementById('type').value;
            let page = document.getElementById('page').value * 1 + 1;
            document.getElementById('page').setAttribute('value', page);
            scrollFlag = 1;
            flashList(type, page, false);
        }
    }
    if (document.getElementById('search').getAttribute("mark") == "mobile") {
        if (document.getElementById('songs_list').scrollTop == 0) {
            document.getElementsByClassName('info_box')[0].setAttribute('style', 'display:inline-block;');
            document.getElementsByClassName('song_list')[0].setAttribute('style', 'top:340px;height:calc(100% - 350px);');
            document.getElementById('footer').setAttribute('style', 'display:inline-block;');
        } else {
            document.getElementsByClassName('info_box')[0].setAttribute('style', 'display:none;');
            document.getElementsByClassName('song_list')[0].setAttribute('style', 'top:0px;height:100%;');
            document.getElementById('footer').setAttribute('style', 'display:none;');
        }
    }
}



/**
 * 搜索
 */
const search = () => {
    scrollFlag = 1;
    let searchContent = document.getElementById('search').value;
    let action = '';
    if (searchContent != 'undefined' && searchContent != '') {
        post('./api/search', { search: searchContent }).then((response) => {
            if (response.code == 1) {
                document.getElementById('songs_list').innerHTML = '';
                listView(response)
            }

        });
    } else {
        post('./api/random').then((response) => {
            if (response.code == 1) {
                if ((document.getElementById('search').getAttribute('mark') == "pc")) {
                    document.getElementById('songs_list').innerHTML = '';
                    listView(response)
                } else {
                    alertify.alert("随机抽取成功", "您随机到的歌曲是《" + response.content[0].song + "》-" + response.content[0].singer + ",点击OK为您自动复制到剪贴板。", function () {
                        copyToClipboard('点歌 ' + response.content[0].song);
                    });
                }
            }

        });
    }
}




/**
 * 工具按钮点击事件
 */
let tool_box_flag = 0;
const toolBox = () => {
    if (tool_box_flag == 0) {
        document.getElementById('tool_button').setAttribute("class", "tool_button rand_animation");
        document.getElementsByClassName('tool_inner_item')[0].setAttribute("style", "display:inline-block;");
        document.getElementsByClassName('tool_inner_item')[0].setAttribute("class", "tool_inner_item scale_animation");
        document.getElementById('tool_button').setAttribute("style", "transform: rotate(180deg);");
        tool_box_flag = 1;
    } else {
        document.getElementById('tool_button').setAttribute("class", "tool_button rand_animation_back");
        document.getElementsByClassName('tool_inner_item')[0].setAttribute("class", "tool_inner_item scale_animation_back");
        setTimeout(() => { document.getElementsByClassName('tool_inner_item')[0].setAttribute("style", "display:none;"); document.getElementById('tool_button').setAttribute("style", "transform: rotate(0deg);"); }, 70);
        tool_box_flag = 0;
    }
}



/**
 * 列表滚动到顶
 */
const scrollTop = () => {
    document.getElementById('songs_list').scrollTo({
        top: 0,
        behavior: "smooth",
    });
    document.getElementById('tool_button').click();
}



/**
 * 复制文本到剪贴板
 * @param {*} text 需复制文本
 */
const copyToClipboard = (text) => {
    // 检查浏览器是否支持 Clipboard API
    if (!navigator.clipboard) {
        // 如果不支持，则使用传统的 document.execCommand("copy") 方式
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.id = 'copyto'
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        document.execCommand("copy");
        document.body.removeChild(textArea);
        alertify.set('notifier', 'position', 'top-center');
        alertify.set('notifier', 'delay', 2);
        alertify.success("已 复 制 点 歌 指 令");
        return;
    }

    // 使用 Clipboard API 复制内容到剪切板
    navigator.clipboard.writeText(text).then(
        function () {
            alertify.set('notifier', 'position', 'top-center');
            alertify.set('notifier', 'delay', 2);
            alertify.success("已 复 制 点 歌 指 令");
        },
        function () {
            alertify.set('notifier', 'position', 'top-center');
            alertify.set('notifier', 'delay', 2);
            alertify.console.error("复 制 失 败");
        }
    );
}



/**
 * 显示加载条
 */
let load_flag = 0;
const loadVisable = (message) => {
    let html = '<div class="load"><div class="loader" title="2"><svg version="1.1" id="loader-1" x="0px" y="0px" width="100px" height="100px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite" /></path></svg><div class="load-msg">demo</div></div></div>';
    let parser = new DOMParser();
    let doc = parser.parseFromString(html, "text/html");
    document.getElementsByTagName('body')[0].prepend(doc.body);
    document.getElementsByClassName('load-msg')[0].innerHTML = message;
    document.getElementsByClassName('load')[0].style = 'display:inline-block';
    load_flag = 1;
}



const loadHide = () => {
    document.getElementsByClassName('load')[0].style = 'display:none';
    load_flag = 0;
}