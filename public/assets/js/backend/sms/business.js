define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'sms/business/index',
                    edit_url: 'sms/business/edit'
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
                        {field: 'id', title: 'Id',operate:false},
                        {field: 'business_type', title: '节点类型', operate: false},
                        {field: 'business_node', title: '节点名称', operate: false},
                        {field: 'start_date', title: '开始时间',operate: false},
                        {field: 'end_date', title: '结束时间', operate: false},
                        {field: 'is_use', title: '是否启用', operate: false},
                       // {field: 'operate',title: __('Operate'),table: table,events: Table.api.events.operate,formatter: Table.api.formatter.operate,searchList: {0:'关闭' ,1:'启用'}}
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [{
                                name: 'edit',
                                text: "编辑",
                                classname: 'btn btn-sm btn-primary btn-editone btn-color-blue',
                                extend: 'data-area=\'["350px", "350px"]\'',
                            }],
                            formatter: Table.api.formatter.operate,
                            searchList: {0:'关闭' ,1:'启用'}
                        }
                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
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
