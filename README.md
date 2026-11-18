# haidev6199/yii2-payswitch

Thư viện PHP/Yii2 giao tiếp với module PaySwitch qua HTTP API nội bộ: quản lý merchant, acquirer, reader (thiết bị POS), MID/TID và health check.

- Namespace: `Haidev6199\PaySwitch\`
- PHP: `^7.3`
- Yii2: `yiisoft/yii2 ^2.0.14`
- License: MIT

## 1. Yêu cầu môi trường

- PHP 7.3 trở lên, các extension `json`, `curl`
- Composer 2.x
- Yii2 2.0.14 trở lên
- Server PaySwitch truy cập được từ mạng nội bộ

## 2. Cài đặt

```bash
composer require haidev6199/yii2-payswitch
```

Cài đặt local vào project Yii2 khác bằng path repository (dùng khi đang phát triển package):

```json
"repositories": [
    { "type": "path", "url": "../yii2-sync-payswitch", "options": { "symlink": true } }
]
```

```bash
composer require haidev6199/yii2-payswitch:@dev
```

Lưu ý: Yii2 2.0.x cần `asset-packagist` trong `repositories` của project cài đặt:

```json
{ "type": "composer", "url": "https://asset-packagist.org" }
```

## 3. Cấu hình môi trường

Credentials và địa chỉ server đọc từ biến môi trường, không được ghi trực tiếp trong source.

| Biến | Bắt buộc | Mặc định | Ý nghĩa |
|---|---|---|---|
| `PAYSWITCH_BASE_URL` | Có | - | Ví dụ `http://10.0.0.5:8080` (dạng `http(s)://host[:port]`) |
| `PAYSWITCH_TIMEOUT` | Không | `30` | Timeout chờ phản hồi (giây) |
| `PAYSWITCH_CONNECT_TIMEOUT` | Không | `5` | Timeout kết nối (giây) |
| `PAYSWITCH_SSL_VERIFY` | Không | `true` | Kiểm tra chứng chỉ TLS khi dùng HTTPS |
| `PAYSWITCH_RETRY_READS` | Không | `true` | Retry 1 lần endpoint `get-*` khi lỗi kết nối/timeout |

Ví dụ `.env` (không commit file này):

```bash
PAYSWITCH_BASE_URL=http://10.0.0.5:8080
PAYSWITCH_TIMEOUT=30
PAYSWITCH_CONNECT_TIMEOUT=5
PAYSWITCH_SSL_VERIFY=true
```

## 4. Cấu hình Yii2

`config/web.php` (hoặc file config tương ứng cho console):

```php
'components' => [
    'payswitch' => [
        'class' => \Haidev6199\PaySwitch\Components\PaySwitchComponent::class,
        // Bỏ trống để đọc từ env
        'baseUrl' => getenv('PAYSWITCH_BASE_URL') ?: null,
        'timeout' => 30,
        'connectTimeout' => 5,
        'sslVerify' => true,
    ],
],
```

Mọi thuộc tính để `null` sẽ lấy từ biến môi trường tương ứng.

## 5. Sử dụng

Mỗi endpoint có một Request DTO. Method của component nhận DTO và trả `ApiResponse`.

```php
use Haidev6199\PaySwitch\Request\Merchant\AddMerchantRequest;
use Haidev6199\PaySwitch\Request\Merchant\GetMerchantRequest;

$response = Yii::$app->payswitch->addMerchant(new AddMerchantRequest(
    'merchant01',          // username
    'Passw0rd!',           // password
    'Cua Hang ABC',        // merchantName
    'ABC',                 // shortName
    'Ho Chi Minh',         // city (bắt buộc)
    'Phuong Ben Nghe',     // ward (bắt buộc)
    '123 Nguyen Hue',      // address (bắt buộc)
    'merchant01@example.com', // email (tùy chọn)
    '0912345678'           // phone, 10 chữ số (tùy chọn)
));

$response->success;     // true
$response->http_status; // 200
$response->error_code;  // null
$response->message;     // null
$response->data;        // toàn bộ JSON body PaySwitch trả về

$merchant = Yii::$app->payswitch->getMerchant(new GetMerchantRequest(42));
```

Dùng trực tiếp client (không qua Yii component):

```php
use Haidev6199\PaySwitch\Client\PaySwitchClient;
use Haidev6199\PaySwitch\Config\PaySwitchConfig;

$client = new PaySwitchClient(PaySwitchConfig::fromEnv());
```

### 5.1. Envelope `ApiResponse`

| Thuộc tính | Kiểu | Ý nghĩa |
|---|---|---|
| `success` | bool | `true` khi HTTP 2xx và `code = "200"` |
| `http_status` | int | HTTP status thực tế |
| `error_code` | ?string | Luôn `null` khi thành công (lỗi được ném dưới dạng exception) |
| `message` | ?string | Luôn `null` khi thành công |
| `data` | mixed | Toàn bộ JSON body PaySwitch (gồm `code`, `message`, `timestamp`, `data`) |

Quy tắc thành công: HTTP status không đủ để kết luận. Body phải có `code = "200"`. Body có `code` khác `"200"` là lỗi nghiệp vụ, kể cả khi HTTP 200.

### 5.2. Danh sách API được hỗ trợ

| Method | Endpoint | Request DTO | Retry |
|---|---|---|---|
| `addMerchant` | `POST /api/merchant/add-merchant` | `Merchant\AddMerchantRequest` | Không |
| `getMerchant` | `POST /api/merchant/get-merchant` | `Merchant\GetMerchantRequest` | Có (1 lần) |
| `updateMerchant` | `POST /api/merchant/update-merchant` | `Merchant\UpdateMerchantRequest` | Không |
| `addAcquirer` | `POST /api/merchant/add-acquirer` | `Merchant\AddAcquirerRequest` | Không |
| `addReader` | `POST /api/reader/add-reader` | `Reader\AddReaderRequest` | Không |
| `getReader` | `POST /api/reader/get-reader` | `Reader\GetReaderRequest` | Có (1 lần) |
| `resetConfigFlags` | `POST /api/reader/reset-config-flags` | `Reader\ResetConfigFlagsRequest` | Không |
| `addSysTid` | `POST /api/mid-tid/add-sys-tid` | `MidTid\AddSysTidRequest` | Không |
| `addAcquirerTid` | `POST /api/mid-tid/add-acquirer-tid` | `MidTid\AddAcquirerTidRequest` | Không |
| `getAcquirerTid` | `POST /api/mid-tid/get-acquirer-tid` | `MidTid\GetAcquirerTidRequest` | Có (1 lần) |
| `getSysTid` | `POST /api/mid-tid/get-sys-tid` | `MidTid\GetSysTidRequest` | Có (1 lần) |
| `changeSysTidPin` | `POST /api/mid-tid/change-sys-tid-pin` | `MidTid\ChangeSysTidPinRequest` | Không |
| `updateSysTidStatus` | `POST /api/mid-tid/update-sys-tid-status` | `MidTid\UpdateSysTidStatusRequest` | Không |
| `home` | `GET /` | - | Có (1 lần) |
| `healthCheck` | `GET /healthcheck` | - | Có (1 lần) |
| `apiDocs` | `GET /api-docs` | - | Có (1 lần) (chỉ bật ở profile local/dev phía server) |

Health endpoint không dùng JSON envelope; `data` là chuỗi trả về của server.

### 5.3. Ví dụ request/response

Request `addSysTid` (có acquirer TID):

```php
new AddSysTidRequest(
    'ST00000001', 'SM00000001', '123456', 42,
    '123 Nguyen Hue, Phuong Sai Gon, Ho Chi Minh',
    'T00000001', 'M00000001', 'ACQ00000001', 'VND'
);
```

Payload gửi lên:

```json
{
  "sysTid": "ST00000001",
  "sysMid": "SM00000001",
  "pin": "123456",
  "merchantId": 42,
  "address": "123 Nguyen Hue, Phuong Sai Gon, Ho Chi Minh",
  "acquirerTid": "T00000001",
  "acquirerMid": "M00000001",
  "acquirer": "ACQ00000001",
  "currency": "VND"
}
```

Response thành công:

```json
{
  "code": "200",
  "message": "Success",
  "timestamp": "2026-11-18T10:00:00",
  "data": { "sysTid": "ST00000001", "acquirerTid": "T00000001" }
}
```

Response lỗi nghiệp vụ (`BusinessException`):

```json
{
  "timestamp": "2026-11-18T10:00:00",
  "code": 404,
  "message": "Merchant không tồn tại",
  "errorDetails": []
}
```

Lưu ý về các trường `ResetConfigFlagsRequest`:
- Truyền đúng một trong `serialNumber` hoặc `allReaders = true`. Truyền cả hai sẽ bị từ chối.
- `allReaders = false` được chấp nhận, không gửi lên server; khi đó `serialNumber` là bắt buộc.
- `configType` để trống thì không gửi field này (reset cả 2 cờ).

Các giá trị hợp lệ:
- `status` (CommonStatus): `ACTIVE`, `INACTIVE`
- `configType`: `EMV_CONFIG`, `CAPK`
- `pin`, `newPin`: 6 đến 8 chữ số
- `phone` (Add/Update Merchant): đúng 10 chữ số
- `currency`: mã ISO 4217 3 ký tự in hoa (ví dụ `VND`)

## 6. Exception

Tất cả exception kế thừa `Haidev6199\PaySwitch\Exceptions\PaySwitchException`.

| Exception | Khi nào | Thông tin bổ sung |
|---|---|---|
| `ConfigurationException` | Thiếu hoặc sai base URL, timeout không hợp lệ | - |
| `ValidationException` | Dữ liệu không hợp lệ, không gửi request | `getErrors()` |
| `ConnectionException` | Không kết nối được tới server | - |
| `TimeoutException` | Hết thời gian chờ | - |
| `HttpException` | HTTP 4xx/5xx, body không có cấu trúc `code` + `message` | `getHttpStatus()`, `getRawBody()` |
| `BusinessException` | `code` khác `"200"` (kể cả HTTP 200), hoặc HTTP 4xx/5xx có `code` + `message` | `getErrorCode()`, `getHttpStatus()`, `getRawBody()` |
| `InvalidResponseException` | HTTP 2xx nhưng body không phải JSON hoặc thiếu `code`/`message` | `getHttpStatus()`, `getRawBody()` |

`getRawBody()` chỉ dùng để debug nội bộ. Không đưa giá trị này vào log hoặc phản hồi cho người dùng cuối vì body có thể chứa dữ liệu nhạy cảm.

```php
try {
    $response = Yii::$app->payswitch->addAcquirer(new AddAcquirerRequest('ACQ1'));
} catch (ValidationException $e) {
    // dữ liệu đầu vào sai
} catch (BusinessException $e) {
    $e->getErrorCode();   // ví dụ "409"
    $e->getMessage();     // ví dụ "ACQUIRER_ALREADY_EXISTS" nếu server trả như vậy
} catch (TimeoutException | ConnectionException $e) {
    // mạng nội bộ có vấn đề
} catch (PaySwitchException $e) {
    // các lỗi khác của package
}
```

## 7. Bảo mật

- Không hardcode URL, token, password hay secret. Chỉ đọc từ env hoặc config.
- SSL verify bật mặc định. Chỉ tắt (`PAYSWITCH_SSL_VERIFY=false`) khi có chủ đích.
- Không log header, body, password, pin, newPin. Package không ghi log dữ liệu nghiệp vụ.
- Endpoint tạo mới (`add-*`, `update-*`, `change-*`, `reset-*`) không tự retry vì chưa có cơ chế idempotency. Nếu timeout xảy ra, ứng dụng cần kiểm tra trạng thái trước khi gửi lại.

## 8. Kiểm thử

Test dùng mock transport, không gọi server thật.

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpunit --testsuite Unit
vendor/bin/phpunit --testsuite Integration
```

Nội dung:
- `tests/Unit`: cấu hình, validation request, parse response, retry, timeout, lỗi HTTP.
- `tests/Integration`: mỗi endpoint trong Postman collection có một case thành công (đúng path, payload, parse) và một case lỗi nghiệp vụ.

## 9. Cấu hình môi trường UAT và Production

Package không có môi trường mặc định. Cấu hình theo từng môi trường bằng biến env, không commit vào repo.

UAT:

```bash
PAYSWITCH_BASE_URL=http://<uat-internal-host>:<port>
PAYSWITCH_SSL_VERIFY=true
```

Production:

```bash
PAYSWITCH_BASE_URL=http://<prod-internal-host>:<port>
PAYSWITCH_TIMEOUT=30
PAYSWITCH_CONNECT_TIMEOUT=5
PAYSWITCH_SSL_VERIFY=true
```

Nếu server dùng HTTPS với chứng chỉ nội bộ, cần cấu hình CA trust cho PHP/cURL trên máy chủ thay vì tắt `PAYSWITCH_SSL_VERIFY`.

## 10. Ví dụ và manual test

- Ví dụ Yii2: `examples/config/web.php`, `examples/MerchantController.php`.
- Manual test trong app `bidv-vinat`: `docs/MANUAL_TEST.md`.

## 11. Publish

Package được publish lên Packagist với tên `haidev6199/yii2-payswitch`.
