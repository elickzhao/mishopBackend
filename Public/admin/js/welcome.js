//x轴下标数组
var dayArray = ["0:00", "1:00", "2:00", "3:00", "4:00", "5:00", "6:00", "7:00", "8:00", "9:00", "10:00", "11:00", "12:00", "13:00", "14:00", "15:00", "16:00", "17:00", "18:00", "19:00", "20:00", "21:00", "22:00", "23:00"];
var weekArray = ["星期一", "星期二", "星期三", "星期四", "星期五", "星期六", "星期日"];

option = {
    color: ['#3398DB'],
    tooltip: {
        trigger: 'axis',
        axisPointer: { // 坐标轴指示器，坐标轴触发有效
            type: 'shadow' // 默认为直线，可选为：'line' | 'shadow'
        }
    },
    grid: {
        left: '3%',
        right: 0,
        bottom: 0,
        containLabel: true
    },
    toolbox: {
        show: true,
        feature: {
            magicType: {
                type: ['bar', 'line']
            },
            restore: {},
            saveAsImage: {}
        }
    },
    xAxis: {
        type: 'category',
        nameRotate: 20,
        axisLabel: {
            interval: 0, //显示所有字段 因为有些设置只针对value类型有效
            rotate: 30 //旋转坐标值 否则排列不下  
        },
        axisTick: {
            alignWithLabel: true
        },
        data: weekArray
    },
    yAxis: {
        type: 'value',
        name: '订单统计', //坐标系名称
        nameLocation: 'center', //显示位置
        nameGap: '50', //与坐标系距离 
        //minInterval:5,            //最小间隔数
        splitNumber: 3, //坐标轴的分割段数   值针对value 这个类型有效
        axisLabel: {
            //formatter: '{value} °C'
            interval: 5,
            margin: 25,
        }
    },
    series: [{
        name: '订单数量',
        type: 'bar',
        data: [1, 5, 7, 8, 12, 6, 4],
        barMaxWidth: 80, //柱形最大宽度
        markPoint: {
            symbolSize: 55, //标注大小 可能存在的问题是三位数可能装不下
            data: [{
                type: 'max',
                name: '最大值'
            }, ]
        }
    }]
};

/*=============================================
=            获取当前月份天数                  =
=============================================*/
function mGetDate() {
    var date = new Date();
    var year = date.getFullYear();
    var month = date.getMonth() + 1;
    var d = new Date(year, month, 0);
    return d.getDate();
}
/*=====  End of 获取当前月份天数   ======*/



/*=============================================
=            月份日期数组                     =
=============================================*/
function monthArray() {
    let arr = [];
    let l = mGetDate();
    for (let index = 0; index < l; index++) {
        arr.push(index + 1 + "日");
    }
    return arr;
}
/*=====  End of 月份日期数组  ======*/



/*=============================================
=            获取图标数据类                    =
=============================================*/
function getDate(url, time) {
    var result;
    $.ajax({
        'type': 'GET',
        'async': false, //取消异步 否则result复制失败 
        'data': {
            'time': time
        },
        'url': url,
        'success': function (res) {
            // console.log(res.sql);
            // console.log(res.data);
            //result = res.data;
            result = res;
        },
        'error': function (e) {
            console.log(e);
        }
    });
    return result;
}
/*=====  End of 获取图标数据类   ======*/


/*=============================================
=            初始化数据                      =
=============================================*/
//这个不是类的写法 应该就是匿名函数
var ChartsData = function () {
    return {
        'id': "",
        'url': "",
        'myChart': {},
        'option':{},
        'yname':"",
        'tname':"",
        'init': function (id, url,yname,tname) {
            this.id = id;
            this.url = url;
            //console.log(yname+"--"+tname);
            this.option = option;
            this.yname = yname;
            this.tname = tname;
            this.myChart = echarts.init(document.getElementById(id),'walden');
            this.t();
        },
        //今天
        t: function (obj) {
            changeGroupBtn(obj);          
            this.option.xAxis.data = dayArray;
            this.option.series[0].data = getDate(this.url, 'today'); //[3, 0, 0, 0, 2, 0, 0, 0, 0, 0, 0, 6, 0, 0, 0, 7, 0, 0, 0, 0, 0, 12, 0, 0];
            //console.log(this.option.yAxis.name);
            this.option.yAxis.name = this.yname;
            this.option.series[0].name=this.tname
            this.myChart.setOption(this.option);
        },
        //昨天
        y: function (obj) {
            changeGroupBtn(obj);
            this.option.xAxis.data = dayArray;
            this.option.series[0].data = getDate(this.url, 'yesterday');
            this.option.yAxis.name = this.yname;
            this.option.series[0].name=this.tname
            this.myChart.setOption(this.option);
        },
        //本周
        w: function (obj) {
            changeGroupBtn(obj);
            this.option.xAxis.data = weekArray;
            this.option.series[0].data = getDate(this.url, 'week');
            this.option.yAxis.name = this.yname;
            this.option.series[0].name=this.tname
            this.myChart.setOption(this.option);
        },
        //本月
        m: function (obj) {
            changeGroupBtn(obj);
            this.option.xAxis.data = monthArray();
            this.option.series[0].data = getDate(this.url, 'month');
            this.option.yAxis.name = this.yname;
            this.option.series[0].name=this.tname
            this.myChart.setOption(this.option);
        },
        todo: function () {
            console.log(this.myChart);
        },

    }
};
/*=====  End of 初始化数据  ======*/