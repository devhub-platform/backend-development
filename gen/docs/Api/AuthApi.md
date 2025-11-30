# OpenAPI\Client\AuthApi

All URIs are relative to http://localhost, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1AuthGoogleLoginGet()**](AuthApi.md#apiV1AuthGoogleLoginGet) | **GET** /api/v1/auth/google/login | Login with google |
| [**apiV1LoginPost()**](AuthApi.md#apiV1LoginPost) | **POST** /api/v1/login | login |
| [**apiV1MePost()**](AuthApi.md#apiV1MePost) | **POST** /api/v1/me | Me |
| [**apiV1ProfileUpdatePasswordPost()**](AuthApi.md#apiV1ProfileUpdatePasswordPost) | **POST** /api/v1/profile/update-password | update current password |
| [**apiV1RefreshPost()**](AuthApi.md#apiV1RefreshPost) | **POST** /api/v1/refresh | refresh token |
| [**apiV1RegisterPost()**](AuthApi.md#apiV1RegisterPost) | **POST** /api/v1/register | register |


## `apiV1AuthGoogleLoginGet()`

```php
apiV1AuthGoogleLoginGet($accept, $content_type): \OpenAPI\Client\Model\ApiV1AuthGoogleLoginGet200Response
```

Login with google

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new OpenAPI\Client\Api\AuthApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$accept = application/json; // string
$content_type = application/json; // string

try {
    $result = $apiInstance->apiV1AuthGoogleLoginGet($accept, $content_type);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AuthApi->apiV1AuthGoogleLoginGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **accept** | **string**|  | [optional] |
| **content_type** | **string**|  | [optional] |

### Return type

[**\OpenAPI\Client\Model\ApiV1AuthGoogleLoginGet200Response**](../Model/ApiV1AuthGoogleLoginGet200Response.md)

### Authorization

No authorization required

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1LoginPost()`

```php
apiV1LoginPost($body): \OpenAPI\Client\Model\ApiV1LoginPost200Response
```

login

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new OpenAPI\Client\Api\AuthApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = array('key' => new \stdClass); // object

try {
    $result = $apiInstance->apiV1LoginPost($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AuthApi->apiV1LoginPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **body** | **object**|  | |

### Return type

[**\OpenAPI\Client\Model\ApiV1LoginPost200Response**](../Model/ApiV1LoginPost200Response.md)

### Authorization

No authorization required

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1MePost()`

```php
apiV1MePost($accept, $content_type): \OpenAPI\Client\Model\ApiV1MePost200Response
```

Me

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setApiKey('Authorization', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Authorization', 'Bearer');


$apiInstance = new OpenAPI\Client\Api\AuthApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$accept = application/json; // string
$content_type = application/json; // string

try {
    $result = $apiInstance->apiV1MePost($accept, $content_type);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AuthApi->apiV1MePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **accept** | **string**|  | [optional] |
| **content_type** | **string**|  | [optional] |

### Return type

[**\OpenAPI\Client\Model\ApiV1MePost200Response**](../Model/ApiV1MePost200Response.md)

### Authorization

[bearer](../../README.md#bearer)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1ProfileUpdatePasswordPost()`

```php
apiV1ProfileUpdatePasswordPost($accept, $current_password, $new_password, $new_password_confirmation): object
```

update current password

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setApiKey('Authorization', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Authorization', 'Bearer');


$apiInstance = new OpenAPI\Client\Api\AuthApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$accept = application/json; // string
$current_password = 'current_password_example'; // string
$new_password = 'new_password_example'; // string
$new_password_confirmation = 'new_password_confirmation_example'; // string

try {
    $result = $apiInstance->apiV1ProfileUpdatePasswordPost($accept, $current_password, $new_password, $new_password_confirmation);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AuthApi->apiV1ProfileUpdatePasswordPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **accept** | **string**|  | [optional] |
| **current_password** | **string**|  | [optional] |
| **new_password** | **string**|  | [optional] |
| **new_password_confirmation** | **string**|  | [optional] |

### Return type

**object**

### Authorization

[bearer](../../README.md#bearer)

### HTTP request headers

- **Content-Type**: `multipart/form-data`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1RefreshPost()`

```php
apiV1RefreshPost($accept, $content_type)
```

refresh token

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setApiKey('Authorization', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Authorization', 'Bearer');


$apiInstance = new OpenAPI\Client\Api\AuthApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$accept = application/json; // string
$content_type = application/json; // string

try {
    $apiInstance->apiV1RefreshPost($accept, $content_type);
} catch (Exception $e) {
    echo 'Exception when calling AuthApi->apiV1RefreshPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **accept** | **string**|  | [optional] |
| **content_type** | **string**|  | [optional] |

### Return type

void (empty response body)

### Authorization

[bearer](../../README.md#bearer)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1RegisterPost()`

```php
apiV1RegisterPost($body, $accept, $content_type): \OpenAPI\Client\Model\ApiV1RegisterPost201Response
```

register

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new OpenAPI\Client\Api\AuthApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = array('key' => new \stdClass); // object
$accept = application/json; // string
$content_type = application/json; // string

try {
    $result = $apiInstance->apiV1RegisterPost($body, $accept, $content_type);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AuthApi->apiV1RegisterPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **body** | **object**|  | |
| **accept** | **string**|  | [optional] |
| **content_type** | **string**|  | [optional] |

### Return type

[**\OpenAPI\Client\Model\ApiV1RegisterPost201Response**](../Model/ApiV1RegisterPost201Response.md)

### Authorization

No authorization required

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
