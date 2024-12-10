<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ShippingConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $shipping;

    /**
     * Create a new message instance.
     *
     * @param  \App\Models\Shipping  $shipping
     * @return void
     */
    public function __construct($shipping)
    {
        $this->shipping = $shipping;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Xác nhận vận chuyển')
                    ->view('emails.shipping_confirmation')
                    ->with(['shipping' => $this->shipping]);
    }
}