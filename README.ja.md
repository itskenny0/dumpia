# dumpia
## これはなに
dumpiaは[itskenny0](github.com/itskenny0)氏によって作成された、アクセスを許可されている[Fantia](fantia.jp)のファンクラブのすべての投稿をダウンロードすることができるPHPスクリプトです  
ペイウォール(有料プラン)のコンテンツは回避できません

## 推奨環境
PHP7.2で開発されていますが、PHP5.x以降なら動作するはずです  
php-curlパッケージが必要なため、ディストリやOSに合わせてインストールしてください  
もっとも簡単な方法としてはWSL環境を構築し、`apt install php-curl`を使用することです  

## 使い方
### dumpia.shを編集  
5行目`<output directory>`に出力先のディレクトリを指定してください  
5行目`<_session_id>`にCokkieにある`_session_id`の値を入力します。(EditThisCokkieなどの拡張機能を使用してください)  
もし、[--verbose] [--downloadExisting] [--exitOnFreePlan]を使いたい場合は、5行目に追加してください  
※WSLを使う場合は、出力先を`mnt/c(d,e,f他)`とドライブを指定し、最後に`$fanid`をつけるのを忘れないでください(例:`mnt/d/Fantia/$fanid`)

### 実行
dumpia.shを実行し、クリエイターIDを入力すると、IDごとのディレクトリが自動的に作成され、そのディレクトリにファイルがダウンロードされます。  
途中でキャンセルされた場合はファイルチェックを行い、続きから自動でダウンロードされます

## インストール例
[![asciicast](https://asciinema.org/a/yM1E9Ia4U8mTioqVNG8gIEvB4.svg)](https://asciinema.org/a/yM1E9Ia4U8mTioqVNG8gIEvB4)
