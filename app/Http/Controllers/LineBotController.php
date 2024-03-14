<?php
namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use LINE\Clients\MessagingApi\Configuration;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Model\TextMessage;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;
use LINE\Clients\MessagingApi\ApiException;
use LINE\Webhook\Model\MessageEvent;
use LINE\Webhook\Model\TextMessageContent;
use LINE\Webhook\Model\Mention;
use LINE\Parser\EventRequestParser;
use Illuminate\Support\Facades\Log;

class LineBotController extends Controller
{
    public function reply(Request $request)
    {
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
        $messagingApi = new MessagingApiApi(
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

                if ($eventMessageTextArr[0] == "文字数" and count($eventMessageTextArr) == 2) {
                  // 文字数カウント
                  $stringCount = mb_strlen($eventMessageTextArr[1]);
                  $stringSub = substr_count($eventMessageTextArr[1],"\n");
                  // 応答メッセージを作成
                  $message1 = new TextMessage([
                    'type' => 'text',
                    'text' => "あなたの送信したテキストの文字数は" . $stringCount - $stringSub . "文字です。",
                  ]); 
                  $message = [$message1];
                } else if ($eventMessageTextArr[0] == "乱数") {
                  // 乱数の生成
                  try {
                    if ( preg_match( '/^[0-9]+$/', $eventMessageTextArr[1])) {
                      $randMax = (int)$eventMessageTextArr[1];
                    } else {
                      $randMax = 100;
                    }
                  } catch ( Exception $ex ) {
                    $randMax = 100;
                  }
                  $rand = rand(0, $randMax);
                  // 応答メッセージを作成
                  $message1 = new TextMessage([
                    'type' => 'text',
                    'text' => $rand,
                  ]); 
                  $message2 = new TextMessage([
                    'type' => 'text',
                    'text' => "乱数範囲は0〜" . $randMax . "です",
                  ]); 
                  $message = [$message1, $message2];
                } else {
                  // 応答メッセージを作成
                  $message1 = new TextMessage([
                    'type' => 'text',
                    'text' => "文字数\nカウントしたい文章",
                  ]);
                  $message2 = new TextMessage([
                    'type' => 'text',
                    'text' => "乱数\n乱数の最大値（半角数字）",
                  ]);
                  $message3 = new TextMessage([
                    'type' => 'text',
                    'text' => "上のように1行目に行いたいアクション。2行目にそのアクションに必要な情報を送信してください。",
                  ]);
                  $message = [$message1, $message2, $message3];
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