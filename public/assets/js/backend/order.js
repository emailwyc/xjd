define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/index',
                    add_url: 'order/add',
                    edit_url: 'order/edit',
                    del_url: 'order/del',
                    multi_url: 'order/multi',
                    table: 'order',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'code', title: __('Code')},
                        {field: 'uid', title: __('Uid')},
                        {field: 'amount', title: __('Amount'), operate: 'BETWEEN'},
                        {field: 'pay', title: __('Pay'), operate: 'BETWEEN'},
                        {field: 'cost', title: __('Cost'), operate: 'BETWEEN'},
                        {field: 'overcost', title: __('Overcost'), operate: 'BETWEEN'},
                        {field: 'overday', title: __('Overday')},
                        {
                            field: 'createtime',
                            title: __('Createtime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'starttime',
                            title: __('Starttime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'endtime',
                            title: __('Endtime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {field: 'rollnum', title: __('Rollnum')},
                        {field: 'status', title: __('Status')},
                        {field: 'dcid', title: __('Dcid')},
                        {field: 'allotdcid', title: __('Allotdcid')},
                        {field: 'fcid', title: __('Fcid')},
                        {field: 'allotfcid', title: __('Allotfcid')},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        fullorderindex: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/fullorderindex'
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
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
                        {field: 'usertype', title: "客户类型", operate: false, searchList: {0: "首借", 1: "续借"}},
                        {field: 'amount', title: "借款金额", operate: false},
                        {field: 'pay', title: "还款金额", operate: false},
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
                        {
                            field: 'o.endtime',
                            title: "还款日期",
                            visible: false,
                            formatter: Table.api.formatter.datetime,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            sortable: true
                        },
                        {field: 'payendtime', title: "还款日期", operate: false},
                        {field: 'cycle', title: "借款天数", operate: false},
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
                        {field: 'channel_name', title: "用户渠道"},
                        {field: 'rem', title: "备注", operate: false},
                        {field: 'orderstatus', title: "处理状态", operate: false},
                        {
                            field: 'a1.nickname', title: "资料审核员",
                            visible: false,
                        },
                        {field: 'dcnickname', title: "资料审核员", operate: false},
                        {
                            field: 'a2.nickname', title: "放款审核员",
                            visible: false,
                        },
                        {field: 'fcnickname', title: "放款审核员", operate: false},
                        {
                            field: 'operate',
                            title: "审核结果",
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [{
                                name: 'detail',
                                text: "查看",
                                //icon: 'fa fa-list',
                                classname: 'btn btn-info btn-sm btn-detail btn-dialog btn-color-blue',
                                extend: 'data-area=\'["600px", "550px"]\'',
                                url: 'authInfo/fqzforsh'
                            }],
                            formatter: Table.api.formatter.operate
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [{
                                name: 'detail',
                                text: "详情",
                                //icon: 'fa fa-list',
                                classname: 'btn btn-info btn-sm btn-detail btn-dialog btn-color-blue',
                                url: 'authInfo/detail'
                            }, {
                                name: 'detail',
                                text: "通过",
                                //icon: 'fa fa-list',
                                classname: 'btn btn-info btn-sm btn-success',
                                url: 'order/through',
                            }, {
                                name: 'detail',
                                text: "拒绝",
                                //icon: 'fa fa-list',
                                classname: 'btn btn-info btn-sm btn-detail btn-danger',
                                url: 'order/refuse'
                            }, {
                                name: 'detail',
                                text: "取消",
                                //icon: 'fa fa-list',
                                classname: 'btn  btn-sm btn-detail btn-dialog btn-default',
                                extend: 'data-area=\'["400px", "400px"]\'',
                                url: 'order/pass/type/3'
                            }],
                            formatter: Table.api.formatter.operate
                        }
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
        regorderlist: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/regorderlist',
                    user_url:'order/user'
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'uid',
                sortName: 'uid',
                search: false,
                columns: [
                    [
                        {
                            field: 'i.status',
                            title: "审核状态",
                            visible: false,
                            searchList: {
                                0: "机器审核",
                                1: "人工审核",
                                2: "人工审核通过",
                                3: "人工审核拒绝",
                                4: "机器审核通过",
                                5: "机器审核拒绝",
                                6: "人工审核取消"
                            }
                        },
                        {checkbox: true},
                        /*{field: 'uid', title: '会员ID'},*/
                        {field: 'uid', title: "会员ID", sortable: true, operate: false},
                        {field: 'username', title: "手机号", operate: 'LIKE'},
                        {field: 'realname', title: "姓名", operate: 'LIKE'},
                        {
                            field: 'u.createtime',
                            title: "注册时间",
                            visible: false,
                            formatter: Table.api.formatter.datetime,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            sortable: true
                        },
                        {
                            field: 'createtime',
                            title: "注册时间",
                            operate: false
                        },
                        {field: 'authinfo', title: "认证项", operate: false},
                        {field: 'channel_name', title: "用户渠道"},
                        {field: 'rem', title: "备注", operate: false},
                        {field: 'u.status', visible: false, title: "处理状态", searchList: {1: "正常", 2: "废弃"}},
                        {field: 'status', title: "处理状态", operate: false},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                            //     {
                            //     name: 'detail',
                            //     text: "报告",
                            //     icon: 'fa fa-list',
                            //     classname: 'btn btn-info btn-xs btn-detail btn-dialog',
                            //     url: 'authInfo/detail'
                            // },
                                {
                                name: 'user',
                                text: "备注",
                               // icon: 'fa fa-list',
                                classname: 'btn btn-sm btn-primary btn-dialog btn-color-blue',
                                extend: 'data-area=\'["400px", "350px"]\'',
                                url:'order/user/type/1'
                            }, {
                                name: 'detail',
                                text: "废弃",
                                //icon: 'fa fa-list',
                                classname: 'btn btn-sm btn-primary btn-danger',
                                extend: 'data-area=\'["400px", "350px"]\'',
                                //url: 'order/user/type/2'
                            }],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        user: function () {
            Controller.api.bindevent();
        },
        edit: function () {
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