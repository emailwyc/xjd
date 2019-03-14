define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'account/statistics/index',
                    add_url: 'account/statistics/add',
                    edit_url: 'account/statistics/edit',
                    del_url: 'account/statistics/del',
                    multi_url: 'account/statistics/multi',
                    table: 'test',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                search:false,
                columns: [
                    [
                        {checkbox: true},
                        {
                            field: 'before', title: "时间",
                            visible: false,
                            formatter: Table.api.formatter.datetime,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            sortable: true
                        },
                        // {
                        //     field: 'end', title: "结束时间",
                        //     visible: false,
                        //     formatter: Table.api.formatter.datetime,
                        //     operate: 'RANGE',
                        //     addclass: 'datetimerange',
                        //     sortable: true
                        // },
                        {field: 'time', title: '日期', operate: false},
                        {field: 'dqzbs', title: '到期笔数', operate: false},
                        {field: 'yq_1_3', title: 'T0还款笔数', operate: false},
                        {field: 'yq_3_7', title: 'T1还款笔数', operate: false},
                        {field: 'yq_7_15', title: 'T2还款笔数', operate: false},
                        {field: 'yq_15', title: 'T3回款笔数', operate: false},
                        {field: 't0yql', title: 'T0逾期率%', operate: false},
                        {field: 'allyql', title: '综合逾期率%', operate: false},
                        {field: 'sjdqzbs', title: '首借到期笔数', operate: false},
                        {field: 'sjyjzbs', title: '首借逾期', operate: false},
                        {field: 'sjyqldata', title: '首借逾期率', operate: false},
                        {field: 'fjdqzbs', title: '复借到期笔数', operate: false},
                        {field: 'fjyjzbs', title: '复借逾期', operate: false},
                        {field: 'fjyqldata', title: '复借逾期率', operate: false},

                    ],
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});