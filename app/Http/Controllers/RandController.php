<?php

namespace App\Http\Controllers;
use Exception;
use LINE\Clients\MessagingApi\Model\TextMessage;

class RandController extends Controller {
  public function __invoke(string $text) {
    // 乱数の生成
    if ( preg_match( '/^[0-9]+$/', $text)) {
      $randMax = (int)$text;
    } else {
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
    return [$message1, $message2];
	}
}