moon-doge is a PHP bot that will buy DogeCoin on Binance when Elon Musk mention DogeCoin on twitter.

*Be aware that this is for educational purposes only*

Every second it will check the occurence of DogeCoin inside Elon's last tweet. 
When found, it will automatically buy DogeCoin on Binance.
It will take all the available USDT in the futures wallet of your Binance account and buy Doge with the leverage you will have previously set for DOGEUSDT Perpetual. It will also automatically sell when the price hit the target (defined by "TARGET").

It is best to have a dedicated Binance account only for this script.
You will need to create an API key with Futures enabled.

The script needs those 2 dependencies : 
- CCXT : https://github.com/ccxt
- twitter-api-php from https://github.com/J7mbo/twitter-api-php

Run in terminal !



![Capture du 2021-04-01 13-13-49](https://user-images.githubusercontent.com/72351273/113289538-a308c780-92f0-11eb-8d56-d551bfde6069.png)

![Capture du 2021-04-01 13-12-10](https://user-images.githubusercontent.com/72351273/113289479-8ff5f780-92f0-11eb-8872-a2a001591f2b.png)

![Capture du 2021-04-01 14-19-41](https://user-images.githubusercontent.com/72351273/113292885-5ecbf600-92f5-11eb-8c90-289f7fbb3d7e.png)

