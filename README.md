# moon-doge PHP BOT

moon-doge v2 is a PHP bot that will buy DogeCoin on FTX when Elon Musk mention DogeCoin on twitter. (check v1 for binance)

*Be aware that this script is for educational purposes only*

The script will open a websocket connection and monitor Elon Musk twitter account.
If a mention of dogecoin is found in his last tweet, the script will immediately send a market-buy order of dogecoin on FTX (spot or with leverage). The script will also optionnaly set the stop loss/take profit.




![Capture du 2021-04-01 13-13-49](https://user-images.githubusercontent.com/72351273/113289538-a308c780-92f0-11eb-8d56-d551bfde6069.png)

![Capture du 2021-04-01 13-12-10](https://user-images.githubusercontent.com/72351273/113289479-8ff5f780-92f0-11eb-8872-a2a001591f2b.png)




## Dependencies

- API key from your FTX account
- API key from twitter developer (https://developer.twitter.com/en/apply-for-access)

The script works using : 
- CCXT : https://github.com/ccxt
- twitter-api-php : https://github.com/J7mbo/twitter-api-php
- spatie/twitter-streaming-api : https://github.com/spatie/twitter-streaming-api

## Installation

>composer require antoinebaron-io/moon-doge

## Configuration

open 

>vendor/antoinebaron-io/moon-doge/MoonDoge.php

and look for the config array at the top of the file

## Usage

Create a new file index.php and paste :

>require_once('vendor/autoload.php');

>$moonDoge = new MoonDoge();

>$moonDoge->run();

Then run in terminal : php index.php

## Run a simulation

You don't want to wait for Elon to tweet about doge to find out there where a configuration problem ...
So you can first run the bot to watch your own twitter account, then send a test tweet containing the doge or dogecoin word, and see if it is working.


>require_once('vendor/autoload.php');

>$moonDoge = new MoonDoge();

>$moonDoge->run('your_twitter_screen_name');




----------------------------------------------------------------------------------------
