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

			// -----------------------------
			// Fast concurrent HTTP helpers
			// -----------------------------

			function _cache_get($key, $ttl_seconds)
			{
				if ($ttl_seconds <= 0) { return NULL; }
				$file = sys_get_temp_dir() . "/php_http_cache_" . $key . ".json";
				if (!file_exists($file)) { return NULL; }
				if ((time() - filemtime($file)) > $ttl_seconds) { return NULL; }
				$raw = @file_get_contents($file);
				if ($raw === FALSE) { return NULL; }
				$decoded = json_decode($raw, TRUE);
				return is_array($decoded) ? $decoded : NULL;
			}

			function _cache_set($key, $data, $ttl_seconds)
			{
				if ($ttl_seconds <= 0) { return; }
				$file = sys_get_temp_dir() . "/php_http_cache_" . $key . ".json";
				@file_put_contents($file, json_encode($data));
			}

			function MultiCallAPI($requests, $timeout_seconds = 12, $connect_timeout_seconds = 4, $cache_ttl_seconds = 60)
			// $requests: associative array key => ["url" => "...", "headers" => ["Header: value", ...]]
			{
				$cache_key = md5(json_encode($requests) . "|" . $timeout_seconds . "|" . $connect_timeout_seconds);
				$cached = _cache_get($cache_key, $cache_ttl_seconds);
				if ($cached !== NULL) { return $cached; }

				$mh = curl_multi_init();
				$handles = [];
				$results = [];

				foreach ($requests as $key => $req)
				{
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $req["url"]);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
					curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
					curl_setopt($ch, CURLOPT_TIMEOUT, $timeout_seconds);
					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connect_timeout_seconds);
					curl_setopt($ch, CURLOPT_ENCODING, ""); // gzip/deflate
					curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64) PHP-curl/fastfetch");
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

					if (isset($req["headers"]) && is_array($req["headers"]) && count($req["headers"]) > 0)
					{
						curl_setopt($ch, CURLOPT_HTTPHEADER, $req["headers"]);
					}

					curl_multi_add_handle($mh, $ch);
					$handles[$key] = $ch;
				}

				$running = NULL;
				do
				{
					$mrc = curl_multi_exec($mh, $running);
					if ($running)
					{
						curl_multi_select($mh, 0.5);
					}
				} while ($running && $mrc == CURLM_OK);

				foreach ($handles as $key => $ch)
				{
					$body = curl_multi_getcontent($ch);
					$err  = curl_error($ch);
					$code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

					$results[$key] = [
						"body" => $body,
						"error" => $err,
						"http_code" => $code
					];

					curl_multi_remove_handle($mh, $ch);
					curl_close($ch);
				}

				curl_multi_close($mh);

				_cache_set($cache_key, $results, $cache_ttl_seconds);
				return $results;
			}

			function extract_xpath_value_from_html($html, $xpath_query)
			{
				if ($html === NULL || $html === FALSE || strlen($html) < 10) { return ""; }
				$doc = new DOMDocument();
				libxml_use_internal_errors(true);
				@$doc->loadHTML($html);
				$xpath = new DOMXpath($doc);
				$elements = $xpath->query($xpath_query);
				$out = "";
				if (!is_null($elements))
				{
					foreach ($elements as $element)
					{
						$nodes = $element->childNodes;
						foreach ($nodes as $node)
						{
							$out = $node->nodeValue;
						}
					}
				}
				return $out;
			}

			function normalize_number($s)
			{
				$s = trim($s);
				$s = str_replace([",", "$", "€", "£", "AUD", "USD"], "", $s);
				$s = preg_replace("/[^0-9\.\-]/", "", $s);
				if ($s === "" || $s === "-" || $s === ".") { return 0.0; }
				return floatval($s);
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
			
			
			// -----------------------------
			// Forex (parallel)
			// -----------------------------
			$forex_currencies = ["USD","EUR","GBP","CHF","CAD","NZD","INR","RUB"];
			$forex_requests = [];
			foreach ($forex_currencies as $ccy)
			{
				$forex_requests[$ccy] = [
					"url" => "https://api.twelvedata.com/exchange_rate?symbol=AUD/" . $ccy . "&apikey=5b94a27a1510460b90431b961174d5c1",
					"headers" => []
				];
			}

			$forex_results = MultiCallAPI($forex_requests, 12, 4, 60);

			// Defaults, in case any one API call fails
			$usdaud = 0; $euraud = 0; $gbpaud = 0; $chfaud = 0; $cadaud = 0; $nzdaud = 0; $inraud = 0; $rubaud = 0;

			foreach ($forex_results as $ccy => $res)
			{
				if ($res["error"] == "" && $res["http_code"] >= 200 && $res["http_code"] < 300)
				{
					$data = json_decode($res["body"], TRUE);
					if (is_array($data) && isset($data["rate"]))
					{
						switch ($ccy)
						{
							case "USD": $usdaud = $data["rate"]; break;
							case "EUR": $euraud = $data["rate"]; break;
							case "GBP": $gbpaud = $data["rate"]; break;
							case "CHF": $chfaud = $data["rate"]; break;
							case "CAD": $cadaud = $data["rate"]; break;
							case "NZD": $nzdaud = $data["rate"]; break;
							case "INR": $inraud = $data["rate"]; break;
							case "RUB": $rubaud = $data["rate"]; break;
						}
					}
				}
			}


			// -----------------------------
			// Crypto (parallel)
			// -----------------------------
			$crypto_requests = [
				"BTC"   => ["url" => "https://api.diadata.org/v1/assetQuotation/Bitcoin/0x0000000000000000000000000000000000000000",   "headers" => []],
				"DASH"  => ["url" => "https://api.diadata.org/v1/assetQuotation/Dash/0x0000000000000000000000000000000000000000",      "headers" => []],
				"ETH"   => ["url" => "https://api.diadata.org/v1/assetQuotation/Ethereum/0x0000000000000000000000000000000000000000",   "headers" => []],
				"BSV"   => ["url" => "https://api.diadata.org/v1/assetQuotation/BitcoinSV/0x0000000000000000000000000000000000000000", "headers" => []],
				"SOL"   => ["url" => "https://api.diadata.org/v1/assetQuotation/Solana/0x0000000000000000000000000000000000000000",     "headers" => []],
				"XMR"   => ["url" => "https://api.diadata.org/v1/assetQuotation/Monero/0x0000000000000000000000000000000000000000",     "headers" => []],
			];

			$crypto_results = MultiCallAPI($crypto_requests, 12, 4, 60);

			$usd_bitcoin = 0; $aud_bitcoin = 0;
			$usd_dash = 0;   $aud_dash = 0;
			$usd_ethereum = 0; $aud_ethereum = 0;
			$usd_bsv = 0;    $aud_bsv = 0;
			$usd_solana = 0; $aud_solana = 0;
			$usd_monero = 0; $aud_monero = 0;

			foreach ($crypto_results as $sym => $res)
			{
				if ($res["error"] == "" && $res["http_code"] >= 200 && $res["http_code"] < 300)
				{
					$data = json_decode($res["body"], TRUE);
					if (is_array($data) && isset($data["Price"]))
					{
						$usd = $data["Price"];
						$aud = ($usdaud > 0) ? ($usd / $usdaud) : 0;

						switch ($sym)
						{
							case "BTC":  $usd_bitcoin = $usd;   $aud_bitcoin = $aud; break;
							case "DASH": $usd_dash = $usd;      $aud_dash = $aud; break;
							case "ETH":  $usd_ethereum = $usd;  $aud_ethereum = $aud; break;
							case "BSV":  $usd_bsv = $usd;       $aud_bsv = $aud; break;
							case "SOL":  $usd_solana = $usd;    $aud_solana = $aud; break;
							case "XMR":  $usd_monero = $usd;    $aud_monero = $aud; break;
						}
					}
				}
			}


			// -----------------------------
			// Commodities + stocks (parallel HTML fetch + XPath)
			// -----------------------------
			$bi_pages = [
				"gold" => ["url" => "https://markets.businessinsider.com/commodities/gold-price", "xpath" => "//*[@class='price-section__current-value']", "headers" => []],
				"silver" => ["url" => "https://markets.businessinsider.com/commodities/silver-price", "xpath" => "//*[@class='price-section__current-value']", "headers" => []],
				"palladium" => ["url" => "https://markets.businessinsider.com/commodities/palladium-price", "xpath" => "//*[@class='price-section__current-value']", "headers" => []],
				"platinum" => ["url" => "https://markets.businessinsider.com/commodities/platinum-price", "xpath" => "//*[@class='price-section__current-value']", "headers" => []],
				"brent" => ["url" => "https://markets.businessinsider.com/commodities/oil-price", "xpath" => "//*[@class='price-section__current-value']", "headers" => []],
				"ethanol" => ["url" => "https://markets.businessinsider.com/commodities/ethanol-price", "xpath" => "//*[@class='price-section__current-value']", "headers" => []],
				"lean-hog" => ["url" => "https://markets.businessinsider.com/commodities/lean-hog-price", "xpath" => "//*[@class='price-section__current-value']", "headers" => []],
				"tsla" => ["url" => "https://markets.businessinsider.com/stocks/tsla-stock", "xpath" => "//*[@class='price-section__current-value']", "headers" => []],
				"amzn" => ["url" => "https://markets.businessinsider.com/stocks/amzn-stock", "xpath" => "//*[@class='price-section__current-value']", "headers" => []],
				"nvda" => ["url" => "https://markets.businessinsider.com/stocks/nvda-stock", "xpath" => "//*[@class='price-section__current-value']", "headers" => []],
				"msft" => ["url" => "https://markets.businessinsider.com/stocks/msft-stock", "xpath" => "//*[@class='price-section__current-value']", "headers" => []],
			];

			$bi_requests = [];
			foreach ($bi_pages as $key => $cfg)
			{
				$bi_requests[$key] = ["url" => $cfg["url"], "headers" => $cfg["headers"]];
			}

			$bi_results = MultiCallAPI($bi_requests, 14, 5, 60);

			$bi_values = [];
			foreach ($bi_pages as $key => $cfg)
			{
				$html = (isset($bi_results[$key]) && $bi_results[$key]["error"] == "" && $bi_results[$key]["http_code"] >= 200 && $bi_results[$key]["http_code"] < 400)
					? $bi_results[$key]["body"]
					: "";
				$raw = extract_xpath_value_from_html($html, $cfg["xpath"]);
				$bi_values[$key] = normalize_number($raw);
			}

			// Precious Metals
			$gold = $bi_values["gold"];
			$gold_aud = ($usdaud > 0) ? ($gold / $usdaud) : 0;
			$gold_aud_out = number_format(floatval($gold_aud), 2, '.', '');

			$silver = $bi_values["silver"];
			$silver_aud = ($usdaud > 0) ? ($silver / $usdaud) : 0;
			$silver_aud_out = number_format(floatval($silver_aud), 2, '.', '');

			$platinum = $bi_values["platinum"];
			$platinum_aud = ($usdaud > 0) ? ($platinum / $usdaud) : 0;
			$platinum_aud_out = number_format(floatval($platinum_aud), 2, '.', '');
			
			$palladium = $bi_values["palladium"];
			$palladium_aud = ($usdaud > 0) ? ($palladium / $usdaud) : 0;
			$palladium_aud_out = number_format(floatval($palladium_aud), 2, '.', '');

			// Energy / Agriculture
			$brent_usd = $bi_values["brent"];
			$brent_out = number_format(floatval($brent_usd), 2, '.', '');

			$ethanol_usd = $bi_values["ethanol"];
			$ethanol_out = number_format(floatval($ethanol_usd), 2, '.', '');

			$lean_hog = $bi_values["lean-hog"];
			$lean_hog_out = number_format(floatval($lean_hog), 2, '.', '');

			// Stocks
			$tsla = $bi_values["tsla"];
			$tsla_out = number_format(floatval($tsla), 2, '.', '');

			$amzn = $bi_values["amzn"];
			$amzn_out = number_format(floatval($amzn), 2, '.', '');

			$nvda = $bi_values["nvda"];
			$nvda_out = number_format(floatval($nvda), 2, '.', '');

			$msft = $bi_values["msft"];
			$msft_out = number_format(floatval($msft), 2, '.', '');

			$time = date("H:i");
			$time2 = new DateTime($time);
			echo('			<div id="outer1">' . PHP_EOL);
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
			echo('<span class="cryptod">' . number_format(floatval($aud_bitcoin), 2, '.', '') . $four_spaces . '</span>' . PHP_EOL);
			echo('<br/>' . PHP_EOL);

			// DASH
			echo('<span class="crypto">DASH:</span>' . PHP_EOL);
			echo('<span class="cryptod">' . number_format(floatval($aud_dash), 2, '.', '') . $four_spaces . '</span>' . PHP_EOL);
			echo('<br/>' . PHP_EOL);

			// ETH
			echo('<span class="crypto">ETH:</span>' . PHP_EOL);
			echo('<span class="cryptod">' . number_format(floatval($aud_ethereum), 2, '.', '') . $four_spaces . '</span>' . PHP_EOL);
			echo('<br/>' . PHP_EOL);

			// BCHSV
			echo('<span class="crypto">BCHSV:</span>' . PHP_EOL);
			echo('<span class="cryptod">' . number_format(floatval($aud_bsv), 2, '.', '') . $four_spaces . '</span>' . PHP_EOL);
			echo('<br/>' . PHP_EOL);
			
			// Solana
			echo('<span class="crypto">Solana:</span>' . PHP_EOL);
			echo('<span class="cryptod">' . number_format(floatval($aud_solana), 2, '.', '') . $four_spaces . '</span>' . PHP_EOL);
			echo('<br/>' . PHP_EOL);
			
			// Monero
			echo('<span class="crypto">Monero:</span>' . PHP_EOL);
			echo('<span class="cryptod">' . number_format(floatval($aud_monero), 2, '.', '') . $four_spaces . '</span>' . PHP_EOL);
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

			echo('					<span class="precious1">Platinum:</span>'  . PHP_EOL);
			echo('					<span class="precious2">' . $platinum_aud_out . '</span>' . PHP_EOL);
			echo('					<span class="precious3">$AUD/troy ounce</span>'  . PHP_EOL);
			echo('					<br/>' . PHP_EOL);

			echo('					<span class="precious1">Palladium:</span>'  . PHP_EOL);
			echo('					<span class="precious2">' . $palladium_aud_out . '</span>' . PHP_EOL);
			echo('					<span class="precious3">$AUD/troy ounce</span>'  . PHP_EOL);
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
			echo('					<h3>United States</h3>' . PHP_EOL);
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
			echo('					<h3>Australia</h3>' . PHP_EOL);
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
