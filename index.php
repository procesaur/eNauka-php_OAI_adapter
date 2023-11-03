<?php 

$site = "http:/dr.rgf.bg.ac.rs";
$oai_uri =  $site."/oai".$_SERVER['REQUEST_URI'];

if(isset($_GET['verb'])) {
$verb = $_GET["verb"];
}
else{$verb="x";}

$options = array(

	CURLOPT_CUSTOMREQUEST  =>"GET",        //set request type post or get
	CURLOPT_POST           =>false,        //set to GET
	CURLOPT_COOKIEFILE     =>"cookie.txt", //set cookie file
	CURLOPT_COOKIEJAR      =>"cookie.txt", //set cookie jar
	CURLOPT_RETURNTRANSFER => true,     // return web page
	CURLOPT_HEADER         => false,    // don't return headers
	CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	CURLOPT_ENCODING       => "",       // handle all encodings
	CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
	CURLOPT_TIMEOUT        => 120,      // timeout on response
	CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	CURLOPT_SSL_VERIFYPEER => false,
	CURLOPT_SSL_VERIFYHOST => false,
);

$ch = curl_init($oai_uri);
curl_setopt_array($ch, $options);
$result = curl_exec($ch);
if(curl_errno($ch))
    echo 'Curl error: '.curl_error($ch);
curl_close ($ch); 


$result = preg_replace ('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $result);
$xml = simplexml_load_string($result, NULL, NULL, "http://www.openarchives.org/OAI/2.0/");

if (!function_exists('trans'))   {
	function trans($x){
		$cyr = array('а','б','в','г','д','ђ','е','ж','з','и','ј','к','л','љ','м','н','њ','о','п','р','с','т','ћ','у','ф','х','ц','ч','џ','ш','А','Б','В','Г','Д','Ђ','Е','Ж','З','И','Ј','К','Л','Љ','М','Н','Њ','О','П','Р','С','Т','Ћ','У','Ф','Х','Ц','Ч','Џ','Ш');
		$lat = array('a','b','v','g','d','đ','e','ž','z','i','j','k','l','lj','m','n','nj','o','p','r','s','t','ć','u','f','h','c','č','dž','š','A','B','V','G','D','Đ','E','Ž','Z','I','J','K','L','Lj','M','N','Nj','O','P','R','S','T','Ć','U','F','H','C','Č','Dž','Š');
		return  str_replace($cyr, $lat, $x);
	}
}
if (!function_exists('iscyr'))   {
	function iscyr($x){
		$cyr = array('а','б','в','г','д','ђ','е','ж','з','и','ј','к','л','љ','м','н','њ','о','п','р','с','т','ћ','у','ф','х','ц','ч','џ','ш','А','Б','В','Г','Д','Ђ','Е','Ж','З','И','Ј','К','Л','Љ','М','Н','Њ','О','П','Р','С','Т','Ћ','У','Ф','Х','Ц','Ч','Џ','Ш');
		foreach ($cyr as $c){
			if (strpos($x, $c)){ return true; }
		}
		return false;
	}
}


if ($verb=="GetRecord" || $verb="ListRecords"){

	$xml->registerXPathNamespace('oai_dc', "http://www.openarchives.org/OAI/2.0/oai_dc/");
	$xml->registerXPathNamespace('x', "http://www.openarchives.org/OAI/2.0/");
	$xml->registerXPathNamespace('dc', "http://purl.org/dc/elements/1.1/");
	$oais = $xml->xpath('//oai_dc:dc');


	$split_fields = array("contributor", "subject", "spatial");
	$map_values = array(
		"Докторска дисертација" => "doctoralThesis",
		"DoctoralThesis" => "doctoralThesis",
		"Саопштење са скупа штампано у изводу" => "conferenceObject",
		"Рад у зборнику" => "conferenceObject",
		"Дипломски рад" => "bachelorThesis",
		"Магистарска теза" => "masterThesis",
		"Мастер рад" => "masterThesis",
		"Рад у часопису" => "journalArticle",
		"Поглавље у монографији" => "bookPart",
		"Књига" => "book",
		"Монографија" => "book",
		"Практикум" => "book",
		"Скрипта" => "book",
		"Уџбеник" => "book",
		"објављена верзија" => "PublishedVersion",
		"радна верзија" => "Proof",
		"нерецензирана верзија" => "submittedVersion",
		"рецензирана верзија" => "AcceptedVersion",
		"коригована верзија" => "correctedVersion",
		"Објављена верзија" => "PublishedVersion",
		"Радна верзија" => "Proof",
		"Нерецензирана верзија" => "submittedVersion",
		"Рецензирана верзија" => "AcceptedVersion",
		"Коригована верзија" => "correctedVersion",
		"Отворени приступ" => "openAccess",
		"Отворен приступ" => "openAccess",
		"Затворни приступ" => "metadataOnlyAccess",
		"Приступ са лозинком" => "restrictedAccess",
		"Одложени приступ" => "embargoedAccess"
	);


	function split($oai_key, $fieldname){
		global $oais;
		
		$field = $oais[$oai_key]->xpath('dc:'.$fieldname);

		foreach($field as $value) {
			foreach(explode(",", str_replace(", ", ",", $value))as $v){
				$oais[$oai_key]->addChild('dc:'.$fieldname, $v, "http://purl.org/dc/elements/1.1/");	
			}
			unset($value[0]);
		}
	}

	function get_orcid($id){
		global $site;
		global $options;
		$ch = curl_init($site."/api/item_sets?id=".$id);
		curl_setopt_array( $ch, $options );
		$res = json_decode(curl_exec($ch), true);
		curl_close ($ch);
		try{
			$x = $res[0]["rgf:orcId"][0]["@id"];
			return $x;
		}
		catch (Exception $e){
			return "";
		}
}
	foreach ($xml->xpath('//dc:*') as $field){
		foreach ($map_values as $v => $mv){
			if ($field[0] == $v){
				$field[0] = $mv;
			}
		}
	}

	foreach($oais as $oai_key => $oai){
		
		$oai->registerXPathNamespace('dc', "http://purl.org/dc/elements/1.1/");

		foreach ($split_fields as $sf){
			split($oai_key, $sf);
		}

		$creators = $oai->xpath('dc:creator');
		$title = $oai->xpath('dc:title');
		if (count($creators) == 1){
			split($oai_key, "creator");
		}
		else{
			unset($creators[0][0]);
			$creators = $oai->xpath('//dc:creator[@href]');
			$i=0;
			foreach ($creators as $creator){
				$orcid = get_orcid(explode("set/",$creator["href"])[1]);
				$creator[0] = rtrim($creator);
				if (!iscyr($title[0])){
					$creator[0] = trans($creator[0]);
				}
				
				if ($orcid != ""){
					$creator[0]["id"]=$orcid;
				}	
				unset($creator[0]["href"]);
			}		
		}
	}
}
header('Content-Type: text/xml;charset=UTF-8');
echo $xml->asXML();
?>
