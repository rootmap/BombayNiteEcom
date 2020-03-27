<?php $__env->startSection('title','Proced To Payment'); ?>
<?php $__env->startSection('content'); ?>
<div id="contentWrapper">
	<div class="page-title title-1">
		<div class="container">
			<div class="row">
				<div class="cell-12">
					<h1 class="fx" data-animate="fadeInLeft">Check <span>out</span></h1>
					<div class="breadcrumbs main-bg fx" data-animate="fadeInUp">
						<span class="bold">You Are In:</span><a href="#">Home</a><span class="line-separate">/</span><a href="#">Shop </a><span class="line-separate">/</span><span>Check out</span>
					</div>
					<div class="cart-icon fx" data-animate="fadeInRight">
						<div class="cart-heading">
							<i class="fa fa-shopping-cart"></i><span id="cart-count"><b>0</b> item(s)</span>
						</div>
						<div class="cart-popup">
							<div class="empty">Your shopping cart is empty!</div>
							<div class="mini-cart">
								<ul class="mini-cart-list">
									<li>
										<a class="cart-mini-lft" href="product.php"><img src="images/shop/pro-1.jpg" alt=""></a>
										<div class="cart-body">
											<a href="product.php">Ultimate Fashion Wear White</a>
											<div class="price">$150</div>
										</div>
										<a class="remove" href="#"><i class="fa fa-times" title="Remove"></i></a>
									</li>
									<li>
										<a class="cart-mini-lft" href="product.php"><img src="images/shop/pro-2.jpg" alt=""></a>
										<div class="cart-body">
											<a href="product.php">Fashion Wear White</a>
											<div class="price">$50</div>
										</div>
										<a class="remove" href="#"><i class="fa fa-times" title="Remove"></i></a>
									</li>
									<li>
										<a class="cart-mini-lft" href="product.php"><img src="images/shop/pro-3.jpg" alt=""></a>
										<div class="cart-body">
											<a href="product.php">Ultimate Fashion</a>
											<div class="price">$220</div>
										</div>
										<a class="remove" href="#"><i class="fa fa-times" title="Remove"></i></a>
									</li>
								</ul>
								<div class="mini-cart-total">
									<div class="clearfix">
										<div class="left">Sub-Total:</div>
										<div class="right">$230.00</div>
									</div>
									<div class="clearfix">
										<div class="left">Tax (-10.00):</div>
										<div class="right">$12.05</div>
									</div>
									<div class="clearfix">
									</div>
									<div class="clearfix">
										<div class="left">Total:</div>
										<div class="right">$200.20</div>
									</div>
								</div>
								<div class="checkout">
									<a class="btn" href="cart.php">View Cart</a><a class="btn" href="check-out.php">Checkout</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<div class="sectionWrapper">
		<div class="container">
			<div class="steps">
				<ul>
					<li><span>1 - Shopping cart</span></li>
					<li><span>2 - Delivery Address</span></li>
					<li><span>3 - Payment Method</span></li>
					<li class="selected"><span>4 - Complete Payment</span></li>
				</ul>
			</div>
			<style type="text/css">
				.success {
				border: 1px solid;
				margin: 10px 0px;
				padding:15px 10px 15px 50px;
				background-repeat: no-repeat;
				background-position: 10px center;
				}
				.success {
				color: #4F8A10;
				background-color: #DFF2BF;
				background-image:url(<?php echo e(url('image-message/success.png')); ?>);
				}
			</style>
			<div class="row">
				
					<div class="cell-4"></div>
					<div class="cell-4">
			
	                        
							<?php if(session('status')): ?>
							<div class="success"><?php echo e(session('status')); ?></div>
							<?php endif; ?>


							<div class="row" style="text-align: center;">
								<a href="<?php echo e(url('order-item')); ?>" class="btn btn-info">Continue Order</a>
								<a href="<?php echo e(url('user/order/all')); ?>" class="btn btn-success">My Order</a>
								<a href="<?php echo e(url('user/dashboard')); ?>" class="btn btn-danger">Dashboard</a>
							</div>

	                </div>



				
			</div>
			<div class="clearfix"></div>

		</div>
	</div>
	
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
    <link rel="stylesheet" href="<?php echo e(url('front-theme/css/custom.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(url('front-theme/calendar/css/pikaday.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(url('front-theme/calendar/timepicker.min.css')); ?>">
    <script src='https://www.google.com/recaptcha/api.js'></script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('slider-js'); ?>
    <script src="<?php echo e(url('front-theme/calendar/pikaday.js')); ?>"></script>

    <script>

        var picker = new Pikaday(
                {
                    field: document.getElementById('datepicker'),
                    firstDay: 1,
                    minDate: new Date(),
                    maxDate: new Date(2020, 12, 31),
                    yearRange: [2000, 2020]
                });

    </script>
    <script src="<?php echo e(url('front-theme/calendar/timepicker.min.js')); ?>"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function (event)
        {
            timepicker.load({
                interval: 15,
                defaultHour: 8
            });
        });
    </script>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('frontend.layout.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>