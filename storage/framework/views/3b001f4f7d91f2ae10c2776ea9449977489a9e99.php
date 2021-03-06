
<?php $__env->startSection('title'); ?>
Today Offer
<?php $__env->stopSection(); ?>
<?php $__env->startSection('barcum'); ?>
<h1>
    Today Offer
    <small>Create Today Offer List</small>
</h1>
<ol class="breadcrumb">
    <li><a href="#" class="active">Today Offer</a></li>
</ol>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('extra.msg', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

<?php $__env->startSection('content'); ?>
<!-- Main content -->
<div class="row">
    <!-- left column -->
    <div class="col-md-12">
        <!-- general form elements -->
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-plus"></i> Create Today Offer</h3>
            </div>
            <!-- /.box-header -->
            <!-- form start -->

            <form method="post" role="form" enctype="multipart/form-data" action="<?php echo e(url('admin-ecom/todayoffer-add')); ?>">
                <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                <div class="box-body">
                    
                    <div class="form-group">
                        <label for="exampleInputEmail1">Offer Title</label>
                        <input type="text" class="form-control" id="exampleInputEmail1" name="title" placeholder="Enter Name">
                    </div>      
                    <div class="form-group">
                        <label for="exampleInputEmail1">Name</label>
                        <input type="text" class="form-control" id="exampleInputEmail1" name="name" placeholder="Enter Name">
                    </div>
                    <div class="form-group">
                        <label for="exampleInputPassword1">Description</label>
                        <textarea class="form-control" id="editor" name="description" placeholder="Enter Description"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="exampleInputEmail1">Offer Image</label>
                        <input type="file" class="" id="offerimage" name="offerimage">
                    </div>
                    <div class="form-group">
                        
                        <input type="checkbox"  class="minimal"  name="isactive" placeholder="Enter Name"> <label style="margin-left: 5px;" for="exampleInputPassword1"> Is Active</label>
                    </div>
                </div>
                <!-- /.box-body -->

                <div class="box-footer">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Create</button> 
                    <button type="reset" class="btn btn-danger"><i class="fa fa-times-circle"></i> Reset</button>
                </div>
            </form>
        </div>
        <!-- /.box -->

    </div>
    <!--/.col (left) -->
</div>

<div class="row">
    <!-- left column -->
    <div class="col-md-12">
        <!-- general form elements -->
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-table"></i> Today Offer List</h3>
            </div>
            <!-- /.box-header -->
            <!-- form start -->
            <form role="form">
                <div class="box-body">                
                    <div id="grid"></div>

                </div>
                <!-- /.box-body -->
            </form>
        </div>
        <!-- /.box -->

    </div>
    <!--/.col (left) -->
</div>
<!-- /.row -->
<!-- /.content -->
<?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<link rel="stylesheet" href="<?php echo e(url('plugins/iCheck/all.css')); ?>">

<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>
<script src="<?php echo e(url('plugins/iCheck/icheck.min.js')); ?>"></script>
<?php echo $__env->make('extra.kendo', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
<script id="edit_client" type="text/x-kendo-template">
    <a class="k-button k-button-icontext k-grid-delete" href="<?php echo e(url('admin-ecom/todayoffer')); ?>/#= id #"><span class="k-icon k-edit"></span>Edit</a> 
    <a class="k-button k-button-icontext k-grid-delete" onclick="javascript:deleteClick(#= id #);" ><span class="k-icon k-delete"></span>Delete</a>
    </script>  
    <script type="text/javascript">
    function deleteClick(id) {
    var c = confirm("Do you want to delete?");
    if (c === true) {
    $.ajax({
    type: "GET",
    dataType: "json",
    url: "todayoffer-delete/"+id,
    success: function (result) {
    $(".k-i-refresh").click();
    }
    });
    }
    }

</script>

<script type="text/javascript">
    $(document).ready(function () {
//$("#brandimage").kendoUpload();

    $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
    checkboxClass: 'icheckbox_minimal-blue',
            radioClass: 'iradio_minimal-blue'
    });
    var dataSource = new kendo.data.DataSource({
    transport: {
    read: {
    url: "todayoffer-data",
            type: "GET",
            datatype: "JSON"

    }
    },
            autoSync: false,
            schema: {
            data: "data",
                    total: "total",
                    model: {
                    id: "id",
                            fields: {
                            id: {nullable: true},
                                    title: {type: "string"},
                                    name: {type: "string"},
                                    description: {type: "string"},
                                    isactive: {type: "boolean"},
                                    created_at: {type: "string"},
                                    updated_at: {type: "string"}

                            }
                    }
            },
            pageSize: 10,
            serverPaging:true,
            serverFiltering: true,
            serverSorting: true
    });
    $("#grid").kendoGrid({
    dataSource: dataSource,
            filterable: true,
            pageable: {
            refresh: true,
                    input: true,
                    numeric: false,
                    pageSizes: true,
                    pageSizes:[10, 20, 50, 100, 200, 400]
            },
            sortable: true,
            groupable: true,
            columns: [
            {field: "id", title: "BID", width: "80px"},
            {field: "title", title: "Title", width: "150px"},
            {field: "name", title: "Name", width: "150px"},
            // {field: "description", title: "Description", width: "150px"},
            {field: "isactive", title: "Is Active", width: "150px"},
            {field: "created_at", title: "Created", width: "150px"},
            {
            title: "Action", width: "290px",
                    template: kendo.template($("#edit_client").html())
            }
            ],
    });
    });

</script>
<script>
    $(document).ready(function () {
        function onPaste(e) {
            kendoConsole.log("paste :: " + kendo.htmlEncode(e.html));
        }
        // create DateTimePicker from input HTML element
        $("#editor").kendoEditor({ resizable: {
            content: true,
            toolbar: true,
            paste: onPaste
        }});
         // $("#offerimage").kendoUpload();
    });
</script> 
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layout.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>