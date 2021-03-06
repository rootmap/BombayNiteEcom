<div class="top-bar" data-sticky="true">
    <div class="container">
        <div class="row">
            <div class="cell-5">
                <ul>
                    
                    <li><a href="skype:<?php echo e($ContactDetail->contact_phone); ?>?call"><i class="fa fa-phone"></i> Call Us: <b> <?php echo e($ContactDetail->contact_phone); ?> </b></a></li>
                </ul>
            </div>
            <div class="cell-7 right-bar">
                <ul class="right">
                    <li class="top-shopping-cart-short"
                    <?php if(isset($tax)): ?>
                        data-tax-type="1" data-tax-amount="<?php echo e($tax->tax_amount); ?>"  
                    <?php else: ?>
                        data-tax-type="0" data-tax-amount="0%"  
                    <?php endif; ?>

                        <?php if(isset($common)): ?>
                            data-disamount-limit="<?php echo e($common->minimum_amount); ?>" data-discount="<?php echo e($common->discount_amount); ?>"
                        <?php elseif(isset($colndel)): ?>
                            data-disamount-limit="<?php echo e($colndel->minimum_amount); ?>" data-discount="<?php echo e($colndel->discount_amount); ?>"
                        <?php else: ?>
                            data-disamount-limit="0" data-discount="0%"
                        <?php endif; ?>
                    >
                        <a href="<?php echo e(url('shopping-cart')); ?>"><i class="fa fa-shopping-cart"></i>
                            0 item(s) - £0.00
                        </a>
                    </li>
                    <!-- <li><a href="siteMap.php"><i class="fa fa-sitemap"></i>Site Map</a></li> -->
                    <?php if(Auth::check()): ?>
                        <li><a href="<?php echo e(url('user/dashboard')); ?>"><i class="fa fa-user"></i>Profile : <?php echo e(Auth::user()->name); ?></a></li>
                        <li><a class="logoutFront" href="javascript:void(0);">
                                <i class="fa fa-lock"></i> Logout
                            </a>
                        </li>
                        <div style=" height: 0px; width: 0px; opacity: 0px;">
                            <form method="post" style="opacity: 0px;" id="logoutFront" action="<?php echo e(url('logout')); ?>" >
                                <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                                <button type="submit" style="height: 0px; width: 0px; background: none; opacity: 0px;" class="btn"></button>
                            </form> 
                        </div>
                    <?php else: ?>
                        <li><a href="<?php echo e(url('new-account')); ?>"><i class="fa fa-user"></i>Register</a></li>
                        <li><a href="<?php echo e(url('user-login')); ?>"><i class="fa fa-unlock-alt"></i> Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>