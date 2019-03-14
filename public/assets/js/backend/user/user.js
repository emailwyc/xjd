define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user/index',
                    edit_url: 'user/user/edit',
                    table: 'user'
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'u.id',
                search: false,
                columns: [
                    [
                        {field: 'id', title: 'Id',operate:false},
                        {field: 'realname', title: '用户名', operate: 'LIKE'},
                        /*{field: 'nickname', title: '昵称', operate: 'LIKE'},*/
                        {field: 'mobile', title: '手机号', operate: 'LIKE'},
                        // {field: 'email', title: '邮箱', operate: 'LIKE'},
                        {field: 'quota', title: '借款额度',operate:false},
                        {field: 'amount', title: '借款金额'},
                        {field: 'pay', title: '应还金额'},
                        {field: 'cost', title: '利息'},
                        {field: 'overcost', title: '逾期金额'},
                        {field: 'overday', title: '逾期天数'},
                        {field: 'status', title: '状态', searchList: {0:'待审核' ,1:'资料审核',2:'资料审核通过',3:'放款审核',4:'放款审核通过' ,5:'审核通过',6:'待放款',7:'放款中' ,8:'已放款',9:'已还款',10:'逾期' ,11:'未通过',12:'机审失败',13:'机审成功' ,14:'资料审核失败',15:'财务审核失败'}},
                        {field: 'createtime', title: '申请日期', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange'},
                        {field: 'starttime', title: '放款日期', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange'},
                        {field: 'endtime', title: '到期日期', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange'},
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
                                url: 'user/user/edit'
                            }],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //绑定TAB事件
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var typeStr = $(this).attr("href").replace('#','');
                //if(typeStr == 'all'){
                //    $('form').append("<input name='type' id='type' value='1' />");
                //}else{
                //    $('form').append("<input name='type' id='type' value='2' />");
                //}
                $.ajax({
                   url:'/admin/user/user/setstatus.html',
                    data:{type:typeStr},
                    type:'post',
                    dataType:'html',
                    ansyc:false,
                    success:function(){}
                });

                //var options = table.bootstrapTable('getOptions');
                //options.pageNumber = 1;
                //options.queryParams = function (params) {
                //    params.type = typeStr;
                //    return params;
                //};
                table.bootstrapTable('refresh', {});
                return false;

            });
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