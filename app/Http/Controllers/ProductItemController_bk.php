<?php

namespace App\Http\Controllers;
use App\category;
use App\ProductItem;
use App\PizzaSize;
use App\PizzaFlabour;
use App\ProductOneSubLevel;
use App\ProductTwoSubLevel;
use App\Product;
use App\PaypalSetting;
use App\Discount;
use App\Tax;
use App\SubCategory;
use Session;
use App\Cart;
use App\Customer;
use App\DeliveryAddress;
use App\OrderInfo;
use Auth;
use App\Orders;
use App\OrdersItem;
use Illuminate\Http\Request;

//paypal lib
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
//paypal lib 

class ProductItemController extends Controller
{
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    private $moduleName="Sales";
    private $sdc;
    private $_api_content;
    public function __construct(){ 
        //$paypal_conf['client_id']
        //$paypal_conf['secret']
        $this->sdc = new MenuPageController(); 
        $paypal_conf=\Config::get('paypal');
        $this->_api_content=new ApiContext(new OAuthTokenCredential(
            $this->sdc->paypal_client_id,
            $this->sdc->paypal_secret
        ));

        $this->_api_content->setConfig($paypal_conf['settings']);
        
    }

    public function index()
    {
        $category=$this->categoryProduct();
        //$product=Product::all();
        $defultReturn=['category'=>$category];

        if($this->checkCommonDiscount())
        {
            $defultReturn=array_merge($defultReturn,['common'=>$this->checkCommonDiscount()]);
        }

        if($this->checkColNDelDiscount())
        {
            $defultReturn=array_merge($defultReturn,['colndel'=>$this->checkColNDelDiscount()]);
        }        

        if($this->checkTax())
        {
            $defultReturn=array_merge($defultReturn,['tax'=>$this->checkTax()]);
        }

        $orderINfo=OrderInfo::orderBy('id','DESC')->first();
        $defultReturn=array_merge($defultReturn,['orderINfoText'=>$orderINfo]);
        //dd($defultReturn);

        return view('frontend.pages.product.index',$defultReturn);
    }

    public function makePayment(Request $request)
    {

        

        $cart = Session::has('cart') ? Session::get('cart') : null;
        $defultReturn=['cart'=>$cart];
        if($this->checkCommonDiscount())
        {
            $defultReturn=array_merge($defultReturn,['common'=>$this->checkCommonDiscount()]);
        }

        if($this->checkColNDelDiscount())
        {
            $defultReturn=array_merge($defultReturn,['colndel'=>$this->checkColNDelDiscount()]);
        }        

        if($this->checkTax())
        {
            $defultReturn=array_merge($defultReturn,['tax'=>$this->checkTax()]);
        }

        //dd(csrf_token());

         
        $totalPrice=$defultReturn['cart']->totalPrice;

        if($totalPrice<1)
        {
            return redirect('order-item')->with('error', 'Failed to process order, Please try again.');
        }

        
        $tax_title="Tax";
        if(isset($defultReturn['tax']->tax_amount))
        {
            $tax_amount=$defultReturn['tax']->tax_amount;
        }
        else
        {
            $tax_amount=0;
        }
        
        if(isset($defultReturn['tax']))
        {
            if (strpos($defultReturn['tax']->tax_amount, '%') !== false) {
               $tax_title="Tax (".$defultReturn['tax']->tax_amount.")";
               $tax_amount=(($totalPrice*$defultReturn['tax']->tax_amount)/100);
            }
        }

        $discount_title="Discount";
        $discount_amount=0;

        if(isset($defultReturn['colndel']))
        {

            if($totalPrice > $defultReturn['colndel']->minimum_amount)
            {

                $recType=$defultReturn['cart']->rec;
            
                if(in_array($defultReturn['colndel']->discount_option,array("Delivery","Collection")))
                {
                   // echo $defultReturn['colndel']->discount_option.";".$recType; die();
                    $discount_title="Discount";
                    $discount_amount=0;
                    if($recType=="Collect")
                    {
                        $discount_title="Discount on Collection";
                        $discount_amount=$defultReturn['colndel']->discount_amount;
                        if (strpos($defultReturn['colndel']->discount_amount, '%') !== false) {

                           $discount_title="Discount (".$defultReturn['colndel']->discount_amount.")";
                           $discount_amount=(($totalPrice*$defultReturn['colndel']->discount_amount)/100);
                        }
                    }
                    elseif($recType=="Delivery")
                    {
                        $discount_title="Discount on Delivery";
                        $discount_amount=$defultReturn['colndel']->discount_amount;
                        if (strpos($defultReturn['colndel']->discount_amount, '%') !== false) {
                           $discount_title="Discount (".$defultReturn['colndel']->discount_amount.")";
                           $discount_amount=(($totalPrice*$defultReturn['colndel']->discount_amount)/100);
                        }
                    }
                }
                else
                {
                    $discount_title="Discount";
                    $discount_amount=$defultReturn['colndel']->discount_amount;
                    if (strpos($defultReturn['colndel']->discount_amount, '%') !== false) {
                       $discount_title="Discount (".$defultReturn['colndel']->discount_amount.")";
                       $discount_amount=(($totalPrice*$defultReturn['colndel']->discount_amount)/100);
                    }
                }    
            }
                
        }
        
       // dd($defultReturn['cart']->items);
        $delivery = new DeliveryAddress;
        $delivery->customer_id = Auth::user()->id;
        $delivery->token = csrf_token();
        $delivery->first_name = $defultReturn['cart']->deliveryDetail["name"];
        $delivery->address = $defultReturn['cart']->deliveryDetail["address"];
        $delivery->mobile_phone =$defultReturn['cart']->deliveryDetail["phone"];
        $delivery->email =$defultReturn['cart']->deliveryDetail["email"];
        $delivery->zip_code =$defultReturn['cart']->deliveryDetail["zipcode"];
        $delivery->save();

        //dd($delivery);

        $pro = new Orders;
        $pro->tracking = csrf_token();
        $pro->cid = Auth::user()->id;
        $pro->invoice_date = date('Y-m-d');
        $pro->due_date = date('Y-m-d');
        $pro->order_status = "Pending";
        $pro->tax_title = $tax_title;
        $pro->tax_amount = $tax_amount;
        $pro->discount_title = $discount_title;
        $pro->discount_amount = $discount_amount;
        $pro->order_total = $totalPrice;
        $pro->order_type = $defultReturn['cart']->rec;
        $pro->order_online = 1;
        $pro->delivery_asap = $defultReturn['cart']->deliveryDetail["asap"];
        $pro->delivery_date = $defultReturn['cart']->deliveryDetail["delivery_date"];
        $pro->delivery_time = $defultReturn['cart']->deliveryDetail["delivery_time"];
        $pro->delivery_note = $defultReturn['cart']->deliveryDetail["delivery_note"];
        $pro->cart_json = serialize($defultReturn['cart']);
        $pro->save();

        $order_id = $pro->id;


        if(count($defultReturn['cart']->items)>0)
        {
            foreach($defultReturn['cart']->items as $itm):
                //dd($itm);

                $protag = new OrdersItem();
                $protag->pid = $itm['item']->id;
                $protag->order_id = $order_id;
                $protag->tracking = csrf_token();
                $protag->quantity = $itm["qty"];
                $protag->unit_price =$itm['item']->price;
                $protag->tax_rate = 0;
                $protag->tax_amount = 0;
                $protag->row_total = $itm["price"];
                $protag->cart_json = serialize($itm);
                $protag->save();

            endforeach;
        }
        else
        {
            return redirect('order-item')->with('error', 'Failed to process order, Please try again.');
        }

        $orderDetailSql=OrdersItem::leftJoin('products','orders_items.pid','=','products.id')
                                    ->leftJoin('sub_categories','products.scid','=','sub_categories.id')
                                    ->where('order_id',$order_id)
                                    ->select('orders_items.id','orders_items.quantity','orders_items.row_total','products.name as product_name','sub_categories.name as sc_name','orders_items.cart_json as row_json')
                                    ->orderBy('products.cid','ASC')
                                    ->get();
        //dd($orderDetailSql);

        $siteMessage='<h2><strong><span style="color: #ff9900;">Receipt</span></strong></h2>
                        <table style="border: 2px solid #000000; width: 436px;">
                        <tbody>
                        <tr style="height: 32px;">
                        <td style="width: 184px; height: 32px;">Order Time</td>
                        <td style="width: 244px; height: 32px;">&nbsp;'.date('dS M Y, h:i A').'</td>
                        </tr>
                        <tr style="height: 46px;">
                        <td style="width: 428px; height: 46px;" colspan="2">
                        <h3 style="display: block; width: 80%; border-bottom: 3px #000 solid;">
                            <strong>Customer Detail</strong>
                        </h3>
                        </td>
                        </tr>
                        <tr style="height: 18px;">
                        <td style="width: 184px; height: 18px;">&nbsp;Name</td>
                        <td style="width: 244px; height: 18px;">'.$delivery->first_name.'</td>
                        </tr>
                        <tr style="height: 18px;">
                        <td style="width: 184px; height: 18px;">&nbsp;Order Type&nbsp;</td>
                        <td style="width: 244px; height: 18px;">'.$defultReturn['cart']->rec.'</td>
                        </tr>';
        if($request->payment_method=="Cash")
        {
            $siteMessage .='<tr style="height: 18px;">
                        <td style="width: 184px; height: 18px;">&nbsp;Payment Type&nbsp;</td>
                        <td style="width: 244px; height: 18px;">Cash</td>
                        </tr>';
        }
        elseif($request->payment_method=="Paypal")
        {
            $siteMessage .='<tr style="height: 18px;">
                                <td style="width: 184px; height: 18px;">&nbsp;Payment Type&nbsp;</td>
                                <td style="width: 244px; height: 18px;">'.$request->payment_method.'</td>
                            </tr>
                            <tr style="height: 18px;">
                                <td style="width: 184px; height: 18px;">&nbsp;Payment Status&nbsp;</td>
                                <td style="width: 244px; height: 18px;">Pending</td>
                            </tr>';
        }
        


        if($defultReturn['cart']->deliveryDetail["asap"]==1)
        {
            $siteMessage .='<tr style="height: 18px;">
                        <td style="width: 184px; height: 18px;">&nbsp;Deliver Date&nbsp;</td>
                        <td style="width: 244px; height: 18px;">ASAP</td>
                        </tr>';
        }
        else
        {
            $siteMessage .='<tr style="height: 18px;">
                        <td style="width: 184px; height: 18px;">&nbsp;Deliver Date &amp; Time&nbsp;</td>
                        <td style="width: 244px; height: 18px;">'.$defultReturn['cart']->deliveryDetail["delivery_date"].' - '.$defultReturn['cart']->deliveryDetail["delivery_time"].'</td>
                        </tr>';
        }

        if(!empty($defultReturn['cart']->deliveryDetail["delivery_note"]))
        {
            $siteMessage .='<tr style="height: 18px;">
                        <td style="width: 184px; height: 18px;">&nbsp;Deliver Note&nbsp;</td>
                        <td style="width: 244px; height: 18px;">'.$defultReturn['cart']->deliveryDetail["delivery_note"].'</td>
                        </tr>';
        }
        

        $siteMessage .='<tr style="height: 18px;">
                        <td style="width: 184px; height: 18px;">&nbsp;Address</td>
                        <td style="width: 244px; height: 18px;">'.$delivery->address.'</td>
                        </tr>
                        <tr style="height: 18px;">
                        <td style="width: 184px; height: 18px;">&nbsp;Phone</td>
                        <td style="width: 244px; height: 18px;">'.$delivery->mobile_phone.'</td>
                        </tr>
                        </tr>
                        <tr style="height: 18px;">
                        <td style="width: 184px; height: 18px;">&nbsp;Email</td>
                        <td style="width: 244px; height: 18px;">'.$delivery->email.'</td>
                        </tr>
                        <tr style="height: 46px;">
                        <td style="width: 428px; height: 46px;" colspan="2">
                        <h3 style="display: block; width: 100%; border-bottom: 1px #000 dashed;">
                            <strong>Order Detail</strong>
                        </h3>
                        </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <table align="center" width="100%">
                                    <thead style="border-bottom: 1px #000 solid;">
                                        <tr>
                                            <th align="left">Quantity</th>
                                            <th align="left">Product Name</th>
                                            <th align="right">Price</th>
                                        </tr>
                                        <tr>
                                            <th colspan="3">
                                                <span style="display: block; height:1px; width: 100%; border-bottom: 1px #000 solid;">&nbsp;
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>';
                        
                        $attachmentProduct='';
                        if(count($orderDetailSql)>0)
                        {
                            foreach($orderDetailSql as $itm):

                                $sc_nn='';
                                if(!empty($itm->sc_name))
                                {
                                    $sc_nn='['.$itm->sc_name.'] ';
                                }

                                $rowJsonUnseri=unserialize($itm->row_json);
                                //echo $rowJsonUnseri['snd_item']; 
                                $proNameFromRow='';
                                if(isset($rowJsonUnseri['snd_item']))
                                {
                                    if(count($rowJsonUnseri['snd_item'])>0)
                                    {
                                        foreach($rowJsonUnseri['snd_item'] as $snd)
                                        {
                                            $proNameFromRow .='<br /> + '.$snd['item']->name.' ('.$snd['qty'].' X&nbsp;&#163;'.$snd['item']->price.')';
                                        }
                                    }
                                }
                                elseif(isset($rowJsonUnseri['execArrayData']))
                                {
                                    $proNameFromRow .='<b>'.$itm->product_name.' </b>';

                                    if(count($rowJsonUnseri['execArrayData'])>0)
                                    {
                                        foreach($rowJsonUnseri['execArrayData'] as $snd)
                                        {
                                            $proNameFromRow .='<br /> + '.$snd;
                                        }
                                    }
                                }
                                else
                                {
                                    $proNameFromRow=$itm->product_name;
                                }

                        //dd($rowJsonUnseri);
                        //die();
                        $siteMessage .='  <tr>
                                            <td valign="top">'.intval($itm->quantity).'</td>
                                            <td>'.$sc_nn;
                        
                        $siteMessage .=$proNameFromRow;

                        $siteMessage .='</td>
                                            <td align="right" valign="top">&#163;'.number_format($itm->row_total,2).'</td>
                                        </tr>';

                            $attachmentProduct .='  <tr>
                                            <td valign="top">'.intval($itm->quantity).'</td>
                                            <td>'.$sc_nn.''.$proNameFromRow.'</td>
                                            <td align="right" valign="top">&#163;'.number_format($itm->row_total,2).'</td>
                                        </tr>';

                            endforeach;
                        }
                        
                        $siteMessage .='  </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3">
                                                <span style="display: block; height:1px; width: 100%; border-bottom: 2px #000 solid;">&nbsp;
                                                </span>
                                            </th>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td>Sub Total</td>
                                            <td align="right">&#163;'.number_format($totalPrice,2).'</td>
                                        </tr>';


                    if($tax_amount>0)
                    {
                        $siteMessage .='      <tr>
                                            <td></td>
                                            <td>'.$tax_title.'</td>
                                            <td align="right">&#163;'.number_format($tax_amount,2).'</td>
                                        </tr>';

                    }
                    
                    if($discount_amount>0)
                    {
                        $siteMessage .='<tr>
                                            <td></td>
                                            <td>'.$discount_title.'</td>
                                            <td align="right">&#163;'.number_format($discount_amount,2).'</td>
                                        </tr>';
                    }
                    

                    $siteMessage .='<tr>
                                            <th colspan="3">
                                                <span style="display: block; width: 100%; border-bottom: 1px #000 solid;">&nbsp;
                                                </span>
                                            </th>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td>Net Payable</td>
                                            <td align="right">&#163;'.number_format((($totalPrice+$tax_amount)-$discount_amount),2).'</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </td>
                        </tr>
                        </tbody>
                        </table>';
          $siteRegards='<p>Kind Regards, '.$this->sdc->SiteName.'&nbsp;</p>
                        <p>&nbsp;</p>';
          $totalInvoiceAmount=number_format((($totalPrice+$tax_amount)-$discount_amount),2);

                        //echo $siteMessage; die();
       
       if($request->payment_method=="Cash")
       {
            $ct=$this->sdc->ContactDetail();
            //dd($ct);
            $attachmentSlip='';
            $attachmentSlip .='<table align="left" width="25%" style="font-family: Ubuntu,sans-serif;">
                                    <tbody>
                                        <tr>
                                            <td align="center"><h3><b>'.$ct->contact_title.'</b></h3></td>
                                        </tr>
                                        <tr>
                                            <td align="left">------------------------------------------------------</td>
                                        </tr>
                                        <tr>
                                            <td align="left">
                                                <table align="left" width="100%">
                                                    <tbody>
                                                        <tr>
                                                            <td width="60%"><font size="4">'.$delivery->first_name.'</font></td>
                                                            <td align="right"><b><font size="4">'.$defultReturn['cart']->rec.'<br />';
                                    if($defultReturn['cart']->deliveryDetail["asap"]==1)
                                    {
                                        $attachmentSlip .='ASAP';
                                    }
                                    else
                                    {
                                        $attachmentSlip .=$defultReturn['cart']->deliveryDetail["delivery_date"].' - '.$defultReturn['cart']->deliveryDetail["delivery_time"];
                                    }


                                    $attachmentSlip .='</font></b></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left">------------------------------------------------------</td>
                                        </tr>
                                        <tr>
                                            <td align="left">
                                                <table align="left" width="100%">
                                                    <tbody>
                                                        <tr>
                                                            <td valign="top" align="left"  width="40%"><b>'.$delivery->mobile_phone.'</b></td>
                                                            <td valign="top" align="right">Cash</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="font-size:15px;"><br />'.$delivery->address.'</td>
                                        </tr>
                                        <tr>
                                            <td align="left">------------------------------------------------------</td>
                                        </tr>

                                        <tr>
                                            <td align="left">
                                                <table align="left" width="100%">
                                                    <tbody style="font-size:15px;">
                                                        '.$attachmentProduct.'
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left"><br /><br /></td>
                                        </tr>
                                        <tr>
                                            <td align="left">
                                                <table align="left" width="100%">
                                                    <tbody>';
                                    if($tax_amount>0)
                                    {

                                      $attachmentSlip .='<tr>
                                                            <td valign="top" align="right"  width="50%"><h5><b>'.$tax_title.'</b></h5></td>
                                                            <td valign="top" align="left"> <h5>: &#163;'.number_format($tax_amount,2).'</h5></td>
                                                        </tr>';

                                    }

                                    if($discount_amount>0)
                                    {
                                        $attachmentSlip .='<tr>
                                                            <td valign="top" align="right"  width="50%"><h5><b>'.$discount_title.'</b></h5></td>
                                                            <td valign="top" align="left"> <h5>: &#163;'.number_format($discount_amount,2).'</h5></td>
                                                        </tr>';
                                    }

                                    $attachmentSlip .='<tr>
                                                            <td valign="top" align="right"  width="50%"><h4><b>Amount Due</b></h4></td>
                                                            <td valign="top" align="left"> <h4>: &#163;'.$totalInvoiceAmount.'</h4></td>
                                                        </tr>';
                                    
                                 $attachmentSlip .='</tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left"><br /><br /></td>
                                        </tr>

                                        <tr>
                                            <td align="center"><b>Thank You</b></td>
                                        </tr>
                                        <tr>
                                            <td align="left"><br /></td>
                                        </tr>
                                        <tr>
                                            <td align="center">'.date('d/m/Y H:i A').'</td>
                                        </tr>
                                        <tr>
                                            <td align="left"><br /><br /><br /><br /><br /><br /></td>
                                        </tr>
                                        <tr>
                                            <td align="center"><h3><b>'.$ct->contact_title.'</b></h3></td>
                                        </tr>
                                        <tr>
                                            <td align="center">'.$ct->contact_address.'</td>
                                        </tr>
                                        <tr>
                                            <td align="center">'.$ct->contact_phone.'</td>
                                        </tr>
                                        <tr>
                                            <td align="center">Vat No : 253864581</td>
                                        </tr>
                                        <tr>
                                            <td align="left"><br /><br /></td>
                                        </tr>
                                        <tr>
                                            <td align="left">
                                                <table align="left" width="100%">
                                                    <tbody>';

                                    $attachmentSlip .='<tr>
                                                            <td valign="top" align="right"  width="50%"><h4><b>Amount Due</b></h4></td>
                                                            <td valign="top" align="left"> <h4>: &#163;'.$totalInvoiceAmount.'</h4></td>
                                                        </tr>';
                                    
                                 $attachmentSlip .='</tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </tbody>
                               </table>';

           // $pos_slip_name=$this->sdc->PDFLayout('POS SLIP',$attachmentSlip,1);

            //echo secure_asset('invoice/'.$pos_slip_name); die();
            //exit();
            $this->sdc->initMail(
            $delivery->email,
            $this->sdc->order_admin_email,
            'Online Order Receipt - '.$this->sdc->SiteName,
            $this->sdc->TableUserOrder($delivery->first_name,$this->sdc->SiteName).'<br>'.$siteMessage.$siteRegards);

            $this->sdc->initMail(
            $this->sdc->order_admin_email,
            $delivery->email,
            'Admin Order Receipt - '.$this->sdc->SiteName,
            $this->sdc->TableAdminOrder($this->sdc->SiteName).'<br>'.$siteMessage,'babz86@hotmail.co.uk');
        }
        
        //echo "Under Maintainence";
        //die();

        if($request->payment_method=="Cash")
        {
            $Ncart = new Cart($cart);
            $Ncart->ClearCart();

            $request->session()->put('cart', $Ncart);
            
            return redirect('complete-payment')->with('status', 'Order placed successfully, Your order will confirmed soon.!');
        }
        elseif($request->payment_method=="Paypal")
        {
            $payer = new Payer();
            $payer->setPaymentMethod("paypal");

            $item1 = new Item();
            $item1->setName('Invoice - '.$order_id)
                ->setCurrency('USD')
                ->setQuantity(1)
                ->setSku($order_id) // Similar to `item_number` in Classic API
                ->setPrice($totalInvoiceAmount);
            


            $itemList = new ItemList();
            $itemList->setItems(array($item1));   

            $details = new Details();
            $details->setSubtotal($totalInvoiceAmount); 

            $amount = new Amount();
            $amount->setCurrency("USD")
                ->setTotal($totalInvoiceAmount)
                ->setDetails($details);   
            
            $transaction = new Transaction();
            $transaction->setAmount($amount)
                ->setItemList($itemList)
                ->setDescription("Invoice Payment description")
                ->setInvoiceNumber(uniqid()); 

            //$baseUrl = url();
            $redirectUrls = new RedirectUrls();
            $redirectUrls->setReturnUrl(url('invoice/payment/paypal/'.$order_id.'/success'))
                ->setCancelUrl(url('invoice/payment/paypal/'.$order_id.'/cancel'));

            $payment = new Payment();
            $payment->setIntent("sale")
                ->setPayer($payer)
                ->setRedirectUrls($redirectUrls)
                ->setTransactions(array($transaction));


            try {
                $payment->create($this->_api_content);
            } catch (\PayPal\Exception\PPConnectionException $ex) {

                dd($ex);
                if(\Config::get('app.debug'))
                {
                    \Session::put('error','Connection has timeout.!!!!, Please try again.');
                    return redirect('payment-method');
                }
                else
                {
                    \Session::put('error','Something went wrong.!!!!, Please try again.');
                    return redirect('payment-method');
                }
            }


            foreach($payment->getLinks() as $link){
                if($link->getRel()=='approval_url')
                {
                    $redirect_url=$link->getHref();
                    break;
                }
            }

            \Session::put('paypal_payment_id',$payment->getId());

            if(isset($redirect_url))
            {
                return redirect($redirect_url);
            }

            \Session::put('error','Unknown error occured, Please try again.!!!!!');
            return redirect('payment-method');
            
        }
        else
        {
            return redirect('payment-method')->with('error', 'Failed, Please try again.!!!!');
        }
        

    }

    public function getPOSPaymentStatusPaypal(Request $request,$invoice_id=0,$status='fahad')
    {
        $cart = Session::has('cart') ? Session::get('cart') : null;
        $defultReturn=['cart'=>$cart];
        if($this->checkCommonDiscount())
        {
            $defultReturn=array_merge($defultReturn,['common'=>$this->checkCommonDiscount()]);
        }

        if($this->checkColNDelDiscount())
        {
            $defultReturn=array_merge($defultReturn,['colndel'=>$this->checkColNDelDiscount()]);
        }        

        if($this->checkTax())
        {
            $defultReturn=array_merge($defultReturn,['tax'=>$this->checkTax()]);
        }

        //dd(csrf_token());

         
        $totalPrice=$defultReturn['cart']->totalPrice;

        if($totalPrice<1)
        {
            return redirect('order-item')->with('error', 'Failed to process order, Please try again.');
        }

        
        $tax_title="Tax";
        if(isset($defultReturn['tax']->tax_amount))
        {
            $tax_amount=$defultReturn['tax']->tax_amount;
        }
        else
        {
            $tax_amount=0;
        }
        
        if(isset($defultReturn['tax']))
        {
            if (strpos($defultReturn['tax']->tax_amount, '%') !== false) {
               $tax_title="Tax (".$defultReturn['tax']->tax_amount.")";
               $tax_amount=(($totalPrice*$defultReturn['tax']->tax_amount)/100);
            }
        }

        $discount_title="Discount";
        $discount_amount=0;

        if(isset($defultReturn['colndel']))
        {

            if($totalPrice > $defultReturn['colndel']->minimum_amount)
            {

                $recType=$defultReturn['cart']->rec;
            
                if(in_array($defultReturn['colndel']->discount_option,array("Delivery","Collection")))
                {
                   // echo $defultReturn['colndel']->discount_option.";".$recType; die();
                    $discount_title="Discount";
                    $discount_amount=0;
                    if($recType=="Collect")
                    {
                        $discount_title="Discount on Collection";
                        $discount_amount=$defultReturn['colndel']->discount_amount;
                        if (strpos($defultReturn['colndel']->discount_amount, '%') !== false) {

                           $discount_title="Discount (".$defultReturn['colndel']->discount_amount.")";
                           $discount_amount=(($totalPrice*$defultReturn['colndel']->discount_amount)/100);
                        }
                    }
                    elseif($recType=="Delivery")
                    {
                        $discount_title="Discount on Delivery";
                        $discount_amount=$defultReturn['colndel']->discount_amount;
                        if (strpos($defultReturn['colndel']->discount_amount, '%') !== false) {
                           $discount_title="Discount (".$defultReturn['colndel']->discount_amount.")";
                           $discount_amount=(($totalPrice*$defultReturn['colndel']->discount_amount)/100);
                        }
                    }
                }
                else
                {
                    $discount_title="Discount";
                    $discount_amount=$defultReturn['colndel']->discount_amount;
                    if (strpos($defultReturn['colndel']->discount_amount, '%') !== false) {
                       $discount_title="Discount (".$defultReturn['colndel']->discount_amount.")";
                       $discount_amount=(($totalPrice*$defultReturn['colndel']->discount_amount)/100);
                    }
                }    
            }
                
        }
        
       // dd($defultReturn['cart']->items);
        /*$delivery = new DeliveryAddress;
        $delivery->customer_id = Auth::user()->id;
        $delivery->token = csrf_token();
        $delivery->first_name = $defultReturn['cart']->deliveryDetail["name"];
        $delivery->address = $defultReturn['cart']->deliveryDetail["address"];
        $delivery->mobile_phone =$defultReturn['cart']->deliveryDetail["phone"];
        $delivery->email =$defultReturn['cart']->deliveryDetail["email"];
        $delivery->zip_code =$defultReturn['cart']->deliveryDetail["zipcode"];
        $delivery->save();*/

        //dd($delivery);

        /*$pro = new Orders;
        $pro->tracking = csrf_token();
        $pro->cid = Auth::user()->id;
        $pro->invoice_date = date('Y-m-d');
        $pro->due_date = date('Y-m-d');
        $pro->order_status = "Pending";
        $pro->tax_title = $tax_title;
        $pro->tax_amount = $tax_amount;
        $pro->discount_title = $discount_title;
        $pro->discount_amount = $discount_amount;
        $pro->order_total = $totalPrice;
        $pro->order_type = $defultReturn['cart']->rec;
        $pro->order_online = 1;
        $pro->delivery_asap = $defultReturn['cart']->deliveryDetail["asap"];
        $pro->delivery_date = $defultReturn['cart']->deliveryDetail["delivery_date"];
        $pro->delivery_time = $defultReturn['cart']->deliveryDetail["delivery_time"];
        $pro->delivery_note = $defultReturn['cart']->deliveryDetail["delivery_note"];
        $pro->save();*/

        $order_id = $invoice_id;


        /*if(count($defultReturn['cart']->items)>0)
        {
            foreach($defultReturn['cart']->items as $itm):
                //dd($itm);

                $protag = new OrdersItem();
                $protag->pid = $itm['item']->id;
                $protag->order_id = $order_id;
                $protag->tracking = csrf_token();
                $protag->quantity = $itm["qty"];
                $protag->unit_price =$itm['item']->price;
                $protag->tax_rate = 0;
                $protag->tax_amount = 0;
                $protag->row_total = $itm["price"];
                $protag->save();

            endforeach;
        }
        else
        {
            return redirect('order-item')->with('error', 'Failed to process order, Please try again.');
        }*/


            //dd($invoice_id);
            $payment_id=\Session::get('paypal_payment_id');
                        \Session::forget('paypal_payment_id');

            if(empty($request->PayerID) || empty($request->token))
            {
                \Session::put('error','Failed token mismatch, Please tryagain');
                return redirect('payment-method');
            }

            $payment=Payment::get($payment_id,$this->_api_content);
            $excution=new PaymentExecution();
            $excution->setPayerId($request->PayerID);

            $result=$payment->execute($excution,$this->_api_content);
            //dd($invoice_id);
            if($result->getState()=='approved')
            {
                $trans=$result->getTransactions();
                //$amtAr=$trans->getAmount();
                $amountPaid=$trans[0]->getAmount()->getTotal();
                //dd($amountPaid);

                $orderTab=Orders::find($order_id);
                $orderTab->order_status="Paid";
                $orderTab->save();


                $orderDetailSql=OrdersItem::leftJoin('products','orders_items.pid','=','products.id')
                                    ->leftJoin('sub_categories','products.scid','=','sub_categories.id')
                                    ->where('order_id',$order_id)
                                    ->select('orders_items.id','orders_items.quantity','orders_items.row_total','products.name as product_name','sub_categories.name as sc_name')
                                    ->orderBy('products.cid','ASC')
                                    ->get();
                //dd($orderDetailSql);



                $siteMessage='<h2><strong><span style="color: #ff9900;">Receipt</span></strong></h2>
                                <table style="border: 2px solid #000000; width: 436px;">
                                <tbody>
                                <tr style="height: 32px;">
                                <td style="width: 184px; height: 32px;">Order Time</td>
                                <td style="width: 244px; height: 32px;">&nbsp;'.date('dS M Y, h:i A').'</td>
                                </tr>
                                <tr style="height: 46px;">
                                <td style="width: 428px; height: 46px;" colspan="2">
                                <h3 style="display: block; width: 80%; border-bottom: 3px #000 solid;">
                                    <strong>Customer Detail</strong>
                                </h3>
                                </td>
                                </tr>
                                <tr style="height: 18px;">
                                <td style="width: 184px; height: 18px;">&nbsp;Name</td>
                                <td style="width: 244px; height: 18px;">'.$defultReturn['cart']->deliveryDetail["name"].'</td>
                                </tr>
                                <tr style="height: 18px;">
                                <td style="width: 184px; height: 18px;">&nbsp;Order Type&nbsp;</td>
                                <td style="width: 244px; height: 18px;">'.$defultReturn['cart']->rec.'</td>
                                </tr>';
                
                    $siteMessage .='<tr style="height: 18px;">
                                        <td style="width: 184px; height: 18px;">&nbsp;Payment Type&nbsp;</td>
                                        <td style="width: 244px; height: 18px;">Paypal</td>
                                    </tr>
                                    <tr style="height: 18px;">
                                        <td style="width: 184px; height: 18px;">&nbsp;Payment Status&nbsp;</td>
                                        <td style="width: 244px; height: 18px;">Paid</td>
                                    </tr>';
                
                


                if($defultReturn['cart']->deliveryDetail["asap"]==1)
                {
                    $siteMessage .='<tr style="height: 18px;">
                                <td style="width: 184px; height: 18px;">&nbsp;Deliver Date&nbsp;</td>
                                <td style="width: 244px; height: 18px;">ASAP</td>
                                </tr>';
                }
                else
                {
                    $siteMessage .='<tr style="height: 18px;">
                                <td style="width: 184px; height: 18px;">&nbsp;Deliver Date &amp; Time&nbsp;</td>
                                <td style="width: 244px; height: 18px;">'.$defultReturn['cart']->deliveryDetail["delivery_date"].' - '.$defultReturn['cart']->deliveryDetail["delivery_time"].'</td>
                                </tr>';
                }

                if(!empty($defultReturn['cart']->deliveryDetail["delivery_note"]))
                {
                    $siteMessage .='<tr style="height: 18px;">
                                <td style="width: 184px; height: 18px;">&nbsp;Deliver Note&nbsp;</td>
                                <td style="width: 244px; height: 18px;">'.$defultReturn['cart']->deliveryDetail["delivery_note"].'</td>
                                </tr>';
                }
                

                $siteMessage .='<tr style="height: 18px;">
                                <td style="width: 184px; height: 18px;">&nbsp;Address</td>
                                <td style="width: 244px; height: 18px;">'.$defultReturn['cart']->deliveryDetail["address"].'</td>
                                </tr>
                                <tr style="height: 18px;">
                                <td style="width: 184px; height: 18px;">&nbsp;Phone</td>
                                <td style="width: 244px; height: 18px;">'.$defultReturn['cart']->deliveryDetail["phone"].'</td>
                                </tr>
                                </tr>
                                <tr style="height: 18px;">
                                <td style="width: 184px; height: 18px;">&nbsp;Email</td>
                                <td style="width: 244px; height: 18px;">'.$defultReturn['cart']->deliveryDetail["email"].'</td>
                                </tr>
                                <tr style="height: 46px;">
                                <td style="width: 428px; height: 46px;" colspan="2">
                                <h3 style="display: block; width: 100%; border-bottom: 1px #000 dashed;">
                                    <strong>Order Detail</strong>
                                </h3>
                                </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <table align="center" width="100%">
                                            <thead style="border-bottom: 1px #000 solid;">
                                                <tr>
                                                    <th align="left">Quantity</th>
                                                    <th align="left">Product Name</th>
                                                    <th align="right">Price</th>
                                                </tr>
                                                <tr>
                                                    <th colspan="3">
                                                        <span style="display: block; height:1px; width: 100%; border-bottom: 1px #000 solid;">&nbsp;
                                                        </span>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>';
                                if(count($orderDetailSql)>0)
                                {
                                    foreach($orderDetailSql as $itm):

                                        $sc_nn='';
                                        if(!empty($itm->sc_name))
                                        {
                                            $sc_nn='['.$itm->sc_name.'] ';
                                        }

                                $siteMessage .='  <tr>
                                                    <td>'.intval($itm->quantity).'</td>
                                                    <td>'.$sc_nn.''.$itm->product_name.'</td>
                                                    <td align="right">&#163;'.number_format($itm->row_total,2).'</td>
                                                </tr>';
                                    endforeach;
                                }
                                
                                $siteMessage .='  </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th colspan="3">
                                                        <span style="display: block; height:1px; width: 100%; border-bottom: 2px #000 solid;">&nbsp;
                                                        </span>
                                                    </th>
                                                </tr>
                                                <tr>
                                                    <td></td>
                                                    <td>Sub Total</td>
                                                    <td align="right">&#163;'.number_format($totalPrice,2).'</td>
                                                </tr>';


                            if($tax_amount>0)
                            {
                                $siteMessage .='      <tr>
                                                    <td></td>
                                                    <td>'.$tax_title.'</td>
                                                    <td align="right">&#163;'.number_format($tax_amount,2).'</td>
                                                </tr>';

                            }
                            
                            $siteMessage .='<tr>
                                                    <td></td>
                                                    <td>'.$discount_title.'</td>
                                                    <td align="right">&#163;'.number_format($discount_amount,2).'</td>
                                                </tr>
                                                <tr>
                                                    <th colspan="3">
                                                        <span style="display: block; width: 100%; border-bottom: 1px #000 solid;">&nbsp;
                                                        </span>
                                                    </th>
                                                </tr>
                                                <tr>
                                                    <td></td>
                                                    <td>Net Payable</td>
                                                    <td align="right">&#163;'.number_format((($totalPrice+$tax_amount)-$discount_amount),2).'</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </td>
                                </tr>
                                </tbody>
                                </table>';
                  $siteRegards='<p>Kind Regards, '.$this->sdc->SiteName.'&nbsp;</p>
                                <p>&nbsp;</p>';
                  $totalInvoiceAmount=number_format((($totalPrice+$tax_amount)-$discount_amount),2);

                                //echo $siteMessage; die();
               
                $this->sdc->initMail(
                    $defultReturn['cart']->deliveryDetail["email"],
                    $this->sdc->order_admin_email,
                    'Online Order Receipt - '.$this->sdc->SiteName,
                    $this->sdc->TableUserOrder($defultReturn['cart']->deliveryDetail["name"],$this->sdc->SiteName).'<br>'.$siteMessage.$siteRegards);

                $this->sdc->initMail(
                    $this->sdc->order_admin_email,
                    $defultReturn['cart']->deliveryDetail["email"],
                    'Admin Order Receipt - '.$this->sdc->SiteName,
                    $this->sdc->TableAdminOrder($this->sdc->SiteName).'<br>'.$siteMessage);


                $cart = Session::has('cart') ? Session::get('cart') : null;
                $Ncart = new Cart($cart);
                $Ncart->ClearCart();
                $request->session()->put('cart', $Ncart);

                return redirect('complete-payment')->with('status', 'Order placed successfully, Your order will confirmed soon.!');
                die();
            }
            else
            {
                \Session::put('error','Payment Failed, Please tryagain');
               return redirect('payment-method'); die();
            }       

    }

    public function completePayment()
    {
        $category=$this->categoryProduct();
        //$product=Product::all();
        $defultReturn=['category'=>$category];

        if($this->checkCommonDiscount())
        {
            $defultReturn=array_merge($defultReturn,['common'=>$this->checkCommonDiscount()]);
        }

        if($this->checkColNDelDiscount())
        {
            $defultReturn=array_merge($defultReturn,['colndel'=>$this->checkColNDelDiscount()]);
        }        

        if($this->checkTax())
        {
            $defultReturn=array_merge($defultReturn,['tax'=>$this->checkTax()]);
        }

        $orderINfo=OrderInfo::orderBy('id','DESC')->first();
        $defultReturn=array_merge($defultReturn,['orderINfoText'=>$orderINfo]);
        //dd($defultReturn);

        return view('frontend.pages.checkout.complete-payment',$defultReturn);
    }

    public function userDashboard()
    {
        $category=$this->categoryProduct();
        //$product=Product::all();
        $defultReturn=['category'=>$category];

        if($this->checkCommonDiscount())
        {
            $defultReturn=array_merge($defultReturn,['common'=>$this->checkCommonDiscount()]);
        }

        if($this->checkColNDelDiscount())
        {
            $defultReturn=array_merge($defultReturn,['colndel'=>$this->checkColNDelDiscount()]);
        }        

        if($this->checkTax())
        {
            $defultReturn=array_merge($defultReturn,['tax'=>$this->checkTax()]);
        }

        $orderINfo=OrderInfo::orderBy('id','DESC')->first();
        $defultReturn=array_merge($defultReturn,['orderINfoText'=>$orderINfo]);
        //dd($defultReturn);

        return view('frontend.pages.user.dashboard',$defultReturn);
    }

    private function checkCommonDiscount()
    {
        $chk=Discount::where('discount_status','Active')
                     ->where('discount_option','Common')
                     ->count();
        if($chk>0)
        {
            $data=Discount::select(
                            'id',
                            'minimum_amount',
                            'discount_option',
                            'discount_type',
                            \DB::Raw("CASE WHEN discount_type='Fixed' THEN discount_amount 
                            ELSE CONCAT(discount_amount,'%') END as discount_amount"),
                            'message',
                            'discount_status',
                            'created_at'
                            )
                          ->where('discount_status','Active')
                          ->where('discount_option','Common')
                          ->first();
            //dd($data);
            return $data;
        }
    }

    private function checkColNDelDiscount()
    {
        $chk=Discount::where('discount_status','Active')
                     ->whereIn('discount_option', ['Delivery','Collection','Order Online'])
                     ->count();
        //dd($chk);
        if($chk>0)
        {
            $data=Discount::select(
                            'id',
                            'minimum_amount',
                            'discount_option',
                            'discount_type',
                            \DB::Raw("CASE WHEN discount_type='Fixed' THEN discount_amount 
                            ELSE CONCAT(discount_amount,'%') END as discount_amount"),
                            'message',
                            'discount_status',
                            'created_at'
                            )
                          ->where('discount_status','Active')
                          ->whereIn('discount_option', ['Delivery','Collection','Order Online'])
                          ->first();
            //dd($data);
            return $data;
        }
    }
    
    private function checkTax()
    {
        $chk=Tax::where('tax_status','Active')
                ->orderBy('id','DESC')
                ->count();
        //dd($chk);



        if($chk>0)
        {
            $data=Tax::where('tax_status','Active')
                     ->orderBy('id','DESC')
                     ->first();
            return $data;
        }
    }
    

    public function addtocart(Request $request)
    {
        if(isset($request->rec))
        {
            $oldCart = Session::has('cart') ? Session::get('cart') : null;
            $cart = new Cart($oldCart);
            $cart->addRec($request->rec);
        }
        elseif(isset($request->snd_item_id))
        {
            $product = Product::find($request->item_id);
            $sndItm = ProductOneSubLevel::find($request->snd_item_id);
            $oldCart = Session::has('cart') ? Session::get('cart') : null;
            $cart = new Cart($oldCart);
            if(isset($request->item_sub_cat_name))
            {
                $cart->addSndSubCat($product, $product->id,$sndItm,$sndItm->id,$request->item_sub_cat_name);
            }
            else
            {
                $cart->addSnd($product, $product->id,$sndItm,$sndItm->id);
            }
            
            
        }
        elseif(isset($request->exec_menu))
        {
            parse_str($request->execArrayData, $searcharray);
            //dd($searcharray); 
            $execArrayData=$searcharray;
            $product = Product::find($request->item_id);
            $oldCart = Session::has('cart') ? Session::get('cart') : null;
            $cart = new Cart($oldCart);
            $cart->addexecMenu($product, $product->id,$execArrayData);
        }
        elseif(isset($request->pizza_menu))
        {
            $postcount=0;
            $nExtra=[];
            $searcharray=json_decode(json_encode(json_decode($request->cartData,true)),true);
            if(isset($searcharray))
            {
                if(count($searcharray)>0)
                {
                    $size=$searcharray['size_info'];;
                    $flabour=$searcharray['flabour'];
                    $extra=$searcharray['extra'];
                    if(count($extra)>0)
                    {
                        
                        foreach($extra as $ex):
                            if(!empty($ex))
                            {
                                $nExtra[]=array(
                                    'extra_name'=>$ex['extra_name'],
                                    'extra_id'=>$ex['extra_id'],
                                    'extra_price'=>$ex['extra_price'],
                                    'extra_quantity'=>$ex['extra_quantity']
                                );
                            }
                            
                        endforeach;
                    }
                }
            }
            //dd($nExtra);
            //$execArrayData=$searcharray;
            $product = Product::find($request->item_id);
            $oldCart = Session::has('cart') ? Session::get('cart') : null;
            $cart = new Cart($oldCart);
            $cart->addPizzaMenu($product, $product->id,$size,$flabour,$nExtra);
        }
        else
        {
            $product = Product::find($request->item_id);
            $oldCart = Session::has('cart') ? Session::get('cart') : null;
            $cart = new Cart($oldCart);
            if(isset($request->item_sub_cat_name))
            {
                $cart->addSingleSubcat($product, $product->id,$request->item_sub_cat_name);
            }
            else
            {
                $cart->add($product, $product->id);
            }
            
        }

        $request->session()->put('cart', $cart);
        return response()->json($cart);  
    }

    public function deltocart(Request $request)
    {
        //dd($request->lid);
        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart = new Cart($oldCart);
        $cart->delProductFullRemove($request->lid);

        $request->session()->put('cart', $cart);
        return response()->json($cart); 
    }

    public function typeofdelivery(Request $request)
    {
        $cart = Session::has('cart') ? Session::get('cart') : null;
        return response()->json($cart->rec); 
    }

    public function cartJson()
    {
        $cart = Session::has('cart') ? Session::get('cart') : null;
        return response()->json($cart);  
    }

    public function ClearCart(Request $request)
    {
        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart = new Cart($oldCart);
        $cart->ClearCart();

        $request->session()->put('cart', $cart);
        return response()->json($cart);  
    }

    public function categoryProduct($filter='')
    {
        $row=[];
        $category=category::where('product','!=',0)
                            //->where('id','52')
                            ->get();
        foreach($category as $index=>$cat)
        {
            $row[$index]['id']=$cat->id;
            $row[$index]['name']=$cat->name;
            $row[$index]['description']=$cat->description;
            $row[$index]['layout']=$cat->layout;
            $row[$index]['product']=$cat->product;
            if($cat->layout==2)
            {
                $subCatData=[];
                $checkSubcid=SubCategory::where('category_id',$cat->id)->count();
                if($checkSubcid > 0)
                {
                    
                    $SubcidData=SubCategory::where('category_id',$cat->id)->get();
                    foreach($SubcidData as $inx=>$sc)
                    {
                        $subCatData[$inx]['id']=$sc->id;
                        $subCatData[$inx]['name']=$sc->name;

                        $product_row=[];
                        $product=Product::where('scid',$sc->id)->get();
                        //dd($product);
                        foreach($product as $key=>$pro)
                        {
                            if($pro->product_level_type==1)
                            {
                                $product_row[$key]['id']=$pro->id;
                                $product_row[$key]['name']=$pro->name;
                                $product_row[$key]['price']=$pro->price;
                                $product_row[$key]['interface']=$pro->product_level_type;
                                $product_row[$key]['description']=$pro->description;
                            }
                            elseif($pro->product_level_type==2)
                            {

                                $suboneData=[];
                                $product_row[$key]['id']=$pro->id;
                                $product_row[$key]['name']=$pro->name;
                                $product_row[$key]['price']=$pro->price;
                                $product_row[$key]['interface']=$pro->product_level_type;
                                $product_row[$key]['description']=$pro->description;
                                $subOne=ProductOneSubLevel::where('pid',$pro->id)->get();
                                foreach($subOne as $soIndex=>$so)
                                {
                                    $suboneData[$soIndex]['id']=$so->id;
                                    $suboneData[$soIndex]['name']=$so->name;
                                    $suboneData[$soIndex]['price']=$so->price;
                                }
                                $product_row[$key]['ProductOneSubLevel']=$suboneData;
                            }
                            elseif($pro->product_level_type==3)
                            {

                                $suboneDatamodal=[];
                                $product_row[$key]['id']=$pro->id;
                                $product_row[$key]['name']=$pro->name;
                                $product_row[$key]['price']=$pro->price;
                                $product_row[$key]['interface']=$pro->product_level_type;
                                $product_row[$key]['description']=$pro->description;
                                $subOne=ProductOneSubLevel::where('pid',$pro->id)->get();

                                foreach($subOne as $soIndex=>$so)
                                {
                                    $suboneDatamodal[$soIndex]['id']=$so->id;
                                    $suboneDatamodal[$soIndex]['name']=$so->name;
                                    $suboneDatamodal[$soIndex]['price']=$so->price;
                                }

                                $product_row[$key]['modal']=$suboneDatamodal;
                            }
                            elseif($pro->product_level_type==4)
                            {
                                $suboneData=[];
                                $product_row[$key]['id']=$pro->id;
                                $product_row[$key]['name']=$pro->name;
                                $product_row[$key]['price']=$pro->price;
                                $product_row[$key]['interface']=$pro->product_level_type;
                                $product_row[$key]['description']=$pro->description;
                                $subOne=ProductOneSubLevel::where('pid',$pro->id)->get();
                                foreach($subOne as $soIndex=>$so)
                                {
                                    $suboneData[$soIndex]['id']=$so->id;
                                    $suboneData[$soIndex]['name']=str_replace('&','&amp;',$so->name);
                                    $suboneData[$soIndex]['is_parent']=$so->parent_id;
                                    $dpsOP=explode(',', str_replace('&','&amp;',$so->description));
                                    $suboneData[$soIndex]['dropdown']=$dpsOP;


                                }

                                $product_row[$key]['ProductOneSubLevel']=$suboneData;
                            }
                            elseif($pro->product_level_type==5)
                            {
                                $suboneData=[];
                                $product_row[$key]['id']=$pro->id;
                                $product_row[$key]['name']=$pro->name;
                                $product_row[$key]['price']=$pro->price;
                                $product_row[$key]['interface']=$pro->product_level_type;
                                $product_row[$key]['description']=$pro->description;
                                
                                $pizzaSize=[];
                                $pizzaSql=PizzaSize::where('pid',$pro->id)->get();
                                foreach($pizzaSql as $SizeIndex=>$sz)
                                {
                                    $pizzaSize[$SizeIndex]['id']=$sz->id;
                                    $pizzaSize[$SizeIndex]['name']=$sz->name;
                                    $pizzaSize[$SizeIndex]['price']=$sz->price;
                                }
                                $product_row[$key]['PizzaSize']=$pizzaSize;

                                $PiFlabour=[];
                                $pizzaSql=PizzaFlabour::where('pid',$pro->id)->get();
                                foreach($pizzaSql as $plIndex=>$sl)
                                {
                                    $PiFlabour[$plIndex]['id']=$sl->id;
                                    $PiFlabour[$plIndex]['name']=$sl->name;
                                    $PiFlabour[$plIndex]['price']=$sl->price;
                                }
                                $product_row[$key]['PizzaFlabour']=$PiFlabour;


                                $suboneData=[];
                                $subOne=ProductOneSubLevel::where('pid',$pro->id)->get();
                                foreach($subOne as $soIndex=>$so)
                                {
                                    $suboneData[$soIndex]['id']=$so->id;
                                    $suboneData[$soIndex]['name']=$so->name;
                                    $suboneData[$soIndex]['price']=$so->price;
                                }

                                $product_row[$key]['pizzaExtra']=$suboneData;
                            }
                        }

                        //dd($product_row);

                        $subCatData[$inx]['sub_product_row']=$product_row;
                    }
                       
                }

                $row[$index]['product_row']=$subCatData; 
                
            }
            else
            {
                $product_row=[];
                $product=Product::where('cid',$cat->id)->get();

                foreach($product as $key=>$pro)
                {
                   

                    if($pro->product_level_type==1)
                    {
                        $product_row[$key]['id']=$pro->id;
                        $product_row[$key]['name']=$pro->name;
                        $product_row[$key]['price']=$pro->price;
                        $product_row[$key]['interface']=$pro->product_level_type;
                        $product_row[$key]['description']=$pro->description;
                    }
                    elseif($pro->product_level_type==2)
                    {

                        $suboneData=[];
                        $product_row[$key]['id']=$pro->id;
                        $product_row[$key]['name']=$pro->name;
                        $product_row[$key]['price']=$pro->price;
                        $product_row[$key]['interface']=$pro->product_level_type;
                        $product_row[$key]['description']=$pro->description;
                        $subOne=ProductOneSubLevel::where('pid',$pro->id)->get();
                        foreach($subOne as $soIndex=>$so)
                        {
                            $suboneData[$soIndex]['id']=$so->id;
                            $suboneData[$soIndex]['name']=$so->name;
                            $suboneData[$soIndex]['price']=$so->price;
                        }
                        $product_row[$key]['ProductOneSubLevel']=$suboneData;
                    }
                    elseif($pro->product_level_type==3)
                    {

                        $suboneDatamodal=[];
                        $product_row[$key]['id']=$pro->id;
                        $product_row[$key]['name']=$pro->name;
                        $product_row[$key]['price']=$pro->price;
                        $product_row[$key]['interface']=$pro->product_level_type;
                        $product_row[$key]['description']=$pro->description;
                        $subOne=ProductOneSubLevel::where('pid',$pro->id)->get();

                        foreach($subOne as $soIndex=>$so)
                        {
                            $suboneDatamodal[$soIndex]['id']=$so->id;
                            $suboneDatamodal[$soIndex]['name']=$so->name;
                            $suboneDatamodal[$soIndex]['price']=$so->price;
                        }

                        $product_row[$key]['modal']=$suboneDatamodal;
                    }
                    elseif($pro->product_level_type==4)
                    {
                        $suboneData=[];
                        $product_row[$key]['id']=$pro->id;
                        $product_row[$key]['name']=$pro->name;
                        $product_row[$key]['price']=$pro->price;
                        $product_row[$key]['interface']=$pro->product_level_type;
                        $product_row[$key]['description']=$pro->description;
                        $subOne=ProductOneSubLevel::where('pid',$pro->id)->get();
                        foreach($subOne as $soIndex=>$so)
                        {
                            $suboneData[$soIndex]['id']=$so->id;
                            $suboneData[$soIndex]['name']=str_replace('&','&amp;',$so->name);
                            $suboneData[$soIndex]['is_parent']=$so->parent_id;
                            $dpsOP=explode(',', str_replace('&','&amp;',$so->description));
                            $suboneData[$soIndex]['dropdown']=$dpsOP;


                        }

                        $product_row[$key]['ProductOneSubLevel']=$suboneData;
                    }
                    elseif($pro->product_level_type==5)
                    {
                        $suboneData=[];
                        $product_row[$key]['id']=$pro->id;
                        $product_row[$key]['name']=$pro->name;
                        $product_row[$key]['price']=$pro->price;
                        $product_row[$key]['interface']=$pro->product_level_type;
                        $product_row[$key]['description']=$pro->description;
                        
                        $pizzaSize=[];
                        $pizzaSql=PizzaSize::where('pid',$pro->id)->get();
                        foreach($pizzaSql as $SizeIndex=>$sz)
                        {
                            $pizzaSize[$SizeIndex]['id']=$sz->id;
                            $pizzaSize[$SizeIndex]['name']=$sz->name;
                            $pizzaSize[$SizeIndex]['price']=$sz->price;
                        }
                        $product_row[$key]['PizzaSize']=$pizzaSize;

                        $PiFlabour=[];
                        $pizzaSql=PizzaFlabour::where('pid',$pro->id)->get();
                        foreach($pizzaSql as $plIndex=>$sl)
                        {
                            $PiFlabour[$plIndex]['id']=$sl->id;
                            $PiFlabour[$plIndex]['name']=$sl->name;
                            $PiFlabour[$plIndex]['price']=$sl->price;
                        }
                        $product_row[$key]['PizzaFlabour']=$PiFlabour;


                        $suboneData=[];
                        $subOne=ProductOneSubLevel::where('pid',$pro->id)->get();
                        foreach($subOne as $soIndex=>$so)
                        {
                            $suboneData[$soIndex]['id']=$so->id;
                            $suboneData[$soIndex]['name']=$so->name;
                            $suboneData[$soIndex]['price']=$so->price;
                        }

                        $product_row[$key]['pizzaExtra']=$suboneData;


                    }
                }

                $row[$index]['product_row']=$product_row;
            }

        }
        //dd($row);
        return $row;
    }

    public function product()
    {
        $product=$this->categoryProduct(
        );
        return response()->json($product);
    }

    public function getPayment()
    {
        $paypalSQL=PaypalSetting::find(1);
        return view('frontend.pages.checkout.select-payment',['paypalData'=>$paypalSQL]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\ProductItem  $productItem
     * @return \Illuminate\Http\Response
     */
    public function show(ProductItem $productItem)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\ProductItem  $productItem
     * @return \Illuminate\Http\Response
     */
    public function edit(ProductItem $productItem)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\ProductItem  $productItem
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ProductItem $productItem)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\ProductItem  $productItem
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProductItem $productItem)
    {
        //
    }
}
