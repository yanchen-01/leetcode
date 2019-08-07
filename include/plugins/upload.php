<?php
include '../_inc.php';
ini_set ( "memory_limit", "64M" );
func_initSession();

/**
 * 通用上传文件类，用于编辑器上传
 * 
 * <script>
    $(document).ready(function() {
        $('#editor').summernote({
        	height:450,
        	callbacks: {
        	    onImageUpload: function(files) {
        	    	 var fd = new FormData();
        	    	 $.each(files, function(index,value){ fd.append( 'images[]', value);});
        	    	
        	         $.ajax({
        	             url: '/include/plugins/upload.php?type=testing',
        	             type: "POST",
        	             data: fd,
        	            
        	             success: function(res) {
        	             	//console.log(res);
        	                $.each(res,function(index,value){
	        	                 var image = $('<img>').attr('src', value.path);
	        	                 $('#editor').summernote("insertNode", image[0]);
        	            	});
        	             },
        	             error: function(fd) {console.log(fd);},
        	             
        	             cache: false,
        	             contentType: false,
        	             processData: false
        	         });
        	    }
        	  }
        });
    });
    ....
    
    
 * @author weiqiwang
 *
 */
class upload {
	protected $filefield = 'images';
	
	protected $filename;
	protected $type = 'album';
	
	private $fileID;
	private $fileType;
	private $userID;
	
	private $extensions = ['jpg','jpeg','png','gif'];
	private $paramPool = [ 'type', 'filefield', 'fileID' ];
	
	function __construct() {
		$this->initParam();
		$this->initFileID();
		
		$this->userID = empty($_SESSION ["UserID"])?0:$_SESSION ["UserID"];
		//$this->debug();
	}
	
	function init() {
		if( empty($_FILES[$this->filefield]) ) $this->display(['error'=>"{$this->filefield} is empty!"]);
		
		$files = $this->restructureFiles($_FILES[$this->filefield]);
		if( empty($files) ) $this->display(['error'=>"{$this->filefield} is empty!"]);
		
		$result = [];
		foreach($files as $fileItem){
			if(empty($fileItem["size"])) continue;
			if(!$this->checkFileType($fileItem)) continue;
			
			$result[] = $this->doUpload($fileItem);
		}
		
		if(empty($result)) $this->display(['error'=>"The uploaded files can not be empty!"]);
		
		$this->display($result);
	}
	
	private function restructureFiles($uploadfiles){
		if(empty($uploadfiles['tmp_name'])) return [];
		
		$files = [];
		foreach($uploadfiles['tmp_name'] as $key=>$val)
			$files[] = [
				'name'=>$uploadfiles['name'][$key],
				'type'=>$uploadfiles['type'][$key],
				'tmp_name'=>$uploadfiles['tmp_name'][$key],
				'error'=>$uploadfiles['error'][$key],
				'size'=>$uploadfiles['size'][$key],
			];
			
		return $files;
	}
	
	private function checkFileType($fileItem){
		$this->fileType = picture::getPicTrueType($fileItem ['tmp_name']);
		if (in_array($this->fileType, $this->extensions)) return true;
		
		$this->fileType = files::getExt ( $fileItem ['name'] );
		if (in_array($this->fileType, $this->extensions)) return true;
		
		return false;
	}
	
	private function doUpload($fileItem){
		$imgPath = files::getUploadPath ( $this->fileID, $this->type );
		$filePath = $this->getPath ( $imgPath );
		
		$ext = files::getExt ( $fileItem['name'] );
		$filename = $this->initFileName($fileItem['name']) . "." . $ext;
		
		// 物理文件上传
		move_uploaded_file ( $fileItem['tmp_name'], $filePath.$filename );
		
		// 处理尺寸过大的图片
		$this->checkPicSize ( $filePath, $filename, $ext, 1000, 1000 );
		
		// 最终文件调用路径
		$final_filename = $imgPath . $filename;
		
		// 读取尺寸，加载路径信息
		$uploadFileInfo = $this->getPicSize( $filePath.$filename );
		$uploadFileInfo['path'] = $final_filename;
		$uploadFileInfo['size'] = $fileItem['size'];
		
		return $uploadFileInfo;
	}
	
	private function getPath($imgPath) {
		$path = DOCUROOT . $imgPath;
		if (!file_exists($path)) files::mkdirs($path);
		
		return $path;
	}
	
	private function initFileID() {
		if (!empty ( $this->fileID ))return;
		$this->fileID = $this->userID . $this->type . date ( "YmdH" );
	}
	
	private function initFileName($filename){
		$filename = md5( $this->fileID.$filename );
		$filename = substr ( $filename, 2, 16 );
		
		return $filename;
	}
	
	private function checkPicSize($path, $filename, $ext, $w, $h) {
		// 当上传的图片范围超过$w*$h时：
		$info = $this->getPicSize ( $path . $filename );
		
		if ($info ['w'] > $w || $info ['h'] > $h) {
			// 不包含扩展名的文件名
			$trueFileName = substr ( $filename, 0, strlen ( $filename ) - strlen ( ".{$ext}" ) );
			
			// 保存原始图片
			@copy ( $path . $filename, $path . 'ori_' . $filename );
			
			// 生成新的图片
			$pic = new picture ();
			$pic->filepath = $path . $filename;
			$pic->save_dir = $path;
			$pic->leixing = 3;
			$pic->ext = $ext;
			$pic->width = $w;
			$pic->height = $h;
			$pic->filename = $trueFileName;
			$pic->echoimage ();
		}
	}
	
	private function getPicSize($filePathInfo) {
		$id = md5 ( $filePathInfo );
		
		static $result;
		if (empty ( $result [$id] ))
			$result [$id] = picture::getPicTrueType ( $filePathInfo, true );
		
		return $result [$id];
	}
	
	private function display($result){
		if(isset($_GET['format'])){
			if($_GET['format']=='debug') {
				debug::D($result);
				exit;
			}
		}
		
		header("Access-Control-Allow-Origin: *");
		header("Content-Type: application/json; charset=UTF-8");
		echo json_encode($result);
		exit;
	}
	
	private function initParam() {
		if (empty ( $_GET ))return;
		
		foreach ( $_GET as $key => $val ){
			if (in_array ( $key, $this->paramPool )) {
				$this->$key = $val;
				if(strtolower($val)=='true') $this->$key = true;
				if(strtolower($val)=='false') $this->$key = false;
			}
		}
	}
	
	private function debug() {
		debug::d ( $_FILES );
		debug::d ( $_POST );
		debug::d ( $_GET );
		exit ();
	}
}

$obj = new upload();
$obj->init();
