<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo $title['act_title'];?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="stylesheet" type="text/css" href="https://statics.shunliandongli.com/resource/css/bq-style.css">
</head>
<body>
<div class="zy">
    <img class="ad" src="<?php if(empty($activity_adv['top_banner'])){ 
        echo 'http://img01.shunliandongli.com/attachment/images/2/2017/01/yIw2KgCLwsLiEqwEiiBsLWzZ3QCwig.jpg';
         }else{
         echo $_W['attachurl'].$activity_adv['top_banner'];
         
         }
             ?>" />
         
    <ul class="list">
        <?php foreach ($list as $key=>$item): ?>
        <li >
            <a href="slmall://goods/item.json?goodsId=<?php echo $item['id'];?>">
                <div class="img">
                    <img class="lazy" original="<?php echo $_W['attachurl'].$item['thumb'];?>_300x300.jpg" />
                </div>
                <div class="name"><?php echo $item['title'];?></div>
                <div class="price">
                    <i>¥<?php echo $item['marketprice'];?></i>
                </div>
            </a>
        </li>
        <?php endforeach;?>
    </ul>
</div>
<script type="text/javascript" src="https://statics.shunliandongli.com/resource/script/jquery-1.11.1.min.js"></script>
<script type="text/javascript" src="https://statics.shunliandongli.com/resource/script/jquery.lazyload.js"></script>
<script type="text/javascript">
  $("img.lazy").lazyload();
</script>
</body>
</html>