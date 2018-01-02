<?php 
include_once('simple_html_dom.php');
//header('Content-Type: text/html; charset=UTF-8'); 
$id = $_REQUEST['plantilla_id'];


$datos = $_REQUEST['datos'];

$datos = str_replace(array('-', '_', '.'), array('+', '/', '='), $datos);
$encrypt_data = base64_decode($datos);
$m = mcrypt_module_open('rijndael-256','','cbc','');
$key = md5("gi7aesawde2zomspgo8guvivmer8oici");
$iv = md5("dob1depatodop7lipdaig7bebeaion9d");
mcrypt_generic_init($m, $key, $iv);
$serial = mdecrypt_generic($m, $encrypt_data);
mcrypt_generic_deinit($m);
mcrypt_module_close($m);
$data = unserialize($serial);

$url = "http://formato.tuna.mx/formatos/$id/docs.google.com/document/";
$html = file_get_html($url);
$html = str_replace('padding:28.3pt 42.5pt 56.7pt 42.5pt', '', $html);
$html = str_replace('#d9d9d9', '#ffffff', $html);
$html = str_replace('{height:11pt}', '{height:5pt}', $html);    
$html = str_replace('{height:141pt}', '{height:10pt}', $html);

function utf8_converter($array)   
{   
    array_walk_recursive($array, function($item, $key){   
        if(!mb_detect_encoding($item, 'utf-8', true)){    
                $item = utf8_encode($item);   
        }   
    });   
    
    return $array;    
}   
$data = utf8_converter(&$data);
foreach ($data as $key => $value) {
  $html = replace($key, $value, $html);
}
$html = str_get_html($html);

foreach($html->find('img') as $img) {
  //if ($img->src)
  if (!strstr($img->src, 'http')) {
    $img->src = $url . $img->src;
  }
}

echo $html;

function replace ($key, $value, $html, $start=''){
  if (is_array($value)) {
    foreach ($value as $subkey => $subvalue) {
      $html = replace($subkey, $subvalue, $html, $start.$key.'_');
    }
  }
  else{
    $values = explode(' ', $value);
    $res = '';
    foreach ($values as $str) {
      $res .= implode(' ', str_split($str, 85)) . ' ';
    }
    //$value = implode(' ', str_split($value, 85));
    $html = str_replace("@$start$key", $res, $html);
    //echo "@$start$key<br>";
  }
  return $html;
}
?>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
<script type="text/javascript">
  var data = <?= json_encode($data) ?>;

  var count = {};
  var trs = $("tr:contains('@@')").not(":contains('<tr')");
  
  trs.each (function (index, tr) {
    var original = $(tr);
    if (!original.find('tr').length) {
      var inline = original.find(":contains('@@'):first").text();
      inline = inline.split('@@')[1];
      inline = inline.split('_')[0];

      if (data[inline]){
        console.log(inline);
        $.each(data[inline], function(index, item){
          var nuevo = original.clone();
          $.each(item, function (key, value) {
            key = '@@' + inline + '_' + key;
            nuevo.html(nuevo.html().replace(key, value));
          });
          nuevo.insertAfter(original);
          //console.log(nuevo);
        });
        original.remove();
      }
    }
  });

  var trs = $("tr:contains('@@')").not(":contains('tr')");
  
  trs.each (function (index, tr) {
    var original = $(tr);
    if (!original.find('tr').length) {
      original.remove();
    }
  });

  $("body").html($("body").html().replace(/\@[a-zA-Z_]+/, ''));
  $("body").html($("body").html().replace('MXN', 'PESOS'));
  $("body").html($("body").html().replace('(***', ''));
  $("body").html($("body").html().replace('***)', 'M.N.'));
</script>
