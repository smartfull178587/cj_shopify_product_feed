<?php

logToFile("cronjob testing");
exit;
$curl = curl_init();

$store_name = 'littleliffner';
$access_token = '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';

curl_setopt_array($curl, array(
	CURLOPT_URL => 'https://'.$store_name.'.myshopify.com/admin/api/2023-10/products.json',
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => '',
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 0,
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => 'GET',
	CURLOPT_HTTPHEADER => array(
		'X-Shopify-Access-Token: '.$access_token
	),
));

$response = curl_exec($curl);

$data = json_decode($response, true);

$products = $data['products'];

$csv_result = 'id' . ',' . 
				'title' . ',' .
				'description' . ',' .
				'link' . ',' .
				'image_link' . ',' .
				'availability' . ',' .
				'price' . ',' .
				'brand' . ',' .
				'identifier_exists' . ',' .
				'condition' . PHP_EOL;

foreach ($products as $product) {
	if ($product['status'] != 'active') continue;

	$description = str_replace(["\r", "\n", "\t"], '', $product['body_html']);
	$description = str_replace('"', '""', $description);
	$description = '"' . $description . '"';

	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://'.$store_name.'.myshopify.com/admin/api/2020-04/products/' . $product['id'] . '/metafields.json',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'GET',
		CURLOPT_HTTPHEADER => array(
			'X-Shopify-Access-Token: '.$access_token
		),
	));

	$response = curl_exec($curl);

	$result = json_decode($response, true);
	$condition = 'new';

	$temp_line = $product['variants'][0]['sku'] . ',' .
					$product['title'] . ',' .
					$description . ',' .
					'https://www.littleliffner.com/products/'.$product['handle'] . ',' .
					$product['images'][0]['src'] . ',' .
					($product['variants'][0]['inventory_quantity'] == 0 ? 'out of stock' : 'in stock') . ',' .
					$product['variants'][0]['price'] . ',' .
					'Little Liffner' . ',' .
					'no' . ',' .
					$condition . PHP_EOL;
	$csv_result .= $temp_line;
}

$file = 'product_feed.csv';
file_put_contents($file, $csv_result);
echo $csv_result;

curl_close($curl);

function logToFile($txt) {
	$log_file = "./log_file.log";
	$log_file_handle = null;
	if (file_exists($log_file)) {
		$log_file_handle = fopen($log_file, "a");
	} else {
		$log_file_handle = fopen($log_file, "w+");
	}

	$date = new DateTime();
	$date = $date->format("y:m:d h:i:s");

	fwrite($log_file_handle, '['.$date.'] '.$txt.PHP_EOL);
	fclose($log_file_handle);
}
