define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'sms/scene/index',
                    add_url: 'sms/scene/add',
                    edit_url: 'sms/scene/edit',
                    del_url: 'sms/scene/del',
                    multi_url: 'sms/scene/multi',
                    table: 'sms_scene',
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
                        {field: 'scene', title: __('Scene')},
                        {field: 'content', title: __('Content')},
                        {
                            field: 'status',
                            title: __('Status'),
                            formatter: function (value, row, index) {
                                if (row.status === 1) {
                                    return '<span class="text-grey"><i class="fa fa-circle"></i>启用</span>';
                                }else {
                                    return '<span class="text-danger"><i class="fa fa-circle"></i>禁用</span>';
                                }
                            }
                        },
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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