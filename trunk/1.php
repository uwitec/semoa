<?php 
exit();
/*
$f = new SaeFetchURL();
$d = $f -> fetch('https://api.weibo.com/2/statuses/user_timeline/ids.json?access_token=2.00QOgF8DjURKUD6c52ede8f00TnUpU&uid=3148713540');
$d = json_decode($d,true);

foreach($d['statuses'] as $key => $val){
 $f -> clean();
    $f -> setMethod('POST');
    $f -> setPostData('id='.$val.'&access_token=2.00QOgF8DjURKUD6c52ede8f00TnUpU');
    $f -> fetch('https://api.weibo.com/2/statuses/destroy.json');
    
}
*/