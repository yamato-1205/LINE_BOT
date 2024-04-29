<?php

namespace App\Http\Controllers;
use LINE\Clients\MessagingApi\Model\TextMessage;

class DefaultController extends Controller {
  public function __invoke() {
    // 応答メッセージを作成
    $message1 = new TextMessage([
      'type' => 'text',
      'text' => "「ヘルプ」と送るとYAMATO_BOTの使い方や利用できるアクションの紹介をします",
    ]);
    return [$message1];
	}
}