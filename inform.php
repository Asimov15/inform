<?php

// David Zuccaro 09/08/2018                       
// Miscellaneous Information                                        
// World Clock 
// 11/08/2018 Added crypto currency prices                                                     
// 12/08/2018 Added FOREX 
// 15/08/2018 Added Silver

?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en'>
	<head>		
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
        <meta http-equiv="refresh" content="1200"/>
        <link rel="stylesheet" type="text/css" href="inform.css"/>
		<title>David's Information</title>           		
	</head>	 

    <body>
        <h1>Information</h1>
        <div id="wrapper">
            <?php 
                function CallAPI($method, $url, $data = false, $key = false)
                // method: html request method
                // url: the web address
                // data: dat to append to url
                // key: the access key to the web site
                {
                    $curl = curl_init();

                    switch ($method)
                    {
                        case 'POST':
                            curl_setopt($curl, CURLOPT_POST, 1);

                            if ($data)
                                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                            break;
                            
                        case 'PUT':
                            curl_setopt($curl, CURLOPT_PUT, 1);
                            break;
                            
                        default:
                            if ($data)
                            {
                                $info = http_build_query($data);
                                $url = sprintf('%s?%s', $url, $info);
                            }
                    }

                    if ($key)
                    {
                        $key_header = array('content-type: application/x-www-form-urlencoded', 'x-api-key: ' . $key);
                        curl_setopt($curl, CURLOPT_HTTPHEADER, $key_header);
                    }
                    
                    // Optional Authentication:
                    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                    curl_setopt($curl, CURLOPT_USERPWD, '');

                    curl_setopt($curl, CURLOPT_URL, $url);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);                

                    $result = curl_exec($curl);
                   
                    $err = curl_error($curl);
                    curl_close($curl);
                    if ($err) 
                    {
                        echo "curl Error :" . $err;
                    }               

                    return $result;
                }           
                
                // FOREX
                setlocale(LC_MONETARY, "en_US");
                $FIXER_API_KEY = "b9c6a28b6be2e9bd243c14a77766ea32"; 
                $params = new \stdClass();
                $params->access_key = $FIXER_API_KEY;
                $params->base = "EUR";
                $params->symbols = "GBP,JPY,USD,AUD";            
                $json = CallAPI('GET', 'http://data.fixer.io/api/latest', $params , false);
                $data = json_decode($json, TRUE);
                $usdaud = $data["rates"]["USD"] / $data["rates"]["AUD"];
                $gbpaud = $data["rates"]["GBP"] / $data["rates"]["AUD"];
                $euraud = 1.0 / $data["rates"]["AUD"];
                
                // Crypto
                
                // BTC_USD
                $json = CallAPI('GET', 'https://api.coindesk.com/v1/bpi/currentprice/AUD.json', false, false);
                $data_btc = json_decode($json, TRUE);
                $btc_price_aud = str_replace(",", "", $data_btc["bpi"]["AUD"]["rate"]);    
                
                //GRC
                $json = CallAPI('GET', 'https://poloniex.com/public?command=returnTicker', false, false);
                $data_poloniex = json_decode($json, TRUE);
                $btc_grc = $data_poloniex["BTC_GRC"]["last"];
                $aud_grc = $btc_grc * $btc_price_aud;
                
                //BCH
                $data_poloniex = json_decode($json, TRUE);
                $btc_bch = $data_poloniex["BTC_BCH"]["last"];
                $aud_bch = $btc_bch * $btc_price_aud;
                
                //ETH
                $data_poloniex = json_decode($json, TRUE);
                $btc_eth = $data_poloniex["BTC_ETH"]["last"];
                $aud_eth = $btc_eth * $btc_price_aud;
                
                // Commodities
                // Gold
                
                $kg_factor = 32.1507;
                $gold_json = CallAPI('GET', 'http://goldpricez.com/api/rates/currency/usd/measure/all', false, '352b69e93c5a43d513e4db1e4803019f352b69e9');
                $gd = str_replace("\\", "", $gold_json);        
                $gd = substr($gd,1,-1);
                $gold_data = json_decode($gd, TRUE);             
                $gold_aud = $gold_data["ounce_price_usd"] / $usdaud;
                
                // Silver 
                // Get newest date
                $api_key = "SBPYxUYb_hz32nKxtqGU"; 
                $params = new \stdClass();            
                $params->start_date = "2018-08-14";
                $params->end_date = "2018-08-14";            
                $params->access_key = $api_key;
                $json = CallAPI('GET', 'https://www.quandl.com/api/v3/datasets/LBMA/SILVER', $params , false);
                
                //echo($json);
                
                $silver_data = json_decode($json, TRUE);
                
                // Get Current Price                
                $params->start_date = $silver_data["dataset"]["newest_available_date"];
                $params->end_date   = $silver_data["dataset"]["newest_available_date"];            
                $params->access_key = $api_key;
                $json = CallAPI('GET', 'https://www.quandl.com/api/v3/datasets/LBMA/SILVER', $params , false);
                $silver_data = json_decode($json, TRUE);
                $silver_aud = $silver_data["dataset"]["data"][0][1] / $usdaud;
               
                echo(PHP_EOL);
                echo('      <div id="outer1">' . PHP_EOL);
                echo('          <div class="box1">'. PHP_EOL);
                echo('              <h2>' . PHP_EOL);
                
                // Timezones
                echo('                  World Clock' . PHP_EOL);
                echo('              </h2>' . PHP_EOL);                                  
                
                $time = date("H:i");                  
                echo('              <span class="time"><span class="home">Melbourne</span></span>' . PHP_EOL);
                echo('              <span class="timed"><span class="home">' . $time . '</span></span>' . PHP_EOL);                        
                $time2 = new DateTime($time);
                echo('              <br/>' . PHP_EOL);
                
                echo('              <span class="time">Jakata</span>' . PHP_EOL);        
                $time2 = $time2->setTimeZone(new DateTimeZone("Asia/Jakarta"));   
                echo('              <span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);
                echo('              <br/>' . PHP_EOL);
                
                echo('              <span class="time">Johannesburg</span>' . PHP_EOL);        
                $time2 = $time2->setTimeZone(new DateTimeZone("Africa/Johannesburg"));   
                echo('              <span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);
                echo('              <br/>' . PHP_EOL);
                
                echo('              <span class="time">London</span>' . PHP_EOL);                
                $time2 = $time2->setTimeZone(new DateTimeZone("Europe/London"));           
                echo('              <span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);
                echo('              <br/>' . PHP_EOL);
                
                echo('              <span class="time">Los Angeles</span>' . PHP_EOL);        
                $time2 = $time2->setTimeZone(new DateTimeZone("America/Los_Angeles"));   
                echo('              <span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);
                echo('              <br/>' . PHP_EOL);
                
                echo('              <span class="time">Moscow</span>' . PHP_EOL);                
                $time2 = $time2->setTimeZone(new DateTimeZone("Europe/Moscow"));           
                echo('              <span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);
                echo('              <br/>' . PHP_EOL);
                
                echo('              <span class="time">New Dehli</span>' . PHP_EOL);                
                $time2 = $time2->setTimeZone(new DateTimeZone("Asia/Kolkata"));           
                echo('              <span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);
                echo('              <br/>' . PHP_EOL);
                
                echo('              <span class="time">New York</span>' . PHP_EOL);        
                $time2 = $time2->setTimeZone(new DateTimeZone("America/New_York"));   
                echo('              <span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);
                echo('              <br/>' . PHP_EOL);
                
                echo('              <span class="time">Rome</span>' . PHP_EOL);   
                $time2 = $time2->setTimeZone(new DateTimeZone("Europe/Rome"));
                echo('              <span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);
                echo('              <br/>' . PHP_EOL);
                
                echo('              <span class="time">Shanghai</span>' . PHP_EOL);        
                $time2 = $time2->setTimeZone(new DateTimeZone("Asia/Shanghai"));   
                echo('              <span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);
                echo('              <br/>' . PHP_EOL);
                
                echo('          </div>' . PHP_EOL);
                echo('      </div><!-- end //outer1 -->' . PHP_EOL);
                
                // Crypto
                echo('      <div id="outer2">' . PHP_EOL);
                echo('          <div class="box1">' . PHP_EOL);
                echo('              <h2>' . PHP_EOL);
                echo('                  Cryptocurrency Prices ($AUD)' . PHP_EOL);
                echo('              </h2>' . PHP_EOL);
                
                // BTC
                echo('              <span class="crypto">BTC:</span>');
                echo('              <span class="cryptod">' . money_format('%9.2i', $btc_price_aud) . '&nbsp;&nbsp;&nbsp;&nbsp;</span>');
                echo('              <br/>' . PHP_EOL);
                
                //GRC
                echo('              <span class="crypto">GRC:</span>');
                echo('              <span class="cryptod">' . money_format('%9.6i', $aud_grc) . '</span>');
                echo('              <br/>' . PHP_EOL);
                
                //BCH
                echo('              <span class="crypto">BCH:</span>');
                echo('              <span class="cryptod">' . money_format('%9.2i', $aud_bch) . '&nbsp;&nbsp;&nbsp;&nbsp;</span>');
                echo('              <br/>' . PHP_EOL);
                
                //ETH
                echo('              <span class="crypto">ETH:</span>');
                echo('              <span class="cryptod">' . money_format('%9.2i', $aud_eth) . '&nbsp;&nbsp;&nbsp;&nbsp;</span>');
                echo('              <br/>' . PHP_EOL);
                
                echo('          </div><!-- end box1 -->' . PHP_EOL); 
                echo('      </div><!-- end //outer2 -->' . PHP_EOL);
                
                // Commodities
                echo('      <div id="outer3">' . PHP_EOL);
                echo('          <div class="box1">' . PHP_EOL);
                echo('          <h2>' . PHP_EOL);
                echo('              Commodities ($AUD / oz, kg)' . PHP_EOL);
                echo('          </h2>' . PHP_EOL);           
                
                echo('              <span class="commod1">Gold  : </span>'  . PHP_EOL);
                echo('              <span class="commod2">' . money_format('%7.2i', $gold_aud  ) . '</span>' . PHP_EOL);
                echo('              <span class="commod2">' . money_format('%7.2i', $gold_aud   * $kg_factor) . '</span>' . PHP_EOL);
                
                echo('<br/>');
                
                echo('              <span class="commod1">Silver: </span>'  . PHP_EOL);
                echo('              <span class="commod2">' . money_format('%7.2i', $silver_aud  ) . '</span>' . PHP_EOL);
                echo('              <span class="commod2">' . money_format('%7.2i', $silver_aud   * $kg_factor) . '</span>' . PHP_EOL);
            
            //    echo('              <span class="commod">Gold  : ' . money_format('%7.2i', $gold_aud  ) . ' ' . money_format('%7.2i', $gold_aud   * $kg_factor) . '</span>' . PHP_EOL);
            //    echo('              <span class="commod">Silver: ' . money_format('%7.2i', $silver_aud) . ' ' . money_format('%7.2i', $silver_aud * $kg_factor) . '</span>' . PHP_EOL);   
                echo(PHP_EOL);
                echo('          </div>' . PHP_EOL);
                echo('      </div><!-- end //outer3 -->' . PHP_EOL);
                
                // Forex
                echo('      <div id="outer4">' . PHP_EOL);
                echo('          <div class="box1">' . PHP_EOL);            
                echo('          <h2>' . PHP_EOL);
                echo('              FOREX ($AUD)' . PHP_EOL);
                echo('          </h2>' . PHP_EOL);
                echo('              <span class="time">USD: ' . money_format('%2.5i', $usdaud) . '</span>' . PHP_EOL);
                echo('              <br/>' . PHP_EOL);   
                echo('              <span class="time">EUR: ' . money_format('%2.5i', $euraud) . '</span>' . PHP_EOL);   
                echo('              <br/>' . PHP_EOL);   
                echo('              <span class="time">GBP: ' . money_format('%2.5i', $gbpaud) . '</span>' . PHP_EOL);   
                echo('              <br/>' . PHP_EOL);   
                echo('          </div>' . PHP_EOL);
                echo('      </div><!-- end //outer4 -->' . PHP_EOL);
                echo('      <div id="footer">' . PHP_EOL);
                echo('      <div class="box2">' . PHP_EOL);
                echo('          <div class="footer2">' . PHP_EOL);
                echo('              <p>Powered by <a href="https://www.coindesk.com/price/">CoinDesk</a></p>' . PHP_EOL);
                echo('              <a href="http://goldpricez.com">Gold rates by <img alt="Gold Price Data" src="http://goldpricez.com/assets/logo.jpg" height="20"/></a>' . PHP_EOL);            
                echo('              <br/>' . PHP_EOL);  
                echo('    	        <a href="http://jigsaw.w3.org/css-validator/check/referer"><img style="border:0;width:89px;height:31px" src="http://jigsaw.w3.org/css-validator/images/vcss" alt="Valid CSS!" /></a>' . PHP_EOL);
                echo('              <br/>' . PHP_EOL);  
                echo('	   	        <a href="http://validator.w3.org/check?uri=referer"><img src="http://www.w3.org/Icons/valid-xhtml10" alt="Valid XHTML 1.0 Strict" height="31" width="89" /></a>' . PHP_EOL);       
                echo('          </div>' . PHP_EOL);            
                echo('      </div>' . PHP_EOL);
                echo('  </div>' . PHP_EOL);
            ?>
        </div> <!-- end //wrapper -->
    </body>
</html>
