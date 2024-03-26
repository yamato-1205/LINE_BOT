<?php

namespace App\Http\Controllers;
use LINE\Clients\MessagingApi\Model\TextMessage;

class DefaultController extends Controller {
  public function __invoke() {
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
    return [$message1, $message2, $message3];
	}
}