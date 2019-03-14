define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'channel/channelstatistics/index'
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
                        {field: 'channel_code', title: '渠道编码', operate: 'LIKE'},
                        {field: 'settle_type', title: '结算方式', operate: false},
                        {field: 'username', title: '注册用户名', operate: 'LIKE'},
                        {field: 'mobile', title: '手机号', operate: 'LIKE'},
                        {field: 'createtime', title: '注册时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange'},
                        {field: 'iscomplete', title: '是否完整进件', operate: false},
                        {field: 'amount', title: '申请金额',operate: false},
                        {field: 'status', title: '处理状态',operate: false}
                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        }
    };
    return Controller;
});