<?php
	/*
	A simple php function to check url of sipp web on the fly and realtime.
	with a simple content validation too.
	init dev by Zeno Dani Kuncoro a.k.a th30nly
	pengadilan tinggi bengkulu
	this code is freely distributed and used by anyone in this universe as is but please consider the credits :)
	you can expand this functionality and include it into your project also, but remember to share it to anyone.
	because knowledges belongs to world
	init build  : Jul 22nd 2016
	last update : Jul 10th 2017
	*/

	
	class sippwebchk{
		
		function __construct(){
			//$this->cachetime = 3600;
			$cachedir = realpath(dirname(__FILE__)).'/cache/' ;
			if (!file_exists($cachedir)) {
				mkdir($cachedir, 0777);
			}
		}
		
		// curl function to get html content 
		// supported method is http POST by passing argument $postinfo
		// and http GET by ignore passing argument $postinfo
		private function curl_get_data($url, $postinfo=''){
			$html = '';
			//$cookie_file_path = "./cookie.txt";
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_NOBODY, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			//curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file_path);
			//curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file_path);
			curl_setopt($ch, CURLOPT_COOKIE, "cookiename=0");
			curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_REFERER, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			
			//request content using http protocol
			if(empty($postinfo)){
				// HTTP GET method
				curl_setopt($ch, CURLOPT_URL, $url);
			}else{
				// HTTP POST method
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_VERBOSE, true);
				curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);			
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postinfo);
				//var_dump(curl_exec($ch));
			}
			$html = @curl_exec($ch);
			curl_close($ch);
			
			// cleaning cookies
			//if(file_exists($cookie_file_path)){
			//	unlink($cookie_file_path);
			//}
			
			return $html;
		}
		
		// read cache file content.
		// actually this is reading file function :D
		private function readcache($cachefile){
			if(file_exists($cachefile)){
				return file_get_contents($cachefile);
			}else{
				return '';
			}		
		}
		// write cache file content.
		// actually this is a write file function :D
		private function writecache($cachefile, $content){
			if ( $content != "" ){
				$f = fopen("$cachefile","w");
				fwrite($f, $content);
				fclose($f);
			}		
		}
		// validate cache expired by passing cache file name $fname and cache time $cachetime as function parameter
		private function is_expired($fname, $cachetime=3600){
			clearstatcache();
			if ( file_exists($fname) && ((time()-$cachetime) < filemtime($fname)) && (filesize($fname) >1 ) ) {
				return false;
			}else{
				return true;
			}
		}
		// cache http GET method
		private function GETdata($cachefile,$cachetime,$url){
			$res = '';
			if( $this->is_expired($cachefile, $cachetime) ){
				$res = $this->curl_get_data($url);
			}
			if( !empty($res) ){
				$this->writecache($cachefile,$res);
				return $res;
			}else{
				if(file_exists($cachefile)){
					return $this->readcache($cachefile);
				}else{
					return '';
				}
			}	
		}
		// cache http POST method and return the content
		private function POSTdata($cachefile,$cachetime,$url,$postinfo){
			$res = '';
			if( $this->is_expired($cachefile, $cachetime) ){
				$res = $this->curl_get_data($url, $postinfo);
			}
			if( !empty($res) ){
				$this->writecache($cachefile,$res);
				return $res;
			}else{
				if(file_exists($cachefile)){
					return $this->readcache($cachefile);
				}else{
					return '';
				}
			}	
		}
		
		public function getPage($url, $cachetime=3600, $postinfo=''){
			$res='';
			// zeno edit july 28 2016
			// remove space in url
			$domain=parse_url(trim($url));
			// validate host existence, if not, define by self
			if(!isset($domain['host'])){
				$needle = array(':', '/', '\/' , ' ');
				$domain['host'] = str_replace($needle, '', $url);
			}
			// end zeno edit
			$cachefile = './cache/.'.$domain['host'].'.htaccess';
			if (empty($postinfo)){
				$res = $this->GETdata($cachefile,$cachetime,$url);
			}else{
				$res = $this->POSTdata($cachefile,$cachetime,$url);
			}
			if( empty($res) ){
				return false;
			}else{
				return $res;
			}
		}
	
			
		// function to get total perkara sipp web
		// will return false if fail
		private function getTotalPerkara($content){
			$total = false;
			$content = trim(preg_replace('/\s+/', ' ', $content));
			//var_dump($content);die();
			if(preg_match('/<div class="total_perkara">[\s\w\d\D]*Total : ([0-9\.]*)[\s]*[\w\s]*<\/div>/i', $content, $total)){
				$total = str_replace('.','',$total[1]);
			}else{
				$total = false;
			}
			return $total;
		}
		
		// function to get last content update date (Pembaharuan data)
		// will return false if fail
		// added on July 10th 2017
		private function getPembaharuan($content){
			$pembaharuan = false;
			$content = trim(preg_replace('/\s+/', ' ', $content));
			//var_dump($content);die();
			if(preg_match('/<div class="total_perkara">[\w\s]*Pembaharuan Data : ([\w]*), ([\d]{1,2}) ([\w]{3})\. ([\d]{4}) ([\d\:]*) ([\w]{3}), Total : ([0-9\.]*)[\s]*[\w\s]*<\/div>/i', $content, $pembaharuan)){
				$hari = $pembaharuan[1];
				$tanggal = $pembaharuan[2];
				$bulan = $pembaharuan[3];
				$tahun = $pembaharuan[4];
				$jam = $pembaharuan[5];
				$zona = $pembaharuan[6];
				$pembaharuan = $hari.", ".$tanggal." ".$bulan." ".$tahun." ".$jam." ".$zona;
			}else{
				$pembaharuan = false;
			}
			return $pembaharuan;
		}
	
		// function to get version of sipp web
		// will return false if fail
		private function getWebVersion($content){
			$version = false;
			if(preg_match('/.*>version ([a-z0-9\.\-]*)<.*/i', $content, $version)){
				$version = $version[1];
			}
			if(empty($version)){
				if(preg_match('/.*>versi ([a-z0-9\.\-]*)<.*/i', $content, $version)){
					$version = $version[1];
				}
			}
			return $version;
		}
	
		// function to get php Error on sipp web
		// will return false if fail
		private function isPHPError($content){
			$error = false;
			if(preg_match('/.*php error.*/i', $content)){
				$error = true;
			}
			return $error;
		}
	
		// function to get message "database belum disetting on sipp web
		// will return false if fail
		private function isCompleteSetting($content){
			$complete = true;
			if(preg_match('/.*database belum disetting.*/i', $content)){
				$complete = false;
			}
			return $complete;
		}
	
		// function to check valid SIPP on sipp web
		// will return false if fail
		private function isCorrectSIPP($content){
			$correct = false;
			
			if(  ($this->isPHPError($content) == false) && ($this->getWebVersion($content) != false ) && (intval($this->getTotalPerkara($content))>0 && $this->getTotalPerkara($content) != false) && $this->getPembaharuan($content) != false ){
				$correct = true;
			}
			return $correct;
		}
	
		// function to get hacked or not on sipp web
		// will return false if fail
		private function isHacked($content){
			$hacked = false;
			if(preg_match('/.*hack.*/i', $content)){
				$hacked = true;
			}
			return $hacked;
		}
	
		// function to get database error on sipp web
		// will return false if fail
		private function isDBError($content){
			$error = false;
			if(preg_match('/.*database error.*/i', $content)){
				$error = true;
			}else if(preg_match('/.*error in your sql.*/i', $content)){
				$error = true;
			}
			return $error;
		}
	
		// function to get 404 error msg on sipp web
		// will return false if fail
		private function is404($content){
			$error = false;
			if(preg_match('/.*not found.*/i', $content)){
				$error = true;
			}
			return $error;
		}
	
		// function to get 404 error msg on sipp web
		// will return false if fail
		private function is403($content){
			$error = false;
			if(preg_match('/.*forbidden.*/i', $content)){
				$error = true;
			}
			return $error;
		}
	
		// main function call, return as array object
		public function checknow($url){
			$retval = array();
			$content = $this->getPage($url);
			$total = $this->getTotalPerkara($content);
			$version = $this->getWebVersion($content);
			$updatetime = $this->getPembaharuan($content);
			$php = $this->isPHPError($content);
			$setup = $this->isCompleteSetting($content);
			
			$retval['uri'] = $url;
			
			if(empty($content)){
				$retval['total'] = '<p style="color:red;">NO DATA</p>';
				$retval['version'] = '<p style="color:red;">NO DATA</p>';
				$retval['valid'] = '<p style="color:red;">INVALID SIPP</p>';
				return $retval;
			}
			
			if($total != false){
				$retval['total'] = '<p style="color:green;">'.$total.'</p>';
			}else{
				$retval['total'] = '<p style="color:red;">ERR DATA SIPP</p>';
			}
			
			if($version != false){
				$retval['version'] = '<p style="color:green;">'.$version.'</p>';
			}else{
				$retval['version'] = '<p style="color:red;">ERR VERSION</p>';
			}
			
			if($updatetime != false){
				$retval['updatetime'] = '<p style="color:green;">'.$updatetime.'</p>';
			}else{
				$retval['updatetime'] = '<p style="color:red;">ERR UPDATE</p>';
			}
			
			
			if($this->isCorrectSIPP($content)){
				$retval['valid'] = '<p style="color:green;">VALID</p>';
			}else{
				$retval['valid'] = '<p style="color:red;">SIPP WEB Bermasalah<br/>';
				if($this->isHacked($content)){
					$retval['valid'] .= 'SIPP HACKED !!<br/>';
				}
				if($this->is404($content)){
					$retval['valid'] .= '404 Not Found<br/>';
				}
				if($this->is403($content)){
					$retval['valid'] .= '403 Forbidden Access<br/>';
				}
				if($this->isDBError($content)){
					$retval['valid'] .= 'Database Error<br/>';
				}
				if($this->isPHPError($content)){
					$retval['valid'] .= 'ERR PHP<br/>';
				}
				if($setup == false){
					$retval['valid'] .= 'ERR Setup';
				}
				$retval['valid'] .= '</p>';
			}
			
			return $retval;
			
		}
		
	}
	
?>