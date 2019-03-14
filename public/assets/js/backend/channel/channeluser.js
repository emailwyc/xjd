define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'channel/channeluser/index'
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
                        {field: 'channel_name', title: '渠道名称', operate:false},
                        {field: 'regCount', title: '注册量', operate: false},
                        {field: 'applyCount', title: '申请量', operate:false},
                        {field: 'txCount', title: '提现量',operate:false},
                        {field: 'createtime', title: "日期",visible: false, formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange'}
                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        }
    };
    return Controller;
});
