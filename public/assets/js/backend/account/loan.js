define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'account/loan/index',
                    add_url: 'account/loan/add',
                    multi_url: 'account/loan/multi',
                    table: 'test',
                    editLimit_url:"account/loan/editLimit",

                    depositconfirm_url:'account/loan/depositConfirm',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'o.id',
                search: false,
                columns: [
                    [
                        {checkbox: true},
                        /*{
                            field: 'o.id', title: "订单ID",
                            visible: false,
                        },*/
                        {field: 'id', title: "订单ID", sortable: true, operate: false},
                        {
                            field: 'u.username', title: "手机号",
                            visible: false,
                        },
                        {field: 'username', title: "手机号", operate: false},
                        {field: 'realname', title: "姓名", operate: 'LIKE'},
                        {field: 'amount', title: "借款金额", operate: 'BETWEEN'},
                        {field: 'pay', title: "还款金额", operate: 'BETWEEN'},
                        {
                            field: 'o.type', title: "客户类型",
                            visible: false,
                            searchList: {
                                1: "首借",
                                2: "续借"
                            }
                        },
                        {field: 'usertype', title: "客户类型", operate: false, searchList: {1: "首借", 2: "续借"}},
                        {
                            field: 'o.createtime',
                            title: "申请时间",
                            visible: false,
                            formatter: Table.api.formatter.datetime,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            sortable: true
                        },
                        {field: 'ordertime', title: "申请时间", operate: false},
                        {field: 'mk_time', title: "放款时间", operate: false},
                        /*{
                            field: 'o.endtime',
                            title: "还款日期",
                            visible: false,
                            formatter: Table.api.formatter.datetime,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            sortable: true
                        },
                        {field: 'payendtime', title: "还款日期", operate: false},
                        {field: 'cycle', title: "借款天数"},*/
                        {field: 'channel_name', title: "用户渠道"},
                        {
                            field: 'o.status',
                            title: "审核状态",
                            visible: false,
                            searchList: {
                                0: "待审核",
                                1: "资料审核",
                                2: "资料审核通过",
                                3: "放款审核",
                                4: "放款审核通过",
                                5: "审核通过",
                                6: "待放款",
                                7: "放款中",
                                8: "已放款",
                                9: "已还款",
                                10: "已逾期",
                                11: "已续期",
                                12: "机审失败",
                                13: "机审成功",
                                14: "资料审核失败",
                                15: "财务审核失败"
                            }
                        },
                        {field: 'orderstatus', title: "处理状态", operate: false},
                        {
                            field: 'a1.nickname', title: "资料审核员",
                            visible: false,
                        },
                        {field: 'dcnickname', title: "资料审核员", operate: false},
                        /*{
                            field: 'a2.nickname', title: "放款审核员",
                            visible: false,
                        },
                        {field: 'fcnickname', title: "放款审核员", operate: false},*/
                        {
                            field: 'operate',
                            title: '查看报告',
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [ {
                                name: 'detail',
                                text: "查看",
                                //icon: 'fa fa-list',
                                classname: 'btn btn-sm btn-primary btn-dialog btn-color-blue',
                                //classname: 'btn btn-info btn-sm btn-detail btn-dialog',
                                url: 'authInfo/detail'
                            }],
                            formatter: Table.api.formatter.operate,
                        },
                        {
                            field: 'operate',
                            title: '审核结果',
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [ {
                                name: 'detail',
                                text: "查看",
                                //icon: 'fa fa-list',
                                //classname: 'btn btn-info btn-xs btn-detail btn-dialog',
                                classname: 'btn btn-sm btn-primary btn-dialog btn-color-blue',
                                extend: 'data-area=\'["500px", "350px"]\'',
                                url: 'authInfo/fqzforsh'
                            }],
                            formatter: Table.api.formatter.operate,
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [ {
                                name: 'detail',
                                text: "复审拒绝",
                                //icon: 'fa fa-list',
                                classname: 'btn btn-info btn-sm btn-detail btn-danger',
                                url: 'account/loan/refuse',
                                disable:function (rows) {
                                    var arr = [7,8,9,10,3,12,13,15];
                                    var leng = arr.length;
                                    for (var i=0;i<leng;i++){
                                        if (rows.status==arr[i]){
                                            return true;
                                        }
                                    }
                                }
                            },{
                                name: 'detail',
                                text: "审核通过",
                                //icon: 'fa fa-list',
                                classname: 'btn btn-info btn-sm btn-detail btn-color-green',
                                url: 'account/loan/approve',
                                disable:function (rows) {
                                    var arr = [4,5,6,7,8,9,10];
                                    var leng = arr.length;
                                    for (var i=0;i<leng;i++){
                                        if (rows.status==arr[i]){
                                            return true;
                                        }
                                    }
                                }
                            }, {
                                name: 'editLimit',
                                text: "修改额度",
                               // icon: 'fa fa-list',
                                classname: 'btn btn-info btn-sm btn-detail btn-dialog',
                                url: 'account/loan/editLimit',
                                extend: 'data-area=\'["400px", "400px"]\'',
                                disable:function (rows) {
                                    if (rows.status!=6){
                                        return true;
                                    }
                                }
                            }, {
                                name: 'depositconfirm',
                                text: "放款",
                                //icon: 'fa fa-list',
                                classname: 'btn btn-info btn-sm btn-detail btn-dialog btn-color-green',
                                url: 'account/loan/depositConfirm',
                                extend: 'data-area=\'["600px", "600px"]\'',
                                disable:function (rows) {
                                    if (rows.status!=6){
                                        return true;
                                    }
                                }
                            },/*{
                                name: 'detail',
                                text: "线下放款",
                                icon: 'fa fa-list',
                                classname: 'btn btn-info btn-xs btn-detail',
                                url: 'account/loan/repay'
                            }*/],
                            formatter: Table.api.formatter.operate,
                        },

                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //绑定TAB事件
            // $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            //     // var options = table.bootstrapTable(tableOptions);
            //     var typeStr = $(this).attr("href").replace('#','');
            //     var options = table.bootstrapTable('getOptions');
            //     options.pageNumber = 1;
            //     options.queryParams = function (params) {
            //         // params.filter = JSON.stringify({type: typeStr});
            //         params.type = typeStr;
            //
            //         return params;
            //     };
            //     table.bootstrapTable('refresh', {});
            //     return false;
            //
            // });

            var options = table.bootstrapTable('getOptions');
            var callback = options.queryParams;
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var typeStr = $(this).attr("href").replace('#','');
                options.queryParams = function (params) {
                    params.type = typeStr;
                    return callback(params);
                };
                table.bootstrapTable('refresh', {});
                return false;
            });
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        editlimit: function () {
            Controller.api.bindevent();
        },

        depositconfirm:function(){

            Controller.api.bindevent();

        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }

    };
    return Controller;
});