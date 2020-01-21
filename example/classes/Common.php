<?php
/*
*Common class for all common functions
*/
class Common
{
	/*
	*Generate random string
    * @param int $strength
	*/	
    public static function randomString( $strength = 16) {
		$input = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';
		$input_length = strlen($input);
		$random_string = '';
		for($i = 0; $i < $strength; $i++) {
			$random_character = $input[mt_rand(0, $input_length - 1)];
			$random_string .= $random_character;
		}
	 
		return $random_string;
    }
	
	/*
	* To upload a file with same extension iin a specific path
    * @param string $name
    * @param string $path
	*/	
    public static function uploadFile($name,$path)
    {
		if(isset($_FILES[$name])) {
			$extension = pathinfo($_FILES[$name]['name'], PATHINFO_EXTENSION);
			$filename = substr(str_replace(['.',' '],['_','-'],$_FILES[$name]['name']),0,10).'-'.self::randomString(10).'.'.$extension;
			if(!empty($filename)){
				if(move_uploaded_file($_FILES[$name]['tmp_name'], rtrim($path,'/').'/'.$filename)) {
					return $filename;
				}
			}
		}
        return false;
    }
}
