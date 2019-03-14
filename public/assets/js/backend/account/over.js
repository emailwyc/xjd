define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'account/over/index',
                    add_url: 'account/over/add',
                    multi_url: 'account/over/multi',
                    pass_url: 'account/over/pass',
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
                        {
                            field: 'o.id', title: "编号",
                            visible: false,
                        },
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
                        {field: 'channel_name', title: "用户渠道"},
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
                        {
                            field: 'endtime',
                            title: '还款日期',
                            formatter: Table.api.formatter.datetime,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            sortable: true},
                        {field: 'agfrom', title: '渠道来源', operate: false},
                        {field: 'dcidname', title: '审核员', operate: false},
                        {field: 'over_mem', title: '催收客服', operate: false},
                        {field: 'over_rem', title: '备注', operate: false,
                            formatter: function (value, row, index) {
                                if (value) {
                                    var lists = value.split('*;*');
                                    return lists.join('<br/>');
                                } else {
                                    return '';
                                }
                            }
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [/*{
                                name: 'detail',
                                text: "结清",
                                icon: 'fa fa-list',
                                refresh:true,
                                classname: 'btn btn-info btn-xs btn-detail',
                                url: 'account/over/jq'
                            },*/{
                                name: 'detail',
                                text: "查看",
                                //icon: 'fa fa-list',
                                classname: 'btn btn-info btn-sm btn-detail btn-dialog btn-color-blue',
                                url: 'authInfo/detail'
                            },{
                                name: 'pass',
                                text: "备注",
                                //icon: 'fa fa-list',
                                classname: 'btn btn-info btn-sm btn-dialog btn-color-green',
                                extend: 'data-area=\'["400px", "350px"]\'',
                                url: 'account/over/pass'
                            },{
                                name: 'black',
                                text: "黑名单",
                                //icon: 'fa fa-list',
                                classname: 'btn btn-info btn-sm btn-dialog btn-danger',
                                url: 'account/over/black'
                            },{
                                name: 'chuis',
                                text: "催收员",
                                //icon: 'fa fa-list',
                                classname: 'btn btn-info btn-sm btn-dialog btn-color-blue',
                                url: 'account/over/chuis'
                            }],
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
        black: function () {
            Controller.api.bindevent();
        },
        chuis: function () {
            Controller.api.bindevent();
        },
        pass: function () {
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