<?php

$index = 0;

$curl = curl_init();

while (true) {
	if ($index == 1) break;
	$index++;
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
				  'google_product_category' . ',' .
				  'brand' . ',' .
				  'identifier_exists' . ',' .
				  'condition' . PHP_EOL;
	
	foreach ($products as $product) {
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
		$product_metafields = $result['metafields'];
		$condition = 'new';
		$google_product_category = '';
		foreach ($product_metafields as $product_metafield) {
			if ($product_metafield['key'] == 'condition') {
				$condition = $product_metafield['value'];
			}
			if ($product_metafield['key'] == 'google_product_category') {
				$google_product_category = $product_metafield['value'];
			}
		}

		$temp_line = $product['variants'][0]['sku'] . ',' .
					 $product['title'] . ',' .
					 $description . ',' .
					 'https://www.littleliffner.com/products/'.$product['handle'] . ',' .
					 $product['images'][0]['src'] . ',' .
					 ($product['variants'][0]['inventory_quantity'] == 0 ? 'out of stock' : 'in stock') . ',' .
					 $product['variants'][0]['price'] . ',' .
					 $google_product_category . ',' .
					 'brand name' . ',' .
					 'no' . ',' .
					 $condition . PHP_EOL;
		$csv_result .= $temp_line;
	}
	
	$file = 'product_feed.csv';
	file_put_contents($file, $csv_result);
	echo $csv_result;
	sleep(1 * 60 * 60);
}

curl_close($curl);
