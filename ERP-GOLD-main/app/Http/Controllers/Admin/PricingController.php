<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoldPrice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PricingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pricings = GoldPrice::all();
        $stock_market = $this->Gold_Price_Api('USD');
        $goldPrices = GoldPrice::all();
        $this->updatePricng();
        return view('admin.pricing.index', compact('pricings', 'stock_market', 'goldPrices'));
    }

    public function get_gold_stock_market_prices()
    {
        $pricings = GoldPrice::all();
        $stock_market_usd = $this->Gold_Price_Api('USD');
        $stock_market_sar = $this->Gold_Price_Api('SAR');

        return view('admin.pricing.stock_market', compact('pricings', 'stock_market_usd', 'stock_market_sar'));
    }

    public function pricing()
    {
        $pricings = GoldPrice::all();
        if (count($pricings) == 0) {
            return $this->updatePricng();
        }
        $pricings = $pricings->first();
        return view('admin.welcome', compact('pricings'));
    }

    public function updatePricng()
    {
        $stock_market = $this->Gold_Price_Api('SAR');

        GoldPrice::updateOrCreate(['id' => 1], [
            'ounce_price' => $stock_market->price,
            'ounce_14_price' => $stock_market->price_gram_14k,
            'ounce_18_price' => $stock_market->price_gram_18k,
            'ounce_21_price' => $stock_market->price_gram_21k,
            'ounce_22_price' => $stock_market->price_gram_22k,
            'ounce_24_price' => $stock_market->price_gram_24k,
            'currency' => $stock_market->currency,
            'last_update' => Carbon::now(),
        ]);
    }

    public function Gold_Price_Api($curr = 'SAR')
    {
        $apiKey = 'goldapi-3qf9kslxkzs02r-io';
        $symbol = 'XAU';
        $date = '';

        $myHeaders = array(
            'x-access-token: ' . $apiKey,
            'Content-Type: application/json'
        );

        $curl = curl_init();

        $url = "https://www.goldapi.io/api/{$symbol}/{$curr}/{$date}";

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $myHeaders
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            echo 'Error: ' . $error;
        } else {
            return json_decode($response);
        }
    }
}
