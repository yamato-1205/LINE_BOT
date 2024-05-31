<?php

namespace App\Http\Controllers;
use LINE\Clients\MessagingApi\Model\TextMessage;

class HelpController extends Controller {
  public function __invoke() {
    // 応答メッセージを作成
    $message1 = new TextMessage([
      'type' => 'text',
      'text' => "1行目に行いたいアクション名\n2行目にそのアクションに必要な情報を送信してください"
    ]);
    $message2 = new TextMessage([
      'type' => 'text',
      'text' => "アクション一覧\n".
      "・「文字数」\n2行目以降に文章を入力することでその文章の文字数を数えてくれます\n".
      "・「乱数」\n2行目に半角数字入力することで0〜入力された数字の範囲で乱数を1つ生成します\n入力がない場合は0〜100の間で生成します\n".
      "・「急上昇」\n2行目に半角数字入力することでYouTubeの日本の急上昇動画のタイトルと動画リンクを1位〜入力された数字(１０位)まで紹介します\n入力がない場合は3位まで紹介します",
    ]);
    return [$message1, $message2];
	}
}