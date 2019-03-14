define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'channel/channelagency/index',
                    add_url: 'channel/channelagency/add',
                    edit_url: 'channel/channelagency/edit',
                    del_url: 'channel/channelagency/del',
                    table: 'channel_agency'
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'createtime',
                columns: [
                    [
                        {field: 'id', title: 'Id',operate:false},
                        {field: 'agency_name', title: '代理名称', operate: 'LIKE'},
                        {field: 'agency_code', title: '代理编码', operate: 'LIKE'},
                        {field: 'agency_phone', title: '代理电话', operate: 'LIKE'},
                        {field: 'createtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange'},
                        {field: 'operate',title: __('Operate'),table: table,events: Table.api.events.operate,formatter: Table.api.formatter.operate}
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