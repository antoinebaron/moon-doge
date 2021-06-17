<?php

class MoonDoge{

	protected $config;
	protected $exchange;
	protected $twitter;
	protected $twitterSocket;

	public function __construct(){

		$this->config = array(

			/*
			* Exchange info
			*/
			'exchange' => 'ftx', /// changes in the code will be required if you want to use another exchange
			'exchange_api_key' => '',
			'exchange_api_secret' => '',

			/*
			* Twitter info
			*/
			'twitter_api_key' => '',
			'twitter_api_secret' => '',
			'twitter_access_token' => '',
			'twitter_access_secret' => '',

			/*
			* Stop loss / Take profit
			*/
			'stop_loss_percent' => 1, // 1-100 or false if no stop loss
			'take_profit_percent' => 5, // 1-100 or false if no take profit

			/*
			* Leverage
			*/
			'leverage' => 100, /// set leverage : false / 1 / 3 / 5 / 10 / 20 / 50 / 100

			/*
			* Amount
			*/
			'amount_usdt' => 10, /// how much usdt to spend when ordering dogecoin

			/*
			* Logs
			*/
			'logs_path' => false, /// path to the log folder (must be writable) - set to false if you don't want logs
		);

	}

	public function run(?string $screenName = ''){

		$this->output("Start script moon-doge");

		//check if the settings for the logs are correct
		if($this->config['logs_path']!==false)
			$this->checkLogsSettings();

		//check if the settings for the exchange are correct
		$this->checkExchangeSettings();

		//check if the settings for the twitter API are correct
		$this->checkTwitterSettings();

		// if no error, we can start to monitor twitter 
		$this->monitor($screenName);

	}

	/*
	* monitor tweets 
	*/
	protected function monitor(?string $screenName = ''){

		if($screenName!=''){

			$idUser = $this->getTwitterIdFromScreenName($screenName);

		}else{

			$idUser = 44196397; //elon musk id 
			$screenName = 'Elonmusk';
		}

		$this->output("Start monitoring $screenName");

		// connect to twitter websocket
		$this->connectToTwitterWebsocket();

		// watch for new tweets
		$this->twitterSocket->whenTweets($idUser, function(array $tweet) {

			$this->output("New tweet : \"" . $this->formatTweet($tweet['text']) . "\" (tweet created at " . $tweet['created_at'] . ")");
			$this->output("Check the tweet ...");

			// if tweet contain doge ...
			if($this->checkTweet($tweet)==true){

				$this->dogeToTheMoon();
				exit;
			}

		})->startListening();
	}

	/*
	* handle the market order, take profit and stop loss
	*/
	protected function dogeToTheMoon(){

		// set symbol
		if($this->config['leverage']!==false)
			$this->symbol = 'DOGE-PERP';
		else
			$this->symbol = 'DOGE/USDT';

		// connect to exchange
		$this->connectToExchange();

		//get the last price
		$ticker = $this->exchange->fetch_ticker($this->symbol);
		$lastPrice = $ticker['last']; 

		//calculate quantity to buy
		$qtt = $this->config['amount_usdt'] / ($lastPrice);

		//set the leverage (if any)
		$leverage = $this->config['leverage'];
		if($leverage!==false){
			 $this->exchange->private_post_account_leverage(array( 'leverage' => $leverage));
			 $qtt = $qtt*$leverage;
		}

		$msg = "Create a market order buy for $qtt at $lastPrice USDT";
		if($leverage!==false)
			$msg .= " with leverage x$leverage";
		$this->output($msg);


		// make the market order
		$this->place_order($qtt, 'buy', 'market');

		// Stop loss
		if($this->config['stop_loss_percent']!=false){

			//calculate stop loss price
			$stop_price = $lastPrice - $lastPrice *($this->config['stop_loss_percent']/100);
			$trigger = $stop_price + $stop_price *(0.5/100);

			$this->place_order($qtt, 'sell', 'stop', $stop_price, $trigger);
			$this->output("Set the stop loss at $stop_price USDT");
		}


		// Take profit
		if($this->config['take_profit_percent']!=false){

			//calculate stop loss price
			$target_price = $lastPrice + $lastPrice *($this->config['take_profit_percent']/100);
			$trigger = $target_price - $target_price *(0.5/100);

			$this->place_order($qtt, 'sell', 'takeProfit', $target_price, $trigger);
			$this->output("Set the take profit at $target_price USDT");
		}
	}

	/*
	* place order on the exchange
	*/
	protected function place_order(int $qtt, string $side, string $type, $price = false, $trigger = false){

		try {
			
			if($price == false){
				$this->exchange->create_order($this->symbol, $type, $side, $qtt);
			}else{
				$this->exchange->create_order($this->symbol, $type, $side, $qtt, $price, ['triggerPrice' => $trigger]);
			}

		} catch (\ccxt\NetworkError $e) {
			$this->errorMsg('[Network Error] ' . $e->getMessage());
		} catch (\ccxt\ExchangeError $e) {
			$this->errorMsg('[Exchange Error] ' . $e->getMessage());
		} catch (Exception $e) {
			$this->errorMsg('[Error] ' . $e->getMessage());
		}
	}


	/*
	* convert twitter screen name to internal twitter id (required to use the twitter webSocket API)
	*/
	protected function getTwitterIdFromScreenName(string $screenName){

		$this->connectToTwitter();

		$url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
		$getfield = "?screen_name={$screenName}&include_rts=false&exlude_replies=true&trim_user=true&count=1";
		$requestMethod = 'GET';

		// get user
		$user = $this->twitter
		  ->setGetfield($getfield)
		  ->buildOauth($url, $requestMethod)
		  ->performRequest();

		$user = json_decode($user, true);

		// check for errors
		if(isset($user['errors'][0]))
			if($user['errors'][0]['code'] == '34')
				$this->errorMsg(4);

		if(!isset($user[0]['user']['id']))
			$this->errorMsg(5);
		
		return $user[0]['user']['id'];
	}

	/*
	* check if tweet is not a reply and if it contains "doge" or "dogecoin"
	*/
	protected function checkTweet($tweet):bool{

		// check if the tweet is a reply
		if($tweet['in_reply_to_screen_name']!=''){

			$this->output("Tweet is a reply ... wait for another tweet");
			return false;

		}else{ // not a reply, check if contains "doge" or "dogecoin"

			$regex  = "/(\s|^)(doge|dogecoin)(\?*|\!*)(\s|$)/i";
			if(preg_match($regex, $tweet['text'], $match)){

				$this->output("Found " . trim($match[0]) . " in the tweet, go ...");
				return true;

			}else{

				$this->output("No doge or dogecoin found in the tweet ... wait for another tweet");
				return false;
			}
		}
	}

	/*
	* check the settings for the exchange
	*/
	protected function checkExchangeSettings(){

		$this->connectToExchange();

		// first check : exchange connection
		$this->output("Check the connection with the exchange ...");

		// if we can't get the balance
		try {
			$balance = $this->exchange->fetch_balance();
		} catch (Exception $e) {
			// $error = $e->getMessage();
			$this->errorMsg(1);
		}

		// if we got the balance
		$this->output("OK");

		// second check : account balance
		$this->output("Check wallet for USDT balance ..."); 
		if($balance['USDT']['free']>=$this->config['amount_usdt'])
			$this->output("OK");
		else
			$this->errorMsg(2);
	}

	/*
	* check the Twitter API keys
	*/
	protected function checkTwitterSettings(){

		$this->output("Check Twitter connection ..."); 
		$this->connectToTwitter();

		//make a test request to check the api keys 
		$testRequest = json_decode($this->twitter->buildOauth('https://api.twitter.com/1.1/statuses/user_timeline.json', 'GET')->performRequest(),true);

		if(isset($testRequest['errors'])){

			if(($testRequest['errors'][0]['code'] == 32) || ($testRequest['errors'][0]['code'] == 215))
				$this->errorMsg(3);
			else
				$this->errorMsg("Error with Twitter connection : " . $testRequest['errors'][0]['message']);
			
		}else{

			$this->output("OK");
		}
	}

	/*
	* check logs settings
	*/
	protected function checkLogsSettings(){

		$this->output("Check the logs settings ..."); 
		
		$file_name = date('d-m-Y', time());

		if(@fopen($this->config['logs_path'] . $file_name, "a+"))
			$this->output("OK"); 
		else
			$this->errorMsg(6);
	}

	/*
	* Connect to twitter api (websocket)
	*/
	protected function connectToTwitterWebsocket(){

		if(!isset($this->twitterSocket)){

			$this->twitterSocket = Spatie\TwitterStreamingApi\PublicStream::create(
				$this->config['twitter_access_token'],
				$this->config['twitter_access_secret'],
				$this->config['twitter_api_key'],
				$this->config['twitter_api_secret']
			);
		}
	}

	/*
	* Connect to twitter api (non-websocket)
	*/
	protected function connectToTwitter(){

		if(!isset($this->twitter)){
			$settings = array(
			  'consumer_key' => $this->config['twitter_api_key'],
			  'consumer_secret' => $this->config['twitter_api_secret'],
			  'oauth_access_token' => $this->config['twitter_access_token'],
			  'oauth_access_token_secret' => $this->config['twitter_access_secret'],
			);

			$this->twitter = new TwitterAPIExchange($settings);
		}
	}

	/*
	* Connect to the exchange 
	*/
	protected function connectToExchange(){

		$exchangeConfig = array (
			'apiKey' => $this->config['exchange_api_key'],
			'secret' => $this->config['exchange_api_secret'],
			'enableRateLimit' => true,
		);

		if($this->config['leverage']!==false)
			$exchangeConfig['options'] = array( 'defaultType' => 'future');


		$ccxt = "\\ccxt\\" . $this->config['exchange'];
		$this->exchange = new $ccxt($exchangeConfig);
	}

	/*
	* Function to output error message from internal error code
	*/
	protected function errorMsg($code){

		$errorMsgs = array(

			'1' => 'Exchange API-key invalid (edit api-key and settings in /vendor/antoinebaron-io/moon-doge/ToTheMoon.php)',
			'2' => 'Insufficient USDT available in your exchange wallet (add funds in the exchange or change the value of amount_usdt in config)',
			'3' => 'Connection to Twitter API failed, check api keys (https://developer.twitter.com/en/docs/developer-portal/)',
			'4' => 'twitter - the user does not seem to exist',
			'5' => 'twitter - could not get the id of the user',
			'6' => 'The log folder does not exist or is not writable',

		);

		$errorMsg = (is_int($code)) ? $errorMsgs[$code] : $code;
		$this->output("ERROR : " . $errorMsg);
		die();
	}

	/*
	* Output txt
	*/
	protected function output(string $txt){

		$txt =  date('d-m-Y H:i:s', time()) . " - $txt " .PHP_EOL;
		echo $txt;

		if($this->config['logs_path']!==false){

			$fw = fopen($this->config['logs_path'] . date('d-m-Y', time()), "a+");
			fputs($fw, $txt);
			fclose($fw);
		}
	}

	/*
	* format tweet
	*/
	public static function formatTweet(string $tweet): string{
		return str_replace(array("\r\n", "\r", "\n", PHP_EOL, chr(10), chr(13), chr(10).chr(13)), " ", $tweet);
	}

}
