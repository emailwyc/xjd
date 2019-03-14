define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'sys/config/index',
                    add_url: 'sys/config/add',
                    edit_url: 'sys/config/edit',
                    del_url: 'sys/config/del',
                    multi_url: 'sys/config/multi',
                    table: 'sys_config',
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
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'sjqb', title: __('Sjqb'), operate: 'BETWEEN'},
                        {field: 'sjdz', title: __('Sjdz'), operate: 'BETWEEN'},
                        /*{field: 'xjqb', title: __('Xjqb'), operate: 'BETWEEN'},
                        {field: 'xjzd', title: __('Xjzd'), operate: 'BETWEEN'},*/
                        {
                            field: 'jkzd', title: __('Jkzd'),
                            formatter: function (value, row, index) {
                                return value * 100 + "%";
                            }
                        },
                        {
                            field: 'rqlv', title: __('Rqlv'),
                            formatter: function (value, row, index) {
                                return value * 100 + "%";
                            }
                        },
                        {
                            field: 'zgyq', title: __('Zgyq'),
                            formatter: function (value, row, index) {
                                return value * 100 + "%";
                            }
                        },
                        /*{
                            field: 'ads', title: __('Ads'),
                            formatter: function (value, row, index) {
                                if (row.ads === 1) {
                                    return '<span class="text-grey"><i class="fa fa-circle"></i>展示</span>';
                                } else {
                                    return '<span class="text-danger"><i class="fa fa-circle"></i>不展示</span>';
                                }
                            }
                        },*/
                        {
                            field: 'create_time',
                            title: __('Create_time'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'update_time',
                            title: __('Update_time'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },

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
            }
        }
    };
    return Controller;
});