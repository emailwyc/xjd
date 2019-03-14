define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'account/repay/index',
                    add_url: 'account/repay/add',
                    //edit_url: 'account/repay/edit',
                    //del_url: 'account/repay/del',
                    multi_url: 'account/repay/multi',
                    table: 'order_repay',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search:false,
                columns: [
                    [
                        /*{checkbox: true},
                        {
                            field: 'orep.order_id', title: "订单号",
                            visible: false,
                        },*/
                        {field: 'id', title: '账单号' ,operate: false},
                        {
                            field: 'du.mobile', title: "手机号",
                            visible: false,
                        },
                        {field: 'mobile', title: '手机号' ,operate: false},
                        {
                            field: 'ui.realname', title: "姓名",
                            visible: false,
                        },
                        {field: 'realname', title: '姓名' ,operate: false},
                        {
                            field: 'dor.type', title: "客户类型",
                            visible: false,
                            searchList: {
                                1: "首借",
                                2: "续借"
                            }
                        },
                        {field: 'usertype', title: "客户类型", operate: false, searchList: {1: "首借", 2: "续借"}},
                        {field: 'total_amount', title: '还款金额' ,operate: false},
                        {field: 'zq_amount', title: '展期费用' ,operate: false},
                        {field: 'real_amount', title: '实收金额' ,operate: false},
                        {field: 'fk_time_detail', title: '放款日期' ,operate: false, addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'repay_time', title: '还款日期' ,operate: false, addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'real_time', title: '实还日期' ,operate: false, addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'zq_time', title: '展期日期' ,operate: false, addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'orep.status', title: "回款状态",
                            searchList: {
                                1: "待处理",
                                2: "正常还款",
                                3: "逾期",
                                4: "逾期还款",
                                5: "续期",
                                6: "展期",
                                7: "逾期续期",
                                8: "逾期展期",
                            },
                            visible: false,
                        },
                        {
                            field: 'status',
                            title: "回款状态",
                            operate: false,
                        },
                        {field: 'channel_name', title: '渠道',},
                        {field: 'allotdce', title:'资料审核员',},
                        {field: 'allotfce', title: '放款审核员',},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [{
                                name: 'detail',
                                text: "结清",
                                //icon: 'fa fa-list',
                                refresh:true,
                                classname: 'btn btn-sm btn-info btn-detail btn-color-green',
                                url: 'account/Repay/del'
                            },/*{
                                name: 'detail',
                                text: "销账",
                                icon: 'fa fa-list',
                                classname: 'btn btn-info btn-xs btn-detail btn-dialog',
                                url: 'account/repay/edit'
                            },*//*{
                                name: 'detail',
                                text: "修改额度",
                                icon: 'fa fa-list',
                                refresh:true,
                                classname: 'btn btn-info btn-xs btn-detail btn-dialog',
                                url: 'account/Repay/black'
                            },*/{
                                name: 'detail',
                                text: "展期",
                                icon: 'fa fa-list',
                                classname: 'btn btn-sm btn-info btn-detail btn-dialog',
                                url: 'account/repay/zhanqi'
                            }],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});