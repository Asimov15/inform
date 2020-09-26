<?php

// David Zuccaro 09/08/2018
// Miscellaneous Information
// World Clock
// 11/08/2018 Added crypto currency prices
// 12/08/2018 Added FOREX
// 15/08/2018 Added Silver
// 03/01/2018 Added Dash and removed BCH
// 24/09/2020 Use different currency API: https://api.exchangeratesapi.io/

?>

<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en'>

	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
		<meta http-equiv="refresh" content="1200"/>
		<link rel="stylesheet" type="text/css" href="inform.css?version=1.5"/>
		<title>David's Information</title>
	</head>

	<body>

		<h1>David's Information</h1>

		<div id="wrapper">

			<?php
				function display_xml_error($error)
				{
					$return = str_repeat('-', $error->column) . "^\n";
	
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
					$return .= trim($error->message) .
								"\n  Line: $error->line" .
								"\n  Column: $error->column";
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
					$command = "wget -q -O " . $file_name . " " . $web_reference;
					shell_exec($command);
					$doc = new DOMDocument();
					libxml_use_internal_errors(true);
					if (!$doc->loadHTMLFile($file_name))
					{
						foreach (libxml_get_errors() as $error) 
						{
							echo display_xml_error($error);
							print "\n";
						}
					};

					//libxml_use_internal_errors(false);

					$xml_string = $doc->saveXML();

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
				setlocale(LC_MONETARY, "en_US");
				$params = new \stdClass();
				$params->base = "AUD";
				$params->symbols = "EUR,GBP,USD,CHF,CAD,NZD,INR";
				$json = CallAPI('GET', 'https://api.exchangeratesapi.io/latest', $params , false);
				$data = json_decode($json, TRUE);
				$usdaud = $data["rates"]["USD"];
				$gbpaud = $data["rates"]["GBP"];
				$chfaud = $data["rates"]["CHF"];
				$cadaud = $data["rates"]["CAD"];
				$nzdaud = $data["rates"]["NZD"];
				$euraud = $data["rates"]["EUR"];
				$inraud = $data["rates"]["INR"];

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

				$json = CallAPI('GET', 'https://poloniex.com/public?command=returnTicker', false, false);
				$data_poloniex = json_decode($json, TRUE);

				//Dash
				$btc_dash = $data_poloniex["BTC_DASH"]["last"];
				$aud_dash = $btc_dash * $btc_price_aud;

				//ETH
				$btc_eth = $data_poloniex["BTC_ETH"]["last"];
				$aud_eth = $btc_eth * $btc_price_aud;

				//BCHSV
				$btc_bchsv = $data_poloniex["BTC_BCHSV"]["last"];
				$aud_bchsv = $btc_bchsv * $btc_price_aud;

				//BCHABC
				$btc_bchabc = $data_poloniex["BTC_BCHABC"]["last"];
				$aud_bchabc = $btc_bchabc * $btc_price_aud;

				//BCHEOS
				$btc_eos = $data_poloniex["BTC_EOS"]["last"];
				$aud_eos = $btc_eos * $btc_price_aud;

				// Precious Metals
				// Gold

				$gold_url = 'http://goldpricez.com/api/rates/currency/usd/measure/all';
				$gold_json = CallAPI('GET', $gold_url, false, '352b69e93c5a43d513e4db1e4803019f352b69e9');
				$gd = str_replace("\\", "", $gold_json);
				$gd = substr($gd, 1, -1);
				$gold_data = json_decode($gd, TRUE);
				$gold_aud = $gold_data["ounce_price_usd"] / $usdaud;

				// Silver
				$silver_usd = get_commodity_price("silver", "http://www.kitco.com/charts/livesilver.html", "//*[@id='sp-bid']");
				$silver_aud = $silver_usd / $usdaud;
				$silver_out_oz =  money_format('%7.2i', $silver_aud);

				// Brent Crude
				$brent_usd = get_commodity_price("brent", "https://markets.businessinsider.com/commodities/oil-price", "//*[@class='price-section__current-value']");
				$brent_out = money_format('%7.2i', $brent_usd);
				// Lean Hog
				$lean_hog  = get_commodity_price("lean-hog", "https://markets.businessinsider.com/commodities/lean-hog-price", "//*[@class='price-section__current-value']");
				$lean_hog_out = money_format('%7.2i', $lean_hog);

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
				echo('					<span class="crypto">BTC:</span>');
				echo(PHP_EOL);
				echo('					<span class="cryptod">' . money_format('%9.2i', floatval($btc_price_aud)) . $four_spaces . '</span>');
				echo(PHP_EOL);
				echo('					<br/>' . PHP_EOL);

				//GRC
				echo('					<span class="crypto">GRC:</span>');
				echo(PHP_EOL);
				echo('					<span class="cryptod">' . money_format('%9.6i', $aud_grc) . '</span>');
				echo(PHP_EOL);
				echo('					<br/>' . PHP_EOL);

				//DASH
				echo('					<span class="crypto">DASH:</span>');
				echo(PHP_EOL);
				echo('					<span class="cryptod">' . money_format('%9.2i', $aud_dash) . $four_spaces . '</span>');
				echo(PHP_EOL);
				echo('					<br/>' . PHP_EOL);

				//ETH
				echo('					<span class="crypto">ETH:</span>');
				echo(PHP_EOL);
				echo('					<span class="cryptod">' . money_format('%9.2i', $aud_eth) . $four_spaces . '</span>');
				echo(PHP_EOL);
				echo('					<br/>' . PHP_EOL);

				//BCHSV
				echo('					<span class="crypto">BCHSV:</span>');
				echo(PHP_EOL);
				echo('					<span class="cryptod">' . money_format('%9.2i', $aud_bchsv) . $four_spaces . '</span>');
				echo(PHP_EOL);
				echo('					<br/>' . PHP_EOL);

				//BCHABC
				echo('					<span class="crypto">BCHABC:</span>');
				echo(PHP_EOL);
				echo('					<span class="cryptod">' . money_format('%9.2i', $aud_bchabc) . $four_spaces . '</span>');
				echo(PHP_EOL);
				echo('					<br/>' . PHP_EOL);

				//EOS
				echo('					<span class="crypto">EOS:</span>');
				echo(PHP_EOL);
				echo('					<span class="cryptod">' . money_format('%9.2i', $aud_eos) . $four_spaces . '</span>');
				echo(PHP_EOL);
				echo('					<br/>' . PHP_EOL);

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
				echo('					<span class="precious2">' . money_format('%7.2i', $gold_aud  ) . '</span>' . PHP_EOL);
				echo('					<span class="precious3">$AUD/troy ounce</span>'  . PHP_EOL);
				echo('					<br/>' . PHP_EOL);
				echo('					<span class="precious1">Silver:</span>'  . PHP_EOL);
				echo('					<span class="precious2">' . $silver_out_oz . '</span>' . PHP_EOL);
				echo('					<span class="precious3">$AUD/troy ounce</span>'  . PHP_EOL);
				echo('					<br/>' . PHP_EOL);
				echo('					<span class="precious1">Brent Crude: </span>' . PHP_EOL);
				echo('					<span class="precious2">' . $brent_out . '</span>' . PHP_EOL);
				echo('					<span class="precious3">$US/barrel</span>' . PHP_EOL);
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
				echo('					<span class="currency">USD: ' . money_format('%2.5i', $usdaud) . '</span>' . PHP_EOL);
				echo('					<br/>' . PHP_EOL);
				echo('					<span class="currency">EUR: ' . money_format('%2.5i', $euraud) . '</span>' . PHP_EOL);
				echo('					<br/>' . PHP_EOL);
				echo('					<span class="currency">GBP: ' . money_format('%2.5i', $gbpaud) . '</span>' . PHP_EOL);
				echo('					<br/>' . PHP_EOL);
				echo('					<span class="currency">CHF: ' . money_format('%2.5i', $chfaud) . '</span>' . PHP_EOL);
				echo('					<br/>' . PHP_EOL);
				echo('					<span class="currency">CAD: ' . money_format('%2.5i', $cadaud) . '</span>' . PHP_EOL);
				echo('					<br/>' . PHP_EOL);
				echo('					<span class="currency">NZD: ' . money_format('%2.5i', $nzdaud) . '</span>' . PHP_EOL);
				echo('					<br/>' . PHP_EOL);
				echo('					<span class="currency">INR: ' . money_format('%2.5i', $inraud) . '</span>' . PHP_EOL);
				echo('					<br/>' . PHP_EOL);
				echo('				</div>' . PHP_EOL);
				echo('			</div><!-- end //outer4 -->' . PHP_EOL);

				// Commodities
				echo('			<div id="outer5">' . PHP_EOL);
				echo('				<div class="box1">' . PHP_EOL);
				echo('					<h2 style="text-align: left; margin-left: -5px">Shares</h2>' . PHP_EOL);
				echo('					<br/>' . PHP_EOL);
				echo('				</div>' . PHP_EOL);
				echo('			</div><!-- end //outer5 -->' . PHP_EOL);

				echo('			<div id="footer">' . PHP_EOL);
				echo('			<div class="box2">' . PHP_EOL);
				echo('				<div class="footer2">' . PHP_EOL);
				echo('					<span>Powered by <a href="https://www.coindesk.com/price/">CoinDesk</a></span>' . PHP_EOL);
				echo('					<a href="http://goldpricez.com">Gold rates by <img alt="Gold Price Data" src="http://goldpricez.com/assets/logo.jpg" height="20"/></a>' . PHP_EOL);
				echo('		  		  <a href="http://jigsaw.w3.org/css-validator/check/referer"><img style="border:0;width:89px;height:31px" src="http://jigsaw.w3.org/css-validator/images/vcss" alt="Valid CSS!" /></a>' . PHP_EOL);
				echo('	  	   		  <a href="http://validator.w3.org/check?uri=referer"><img src="http://www.w3.org/Icons/valid-xhtml10" alt="Valid XHTML 1.0 Strict" height="31" width="89" /></a>' . PHP_EOL);

				if ($silver_error != 0)
				{
					echo('<br/>' . PHP_EOL);
					if ($silver_error == 4)
					{
						echo('Network Error Accessing Silver Price Web Page' . PHP_EOL);
					}
					else
					{
						echo('Silver Error Code: ' . $silver_error . PHP_EOL);
					}
				}
				echo('				</div>' . PHP_EOL);
				echo('			</div>' . PHP_EOL);
				echo('		</div>' . PHP_EOL);

				$servername = "127.0.0.1";
				$username = "julius";
				$password = "happy1";
				$dbname = "rome";

				// Create connection
				$conn = new mysqli($servername, $username, $password, $dbname);

				// Check connection
				if ($conn->connect_error)
				{
					die("Connection failed: " . $conn->connect_error);
				}

				$country	= ip_info($_SERVER['REMOTE_ADDR'], 'country');
				$address 	= ip_info($_SERVER['REMOTE_ADDR'], 'address');
				$city		= ip_info($_SERVER['REMOTE_ADDR'], 'city');
				$state		= ip_info($_SERVER['REMOTE_ADDR'], 'state');
				$region		= ip_info($_SERVER['REMOTE_ADDR'], 'region');

				if (strlen($country) <= 0)
					$country = ip_info($_SERVER['HTTP_X_FORWARDED_FOR'], 'Country') ;

				$now = date('Y-m-d H:i:s');
				$sql = "INSERT INTO palatine
						(time_access, remote_addr, http_x_forwarded_for, address, city, state, region, country) VALUES
						('" .   $now	 . "', '" . $_SERVER['REMOTE_ADDR'] . "', '" . $_SERVER['HTTP_X_FORWARDED_FOR'] . "', '" .
								$address . "', '" . $city				   . "', '" . $state . "', '" .
								$region  . "', '" . $country . "')";

				if ($conn->query($sql) === TRUE)
				{
					echo(PHP_EOL);
				}
				else
				{
					echo "Error: " . $sql . "<br>" . $conn->error;
				}

				$conn->close();
			?>
		</div> <!-- end //wrapper -->
	</body>
</html>
