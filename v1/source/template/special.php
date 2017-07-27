<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo $special['title'];?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="format-detection" content="telephone=no">
    <link href="//statics.shunliandongli.com/source/modules/bj_qmxk/recouse/css/dzd_bjcommon.css?v=20160529" rel="stylesheet" type="text/css" />
    <?php if(!empty($special['css'])):?>
    <style type="text/css">
    <?php echo $special['css'];?>
    </style>
    <?php endif;?>
</head>
<body style="margin:0 auto; padding:0 auto">
<div id="viewport" class="viewport">
 <div id="home-page" data-role="page" data-member-sn="ejWCX" data-member-subscribe="true">
  <div role="main" class="ui-content ">
  <div class="zidingyi">
 <?php echo $special['content'];?>
 </div>
 </div>
</div>
</div>
<script type="text/javascript" src="https://statics.shunliandongli.com/resource/script/jquery-1.11.1.min.js"></script>
<script type="text/javascript" src="https://statics.shunliandongli.com/resource/script/jquery.lazyload.js"></script>
<script type="text/javascript">
  $("img.lazy").lazyload();
</script>
</body>
</html>