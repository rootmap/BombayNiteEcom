<?php $__env->startSection('title'); ?>
Modify Order Content
<?php $__env->stopSection(); ?>
<?php $__env->startSection('barcum'); ?>
<h1>
    Edit Order Content
    <small>Modify Order Content</small>
</h1>
<!--<ol class="breadcrumb-->
    <li><a href="<?php echo e(url('admin-ecom/orderInfo')); ?>">Payment Order Content</a></li>
    <li><a href="#" class="active">Modify Order Content</a></li>
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
                <h3 class="box-title"><i class="fa fa-pencil-square-o"></i> Edit Order Content</h3>
            </div>
            <!-- /.box-header -->
            <!-- form start -->

            <form method="post" role="form" enctype="multipart/form-data" action="<?php echo e(url('admin-ecom/orderInfo-update')); ?>">
                <div class="box-body">
                    <input type="hidden" name="id" value="<?= $data->id; ?>" />
                    <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                    <div class="form-group">
                        <label for="exampleInputPassword1">Delivery Time</label>
                        <textarea class="form-control editor" id="" name="delivery_time" placeholder="Enter Delivery Time"><?= $data->delivery_time; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="exampleInputPassword1">Allergy Alert</label>
                        <textarea class="form-control editor"  name="allergy_alert" placeholder="Enter Allergy Alert"><?= $data->allergy_alert; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="exampleInputPassword1">Delivery Info</label>
                        <textarea class="form-control editor"  name="delivery_info" placeholder="Enter Delivery Info"><?= $data->delivery_info; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="exampleInputPassword1">Description</label>
                        <textarea class="form-control editor"  name="description" placeholder="Enter Description"><?= $data->description; ?></textarea>
                    </div>
                    <div class="form-group">

                        <input type="checkbox"
                               <?php if(!empty($data->isactive)): ?>
                               checked="checked"  
                               <?php endif; ?>
                               class="minimal"  name="isactive" placeholder="Enter Name"> <label style="margin-left: 5px;" for="exampleInputPassword1"> Is Active</label>
                    </div>
                    <div class="form-group">

                        <input type="checkbox"
                               <?php if(!empty($data->isoffline)): ?>
                               checked="checked"  
                               <?php endif; ?>
                               class="minimal"  name="isoffline" id="isoffline" placeholder="Enter Name"> <label style="margin-left: 5px;" for="exampleInputPassword1"> Order Offline</label>
                    </div>
                    <div class="form-group offline_notice" style="display: none;">
                        <label for="exampleInputPassword1">Offline Notice</label>
                        <textarea class="form-control editor"  name="offline_notice" placeholder="Enter Description"><?= $data->offline_notice; ?></textarea>
                    </div>
                </div>
                <!-- /.box-body -->

                <div class="box-footer">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-pencil-square"></i> Modify</button> 
                    <button type="reset" class="btn btn-danger"><i class="fa fa-times-circle"></i> Reset</button>
                    <a class="btn btn-info pull-right" href="<?php echo e(url('admin-ecom/order-Info')); ?>"><i class="fa fa-table"></i> Back To List</a>
                </div>
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
<script>
    $(document).ready(function () {

        $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
            checkboxClass: 'icheckbox_minimal-blue',
            radioClass: 'iradio_minimal-blue'
        });

        $('#isoffline').on('ifChecked', function () { 
            $(".offline_notice").fadeIn('slow');
        });

        // For onUncheck callback
        $('#isoffline').on('ifUnchecked', function () { 
            $(".offline_notice").fadeOut('slow');
        });

        <?php if(!empty($data->isoffline)): ?>
            $(".offline_notice").fadeIn('slow'); 
        <?php endif; ?>

    });
</script>
<script>
    $(document).ready(function () {
        function onPaste(e) {
            kendoConsole.log("paste :: " + kendo.htmlEncode(e.html));
        }
        // create DateTimePicker from input HTML element
        $("#datepicker").kendoDatePicker({
            value: new Date(),
            dateInput: true
        });
        $("#timepicker").kendoTimePicker({
            value: new Date(),
            dateInput: true
        });
        $(".editor").kendoEditor({ resizable: {
            content: true,
            toolbar: true,
            paste: onPaste
        }});
    });
</script>   
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layout.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>