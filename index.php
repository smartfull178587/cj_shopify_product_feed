<?php

include 'helper.php';
require_once 'vendor/autoload.php';

use GuzzleHttp\Client;

logToFile(" > api initializing");

$curl = curl_init();
$client = new Client();

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

logToFile("send rest api request to get products");

$response = curl_exec($curl);

logToFile("receive response for the rest api request to get products");

$data = json_decode($response, true);

$products = $data['products'];

$csv_header = 'id' . ',' . 
				  'title' . ',' .
				  'description' . ',' .
				  'link' . ',' .
				  'image_link' . ',' .
				  'availability' . ',' .
				  'price' . ',' .
				  'brand' . ',' .
				  'identifier_exists' . ',' .
				  'condition' . PHP_EOL;

$csv_result_sek = $csv_result_eur = $csv_result_usd = $csv_header;

logToFile("beginning making products detail");

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

	$query = '
		query ProductDetails($id: ID!) {
			product(id: $id) {
			variants(first: 1) {
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

	$csv_result_sek .= $product['variants'][0]['sku'] . ',' .
						$product['title'] . ',' .
						$description . ',' .
						'https://www.littleliffner.com/products/'.$product['handle'] . ',' .
						$product['images'][0]['src'] . ',' .
						($product['variants'][0]['inventory_quantity'] == 0 ? 'out of stock' : 'in stock') . ',' .
						$data['data']['product']['variants']['nodes'][0]['pricingInSEK']['price']['amount'] . ',' .
						'Little Liffner' . ',' .
						'no' . ',' .
						$condition . PHP_EOL;
	$csv_result_usd .= $product['variants'][0]['sku'] . ',' .
					   $product['title'] . ',' .
					   $description . ',' .
					   'https://www.littleliffner.com/products/'.$product['handle'] . ',' .
					   $product['images'][0]['src'] . ',' .
					   ($product['variants'][0]['inventory_quantity'] == 0 ? 'out of stock' : 'in stock') . ',' .
					   $data['data']['product']['variants']['nodes'][0]['pricingInUSD']['price']['amount'] . ',' .
					   'Little Liffner' . ',' .
					   'no' . ',' .
					   $condition . PHP_EOL;
	$csv_result_eur .= $product['variants'][0]['sku'] . ',' .
					   $product['title'] . ',' .
					   $description . ',' .
					   'https://www.littleliffner.com/products/'.$product['handle'] . ',' .
					   $product['images'][0]['src'] . ',' .
					   ($product['variants'][0]['inventory_quantity'] == 0 ? 'out of stock' : 'in stock') . ',' .
					   $data['data']['product']['variants']['nodes'][0]['pricingInEUR']['price']['amount'] . ',' .
					   'Little Liffner' . ',' .
					   'no' . ',' .
					   $condition . PHP_EOL;
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