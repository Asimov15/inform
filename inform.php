<?php

# David Zuccaro 09/08/2018                       
# Miscellaneous Information                                        
# World Clock 
# 11/08/2018 Added crypto currency prices                                                     

?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en'>
	<head>		
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
        <meta http-equiv="refresh" content="1200"/>
        <link rel="stylesheet" type="text/css" href="inform.css"/>
		<title>David's Information</title>           		
	</head>	 
</html>

<body>
    <h1>Information</h1>
    <div id="wrapper">
        <?php 
            function CallAPI($method, $url, $data = false, $key = false)
            # method: html request method
            # url: the web address
            # data: dat to append to url
            # key: the access key to the web site
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
                            // echo($info);
                            $url = sprintf('%s?%s', $url, $info);
                        }
                }
                // echo($url);
                if ($key)
                    curl_setopt($curl, CURLOPT_HTTPHEADER, array('content-type: application/x-www-form-urlencoded', 'x-api-key: 352b69e93c5a43d513e4db1e4803019f352b69e9'));
                
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
                    echo "curl Error #:" . $err;
                }               

                return $result;
            }
            
            setlocale(LC_MONETARY, "en_US");
            $FIXER_API_KEY = "b9c6a28b6be2e9bd243c14a77766ea32";
            
            echo(PHP_EOL);
            echo('      <div id="outer1">' . PHP_EOL);
            echo('          <div class="box1">'. PHP_EOL);
            echo('              <h2>' . PHP_EOL);
            echo('                  World Clock' . PHP_EOL);
            echo('              </h2>' . PHP_EOL);                 
                 
            $json = CallAPI('GET', 'https://api.coindesk.com/v1/bpi/currentprice/AUD.json', false, false);
            $data = json_decode($json, TRUE);
            $time = date("H:i");                        
            echo('              <span class="time"><span class="home">Melbourne</span></span>' . PHP_EOL);
            echo('              <span class="timed"><span class="home">' . $time . '</span></span>' . PHP_EOL);                        
            $time2 = new DateTime($time);
            
            echo('              <span class="time">Rome</span>' . PHP_EOL);   
            $time2 = $time2->setTimeZone(new DateTimeZone("Europe/Rome"));
            echo('              <span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);  
                                    
            echo('              <span class="time">London</span>' . PHP_EOL);                
            $time2 = $time2->setTimeZone(new DateTimeZone("Europe/London"));           
            echo('              <span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);  
            
            echo('              <span class="time">Moscow</span>' . PHP_EOL);                
            $time2 = $time2->setTimeZone(new DateTimeZone("Europe/Moscow"));           
            echo('              <span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);  
            
            echo('              <span class="time">Los Angeles</span>' . PHP_EOL);        
            $time2 = $time2->setTimeZone(new DateTimeZone("America/Los_Angeles"));   
            echo('              <span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);  
            
            echo('              <span class="time">New York</span>' . PHP_EOL);        
            $time2 = $time2->setTimeZone(new DateTimeZone("America/New_York"));   
            echo('              <span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);  
            
            echo('              <span class="time">Shanghai</span>' . PHP_EOL);        
            $time2 = $time2->setTimeZone(new DateTimeZone("Asia/Shanghai"));   
            echo('              <span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);  
            
            echo('              <span class="time">Jakata</span>' . PHP_EOL);        
            $time2 = $time2->setTimeZone(new DateTimeZone("Asia/Jakarta"));   
            echo('              <span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);
            
            echo('          </div>' . PHP_EOL);
            echo('      </div><!-- end #outer1 -->' . PHP_EOL);
            echo('      <div id="outer2">' . PHP_EOL);
            echo('          <div class="box1">' . PHP_EOL);
            echo('          <h2>' . PHP_EOL);
            echo('              Cryptocurrency Prices' . PHP_EOL);
            echo('          </h2>' . PHP_EOL);
            echo('          <span class="time">1 BTC = $AUD ' . $data["bpi"]["AUD"]["rate"] . '</span>');
            echo('          </div>' . PHP_EOL);
            echo('      </div><!-- end #outer2 -->' . PHP_EOL);
            echo('      <div id="outer3">' . PHP_EOL);
            echo('          <div class="box1">' . PHP_EOL);
            echo('          <h2>' . PHP_EOL);
            echo('              Commodities' . PHP_EOL);
            echo('          </h2>' . PHP_EOL);
            $gold_json = CallAPI('GET', 'http://goldpricez.com/api/rates/currency/usd/measure/all', false, '352b69e93c5a43d513e4db1e4803019f352b69e9');
            $gd = str_replace("\\", "", $gold_json);        
            $gd = substr($gd,1,-1);
            $gold_data = json_decode($gd, TRUE);           
            echo('              <span class="time">Gold: $' . $gold_data["ounce_price_usd"] . ' per ounce' . '</span>' . PHP_EOL);   
            echo('          </h2>' . PHP_EOL);
            echo('          </div>' . PHP_EOL);
            echo('      </div><!-- end #outer3 -->' . PHP_EOL);
            echo('      <div id="outer4">' . PHP_EOL);
            echo('          <div class="box1">' . PHP_EOL);
            $params = new \stdClass();
            $params->access_key = $FIXER_API_KEY;
            $params->base = "EUR";
            $params->symbols = "GBP,JPY,USD,AUD";            
            $json = CallAPI('GET', 'http://data.fixer.io/api/latest', $params , false);
            $data = json_decode($json, TRUE);
            $usdaud = $data["rates"]["USD"] / $data["rates"]["AUD"];
            $gbpaud = $data["rates"]["GBP"] / $data["rates"]["AUD"];
            $euraud = 1.0 / $data["rates"]["AUD"];
            echo('          <h2>' . PHP_EOL);
            echo('              FOREX (AUD)' . PHP_EOL);
            echo('          </h2>' . PHP_EOL);
            echo('              <span class="time">USD: ' . money_format('%2.5i', $usdaud) . '</span>' . PHP_EOL);   
            echo('              <span class="time">EUR: ' . money_format('%2.5i', $euraud) . '</span>' . PHP_EOL);   
            echo('              <span class="time">GBP: ' . money_format('%2.5i', $gbpaud) . '</span>' . PHP_EOL);   
            echo('          </div>' . PHP_EOL);
            echo('      </div><!-- end #outer4 -->' . PHP_EOL);
            echo('      <div id="footer">' . PHP_EOL);
            echo('      <div class="box2">' . PHP_EOL);
            echo('          <span class="footer">' . PHP_EOL);
            echo('              <p>Powered by <a href="https://www.coindesk.com/price/">CoinDesk</a></p>' . PHP_EOL);
            echo('              <a href="http://goldpricez.com">Gold rates by <img alt="Gold Price Data" src="http://goldpricez.com/assets/logo.jpg" height="20"</a>' . PHP_EOL);
            echo('          </span>' . PHP_EOL);
            echo('      </div>' . PHP_EOL);
            echo('  </div>' . PHP_EOL);
            //var_dump(json_decode($json, true));
            //echo count($data);
        ?>
    </div> <!-- end #wrapper -->
</body>
