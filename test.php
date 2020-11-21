<?php
	$output = "";
	$commodity = "brent";
	$web_reference = "oil-price";
	$file_name = "/var/www/html/temp/" . $commodity . ".html ";
	$command = "rm " . $file_name;
	shell_exec($command);
	$web_address = "https://markets.businessinsider.com/commodities/" . $web_reference;
	$command = "wget -q -O " . $file_name . $web_address;
	print $command;
	shell_exec($command);
	$doc = new DOMDocument();
	libxml_use_internal_errors(true);
	$doc->loadHTMLFile($file_name);
	libxml_use_internal_errors(false);
	print "doc";
	$xml_string = $doc->saveXML();
	print $xml_string;
	$xpath = new DOMXpath($doc);
	$elements = $xpath->query("//*[@class='price-section__current-value']");

	if (!is_null($elements))
	{
		print "hello";
		foreach ($elements as $element)
		{
			$nodes = $element->childNodes;
			foreach ($nodes as $node)
			{
				print "hello2";
				$output = $node->nodeValue;
			}
		}
	}
	return $output;
?>
