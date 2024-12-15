<?php

namespace App\Mail;

use App\Models\Shipping;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ShippingConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $shipping;
    public $order;

    public function __construct(Shipping $shipping, Order $order)
    {
        $this->shipping = $shipping;
        $this->order = $order;
    }

    public function build()
    {
        return $this->subject("Xác nhận vận chuyển đơn hàng")
            ->view('emails.shipping_confirmation')
            ->with([
                'shipping' => $this->shipping,
                'order' => $this->order,
            ]);
    }
}