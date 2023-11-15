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

$affiliateProducts = [];

foreach ($products as $product) {
	$affiliateProduct = [
		'id' => $product['variants'][0]['sku'],
		'title' => $product['title'],
		'description' => $product['body_html'],
		'link' => 'https://www.littleliffner.com/products/'.$product['handle'],
		'availability' => $product['variants'][0]['inventory_quantity'] == 0 ? 'out of stock' : 'in stock',
		'price' => $product['variants'][0]['price'],
		'brand' => '',
		'gtin' => '',
		'mpn' => '',
		'condition' => 'refurbished'
	];

	$affiliateProducts[] = $affiliateProduct;
}

echo json_encode($affiliateProducts);