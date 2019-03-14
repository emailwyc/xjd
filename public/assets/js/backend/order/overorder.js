define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'overorder/index',
                    add_url: 'overorder/add',
                    edit_url: 'overorder/edit',
                    del_url: 'overorder/del',
                    multi_url: 'overorder/multi',
                    table: 'overorder',
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
                        {field: 'amount', title: __('Amount'), operate:'BETWEEN'},
                        {field: 'pay', title: __('Pay'), operate:'BETWEEN'},
                        {field: 'cost', title: __('Cost'), operate:'BETWEEN'},
                        {field: 'overcost', title: __('Overcost'), operate:'BETWEEN'},
                        {field: 'overday', title: __('Overday')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'starttime', title: __('Starttime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'endtime', title: __('Endtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'rollnum', title: __('Rollnum')},
                        {field: 'status', title: __('Status')},
                        {field: 'dcid', title: __('Dcid')},
                        {field: 'allotdcid', title: __('Allotdcid')},
                        {field: 'fcid', title: __('Fcid')},
                        {field: 'allotfcid', title: __('Allotfcid')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});