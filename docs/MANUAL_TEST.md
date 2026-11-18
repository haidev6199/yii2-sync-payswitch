# Manual test trong Yii2 (bidv-vinat)

Tài liệu này hướng dẫn test thủ công package `haidev6199/yii2-payswitch` sau khi cài vào workspace `bidv-vinat`.

## 1. Trạng thái cài đặt

Package đã được cài dưới dạng `path repository` với symlink:

- Package: `D:\www\working\VINAT\yii2-sync-payswitch`
- App: `D:\www\working\VINAT\bidv-vinat`
- Composer name: `haidev6199/yii2-payswitch:@dev`
- Namespace: `Haidev6199\PaySwitch\`

Kiểm tra đã cài:

```bash
cd D:\www\working\VINAT\bidv-vinat
composer show haidev6199/yii2-payswitch
```

Kết quả mong đợi: package hiển thị với phiên bản `dev-main`, source trỏ tới `../yii2-sync-payswitch`.

## 2. Cấu hình env

Project `bidv-vinat` không tự load `.env` trong CLI. Các biến phải được export bởi web server, Herd, Docker hoặc shell đang chạy lệnh.

Biến bắt buộc:

```bash
PAYSWITCH_BASE_URL=https://pay-switch-uat.quikpay.vn
```

Các biến tùy chọn:

```bash
PAYSWITCH_TIMEOUT=30
PAYSWITCH_CONNECT_TIMEOUT=5
PAYSWITCH_SSL_VERIFY=true
PAYSWITCH_RETRY_READS=true
```

Ví dụ export tạm trên PowerShell:

```powershell
$env:PAYSWITCH_BASE_URL = 'https://pay-switch-uat.quikpay.vn'
$env:PAYSWITCH_TIMEOUT = '30'
$env:PAYSWITCH_CONNECT_TIMEOUT = '5'
$env:PAYSWITCH_SSL_VERIFY = 'true'
```

Component đã được đăng ký tại `common/config/main.php`:

```php
'payswitch' => [
    'class' => \Haidev6199\PaySwitch\Components\PaySwitchComponent::class,
],
```

Kiểm tra nhanh không gọi HTTP:

```powershell
$env:DNS_MYSQL = 'mysql:host=127.0.0.1;dbname=test'
$env:PAYSWITCH_BASE_URL = 'https://pay-switch-uat.quikpay.vn'
php -r "require 'vendor/autoload.php'; echo class_exists('Haidev6199\\PaySwitch\\Components\\PaySwitchComponent') ? 'OK' . PHP_EOL : 'FAIL' . PHP_EOL;"
```

## 3. Cách gọi component trong Yii2

Trong controller, service hoặc console command:

```php
use Haidev6199\PaySwitch\Request\Merchant\GetMerchantRequest;

$response = Yii::$app->payswitch->getMerchant(new GetMerchantRequest(999999999));
```

Quy tắc quan trọng:

- `success = true` chỉ khi HTTP 2xx **và** `code = "200"`.
- Mọi `code` khác `"200"` là lỗi nghiệp vụ, ném `BusinessException`, kể cả HTTP 200.
- `ApiResponse::$data` là toàn bộ JSON body PaySwitch trả về.
- Không log `password`, `pin`, `newPin`, header hoặc response body.

## 4. Các case manual test

### 4.1. Health check

Mục đích: xác nhận kết nối và base URL đúng.

```php
$response = Yii::$app->payswitch->home();
// hoặc
$response = Yii::$app->payswitch->healthCheck();
```

Kỳ vọng:
- Không có exception.
- `success = true`.
- `data` là chuỗi trả về từ server, ví dụ `Health Check OK !!!`.

### 4.2. Get merchant không tồn tại

Mục đích: kiểm tra phân biệt lỗi nghiệp vụ và HTTP error.

```php
use Haidev6199\PaySwitch\Exceptions\BusinessException;
use Haidev6199\PaySwitch\Request\Merchant\GetMerchantRequest;

try {
    Yii::$app->payswitch->getMerchant(new GetMerchantRequest(999999999));
} catch (BusinessException $e) {
    echo $e->getErrorCode();   // code trong body, ví dụ 404
    echo $e->getMessage();     // message trong body
    echo $e->getHttpStatus();  // HTTP status thực tế
}
```

Kỳ vọng:
- Ném `BusinessException`.
- `error_code` và `message` phản ánh đúng body PaySwitch.
- Không `HttpException`, vì body có `code` và `message`.

### 4.3. Get reader không tồn tại

```php
use Haidev6199\PaySwitch\Request\Reader\GetReaderRequest;

Yii::$app->payswitch->getReader(new GetReaderRequest('SN_NOT_EXIST'));
```

Kỳ vọng: `BusinessException` với `error_code` theo response của server (ví dụ `READER_NOT_FOUND`).

### 4.4. Get sys TID không tồn tại

```php
use Haidev6199\PaySwitch\Request\MidTid\GetSysTidRequest;

Yii::$app->payswitch->getSysTid(new GetSysTidRequest('SYS_NOT_EXIST'));
```

Kỳ vọng: `BusinessException`, không phải `ApiResponse success = false`.

### 4.5. Add merchant

Chỉ chạy trên UAT với dữ liệu test duy nhất. Không chạy trên production.

```php
use Haidev6199\PaySwitch\Request\Merchant\AddMerchantRequest;

$suffix = substr((string) time(), -8);

$response = Yii::$app->payswitch->addMerchant(new AddMerchantRequest(
    'manual' . $suffix,
    'Passw0rd!',
    'Cua Hang Manual Test',
    'MT' . $suffix,
    'Ho Chi Minh',
    'Phuong Ben Nghe',
    '123 Nguyen Hue',
    'manual' . $suffix . '@example.com',
    '09' . $suffix
));
```

Kỳ vọng:
- `success = true`, `http_status = 200`.
- `data['data']['id']` là số; lưu lại `id` để test các endpoint khác.

### 4.6. Add acquirer

```php
use Haidev6199\PaySwitch\Request\Merchant\AddAcquirerRequest;

$acquirer = 'ACQ' . substr((string) time(), -8);
$response = Yii::$app->payswitch->addAcquirer(new AddAcquirerRequest($acquirer, 'Ngan hang test', 'ACTIVE'));
```

Kỳ vọng: `success = true`. Lưu tên acquirer để dùng ở các test MID/TID.

### 4.7. Add reader

```php
use Haidev6199\PaySwitch\Request\Reader\AddReaderRequest;

$serial = 'SN' . substr((string) time(), -10);
$response = Yii::$app->payswitch->addReader(new AddReaderRequest($serial, 'ACTIVE', 'PAX_A920', 42));
```

Kỳ vọng: `success = true`. Thay `42` bằng `merchantId` thật từ bước 4.5.

### 4.8. Add SYS TID có acquirer

```php
use Haidev6199\PaySwitch\Request\MidTid\AddSysTidRequest;

$suffix = substr((string) time(), -8);
$response = Yii::$app->payswitch->addSysTid(new AddSysTidRequest(
    'ST' . $suffix,
    'SM' . $suffix,
    '123456',
    42,
    '123 Nguyen Hue, Ho Chi Minh',
    'T' . $suffix,
    'M' . $suffix,
    'ACQ_TEN_DA_LUU',
    'VND'
));
```

Kỳ vọng: `success = true`; kiểm tra `data['data']['sysTid']` và `data['data']['acquirerTid']`.

### 4.9. Reset config flags

```php
use Haidev6199\PaySwitch\Request\Reader\ResetConfigFlagsRequest;

// Một reader
$response = Yii::$app->payswitch->resetConfigFlags(
    new ResetConfigFlagsRequest('SN_DA_LUU', false, 'EMV_CONFIG')
);

// Tất cả reader
$response = Yii::$app->payswitch->resetConfigFlags(
    new ResetConfigFlagsRequest(null, true, 'CAPK')
);
```

Kỳ vọng: `data['data']['resetCount']` là số.

Validation mong đợi (không gửi request):

```php
new ResetConfigFlagsRequest('SN_DA_LUU', true); // ValidationException
new ResetConfigFlagsRequest(null, false);      // ValidationException
```

### 4.10. Validation trước khi gửi

Các trường hợp sau phải ném `ValidationException` **trước khi gọi HTTP**:

```php
use Haidev6199\PaySwitch\Exceptions\ValidationException;
use Haidev6199\PaySwitch\Request\Merchant\AddMerchantRequest;
use Haidev6199\PaySwitch\Request\MidTid\AddSysTidRequest;

try {
    new AddSysTidRequest('ST1', 'SM1', '12345', 42); // PIN 5 số
} catch (ValidationException $e) {
    // đúng
}

$request = new AddMerchantRequest('', 'Passw0rd!', 'Name', '', 'HCM', '', '');
$request->validate(); // có lỗi shortName, city, ward, address
```

## 5. Bảng kiểm tra nhanh

| Case | Cách gọi | Kết quả mong đợi |
|---|---|---|
| Health | `healthCheck()` | `success = true`, `data` chứa `Health Check OK !!!` |
| Merchant không tồn tại | `getMerchant(999999999)` | `BusinessException` |
| Reader không tồn tại | `getReader('SN_NOT_EXIST')` | `BusinessException` |
| Sys TID không tồn tại | `getSysTid('SYS_NOT_EXIST')` | `BusinessException` |
| Add merchant trùng username | `addMerchant(...)` với username cũ | `BusinessException`, `error_code` phản ánh lỗi 409 |
| Add acquirer trùng tên | `addAcquirer(...)` với tên cũ | `BusinessException` |
| PIN sai | `AddSysTidRequest(..., '12345', ...)` | `ValidationException`, không gửi HTTP |
| Reset flags truyền cả serial + allReaders | `ResetConfigFlagsRequest('SN1', true)` | `ValidationException`, không gửi HTTP |
| HTTP 5xx / server lỗi | (mô phỏng bằng mock trong unit test) | `HttpException` hoặc `BusinessException` theo body |

## 6. Xử lý lỗi thường gặp

| Lỗi | Nguyên nhân | Cách sửa |
|---|---|---|
| `ConfigurationException: Thiếu cấu hình base URL` | Chưa export `PAYSWITCH_BASE_URL` | Export biến env trước khi chạy lệnh/reload web server |
| `ConfigurationException: Base URL không hợp lệ` | Thiếu `https://` hoặc có khoảng trắng thừa | Dạng đúng: `https://pay-switch-uat.quikpay.vn` |
| `ConnectionException` | Mạng nội bộ/VPN/DNS không truy cập được host | Kiểm tra `ping`/`curl` tới host |
| `TimeoutException` | Server phản hồi quá chậm | Tăng `PAYSWITCH_TIMEOUT` |
| `InvalidResponseException` | Server trả HTML thay vì JSON | Kiểm tra URL, proxy, gateway |
| `BusinessException` | PaySwitch từ chối nghiệp vụ | Đọc `getErrorCode()` và `getMessage()` |

## 7. Lưu ý khi test trên UAT

- Chỉ tạo dữ liệu có suffix thời gian để tránh trùng.
- Không gọi endpoint `add-*` trên production.
- Endpoint `add-*` không có cơ chế idempotency, package không tự retry.
- Nếu gặp timeout khi gọi `add-*`, phải kiểm tra trên PaySwitch xem bản ghi đã được tạo chưa trước khi gọi lại.
- Không copy response body vào log hoặc ticket nếu có chứa dữ liệu test nhạy cảm.
