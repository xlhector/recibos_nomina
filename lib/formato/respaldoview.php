<?php 
include_once('simple_html_dom.php');

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

$html = file_get_html("https://docs.google.com/document/pub?id=$id&embedded=true");
$html = str_replace('padding:28.3pt 42.5pt 56.7pt 42.5pt', '', $html);
$html = str_replace('#d9d9d9', '#ffffff', $html);

foreach ($data as $key => $value) {
  $html = replace($key, $value, $html);
}
$html = str_get_html($html);

foreach($html->find('img') as $img) {
  //if ($img->src)
  if (!strstr($img->src, 'http')) {
    $img->src = 'https://docs.google.com/document/' . $img->src;
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

</script>
