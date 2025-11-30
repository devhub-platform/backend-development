# OpenAPI\Client\AuthForgetPasswordApi

All URIs are relative to http://localhost, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1PasswordForgotPost()**](AuthForgetPasswordApi.md#apiV1PasswordForgotPost) | **POST** /api/v1/password/forgot | forget password |
| [**apiV1PasswordVerifyOtpPost()**](AuthForgetPasswordApi.md#apiV1PasswordVerifyOtpPost) | **POST** /api/v1/password/verify-otp | verify-otp to change password |


## `apiV1PasswordForgotPost()`

```php
apiV1PasswordForgotPost($email): \OpenAPI\Client\Model\ApiV1PasswordForgotPost200Response
```

forget password

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new OpenAPI\Client\Api\AuthForgetPasswordApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$email = 'email_example'; // string

try {
    $result = $apiInstance->apiV1PasswordForgotPost($email);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AuthForgetPasswordApi->apiV1PasswordForgotPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **email** | **string**|  | [optional] |

### Return type

[**\OpenAPI\Client\Model\ApiV1PasswordForgotPost200Response**](../Model/ApiV1PasswordForgotPost200Response.md)

### Authorization

No authorization required

### HTTP request headers

- **Content-Type**: `multipart/form-data`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PasswordVerifyOtpPost()`

```php
apiV1PasswordVerifyOtpPost($email, $otp): object
```

verify-otp to change password

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new OpenAPI\Client\Api\AuthForgetPasswordApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$email = 'email_example'; // string
$otp = 'otp_example'; // string

try {
    $result = $apiInstance->apiV1PasswordVerifyOtpPost($email, $otp);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AuthForgetPasswordApi->apiV1PasswordVerifyOtpPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **email** | **string**|  | [optional] |
| **otp** | **string**|  | [optional] |

### Return type

**object**

### Authorization

No authorization required

### HTTP request headers

- **Content-Type**: `multipart/form-data`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
