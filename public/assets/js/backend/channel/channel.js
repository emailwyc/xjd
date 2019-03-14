define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'channel/channel/index',
                    add_url: 'channel/channel/add',
                    edit_url: 'channel/channel/edit',
                    del_url: 'channel/channel/del',
                    table: 'channel'
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'c.createtime',
                columns: [
                    [
                        {field: 'id', title: 'Id',operate:false},
                        {field: 'channel_name', title: '渠道名称', operate: 'LIKE'},
                        {field: 'channel_code', title: '渠道编码', operate: 'LIKE'},
                        {field: 'channel_url', title: '渠道地址', operate: false},
                        {field: 'settle_type', title: '结算方式', operate: 'LIKE'},
                        {field: 'createtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange'},
                        {field: 'username', title: '渠道名称',operate: 'LIKE'},
                        {field: 'pw', title: '密码',operate: false},
                        {field: 'template_name', title: '模板名称', operate: 'LIKE'},
                        {field: 'template_preview_pc', title: '模板预览PC', formatter: Controller.api.formatter.thumb_pc, operate: false},
                        {field: 'template_preview_app', title: '模板预览APP', formatter: Controller.api.formatter.thumb_app, operate: false},
                        {field: 'channel_desc', title: '备注',operate:false},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'edit',
                                    text: "编辑",
                                    classname: 'btn btn-sm btn-primary btn-editone btn-color-blue',
                                    extend: 'data-area=\'["450px", "450px"]\'',
                                },
                                {
                                    name: 'del',
                                    text: "删除",
                                    classname: 'btn btn-sm btn-danger btn-delone',
                                },
                            ],
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


