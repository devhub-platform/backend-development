# OpenAPI\Client\AuthVerifyEmailApi

All URIs are relative to http://localhost, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1EmailResendOtpPost()**](AuthVerifyEmailApi.md#apiV1EmailResendOtpPost) | **POST** /api/v1/email/resend-otp | send otp to verify account |
| [**apiV1EmailVerifyOtpPost()**](AuthVerifyEmailApi.md#apiV1EmailVerifyOtpPost) | **POST** /api/v1/email/verify-otp | verify-otp account |
| [**apiV1PasswordResetPost()**](AuthVerifyEmailApi.md#apiV1PasswordResetPost) | **POST** /api/v1/password/reset | reset password |


## `apiV1EmailResendOtpPost()`

```php
apiV1EmailResendOtpPost($email): \OpenAPI\Client\Model\ApiV1PasswordForgotPost200Response
```

send otp to verify account

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new OpenAPI\Client\Api\AuthVerifyEmailApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$email = 'email_example'; // string

try {
    $result = $apiInstance->apiV1EmailResendOtpPost($email);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AuthVerifyEmailApi->apiV1EmailResendOtpPost: ', $e->getMessage(), PHP_EOL;
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

## `apiV1EmailVerifyOtpPost()`

```php
apiV1EmailVerifyOtpPost($email, $otp): object
```

verify-otp account

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setApiKey('Authorization', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Authorization', 'Bearer');


$apiInstance = new OpenAPI\Client\Api\AuthVerifyEmailApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$email = 'email_example'; // string
$otp = 'otp_example'; // string

try {
    $result = $apiInstance->apiV1EmailVerifyOtpPost($email, $otp);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AuthVerifyEmailApi->apiV1EmailVerifyOtpPost: ', $e->getMessage(), PHP_EOL;
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

[bearer](../../README.md#bearer)

### HTTP request headers

- **Content-Type**: `multipart/form-data`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PasswordResetPost()`

```php
apiV1PasswordResetPost($email, $otp, $password, $password_confirmation): object
```

reset password

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new OpenAPI\Client\Api\AuthVerifyEmailApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$email = 'email_example'; // string
$otp = 'otp_example'; // string
$password = 'password_example'; // string
$password_confirmation = 'password_confirmation_example'; // string

try {
    $result = $apiInstance->apiV1PasswordResetPost($email, $otp, $password, $password_confirmation);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AuthVerifyEmailApi->apiV1PasswordResetPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **email** | **string**|  | [optional] |
| **otp** | **string**|  | [optional] |
| **password** | **string**|  | [optional] |
| **password_confirmation** | **string**|  | [optional] |

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
