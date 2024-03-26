<?php

namespace App\Http\Controllers;

use LINE\Clients\MessagingApi\Model\TextMessage;
use Google_Client;
use Google_Service_YouTube;
use Log;

class YoutubeTrendController extends Controller
{
  public function __invoke(string $text)
  {
    // Googleへの接続情報のインスタンスを作成と設定
    $client = new Google_Client();
    $client->setDeveloperKey(env('GOOGLE_API_KEY'));
    // 接続情報のインスタンスを用いてYoutubeのデータへアクセス可能なインスタンスを生成
    $youtube = new Google_Service_YouTube($client);
    // 何位まで取得するか（制限は1~10）
    if (preg_match('/^[0-9]+$/', $text)) {
      $rank = (int) $text;
      if ($rank < 1 || $rank > 10) {
        $rank = 3;
      }
    } else {
      $rank = 3;
    }
    $part = [
      'snippet',
      'statistics'
    ];
    $params = [
      'chart' => 'mostPopular',
      'maxResults' => $rank,
      'regionCode' => 'JP'
    ];
    // 必要情報を引数に持たせ、listSearchで検索して動画一覧を取得
    $search_results = $youtube->videos->listVideos($part, $params);
    $message = [];
    foreach ($search_results['items'] as $i => $search_result) {
      $message[] = new TextMessage([
        'type' => 'text',
        'text' => "急上昇" . $i + 1 . "位\n" . $search_result['snippet']['title'] . "\n" . "https://www.youtube.com/watch?v=" . $search_result['id']
      ]);
    }
    return $message;
  }
}

