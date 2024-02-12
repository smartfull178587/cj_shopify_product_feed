<?php

include 'helper.php';
require_once 'vendor/autoload.php';

use GuzzleHttp\Client;

logToFile(" > api initializing");

$curl = curl_init();
$client = new Client();

$store_name = 'littleliffner';
$access_token = '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';

$since_id = 0;

$csv_header = 'id' . ',' . 
					  'title' . ',' .
					  'description' . ',' .
					  'google_product_category' . ',' .
					  'link' . ',' .
					  'image_link' . ',' .
					  'availability' . ',' .
					  'price' . ',' .
					  'size' . ',' .
					  'brand' . ',' .
					  'identifier_exists' . ',' .
					  'condition' . PHP_EOL;
	
$csv_result_sek = $csv_result_eur = $csv_result_usd = $csv_header;
	
while(true) {
	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://'.$store_name.'.myshopify.com/admin/api/2023-10/products.json?since_id=' . $since_id,
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
	
	logToFile("send rest api request to get products");
	$response = curl_exec($curl);
	
	logToFile("receive response for the rest api request to get products");
	
	$data = json_decode($response, true);

	
	$products = $data['products'];
	
	logToFile("making products detail");
	
	if (!count($products)) break;

	$since_id = $products[count($products)-1]['id'];

	foreach ($products as $product) {
		if ($product['status'] != 'active') continue;
		$sizeOptionIndex = 0;
		$flag = false;
		foreach ($product['options'] as $option) {
			$sizeOptionIndex++;
			if ($option['name'] == 'Size') {
				$flag = true;
				break;
			}
		}
		$index = 0;
		foreach ($product['variants'] as $variant) {
			$title = str_replace(["\r", "\n", "\t"], '', $product['title']);
			$title = str_replace('"', '""', $title);
			$title = '"' . $title . '"';
			$description = str_replace(["\r", "\n", "\t"], '', $product['body_html']);
			$description = str_replace('"', '""', $description);
			$description = '"' . $description . '"';
			if ($flag)
				$size = '"' . $variant['option'.$sizeOptionIndex] . '"';
			else $size = '""';
		
			$condition = 'new';
		
			$query = '
				query ProductDetails($id: ID!) {
					product(id: $id) {
						variants(first: 10) {
							nodes {
								pricingInUSD: contextualPricing(context: { country: US }) {
									price { amount currencyCode }
								}
								pricingInSEK: contextualPricing(context: { country: SE }) {
									price { amount currencyCode }
								}
								pricingInEUR: contextualPricing(context: { country: DE }) {
									price { amount currencyCode }
								}
							}
						}
						productCategory {
							productTaxonomyNode {
								fullName
							}
						}
					}
				}
			';
			$variables = [
				"id" => $product['admin_graphql_api_id']
			];
			$response = $client->request('POST', 'https://'.$store_name.'.myshopify.com/admin/api/2023-10/graphql.json', [
				'headers' => [
					'Content-Type' => 'application/json',
					'X-Shopify-Access-Token' => $access_token,
				],
				'json' => [
					'query' => $query,
					"variables" => $variables
				],
			]);

			$response_body = $response->getBody()->getContents();
			$data = json_decode($response_body, true);

			$google_category = '""';
			if ($data['data']['product']['productCategory'] != null) {
				$google_category = '"'.$data['data']['product']['productCategory']['productTaxonomyNode']['fullName'].'"';
			}

			$csv_result_sek .= $product['variants'][$index]['sku'] . '-' . $product['variants'][$index]['id'] . ',' .
							$title . ',' .
							$description . ',' .
							$google_category . ',' .
							'https://www.littleliffner.com/products/'.$product['handle'].'?variant='.$product['variants'][$index]['id'] . ',' .
							$product['images'][0]['src'] . ',' .
							($product['variants'][$index]['inventory_quantity'] == 0 ? 'out of stock' : 'in stock') . ',' .
							$data['data']['product']['variants']['nodes'][$index]['pricingInSEK']['price']['amount'] . ',' .
							$size . ',' .
							'Little Liffner' . ',' .
							'no' . ',' .
							$condition . PHP_EOL;
			$csv_result_usd .= $product['variants'][$index]['sku'] . '-' . $product['variants'][$index]['id'] . ',' .
   							$title . ',' .
   							$description . ',' .
							$google_category . ',' .
							'https://www.littleliffner.com/products/'.$product['handle'].'?variant='.$product['variants'][$index]['id'] . ',' .
   							$product['images'][0]['src'] . ',' .
   							($product['variants'][$index]['inventory_quantity'] == 0 ? 'out of stock' : 'in stock') . ',' .
   							$data['data']['product']['variants']['nodes'][$index]['pricingInUSD']['price']['amount'] . ',' .
							$size . ',' .
							'Little Liffner' . ',' .
   							'no' . ',' .
   							$condition . PHP_EOL;
			$csv_result_eur .= $product['variants'][$index]['sku'] . '-' . $product['variants'][$index]['id'] . ',' .
							$title . ',' .
							$description . ',' .
							$google_category . ',' .
							'https://www.littleliffner.com/products/'.$product['handle'].'?variant='.$product['variants'][$index]['id'] . ',' .
							$product['images'][0]['src'] . ',' .
							($product['variants'][$index]['inventory_quantity'] == 0 ? 'out of stock' : 'in stock') . ',' .
							$data['data']['product']['variants']['nodes'][$index]['pricingInEUR']['price']['amount'] . ',' .
							$size . ',' .
							'Little Liffner' . ',' .
							'no' . ',' .
							$condition . PHP_EOL;
			$index++;
		}
	}
}

logToFile("ending making products detail");

logToFile("beginning creating csv file");

generateCSV('product_feed_sek.csv', $csv_result_sek);
generateCSV('product_feed_usd.csv', $csv_result_usd);
generateCSV('product_feed_eur.csv', $csv_result_eur);

logToFile("ending creating csv file");

curl_close($curl);

echo 'success!';

logToFile(" < api ending");