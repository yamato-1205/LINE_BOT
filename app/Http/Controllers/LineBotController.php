<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use LINE\Clients\MessagingApi\Configuration;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;
use LINE\Clients\MessagingApi\ApiException;
use LINE\Webhook\Model\MessageEvent;
use LINE\Webhook\Model\TextMessageContent;
use LINE\Webhook\Model\Mention;
use LINE\Parser\EventRequestParser;
use Illuminate\Support\Facades\Log;

use Google_Client;
use Google_Service_YouTub;

class LineBotController extends Controller {
  public function reply(Request $request) {
    // チャネルシークレットとチャネルアクセストークンを読み込む
    $channelSecret = config('services.line.secret');
    $channelToken = config('services.line.token');
    // Webhookイベントを取得する
    $httpRequestBody = $request->getContent();
    // 署名を検証する（Messaging APIから送られたものであるかチェック）
    $hash = hash_hmac('sha256', $httpRequestBody, $channelSecret, true);
    $signature = base64_encode($hash);
    if ($signature !== $request->header('X-Line-Signature')) return;
    // LINEBOTクライアントを作成
    $client = new Client();
    $config = new Configuration();
    $config->setAccessToken($channelToken);
    // LINE Messaging APIを作成
    $messagingApi = new MessagingApiApi (
      client: $client,
      config: $config,
    );

    try {
      // イベントリクエストをパース
      $parsedEvents = EventRequestParser::parseEventRequest($httpRequestBody, $channelSecret, $signature);
      // イベントは配列（必ずしも１つとは限らない）で来るのでforeachで回す
      foreach ($parsedEvents->getEvents() as $event) {
        // メッセージイベント以外は無視
        if (!($event instanceof MessageEvent)) continue;
        // メッセージを取得
        $eventMessage = $event->getMessage();
        // テキストメッセージ以外は無視
        if (!($eventMessage instanceof TextMessageContent)) continue;
        // テキストメッセージを取得
        $eventMessageText = $eventMessage->getText();
        // 改行コードを統一
        $eventMessageText = str_replace(array("\r\n", "\r", "\n"), "\n", $eventMessageText);
        // 命令文を抽出
        $eventMessageTextArr = explode("\n", $eventMessageText, 2);

        switch ($eventMessageTextArr[0]) {
          case "文字数":
            $controller = new CountTextController();
            if (count($eventMessageTextArr) == 2) {
              $message = $controller($eventMessageTextArr[1]);
            } else {
              $message = $controller("");
            }
            break;
          case "乱数":
            $controller = new RandController();
            if (count($eventMessageTextArr) == 2) {
              $message = $controller($eventMessageTextArr[1]);
            } else {
              $message = $controller(100);
            }
            break;
          case "急上昇":
            $controller = new YoutubeTrendController();
            if (count($eventMessageTextArr) == 2) {
              $message = $controller($eventMessageTextArr[1]);
            } else {
              $message = $controller(3);
            }
            break;
          default:
            $controller = new DefaultController();
            $message = $controller();
            break;
        }

        // 応答リクエストを作成
        $request = new ReplyMessageRequest([
          'replyToken' => $event->getReplyToken(),
          'messages' => $message, // 配列である必要があります
        ]);
        // 応答リクエストを送信する
        $response = $messagingApi->replyMessageWithHttpInfo($request);
        // レスポンスをチェックする（エラーの場合の処理）
        $responseBody = $response[0];
        $responseStatusCode = $response[1];
        if ($responseStatusCode != 200) {
          throw new \Exception($responseBody);
        }
      }
      return;
    } catch (ApiException $e) {
      // エラー内容をログに出力
      Log::error($e->getCode() . ':' . $e->getResponseBody());
    }
  }
}