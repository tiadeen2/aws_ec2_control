`aws_ec2_control`
=================

`AWS SDK for PHP2` を使って、EC2の起動と停止を外部サーバーから制御する。

なんで作ったの？
----------------
EC2をずっと起動しておくとお金がかかる。個人的な実験用に使っているEC2なので
必要になったら起動して、要らなくなったら停止しておきたいのです。

どうやって実装したの？
----------------------
外部サーバーから、`AWS SDK for PHP2`を使ってAPI経由で制御できるので、その方法で実装しています。

なので、`AWS SDK for PHP2` の下記URLの「前提条件」にあった動作環境が必要です。<br />
[AWS SDK for PHP 2](http://aws.amazon.com/jp/sdkforphp2/)



インストール方法
================

まずはSDKをダウンロードしてインストールします。

SDKのインストール方法は下記のURLでいくつか提供方法があるようです。<br />
[Installation (英語)](http://docs.aws.amazon.com/aws-sdk-php-2/guide/latest/installation.html)

私はzipで解凍しただけにしました。解凍したディレクトリに下記サンプル プログラムを書いている想定で続けます。<br />
（`aws-autoloader.php`と同階層に置いてある想定）


準備
----

`ec2_control.php`を開いて、下記の4箇所を自分のAWSインスタンスに合うようにします。

    $client = Ec2Client::factory(array(
            'key'    => '＜アクセスキーを指定＞',
            'secret' => '＜シークレットキーを指定＞',
            'region' => Region::TOKYO
            )
        );
    
    define('INSTANCE_ID', '＜インスタンスIDを指定＞');
    define('ERASTIC_IP',  '＜ErasticIPを指定＞');


操作方法
========

ブラウザからアクセスするだけです。

* インスタンスの状態確認
    * http://domain.example.jp/installed-dir/ec2_control.php?cmd=status
* 起動する
    * http://domain.example.jp/installed-dir/ec2_control.php?cmd=start
* 停止する
    * http://domain.example.jp/installed-dir/ec2_control.php?cmd=stop
* インスタンスの変更 (インスタンスが停止中でなければ変更不可)
    * http://domain.example.jp/installed-dir/ec2_control.php?cmd=chinst&type=t1.micro
    * http://domain.example.jp/installed-dir/ec2_control.php?cmd=chinst&type=m1.small

※適当に操作されないように、BASIC認証もしくはIPアドレス制限を設定しておくといいでしょう。