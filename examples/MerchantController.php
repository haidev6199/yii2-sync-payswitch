<?php

namespace app\controllers;

use Haidev6199\PaySwitch\Exceptions\BusinessException;
use Haidev6199\PaySwitch\Exceptions\ConnectionException;
use Haidev6199\PaySwitch\Exceptions\PaySwitchException;
use Haidev6199\PaySwitch\Exceptions\TimeoutException;
use Haidev6199\PaySwitch\Exceptions\ValidationException;
use Haidev6199\PaySwitch\Request\Merchant\AddMerchantRequest;
use Haidev6199\PaySwitch\Request\Merchant\GetMerchantRequest;
use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * Ví dụ sử dụng PaySwitch trong controller Yii2.
 */
class MerchantController extends Controller
{
    public function actionCreate(): Response
    {
        $input = Yii::$app->request->getBodyParams();

        try {
            $response = Yii::$app->payswitch->addMerchant(new AddMerchantRequest(
                (string) ($input['username'] ?? ''),
                (string) ($input['password'] ?? ''),
                (string) ($input['merchantName'] ?? ''),
                (string) ($input['shortName'] ?? ''),
                (string) ($input['city'] ?? ''),
                (string) ($input['ward'] ?? ''),
                (string) ($input['address'] ?? ''),
                $input['email'] ?? null,
                $input['phone'] ?? null
            ));

            return $this->asJson(['success' => true, 'data' => $response->data]);
        } catch (ValidationException $e) {
            return $this->asJson(['success' => false, 'errors' => $e->getErrors()])->setStatusCode(422);
        } catch (BusinessException $e) {
            return $this->asJson([
                'success' => false,
                'error_code' => $e->getErrorCode(),
                'message' => $e->getMessage(),
            ])->setStatusCode(422);
        } catch (TimeoutException | ConnectionException $e) {
            // Endpoint add-* không tự retry. Quyết định gửi lại thuộc về nghiệp vụ, cần kiểm tra trạng thái trước.
            Yii::error('PaySwitch không phản hồi: ' . get_class($e), 'payswitch');

            return $this->asJson(['success' => false, 'message' => 'Hệ thống thanh toán tạm thời không khả dụng.'])
                ->setStatusCode(503);
        } catch (PaySwitchException $e) {
            Yii::error('Lỗi PaySwitch: ' . get_class($e), 'payswitch');

            return $this->asJson(['success' => false, 'message' => 'Lỗi khi gọi PaySwitch.'])->setStatusCode(502);
        }
    }

    public function actionView(int $id): Response
    {
        $response = Yii::$app->payswitch->getMerchant(new GetMerchantRequest($id));

        return $this->asJson(['success' => true, 'data' => $response->data]);
    }
}
