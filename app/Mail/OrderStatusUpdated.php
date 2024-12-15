<?php
namespace App\Mail;

use App\Models\Order;
use App\Models\Shipping;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $shipping;

    public function __construct(Order $order, Shipping $shipping)
    {
        $this->order = $order;
        $this->shipping = $shipping;
    }

    public function build()
    {
        return $this->subject('Trạng thái đơn hàng đã được cập nhật')
                    ->view('emails.order_status_updated')
                    ->with([
                        'order' => $this->order,
                        'shipping' => $this->shipping
                    ]);
    }
}

?>