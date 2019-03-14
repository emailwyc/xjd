define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'channel/channeltemplate/index',
                    add_url: 'channel/channeltemplate/add',
                    edit_url: 'channel/channeltemplate/edit',
                    del_url: 'channel/channeltemplate/del',
                    table: 'channel_template'
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
                        {field: 'template_name', title: '模板名称', operate: 'LIKE'},
                        {field: 'template_url', title: '模板地址', operate: false},
                        {field: 'template_code', title: '模板编码', operate: false},
                        {field: 'template_preview_pc', title: '模板预览PC', formatter: Controller.api.formatter.thumb_pc, operate: false},
                        {field: 'template_preview_app', title: '模板预览APP', formatter: Controller.api.formatter.thumb_app, operate: false},
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
            },
            formatter: {
                thumb_pc: function (value, row, index) {
                    return '<a href="' + row.template_preview_pc + '" target="_blank"><img src="' + row.template_preview_pc + '" alt="" style="max-height:54px;max-width:96px"></a>';
                },
                thumb_app: function (value, row, index) {
                    return '<a href="' + row.template_preview_app + '" target="_blank"><img src="' + row.template_preview_app + '" alt="" style="max-height:90px;max-width:120px"></a>';
                }
            }
        }
    };
    return Controller;
});
