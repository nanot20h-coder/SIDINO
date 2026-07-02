<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripeController extends Controller
{
    public function checkout(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        if ($request->plan == 'mensual') {

            $nombre = 'Plan Mensual SIDINO';
            $precio = 30000000;

        } else {

            $nombre = 'Plan Anual SIDINO';
            $precio = 300000000;
        }

        $session = Session::create([

            'payment_method_types' => ['card'],

            'line_items' => [[

                'price_data' => [

                    'currency' => 'cop',

                    'product_data' => [
                        'name' => $nombre
                    ],

                    'unit_amount' => $precio,

                ],

                'quantity' => 1,

            ]],

            'mode' => 'payment',

            'success_url' => route('success'),

            'cancel_url' => route('cancel'),

        ]);

        return redirect($session->url);
    }

    public function success()
    {
        return view('success');
    }

    public function cancel()
    {
        return view('cancel');
    }
}