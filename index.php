<?php

$store_name = 'littleliffner';
$access_token = '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';

$curl = curl_init();

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
curl_close($curl);

$data = json_decode($response, true);

$products = $data['products'];

$csv_result = 'id' . ',' . 
			  'title' . ',' .
			  'description' . ',' .
			  'link' . ',' .
			  'availability' . ',' .
			  'price' . ',' .
			  'brand' . ',' .
			  'gtin' . ',' .
			  'mpn' . ',' .
			  'condition' . PHP_EOL;

foreach ($products as $product) {
	$temp_line = $product['variants'][0]['sku'] . ',' .
				 $product['title'] . ',' .
				 'description' . ',' .
				//  str_replace(["\r", "\n", "\t"], '', $product['body_html']) . ',' .
				 'https://www.littleliffner.com/products/'.$product['handle'] . ',' .
				 ($product['variants'][0]['inventory_quantity'] == 0 ? 'out of stock' : 'in stock') . ',' .
				 $product['variants'][0]['price'] . ',' .
				 '' . ',' .
				 '' . ',' .
				 '' . ',' .
				 'refurbished' . PHP_EOL;
	$csv_result .= $temp_line;
}

$file = 'file.csv';
file_put_contents($file, $csv_result);

echo $csv_result;