define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'account/noraml/index',
                    add_url: 'account/noraml/add',
                    multi_url: 'account/noraml/multi',
                    table: 'order',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'o.id',
                search:false,
                columns: [
                    [
                        {checkbox: true},
                        /*{
                            field: 'o.id', title: "编号",
                            visible: false,
                        },*/
                        {field: 'id', title: '编号', operate: false},
                        {field: 'status', title: '状态', operate: false},
                        {
                            field: 'dui.realname', title: "姓名",
                            visible: false,
                        },
                        {field: 'realname', title: '姓名', operate: false},
                        {
                            field: 'd.mobile', title: "手机号",
                            visible: false,
                        },
                        {field: 'mobile', title: '手机号', operate: false},
                        {field: '', title: '渠道', operate: false},
                        {
                            field: 'o.pay', title: "还款金额",
                            visible: false,
                        },
                        {field: 'pay', title: '还款金额', operate: false},
                        {
                            field: 'o.overcost', title: "逾期金额",
                            visible: false,
                        },
                        {field: 'overcost', title: '逾期金额', operate: false},
                        {field: 'usertype', title: "客户类型", operate: false, searchList: {1: "首借", 2: "续借"}},
                        {field: 'endtime', title: '还款日期', operate: false},
                        {field: '', title: '渠道来源', operate: false},
                        {field: 'dcidname', title: '审核员', operate: false},
                        {field: '', title: '催收记录', operate: false},
                        {field: '', title: '催收客服', operate: false},
                        /*{
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [{
                                name: 'detail',
                                text: "结清",
                                icon: 'fa fa-list',
                                classname: 'btn btn-info btn-xs btn-detail',
                                url: 'account/noraml/del'
                            }],
                            formatter: Table.api.formatter.operate,
                        }*/
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