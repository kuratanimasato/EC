<?php
require_once dirname(__DIR__) . '/app/functions.php';
//セッションの開始
startSession();

//POSTされてきたデータを格納する変数の定義と初期化
destroySession();

//ログインページへリダイレクト
header("location: login.php");