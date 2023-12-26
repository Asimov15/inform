<?php

// David Zuccaro 09/08/2018
// Miscellaneous Information
// World Clock
// 11/08/2018 Added crypto currency prices
// 12/08/2018 Added FOREX
// 15/08/2018 Added Silver
// 03/01/2018 Added Dash and removed BCH
// 24/09/2020 Use different currency API: https://api.exchangeratesapi.io/
// 27/09/2020 Add favicon
// 21/11/2020 Fix BCH
// 26/12/2023 Replace money format function with number format 
//            fix favicon

?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en'>

	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
		<meta http-equiv="refresh" content="1200"/>
		<link rel="stylesheet" type="text/css" href="inform.css?version=1.5"/>
		<link rel="icon" href="http://198.199.72.243/images/infoicon.jpg"/>
		<title>David's Information</title>
	</head>

	<body>

		<h1>David's Information</h1>

		<div id="wrapper">

		<?php
			function display_xml_error($error)
			{
				$return = str_repeat('-', $error->column) . "\n";

				switch ($error->level) 
				{
					case LIBXML_ERR_WARNING:
						$return .= "Warning $error->code: ";
						break;
					case LIBXML_ERR_ERROR:
						$return .= "Error $error->code: ";
						break;
					case LIBXML_ERR_FATAL:
						$return .= "Fatal Error $error->code: ";
						break;
				}
				$return .= trim($error->message) . "\n  Line: $error->line" . "\n  Column: $error->column";

				if ($error->file) 
				{
					$return .= "\n  File: $error->file";
				}
				return "$return\n\n--------------------------------------------\n\n";
			}

			function get_commodity_price($commodity, $web_reference, $xpath_query)
			{
				$output = "0.00";
				$file_name = "/var/www/html/temp/" . $commodity . ".html";
				$command = "rm " . $file_name;
				shell_exec($command);
				$command = "wget --read-timeout=10 --dns-timeout=10 --connect-timeout=10 -q -O " . $file_name . " " . $web_reference;
				shell_exec($command);
				$doc = new DOMDocument();
				libxml_use_internal_errors(true);
				if (!$doc->loadHTMLFile($file_name))
				{
					foreach (libxml_get_errors() as $error) 
					{
						echo('wget timeout!<br/>' . PHP_EOL);
						echo(display_xml_error($error));
						echo(PHP_EOL);
						echo('<br/>' . PHP_EOL);
					}
				};
				libxml_use_internal_errors(false);

				$xpath = new DOMXpath($doc);
				$elements = $xpath->query($xpath_query);

				if (!is_null($elements))
				{
					foreach ($elements as $element)
					{
						$nodes = $element->childNodes;
						foreach ($nodes as $node)
						{
							$output = $node->nodeValue;
						}
					}
				}
				return $output;
			}

			function ip_info($ip = NULL, $purpose = "location", $deep_detect = TRUE)
			{
				$output = NULL;
				if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE)
				{
					$ip = $_SERVER['REMOTE_ADDR'];
					if ($deep_detect)
					{
						if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
							$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];

						if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
							$ip = $_SERVER['HTTP_CLIENT_IP'];
					}
				}

				$purpose	= str_replace(array("name", "\n", "\t", " ", "-", "_"), NULL, strtolower(trim($purpose)));
				$support	= array("country", "countrycode", "state", "region", "city", "location", "address");
				$continents = array(
					"AF" => "Africa",
					"AN" => "Antarctica",
					"AS" => "Asia",
					"EU" => "Europe",
					"OC" => "Australia (Oceania)",
					"NA" => "North America",
					"SA" => "South America"
				);

				if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support))
				{
					$ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));
					if (@strlen(trim($ipdat->geoplugin_countryCode)) == 2)
					{
						switch ($purpose)
						{
							case "location":
								$output = array(
									"city"				=> @$ipdat->geoplugin_city,
									"state"				=> @$ipdat->geoplugin_regionName,
									"country"			=> @$ipdat->geoplugin_countryName,
									"country_code"		=> @$ipdat->geoplugin_countryCode,
									"continent"			=> @$continents[strtoupper($ipdat->geoplugin_continentCode)],
									"continent_code"	=> @$ipdat->geoplugin_continentCode
								);
								break;
							case "address":
								$address = array($ipdat->geoplugin_countryName);
								if (@strlen($ipdat->geoplugin_regionName) >= 1)
									$address[] = $ipdat->geoplugin_regionName;
								if (@strlen($ipdat->geoplugin_city) >= 1)
									$address[] = $ipdat->geoplugin_city;
								$output = implode(", ", array_reverse($address));
								break;
							case "city":
								$output = @$ipdat->geoplugin_city;
								break;
							case "state":
								$output = @$ipdat->geoplugin_regionName;
								break;
							case "region":
								$output = @$ipdat->geoplugin_regionName;
								break;
							case "country":
								$output = @$ipdat->geoplugin_countryName;
								break;
							case "countrycode":
								$output = @$ipdat->geoplugin_countryCode;
								break;
						}
					}
				}
				return $output;
			}

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
				
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); // 0 to disable host verification
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 0 to not verify peer

				$result = curl_exec($curl);

				$err = curl_error($curl);
				curl_close($curl);
				if ($err)
				{
					echo "curl Error :" . $err;
				}

				return $result;
			}

			$four_spaces = '&nbsp;&nbsp;&nbsp;&nbsp;';

			// FOREX
			
			function GetForex($currency)
			{
				setlocale(LC_ALL, "en_US");

				$curl = curl_init();

				curl_setopt_array($curl, [
					CURLOPT_URL => 
					"https://api.twelvedata.com/exchange_rate?symbol=AUD/" . $currency . "&apikey=5b94a27a1510460b90431b961174d5c1",
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_SSL_VERIFYHOST => 0,
					CURLOPT_SSL_VERIFYPEER => 0,					
					CURLOPT_ENCODING => "",
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 30,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => "GET",
					CURLOPT_HTTPHEADER => [
						"X-RapidAPI-Host: twelve-data1.p.rapidapi.com",
						"X-RapidAPI-Key: f4822a10bdmsh1ea92c3e50995a4p114ccdjsn5765c48aad9f"
					],
				]);

				$response = curl_exec($curl);
				$err = curl_error($curl);

				curl_close($curl);

				if ($err) 
				{
					echo "cURL Error #:" . $err;
				} 
				else 
				{
					// echo $response;
					$data = json_decode($response, TRUE);
				}
				return $data;
			}			
			
			$rate = GetForex("USD");
			
			$usdaud = $rate["rate"];
			
			$rate = GetForex("EUR");
			
			$euraud = $rate["rate"];
			
			$rate = GetForex("GBP");
			
			$gbpaud = $rate["rate"];
			
			$rate = GetForex("CHF");
			
			$chfaud = $rate["rate"];
			
			$rate = GetForex("CAD");
			
			$cadaud = $rate["rate"];
			
			$rate = GetForex("NZD");			
			
			$nzdaud = $rate["rate"];
			
			$rate = GetForex("INR");
			
			$inraud = $rate["rate"];
			
			$rate = GetForex("RUB");
			
			$rubaud = $rate["rate"];			
			
			// Crypto

			// BTC_USD
			$json = CallAPI('GET', 'https://api.coindesk.com/v1/bpi/currentprice/AUD.json', false, false);
			$data_btc = json_decode($json, TRUE);
			$btc_price_aud = str_replace(",", "", $data_btc["bpi"]["AUD"]["rate"]);

			//GRC
			$json = CallAPI('GET', 'https://www.southxchange.com/api/price/GRC/BTC', false, false);
			$data_south_exchange = json_decode($json, TRUE);
			$btc_grc = $data_south_exchange["Last"];
			$aud_grc = $btc_grc * $btc_price_aud;
			
			//BCH
			$json = CallAPI('GET', 'https://www.southxchange.com/api/price/BCH/BTC', false, false);
			$data_south_exchange = json_decode($json, TRUE);
			$btc_bch = $data_south_exchange["Last"];
			$aud_bch = $btc_bch * $btc_price_aud;

			//DASH
			$json = CallAPI('GET', 'https://www.southxchange.com/api/price/DASH/BTC', false, false);
			$data_south_exchange = json_decode($json, TRUE);
			$btc_dash = $data_south_exchange["Last"];
			$aud_dash = $btc_dash * $btc_price_aud;
			
			//ETH
			$json = CallAPI('GET', 'https://www.southxchange.com/api/price/ETH/BTC', false, false);
			$data_south_exchange = json_decode($json, TRUE);
			$btc_eth = $data_south_exchange["Last"];
			$aud_eth = $btc_eth * $btc_price_aud;			
			
			//BCHSV
			$json        = CallAPI('GET', 'https://api.diadata.org/v1/assetQuotation/BitcoinSV/0x0000000000000000000000000000000000000000', false, false);
			$diadata_bchsv = json_decode($json, TRUE);
			$usd_bchsv     = $diadata_bchsv["Price"];
			$aud_bchsv   = $usd_bchsv / $usdaud;
			
			//Solana
			$json        = CallAPI('GET', 'https://api.diadata.org/v1/assetQuotation/Solana/0x0000000000000000000000000000000000000000', false, false);
			$diadata_solana = json_decode($json, TRUE);
			$usd_solana     = $diadata_solana["Price"];
			$aud_solana   = $usd_solana / $usdaud;			
			
			// Precious Metals
	
			// Gold
			$gold = get_commodity_price("gold", "https://markets.businessinsider.com/commodities/gold-price", "//*[@class='price-section__current-value']");
			$gold_aud = $gold / $usdaud;
			$gold_aud_out = number_format(floatval($gold_aud), 2, '.', '');
			
			// silver
			$silver = get_commodity_price("silver", "https://markets.businessinsider.com/commodities/silver-price", "//*[@class='price-section__current-value']");
			$silver_aud = $silver / $usdaud;
			$silver_aud_out = number_format(floatval($silver_aud), 2, '.', '');
			
			// Paladium
			$paladium = get_commodity_price("paladium", "https://markets.businessinsider.com/commodities/palladium-price", "//*[@class='price-section__current-value']");
			$paladium_out = number_format(floatval($paladium), 2, '.', '');

			// Brent Crude
			$brent_usd = get_commodity_price("brent", "https://markets.businessinsider.com/commodities/oil-price", "//*[@class='price-section__current-value']");
			$brent_out = number_format(floatval($brent_usd), 2, '.', '');

			// Ethanol
			$ethanol_usd = get_commodity_price("ethanol", "https://markets.businessinsider.com/commodities/ethanol-price", "//*[@class='price-section__current-value']");
			$ethanol_out = number_format(floatval($ethanol_usd), 2, '.', '');

			// Lean Hog
			$lean_hog = get_commodity_price("lean-hog", "https://markets.businessinsider.com/commodities/lean-hog-price", "//*[@class='price-section__current-value']");
			$lean_hog_out = number_format(floatval($lean_hog), 2, '.', '');

			// TSLA
			$tsla = get_commodity_price("tsla", "https://markets.businessinsider.com/stocks/tsla-stock", "//*[@class='price-section__current-value']");
			$tsla_out = number_format(floatval($tsla), 2, '.', '');

			// AMZN
			$amzn  = get_commodity_price("amzn", "https://markets.businessinsider.com/stocks/amzn-stock", "//*[@class='price-section__current-value']");

			$b = str_replace( ',', '', $amzn );

			if( is_numeric( $b ) ) 
			{
				$amzn = $b;
			}
			
			// NVDA
			$nvda = get_commodity_price("nvda", "https://markets.businessinsider.com/stocks/nvda-stock", "//*[@class='price-section__current-value']");
			$nvda_out = number_format(floatval($nvda), 2, '.', '');
			
			// Assuming $amzn is already defined earlier in your code
			$amzn_out = number_format(floatval($amzn), 2, '.', '');

			// MSFT
			$msft = get_commodity_price("msft", "https://markets.businessinsider.com/stocks/msft-stock", "//*[@class='price-section__current-value']");
			$msft_out = number_format(floatval($msft), 2, '.', '');

			echo('			<div id="outer1">' . PHP_EOL);

			// Timezones
			$time = date("H:i");
			$time2 = new DateTime($time);
			echo('				<div class="box1">'. PHP_EOL);
			echo('					<h2>World Clock</h2>' . PHP_EOL);
			echo('					<span class="time"><span class="home">Melbourne</span></span>' . PHP_EOL);
			echo('					<span class="timed"><span class="home">' . $time . '</span></span>' . PHP_EOL);
			echo('					<br/>' . PHP_EOL);

			echo('					<span class="time">Jakata</span>' . PHP_EOL);
			$time2 = $time2->setTimeZone(new DateTimeZone("Asia/Jakarta"));
			echo('					<span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);
			echo('					<br/>' . PHP_EOL);

			echo('					<span class="time">Johannesburg</span>' . PHP_EOL);
			$time2 = $time2->setTimeZone(new DateTimeZone("Africa/Johannesburg"));
			echo('					<span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);
			echo('					<br/>' . PHP_EOL);

			echo('					<span class="time">London</span>' . PHP_EOL);
			$time2 = $time2->setTimeZone(new DateTimeZone("Europe/London"));
			echo('					<span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);
			echo('					<br/>' . PHP_EOL);

			echo('					<span class="time">Los Angeles</span>' . PHP_EOL);
			$time2 = $time2->setTimeZone(new DateTimeZone("America/Los_Angeles"));
			echo('					<span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);
			echo('					<br/>' . PHP_EOL);

			echo('					<span class="time">Moscow</span>' . PHP_EOL);
			$time2 = $time2->setTimeZone(new DateTimeZone("Europe/Moscow"));
			echo('					<span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);
			echo('					<br/>' . PHP_EOL);

			echo('					<span class="time">New Dehli</span>' . PHP_EOL);
			$time2 = $time2->setTimeZone(new DateTimeZone("Asia/Kolkata"));
			echo('					<span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);
			echo('					<br/>' . PHP_EOL);

			echo('					<span class="time">New York</span>' . PHP_EOL);
			$time2 = $time2->setTimeZone(new DateTimeZone("America/New_York"));
			echo('					<span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);
			echo('					<br/>' . PHP_EOL);

			echo('					<span class="time">Rome</span>' . PHP_EOL);
			$time2 = $time2->setTimeZone(new DateTimeZone("Europe/Rome"));
			echo('					<span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);
			echo('					<br/>' . PHP_EOL);

			echo('					<span class="time">Shanghai</span>' . PHP_EOL);
			$time2 = $time2->setTimeZone(new DateTimeZone("Asia/Shanghai"));
			echo('					<span class="timed">' . $time2->format("H:i") . '</span>' . PHP_EOL);
			echo('					<br/>' . PHP_EOL);

			echo('				</div>' . PHP_EOL);
			echo('			</div><!-- end //outer1 -->' . PHP_EOL);

			// Crypto
			echo('			<div id="outer2">' . PHP_EOL);
			echo('				<div class="box1">' . PHP_EOL);
			echo('					<h2>' . PHP_EOL);
			echo('						Crypto ($AUD)' . PHP_EOL);
			echo('					</h2>' . PHP_EOL);

			// BTC
			echo('<span class="crypto">BTC:</span>' . PHP_EOL);
			echo('<span class="cryptod">' . number_format(floatval($btc_price_aud), 2, '.', '') . $four_spaces . '</span>' . PHP_EOL);
			echo('<br/>' . PHP_EOL);

			// GRC
			echo('<span class="crypto">GRC:</span>' . PHP_EOL);
			echo('<span class="cryptod">' . number_format(floatval($aud_grc), 6, '.', '') . '</span>' . PHP_EOL);
			echo('<br/>' . PHP_EOL);

			// DASH
			echo('<span class="crypto">DASH:</span>' . PHP_EOL);
			echo('<span class="cryptod">' . number_format(floatval($aud_dash), 2, '.', '') . $four_spaces . '</span>' . PHP_EOL);
			echo('<br/>' . PHP_EOL);

			// ETH
			echo('<span class="crypto">ETH:</span>' . PHP_EOL);
			echo('<span class="cryptod">' . number_format(floatval($aud_eth), 2, '.', '') . $four_spaces . '</span>' . PHP_EOL);
			echo('<br/>' . PHP_EOL);

			// BCHSV
			echo('<span class="crypto">BCHSV:</span>' . PHP_EOL);
			echo('<span class="cryptod">' . number_format(floatval($aud_bchsv), 2, '.', '') . $four_spaces . '</span>' . PHP_EOL);
			echo('<br/>' . PHP_EOL);

			// BCH
			echo('<span class="crypto">BCH:</span>' . PHP_EOL);
			echo('<span class="cryptod">' . number_format(floatval($aud_bch), 2, '.', '') . $four_spaces . '</span>' . PHP_EOL);
			echo('<br/>' . PHP_EOL);

			// Solna
			echo('<span class="crypto">Solana:</span>' . PHP_EOL);
			echo('<span class="cryptod">' . number_format(floatval($aud_solana), 2, '.', '') . $four_spaces . '</span>' . PHP_EOL);
			echo('<br/>' . PHP_EOL);

			echo('				</div><!-- end box1 -->' . PHP_EOL);
			echo('			</div><!-- end outer2 -->' . PHP_EOL);

			// Precious Metals
			echo('			<div id="outer3">' . PHP_EOL);
			echo('				<div class="box1">' . PHP_EOL);
			echo('				<h2>Comodities</h2>' . PHP_EOL);
			echo('					');
			echo('				' . PHP_EOL);
			echo('					<span class="pmhead1">Commodity</span>'  . PHP_EOL);
			echo('					<span class="pmhead2">Price</span>'  . PHP_EOL);
			echo('					<span class="pmhead3">Unit</span>'  . PHP_EOL);
			echo('					<br/>' . PHP_EOL);

			echo('					<span class="precious1">Gold:</span>'  . PHP_EOL);
			echo('                  <span class="precious2">' . number_format(floatval($gold_aud_out), 2, '.', '') . '</span>' . PHP_EOL);

			echo('					<span class="precious3">$AUD/troy ounce</span>'  . PHP_EOL);
			echo('					<br/>' . PHP_EOL);

			echo('					<span class="precious1">Silver:</span>'  . PHP_EOL);
			echo('					<span class="precious2">' . $silver_aud_out . '</span>' . PHP_EOL);
			echo('					<span class="precious3">$AUD/troy ounce</span>'  . PHP_EOL);
			echo('					<br/>' . PHP_EOL);

			echo('					<span class="precious1">Paladium:</span>'  . PHP_EOL);
			echo('					<span class="precious2">' . $paladium_out . '</span>' . PHP_EOL);
			echo('					<span class="precious3">$US/troy ounce</span>'  . PHP_EOL);
			echo('					<br/>' . PHP_EOL);

			echo('					<span class="precious1">Brent Crude: </span>' . PHP_EOL);
			echo('					<span class="precious2">' . $brent_out . '</span>' . PHP_EOL);
			echo('					<span class="precious3">$US/barrel</span>' . PHP_EOL);
			echo('					<br/>' . PHP_EOL);

			echo('					<span class="precious1">Ethanol: </span>' . PHP_EOL);
			echo('					<span class="precious2">' . $ethanol_out . '</span>' . PHP_EOL);
			echo('					<span class="precious3">$US/gallon</span>' . PHP_EOL);
			echo('					<br/>' . PHP_EOL);

			echo('					<span class="precious1">Lean Hog: </span>' . PHP_EOL);
			echo('					<span class="precious2">' . $lean_hog_out . '</span>' . PHP_EOL);
			echo('					<span class="precious3">$US/lb</span>' . PHP_EOL);
			echo('				</div>' . PHP_EOL);
			echo('			</div><!-- end //outer3 -->' . PHP_EOL);

			// Forex
			echo('			<div id="outer4">' . PHP_EOL);
			echo('				<div class="box1">' . PHP_EOL);
			echo('				<h2>' . PHP_EOL);
			echo('					FOREX ($AUD)' . PHP_EOL);
			echo('				</h2>' . PHP_EOL);
			echo('					<span class="currency_label">USD:</span>' . PHP_EOL);
			echo('                  <span class="currency_data">' . number_format(floatval($usdaud), 5, '.', '') . '</span>' . PHP_EOL);
			echo('                  <br/>' . PHP_EOL);
            echo('                  <span class="currency_label">EUR:</span>' . PHP_EOL);
			echo('                  <span class="currency_data">' . number_format(floatval($euraud), 5, '.', '') . '</span>' . PHP_EOL);
			echo('                  <br/>' . PHP_EOL);
			echo('                  <span class="currency_label">GBP:</span>' . PHP_EOL);
            echo('                  <span class="currency_data">' . number_format(floatval($gbpaud), 5, '.', '') . '</span>' . PHP_EOL);
            echo('                  <br/>' . PHP_EOL);
			echo('                  <span class="currency_label">CHF:</span>' . PHP_EOL);
            echo('                  <span class="currency_data">' . number_format(floatval($chfaud), 5, '.', '') . '</span>' . PHP_EOL);
			echo('                  <br/>' . PHP_EOL);
			echo('                  <span class="currency_label">CAD:</span>' . PHP_EOL);
			echo('                  <span class="currency_data">' . number_format(floatval($cadaud), 5, '.', '') . '</span>' . PHP_EOL);
			echo('                  <br/>' . PHP_EOL);
			echo('                  <span class="currency_label">NZD:</span>' . PHP_EOL);
			echo('                  <span class="currency_data">' . number_format(floatval($nzdaud), 5, '.', '') . '</span>' . PHP_EOL);
			echo('                  <br/>' . PHP_EOL);
			echo('                  <span class="currency_label">INR:</span>' . PHP_EOL);
			echo('                  <span class="currency_data">' . number_format(floatval($inraud), 5, '.', '') . '</span>' . PHP_EOL);
			echo('                  <br/>' . PHP_EOL);
			echo('					<span class="currency_label">RUB:</span>' . PHP_EOL); 
			echo('                  <span class="currency_data">' . number_format(floatval($rubaud), 5, '.', '') . '</span>' . PHP_EOL);
			echo('					<br/>' . PHP_EOL);
			echo('				</div>' . PHP_EOL);
			echo('			</div><!-- end //outer4 -->' . PHP_EOL);

			// Stocks
			echo('			<div id="outer5">' . PHP_EOL);
			echo('				<div class="box1">' . PHP_EOL);
			echo('					<h2>Shares</h2>' . PHP_EOL);
			echo('					<span class="currency_label">TSLA:</span>' . PHP_EOL);
			echo('					<span class="currency_data">' . $tsla_out . '</span>' . PHP_EOL);
			echo('					<span class="currency_label">$US</span>' . PHP_EOL);
			echo('					<br/>' . PHP_EOL);
			echo('					<span class="currency_label">AMZN:</span>' . PHP_EOL);
			echo('					<span class="currency_data">' . $amzn_out . '</span>' . PHP_EOL);
			echo('					<span class="currency_label">$US</span>' . PHP_EOL);
			echo('					<br/>' . PHP_EOL);
			echo('					<span class="currency_label">MSFT:</span>' . PHP_EOL);
			echo('					<span class="currency_data">' . $msft_out . '</span>' . PHP_EOL);
			echo('					<span class="currency_label">$US</span>' . PHP_EOL);
			echo('					<br/>' . PHP_EOL);
			echo('					<span class="currency_label">NVDA:</span>' . PHP_EOL);
			echo('					<span class="currency_data">' . $nvda_out . '</span>' . PHP_EOL);
			echo('					<span class="currency_label">$US</span>' . PHP_EOL);
			echo('					<br/>' . PHP_EOL);
			echo('				</div>' . PHP_EOL);
			echo('			</div><!-- end //outer5 -->' . PHP_EOL);
			echo('			<div id="footer">' . PHP_EOL);
			echo('			<div class="box2">' . PHP_EOL);
			echo('				<div class="footer2">' . PHP_EOL);			
			echo('		  		  <a href="http://jigsaw.w3.org/css-validator/check/referer"><img style="border:0;width:89px;height:31px" src="http://jigsaw.w3.org/css-validator/images/vcss" alt="Valid CSS!" /></a>' . PHP_EOL);
			echo('	  	   		  <a href="http://validator.w3.org/check?uri=referer"><img src="http://www.w3.org/Icons/valid-xhtml10" alt="Valid XHTML 1.0 Strict" height="31" width="89" /></a>' . PHP_EOL);			
			echo('				</div>' . PHP_EOL);
			echo('			</div>' . PHP_EOL);
			echo('		</div>' . PHP_EOL);			
		?>
		</div> <!-- end //wrapper -->
	</body>
</html>
