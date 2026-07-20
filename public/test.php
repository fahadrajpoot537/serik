
<?php

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://query.ampre.ca/odata/Property?%24filter=contains%28City%2C%27Brampton%27%29%20and%20PropertySubType%20ne%20%27Industrial%27%20and%20PropertySubType%20ne%20%27Commercial%20Retail%27&%24top=5&%24select=UnparsedAddress%2CBedroomsAboveGrade%2CBedroomsBelowGrade%2CBathroomsTotalInteger%2CParkingTotal%2CLivingAreaRange%2CPropertySubType%2CTaxAnnualAmount%2CLotWidth%2CLotDepth',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJ2ZW5kb3IvdHJyZWIvMTAzMjQiLCJhdWQiOiJBbXBVc2Vyc1ByZCIsInJvbGVzIjpbIkFtcFZlbmRvciJdLCJpc3MiOiJwcm9kLmFtcHJlLmNhIiwiZXhwIjoyNTM0MDIzMDA3OTksImlhdCI6MTc3NTgyNzU2Mywic3ViamVjdFR5cGUiOiJ2ZW5kb3IiLCJzdWJqZWN0S2V5IjoiMTAzMjQiLCJqdGkiOiJjYTUyNDA2MzUxNDM2MDg4IiwiY3VzdG9tZXJOYW1lIjoidHJyZWIifQ.-DSLkpKUIymMWipYYNBmTfLA9SH58pToG-NhTWL-0rs',
    'Cookie: JSESSIONID=847DA7F093ED3BC4EF8471F6CCDBE555'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
