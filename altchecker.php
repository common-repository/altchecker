<?php
/**
 * Plugin Name: Alt checker
 * Plugin URI: http://www.infoalap.hu
 * Description: Alt checker
 * Version: 1.3
 * Author: Aron Ocsvari
 * Author URI: http://www.oaron.hu
 * Text Domain: altchecker
 * License: GPL2
 */
 
 
add_action( 'admin_menu', 'my_plugin_menu' );
function my_plugin_menu() {
	add_options_page( 'Altchecker beállításai', 'Alt Checker', 'manage_options', 'altchecker-options', 'options' );
}

function myplugin_init() {
 $plugin_dir = basename(dirname(__FILE__));
 load_plugin_textdomain( 'altchecker', false, $plugin_dir.'/langs/' );
}
add_action('plugins_loaded', 'myplugin_init');

function options() {

//include "locale/".get_locale().".php";
	
if (isset($_POST['sbmbtn'])){
  $szerzok=array(); 
  $postok=array();
  $kulcsok=array_keys($_POST);
  for ($cv = 0; $cv < count($kulcsok); $cv++){
    $sv=$kulcsok[$cv];
    if (substr($sv,0,3) == 'pid'){
	  if ($_POST[$sv]>0){
	    $pid=substr($sv,3);
		$db=$_POST[$sv];
		$aktpost=get_post($pid);
		$aktszerzo=$aktpost->post_author;
		$szerzok[$aktszerzo]=$aktszerzo;
		array_push($postok, array(szerzo=>$aktszerzo,cim=>$aktpost->post_title,permalink=>get_permalink($pid),db=>$db));	
	  }
	}
  }	
  $kulcsok=array_keys($szerzok);
  for ($cv = 0; $cv < count($kulcsok); $cv++){
    $aktszerzo=$kulcsok[$cv];
	$user = get_user_by( 'id', $aktszerzo );
	$headers  = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type: text/html; charset=utf-8" . "\r\n";	
    $headers .= "From: ".get_option('admin_email') . " (".get_option('blogname')."}\r\n";
	$cimzett=$user->user_email;
	$targy=__( 'Missing alternate text', 'altchecker' ); 
	$uzenet=sprintf(__( 'Dear %s,', 'altchecker' ),$user->display_name)."<br><br>";
	$uzenet=$uzenet.__( 'Your following posts contain images without (or possible automatic) alternate text.', 'altchecker' )."<br>".__( 'Please fill out these fields so that screen reader users can also access this information!', 'altchecker' )."<br><br>";
	$aktpostok =array();
	for ($cv2 = 0; $cv2 < count($postok); $cv2++){
     if ($postok[$cv2][szerzo]==$aktszerzo){
	   $pcim=$postok[$cv2][cim];
       $ppermalink=$postok[$cv2][permalink]; 
       $pdb=$postok[$cv2][db];  	   
       array_push($aktpostok, array(cim=>$pcim,permalink=>$ppermalink,db=>$pdb));
	 }
    }
	for ($cv2=0;$cv2<count($aktpostok);$cv2++){
	  $uzenet=$uzenet."<a href='".$aktpostok[$cv2][permalink]."'>".$aktpostok[$cv2][cim]."</a> (".$aktpostok[$cv2][db].")<br>";
	}
	$uzenet =$uzenet."<br><br><br>".__( "This is an automatically generated message. Don't answer this e-mail address!", 'altchecker' )."<br><br>".
	__( 'Best Regards:', 'altchecker' )."<br>".get_option('blogname');
	$l=mail($cimzett, '=?UTF-8?B?'.base64_encode($targy).'?=', $uzenet, $headers);
	if ($l) {
	    echo sprintf(__( 'Sending e-mail to %s  was succesfull!', 'altchecker' ),$user->display_name).'<br>';
	  }	
	  else{
	    echo sprintf(__( 'Sending e-mail to %s  failed!', 'altchecker' ),$user->display_name).'<br>';
	}
  }	
}
else{
  //$regkif ='/img.*alt=\"\".*\" \/\>/';
  //filtering empty tags
  query_posts( $query_string . '&posts_per_page=-1' );
  $voltkep=false;
  echo "<form name='mailform' id='mailform' action='".the_permalink()."' method='post'>";
  if (have_posts()){
	global $more;
	$more=1;//eliminating more link
    while (have_posts()) : the_post();
      $actual =get_the_content();
	  $db=0;
      $xml = new DOMDocument();
	  libxml_use_internal_errors(true);
      $xml->loadHTML('<?xml encoding="utf-8" ?>'.$actual);
	  libxml_clear_errors();
      $imgs = $xml->getElementsByTagName('img');
      foreach ($imgs as $img) {
	    $ki=false;  
	    $alt=$img->getAttribute("alt");
        $src=$img->getAttribute("src");
        $node=$img->c14n();
	    if ($alt==""){
	      $ki=true;	
	    }
	    else{
	      //if the alt in filename?	
	      $kezd=strrpos($src,'/',-1);
          $veg=strrpos($src,'.',-1);
          $ss=substr($src,$kezd+1,$veg-$kezd-1);  
          if (strpos($src,$alt)){
		    $ki=true;  
	      }	  
	    }
        //echo "<tr><td>$alt</td><td>$src</td><td>$ss</td><td>$node</td></tr>";
        if ($ki){
	      if (!$voltkep){
	        echo "<br>";
	        echo "<input type='submit' name='sbmbtn' value='".__( 'Send e-mail to authors', 'altchecker' )."'>";
	        echo "<br>";
	        echo "<br>";
	        echo "<table border =5>";
	        echo "<tr><th>".__( 'Post', 'altchecker' )."</th><th>".__( 'Image', 'altchecker' )."</th></tr>";
			$voltkep=true;
	      }	
		  
		  echo "<tr><td><a href='";
		  the_permalink();
		  echo "' target='_blank'>";
		  the_title();
		  $title=$img->getAttribute("title");
		  $node="<center><img src=\"$src\" title=\"$title\" style='max-width:50%; height:auto;padding:5px;></center>";
		  echo "</a></td><td>$node</td></tr>";
		  $id=get_the_ID();
		  $db++;
          echo "<input type='hidden' name='pid".$id."' value='".$db."'>";
	    } 	
       }	  
  
  /*$db=0;
  $res =explode("<", $actual);
  foreach ($res as $elem) {
  if (preg_match($regkif, $elem, $eredm)) {

  ?>
  <Tr><td><a href="<?php the_permalink() ?>" target="_blank"><?php the_title(); $db++;?></a></td><td><<?php print $eredm[0]; ?></td></tr>
  <?php
     
  }
  }
  if ($db>0){ 
	   $id=get_the_ID();
       echo "<input type='hidden' name='pid".$id."' value='".$db."'>";
  }*/
  endwhile;
  if ($voltkep){
    echo '</table>';
  }
  else{
	echo  __( 'Your posts have no images with alternate text problems.', 'altchecker' );
  }   
  echo "</form>";
  
  }
}  
}
?>