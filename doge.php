<?php


ini_set('display_errors', 1);
error_reporting(E_ALL); 

require __DIR__ . '/vendor/autoload.php';
require_once 'config.php';

date_default_timezone_set('UTC');

/*
uncomment the line bellow to check if you are all set to place an order on binance
if does not trow error, it means it is all good, else check the error message
you can also set AMOUNT to 6 and call doge_to_the_moon(); to place a test order of 6 USDT (minimal order)
*/
//check_binance(); exit;

$last_id = 0;
$count = 0;

//as long as the last tweet don't mention Doge
while (true) {

	$getTweet = get_last_elon_tweet();

	/// wait 1 second
	sleep(1);
	
	if($getTweet!=null){

		$isIdOk = true;

		$elon_tweet = $getTweet;

		echo date('d-m-Y H:i:s') . ' - Elon last tweet : ' . $elon_tweet['txt'];
		echo "\n";

		//sometimes twitter api is giving some old tweet so make sure the id of the tweet is the newest
		if($elon_tweet['id']<$last_id){ 
			$isIdOk = false;
		}else{
			$last_id = $elon_tweet['id'];
		}

		if($isIdOk===true){

			///	check if the tweet is recent (< 1 minute)
			/// won't work with less than 1 minute as the time provided from twitter api does not include seconds
			/// in case we start the script and Elon last tweet have mentioned dogecoin but it is too late
			if(time()-$elon_tweet['time']<60){

				//if we find doge in last tweet
				if(is_doge_found_in_tweet($elon_tweet['txt'])){

					echo "\n";
					echo 'let\'s go ! ...' . "  \n";
					echo "\n";
					doge_to_the_moon();
					break;

				}
			}
		}
	}
}


function is_doge_found_in_tweet($tweet){

	$findMe   = array(	'Doge', 'doge', 'DOGE', 'DogeCOIN', 'DogeCoin', 'Dogecoin', 'DOGECOIN', 'dogecoin', 'dogeCoin', '$DOGE');
	$found = false;

		foreach ($findMe as $key => $value) {

			$find = $findMe[$key];

			/// if the tweet cointain only doge word
			if(trim($tweet)==$find){
				echo 'found';
				echo "\n";
				$found = true;
				break;
			}

			//check if the tweet start with "word "
			if (string_starts_with($tweet, $find . " ")) {
				echo 'The tweet starts with "' . $find . ' "';
				echo "\n";
				$found = true;
				break;
			} 

			//check if the tweet ends with " word"
			if (string_ends_with($tweet, " " . $find)) {
				echo 'The tweet ends with " ' . $find . '"';
				echo "\n";
				$found = true;
				break;
			} 

			//check if the tweet contains " word "
			if(strpos(trim($tweet), " " . $find . " ") !== false){
				echo 'found inside tweet';
				echo "\n";
				$found = true;
				break;
			}

		}
	
	return $found;
}

function string_starts_with($string, $prefix) {
	return substr($string, 0, strlen($prefix)) == $prefix;
}


function string_ends_with($string, $prefix) {
	return substr($string, -strlen($prefix), strlen($prefix)) == $prefix;
}

//get Elon Musk last tweet
//User Tweet timeline 
//1500 request / 15 minute PER APP AUTH == 100 request / minute
function get_last_elon_tweet(){

	// Set here your twitter application tokens
	$settings = array(
	  'consumer_key' => TWITTER_API_KEY,
	  'consumer_secret' => TWITTER_SECRET,
	  'oauth_access_token' => '',
	  'oauth_access_token_secret' => '',
	);

	$screen_name = 'elonmusk';

	$url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
	$getfield = "?screen_name={$screen_name}&include_rts=false";
	$requestMethod = 'GET';

	$twitter = new TwitterAPIExchange($settings);
	$user_timeline = $twitter
	  ->setGetfield($getfield)
	  ->buildOauth($url, $requestMethod)
	  ->performRequest();

	$user_timeline = array_values(json_decode($user_timeline, true));
	$last_tweet = array('txt'=>"", 'id'=>"");

	foreach ($user_timeline as $key => $value) {
		
		if(empty($user_timeline[$key]['in_reply_to_user_id'])){

			$last_tweet_text = $user_timeline[$key]['text'];
			$last_tweet_id = $user_timeline[$key]['id'];
			$last_tweet_time = strtotime($user_timeline[$key]['created_at']);

			return array('txt' => $last_tweet_text, 'id' => $last_tweet_id, 'time' => $last_tweet_time);

			break;

		}
	}

}


function check_binance($binance = false){


	if($binance==false){
		$binance     = new \ccxt\binance  (array (
		    'apiKey' => BINANCE_API_KEY,
		    'secret' => BINANCE_SECRET,
		    'enableRateLimit' => true,
		    'options' => array(
		        'defaultType' => 'future'
		    ),
		));
	}

	//$binance->verbose = true; //  uncomment for debugging

	$symbol = 'DOGE/USDT';

	$balance = $binance->fetch_balance();

	/// some checking
	if($balance["info"]["canTrade"]!=1){ 
		echo 'not allowed to trade, check API settings'; 
		exit;
	}

	//find USDT id in balance array
	$usdt_key = false;
	foreach ($balance["info"]["assets"] as $key => $arrayValue) { 
		if($balance["info"]["assets"][$key]['asset']=='USDT') $usdt_key = $key; 
	}

	if($usdt_key===false){
		echo 'USDT token not found on binance'; 
		exit;  
	} 

	// get usdt balance
	$usdt_balance = $balance["info"]["assets"][$usdt_key]['walletBalance'];
	if($usdt_balance==0){
		echo  'no USDT found, add USDT to account'; 
		exit; 
	} 

	// check if enought USDT 
	if(AMOUNT!=''){
		if(AMOUNT>$usdt_balance){ 
			echo 'You have set amount to ' . AMOUNT . 'USDT but there is only ' . $usdt_balance . 'USDT in your balance'; 
			exit;
		}
	}


}


function doge_to_the_moon(){

	$binance     = new \ccxt\binance  (array (
	    'apiKey' => BINANCE_API_KEY,
	    'secret' => BINANCE_SECRET,
	    'enableRateLimit' => true,
	    'options' => array(
	        'defaultType' => 'future'
	    ),
	));

	//$binance->verbose = true; //  uncomment for debugging

	$symbol = 'DOGE/USDT';

	$balance = $binance->fetch_balance();

	//print_r($balance);
	check_binance($binance);

	//find USDT id in balance array
	$usdt_key = false;
	foreach ($balance["info"]["assets"] as $key => $arrayValue) { 
		if($balance["info"]["assets"][$key]['asset']=='USDT') $usdt_key = $key; 
	}

	// get usdt balance
	$usdt_balance = $balance["info"]["assets"][$usdt_key]['walletBalance'];

	//get the last price
	$ticker = $binance->fetch_ticker ($symbol);
	$lastPrice = $ticker['last']; 

	//calculate target price
	$target_price = $lastPrice + $lastPrice *(TARGET/100);

	echo 'actual balance : ' . $usdt_balance . " USDT \n";
	echo 'actual DOGE price : ' . $lastPrice . " USDT\n";
	echo 'target DOGE price : ' . $target_price . " USDT\n";

	//calculate amount to buy
	///add 0.01 to get a slightly inferior qtt of what we can get 
	//just in case ...
	if(AMOUNT == ''){
		//from available balance 
		$qtt = $usdt_balance / ($lastPrice+0.01); 
		echo 'creating market order for '.$usdt_balance.' USDT : ' . $qtt . " DOGE\n";
	}else{
		//from AMOUNT
		$qtt = AMOUNT / ($lastPrice+0.01);; 
		echo 'creating market order for '.AMOUNT.' USDT : ' . $qtt . " DOGE\n";
	}

	//create BUY order
	create_market_order($binance, $symbol, $qtt, 'buy');

	echo 'order successfully created, waiting for the price to pump ...' . "\n";

	////wait the price to pump
	$actual_price = $lastPrice;
	$entry_price = $lastPrice;

	while ($lastPrice <= $target_price+1) {
		
		$ticker = $binance->fetch_ticker ($symbol);
		$actual_price = $ticker['last']; 
		$change_percent = round(($actual_price-$entry_price)/$entry_price*100, 2);

		echo 'actual price : ' . $actual_price . ' USDT ('. $change_percent .'%)' . "\n";

		//if the price is on target
		if($actual_price>= $target_price){

			///market sell
			echo 'The price is on target ... creating sell order' . "\n";
			create_market_order($binance, $symbol, $qtt, 'sell');
			echo 'Market sell order done !' . "\n";
			break;
		}else{
			sleep(1);
		}

		//set last price for the next iteration
		$lastPrice = $actual_price;

	}

}


function create_market_order($exchange, $symbol, $qtt, $buyOrSell){

	try {

	  $exchange->create_order ($symbol, 'market', $buyOrSell, $qtt);

	} catch (\ccxt\NetworkError $e) {
	    echo '[Network Error] ' . $e->getMessage() . "\n"; exit;
	} catch (\ccxt\ExchangeError $e) {
	    echo '[Exchange Error] ' . $e->getMessage() . "\n"; exit;
	} catch (Exception $e) {
	    echo '[Error] ' . $e->getMessage() . "\n"; exit;
	}

}
