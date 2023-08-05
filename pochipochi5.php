<?php
//pochipochi5(ポチポチファイブ)はカエレバと同じものを自前で用意したものである
//Ri○kerは100%GPLじゃないからちょっと、ショートコードを実装するためにfunction.phpをいじるのもちょっとなという方におすすめだ

// 利用するSDKのクラスをインポート
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\api\DefaultApi;
use Amazon\ProductAdvertisingAPI\v1\ApiException;
use Amazon\ProductAdvertisingAPI\v1\Configuration;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\GetItemsRequest;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\GetItemsResource;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\PartnerType;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\ProductAdvertisingAPIClientException;

// ブックマークレットからGETするASINコードとキーワードを変数に格納する
$getasin = $_GET["asin"];
$getkw = $_GET["kw"];

//楽天アフィリエイトID
  $rakuten_affiliate_id = '10ef1d94.c90f9829.10ef1d95.53606a39';
  //Yahoo!バリューコマースSID
  $sid = 'XXXXXXXXXXXXXXXXX';
  //Yahoo!バリューコマースPID
  $pid = 'XXXXXXXXXXXXXXXXX';

//Amazonキーワード検索用にアソシエイトタグを入れておく
  $ptag = 'XXXXXXXXXXXXXXXXX-22';

// SDKを利用するためautoload.phpを読み込み
require_once(dirname( __FILE__ ) . '/files/paapi5-php-sdk/vendor/autoload.php'); 

// 基本のリクエストパラメータを設定するConfigurationのインスタンスを作成
$config = new Configuration();
$config->setAccessKey('XXXXXXXXXXXXXXXXX'); // アクセスキーIDを指定
$config->setSecretKey('XXXXXXXXXXXXXXXXX'); // シークレットキーを指定
$config->setHost('webservices.amazon.co.jp');  // Hostを指定
$config->setRegion('us-west-2'); // Regionを指定

// PA-APIのリクエストを行うDefaultApiのインスタンスを作成
$apiInstance = new DefaultApi(
	new \GuzzleHttp\Client(), 
	$config
);

// GetItemsオペレーションを利用するため、GetItemsRequestのインスタンスを作成
$getItemsRequest = new GetItemsRequest();
$getItemsRequest->setPartnerTag($ptag); // アソシエイトタグを指定
$getItemsRequest->setPartnerType(PartnerType::ASSOCIATES); // PartnerTypeを指定
$itemIds = array(
	$getasin
);
$getItemsRequest->setItemIds($itemIds); // 取得する商品のASIN番号を指定
$resources = array(
	GetItemsResource::ITEM_INFOTITLE,
	GetItemsResource::OFFERSLISTINGSPRICE,
	GetItemsResource::IMAGESPRIMARYMEDIUM
);
$getItemsRequest->setResources($resources); // 取得したいレスポンスデータによってリソースを指定

// GetItemsRequestのインスタンスに指定したリクエストパラメータが無効の場合にエラーを出力
$invalidPropertyList = $getItemsRequest->listInvalidProperties();
$length = count($invalidPropertyList);
if ($length > 0) {
	echo "Error forming the request", PHP_EOL;
	foreach ($invalidPropertyList as $invalidProperty) {
		echo $invalidProperty, PHP_EOL;
	}
	return;
}

// ASIN番号をもとに、取得したレスポンスデータをマッピングする関数
function parseResponse($items){
    $mappedResponse = array();
    foreach ($items as $item) {
        $mappedResponse[$item->getASIN()] = $item;
	}
    return $mappedResponse;
}

try {
    // GetItemsオペレーションのPA-APIのリクエストを送信してレスポンスデータを取得
    $getItemsResponse = $apiInstance->getItems($getItemsRequest);

    if ($getItemsResponse->getItemsResult()->getItems() != null) {
        // ASIN番号をもとに、取得したレスポンスデータをマッピング
        $responseList = parseResponse($getItemsResponse->getItemsResult()->getItems());

        // 商品ごとにデータを出力
        foreach ($itemIds as $itemId) {
            $item = $responseList[$itemId];
            if ($item != null) {
                // 商品データを取得
                $title = $item->getItemInfo()->getTitle()->getDisplayValue();
                $page_url = $item->getDetailPageURL();
                $image_url = $item->getImages()->getPrimary()->getMedium()->getURL();
                $price = $item->getOffers()->getListings()[0]->getPrice()->getDisplayAmount();
                // 商品データを出力
                echo "<a href='" . htmlspecialchars($page_url, ENT_QUOTES, 'UTF-8') . "' target='blank' rel='nofollow'>";
                echo '<p>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</p>';    
                echo "<img src='" . htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') . "' alt='" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "'>";
                echo '<p>' . htmlspecialchars($price, ENT_QUOTES, 'UTF-8') . '</p>';    
                echo '</a>';
            } else {
                echo "<p>商品が見つかりません</p>";
            }
        }
    }

    if ($getItemsResponse->getErrors() != null) {
        // 取得したレスポンスデータが無効の場合、エラーを出力
        echo PHP_EOL, 'Printing Errors:', PHP_EOL, 'Printing first error object from list of errors', PHP_EOL;
        echo 'Error code: ', $getItemsResponse->getErrors()[0]->getCode(), PHP_EOL;
        echo 'Error message: ', $getItemsResponse->getErrors()[0]->getMessage(), PHP_EOL;
    }
} catch (ApiException $exception) {
    // PA-APIのリクエストがエラーの場合、エラーを出力
    echo "Error calling PA-API 5.0!", PHP_EOL;
    echo "HTTP Status Code: ", $exception->getCode(), PHP_EOL;
    echo "Error Message: ", $exception->getMessage(), PHP_EOL;
    if ($exception->getResponseObject() instanceof ProductAdvertisingAPIClientException) {
        $errors = $exception->getResponseObject()->getErrors();
        foreach ($errors as $error) {
            echo "Error Type: ", $error->getCode(), PHP_EOL;
            echo "Error Message: ", $error->getMessage(), PHP_EOL;
        }
    } else {
        echo "Error response body: ", $exception->getResponseBody(), PHP_EOL;
    }
} catch (Exception $exception) {
    // その他のエラーを出力
    echo "Error Message: ", $exception->getMessage(), PHP_EOL;
}

// 各種アフィリエイトのキーワード検索用URLをそれぞれの変数に入れる
$amazon_url = 'https://www.amazon.co.jp/gp/search?keywords='.$getkw.'&tag='.$ptag;
$rakuten_url = 'https://hb.afl.rakuten.co.jp/hgc/'.$rakuten_affiliate_id.'/?pc=https%3A%2F%2Fsearch.rakuten.co.jp%2Fsearch%2Fmall%2F'.$getkw.'%2F-%2Ff.1-p.1-s.1-sf.0-st.A-v.2%3Fx%3D0%26scid%3Daf_ich_link_urltxt%26m%3Dhttp%3A%2F%2Fm.rakuten.co.jp%2F';
$yahoo_url = 'https://ck.jp.ap.valuecommerce.com/servlet/referral?sid='.$sid.'&pid='.$pid.'&vc_url=http%3A%2F%2Fsearch.shopping.yahoo.co.jp%2Fsearch%3Fp%3D'.$getkw;

?>
<html>
<head>
    <title>ポチポチファイブ</title>
<style>
	/*ポチレバ*/
.pochireba {
  border: 1px solid #ccc;
  border-radius: 4px;
  box-shadow: 1px 1px 3px 1px #ddd;
  padding: 15px !important;
  margin-bottom: 20px;
  text-align:center;
}
.pochireba .pochi_img {
  box-shadow: 0 0 1px 1px #ccc;
  margin: 25px !important;
  width:170px;
}
.pochireba .pochi_info {
  margin-left: 5px;
}
.pochireba .pochi_name {
  text-align: center;
}
.pochireba .pochi_name a {
  display: block;
  font-size: 18px;
  text-decoration: none;
  text-align: center;
  margin: 0 10px 10px;
  padding: 6px;
  border: 1px solid #ccc;
  border-radius: 8px;
  text-shadow:1px 1px 1px rgba(0,0,0,0.3);
  line-height: 26px;
  background: -moz-linear-gradient(top,#FFF 0%,#EEE);
  background: -webkit-gradient(linear, left top, left bottom, from(#FFF), to(#EEE));
}
.pochireba .pochi_seller {
  margin-bottom: 5px;
}
.pochireba .pochi_name a::after {
  content: 'ダウンロードページへ';
  display: block;
  font-size: 15px;
  color: #555;
}
/*カエレバ*/
.kaerebalink-box,
    .booklink-box {
       width:100%;
       font-size:12px;
       color:#5e6065;
       border:1px solid #ddd;
       padding:15px 15px 14px;
       margin:26px 0 28px;
       box-sizing:border-box;
       word-break:break-all;
    }
    .kaerebalink-box:after,
    .booklink-box:after {
       content:"";
       display:block;
       clear:both;
    }
    .kaerebalink-image,
    .booklink-image {
       float:left;
       width:100px;
       text-align:center;
    }
    .kaerebalink-image img,
    .booklink-image img {
       margin:0 !important;
       width:100%;
       height:auto;
    }
    .kaerebalink-info,
    .booklink-info {
       margin:0 0 0 110px;
    }
    .kaerebalink-name > a,
    .booklink-name > a {
       font-size:14px;
       font-weight:bold;
       color:#2e3035;
    }
    .kaerebalink-powered-date,
    .booklink-powered-date {
       line-height:1.5;
       margin:3px 0;
    }
    .kaerebalink-powered-date a,
    .booklink-powered-date a {
       color:#5e6065;
    }
    .kaerebalink-detail,
    .booklink-detail {
       line-height:1.5;
    }
    .kaerebalink-link1 a,
    .booklink-link2 a {
       color:#fff;
       text-decoration:none;
       display:block;
       text-align:center;
       line-height:28px;
       border-radius:4px;
    }
    /* ブランドカラーより少し薄めに */
    .shoplinkamazon a {
       background:#f8a512;
       margin:7px 0 5px;
    }
    .shoplinkkindle a {
       background:#159dd6;
       margin:5px 0;
    }
    .shoplinkrakuten a {
       background:#d43232;
    }
    .shoplinkyahoo a {
       background:#848484;
    }
    /* ショップ名の後ろに「で探す」を表示 */
    .shoplinkamazon a:after,
    .shoplinkyahoo a:after,
    .shoplinkkindle a:after,
    .shoplinkrakuten a:after {
       content:"\3067\63A2\3059";
    }
    .kaerebalink-link1 a:hover,
    .booklink-link2 a:hover {
       opacity:.8;
       color:#fff;
    }
    .kaerebalink-box p,
    .booklink-box p {
       margin:0;
    }
    @media screen and (min-width:471px) {
      .kaerebalink-detail,
      .booklink-detail {
         margin-bottom:7px;
      }
      .kaerebalink-link1,
      .booklink-link2 {
         margin-right:-6px;
      }
      .kaerebalink-link1 div,
      .booklink-link2 div {
         width:50%;
         float:left;
         margin-bottom:5px;
         padding-right:0px;
      }
      .booklink-link2 div:nth-of-type(3) {
         margin-top:1px;
      }
      .shoplinkamazon a,
      .shoplinkkindle a {
         margin:0;
      }
    }
    @media screen and (min-width:581px) {
      .kaerebalink-box,
      .booklink-box {
         font-size:13px;
         padding:20px 20px 19px;
         margin:28px 0 32px;
      }
      .kaerebalink-image,
      .booklink-image {
         width:112px;
      }
      .kaerebalink-info,
      .booklink-info {
         margin:0 0 0 124px;
      }
      .kaerebalink-name > a,
      .booklink-name > a {
         font-size:15px;
      }
      .kaerebalink-powered-date,
      .booklink-powered-date {
         margin:4px 0;
      }
      .kaerebalink-link1 a,
      .booklink-link2 a {
         line-height:30px;
         font-size:12px;
      }
			 /*ポチレバスマホ用コード*/
  .pochireba > a {
  display: block;
}
.pochireba .pochi_img {
  float: none !important;
  margin: 0 auto 10px !important;
}
.pochireba .pochi_name {
  font-size: 17px;
}

    }
    @media screen and (min-width:768px) {
      .kaerebalink-link1 div,
      .booklink-link2 div {
         width:33.33333333%;
		}
		.c-site-branding .custom-logo, .wpaw-site-branding .custom-logo {
    height: auto!important;
    width: 300px!important;
      }
      .booklink-link2 div:nth-of-type(3) {
         margin-top:0;
      }
    }
</style>
</head>
<body>
<h1>ポチポチファイブ</h1>
魚住惇がカエレバと同じようにリンクを作るためだけに作ったページをPA-PAI5.0に対応させたもの
<h2>使い方</h2>
まずは、このブックマークレットをブラウザに保存する。<br>
<a href="javascript:(function(){var nakami;$nakami=document.getElementById('ASIN').value;window.open('https://jun3010.me/pochipochi5.php?asin='+$nakami+'&kw='+window.getSelection().toString())})();">ポチポチ5ブックマークレット</a><br>
あとは、Amazonの商品ページで、楽天とYahoo!のキーワードにするキーワードを選択した状態でブックマークレットを実行するだけ。<br>

<div class="cstmreba">
<div class="kaerebalink-box">
<div class="kaerebalink-image"><a href="<?php echo htmlspecialchars($page_url); ?>" target="_blank" ><img src="<?php echo htmlspecialchars($image_url); ?>" style="border: none;" /></a></div>
<div class="kaerebalink-info">
<div class="kaerebalink-name"><a href="<?php echo htmlspecialchars($page_url); ?>" target="_blank" ><?php echo htmlspecialchars($title); ?></a>
<div class="kaerebalink-powered-date">posted with <a href="https://jun3010.me/pochipochi5.php" rel="nofollow" target="_blank">ポチポチファイブ</a></div>
</div>
<div class="kaerebalink-link1">
<div class="shoplinkamazon"><a href="<?php echo htmlspecialchars($amazon_url); ?>" target="_blank" >Amazon</a></div>
<div class="shoplinkrakuten"><a href="<?php echo htmlspecialchars($rakuten_url); ?>" target="_blank" >楽天市場</a></div>
<div class="shoplinkyahoo"><a href="<?php echo htmlspecialchars($yahoo_url); ?>" target="_blank" >Yahooショッピング<img src="//ad.jp.ap.valuecommerce.com/servlet/gifbanner?sid=3040825&#038;pid=884909937" height="1" width="1" border="0"></a></div>
</div>
</div>
<div class="booklink-footer"></div>
</div>
</div>
<textarea onclick="this.select()">
<div class="cstmreba">
<div class="kaerebalink-box">
<div class="kaerebalink-image"><a href="<?php echo htmlspecialchars($page_url); ?>" target="_blank" ><img src="<?php echo htmlspecialchars($image_url); ?>" style="border: none;" /></a></div>
<div class="kaerebalink-info">
<div class="kaerebalink-name"><a href="<?php echo htmlspecialchars($page_url); ?>" target="_blank" ><?php echo htmlspecialchars($title); ?></a>
<div class="kaerebalink-powered-date">posted with <a href="https://jun3010.me/pochipochi5.php" rel="nofollow" target="_blank">ポチポチファイブ</a></div>
</div>
<div class="kaerebalink-link1">
<div class="shoplinkamazon"><a href="<?php echo htmlspecialchars($amazon_url); ?>" target="_blank" >Amazon</a></div>
<div class="shoplinkrakuten"><a href="<?php echo htmlspecialchars($rakuten_url); ?>" target="_blank" >楽天市場</a></div>
<div class="shoplinkyahoo"><a href="<?php echo htmlspecialchars($yahoo_url); ?>" target="_blank" >Yahooショッピング<img src="//ad.jp.ap.valuecommerce.com/servlet/gifbanner?sid=3040825&#038;pid=884909937" height="1" width="1" border="0"></a></div>
</div>
</div>
<div class="booklink-footer"></div>
</div>
</div></textarea>

</body>
</html>
