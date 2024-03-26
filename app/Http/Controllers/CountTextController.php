<?php

namespace App\Http\Controllers;
use LINE\Clients\MessagingApi\Model\TextMessage;

class CountTextController extends Controller {
  public function __invoke(string $text) {
    // 文字数カウント
    $stringCount = mb_strlen($text);
    $stringSub = substr_count($text,"\n");
    // 応答メッセージを作成
    $message1 = new TextMessage([
      'type' => 'text',
      'text' => "あなたの送信したテキストの文字数は" . $stringCount - $stringSub . "文字です。",
    ]); 
    return [$message1];
	}
}