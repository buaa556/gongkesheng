<?php
/*
    方倍工作室
    CopyRight 2014 All Rights Reserved
*/

define("TOKEN", "weixin");

require __DIR__.'/vendor/autoload.php';

use NumPHP\Core\NumArray;
use NumPHP\LinAlg\LinAlg;


$wechatObj = new wechatCallbackapiTest();
if (!isset($_GET['echostr'])) {
    $wechatObj->responseMsg();
}else{
    $wechatObj->valid();
}

class wechatCallbackapiTest
{
    //验证签名
    public function valid()
    {
        $echoStr = $_GET["echostr"];
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if($tmpStr == $signature){
            echo $echoStr;
            exit;
        }
    }

    public function responseMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postStr)){
            $this->logger("R ".$postStr);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);

            $result = "";
            switch ($RX_TYPE)
            {
                case "event":
                    $result = $this->receiveEvent($postObj);
                    break;
                case "text":
                    $result = $this->receiveText($postObj);
                    break;
            }
            $this->logger("T ".$result);
            echo $result;
        }else {
            echo "";
            exit;
        }
    }

    private function receiveEvent($object)
    {
        switch ($object->Event)
        {
            case "subscribe":
                $content = "既然你关注了我，说明你很有品味。回复博主真帅，查看本平台使用帮助 ";
                break;
        }
        $result = $this->transmitText($object, $content);
        return $result;
    }

    private function receiveText($object)
    {
		
        $keyword_raw = trim($object->Content);
		
		$str_key = mb_substr($keyword_raw,0,2,"UTF-8");
		$keyword = mb_substr($keyword_raw,2,mb_strlen($keyword_raw)-2,"UTF-8");
		if($keyword_raw == "博主真帅"){
			$result = $this->transmitText($object, "谢谢夸奖。。不过我好像记错了。。。回复“帮助”查看帮助。。");
			return $result;
		}
		if($keyword_raw == "主页君照片"){
			$result = $this->transmitText($object, '<a href="http://pic4.nipic.com/20091206/3688710_225859078487_2.jpg">点此查看主页君照片</a>');
			return $result;
		}
		if($keyword_raw == "关于平台"){
			$content = json_decode('[{
								"Title": "关于平台",
								"Description": "关于本平台的一切",
								"PicUrl": "http://mmbiz.qpic.cn/mmbiz/9p8h2H28HOmAbjeSichfxCpLZt5Y4icvaicuwwf6JCXkVKvhVhVveuad1q4WD2FMOALOrCJNHQ2wA7unGvFpZufRw/640?wx_fmt=png&tp=webp&wxfrom=5&wx_lazy=1",
								"Url": "http://mp.weixin.qq.com/s?__biz=MzA3NDY5MDQ0OQ==&mid=401351136&idx=1&sn=2dbcbde7a50df32f5af8917c703d5d48&scene=4#wechat_redirect"
							}]',true);
			
			$result = $this->transmitNews($object, $content);
			return $result;
		}
		if($keyword_raw == "帮助"){
			$result = $this->transmitText($object, "回复天气+城市名（如“天气北京”）查看本地天气预报\n回复翻译+词汇（如“翻译你好”）查看中译英\n回复英文单词（如“hello”）查看英译中（注：若有标点，请在前加翻译二字。）\n回复“关于平台”查看本平台详细信息\n回复“新闻”查看新闻\n回复菜名，如“鱼香肉丝”查看菜谱\n普通回复，将会召唤出图灵机器人与您聊天。\n回复邮件+内容（如“邮件主页君你好帅”）为主页君发送邮件，最好在邮件内容中附上你的邮箱方便主页君尽快联系您。");
			return $result;
		}
        if($str_key == '计算'){
			if(strstr($keyword,"[")){
                $input = explode("#",$keyword);
                if(sizeof($input) == 2){
                    eval('$array1 = '.$input[0].';');
                    $matrix1 = new NumArray($array1);
                    if(trim($input[1]) == "inv")
                            $matrix1 = LinAlg::inv($matrix1);
                    if(trim($input[1]) == "det")
                            $matrix1 = LinAlg::det($matrix1);
                }
                elseif(sizeof($input) == 3)
                {
               
                        eval('$array1 = '.$input[0].';');
                        eval('$array2 = '.$input[2].';');
                        $matrix1 = new NumArray($array1);
                        $matrix2 = new NumArray($array2);
                        if(trim($input[1]) == "+")
                            $matrix1->add($matrix2);
                        if(trim($input[1]) == "-")
                            $matrix1->sub($matrix2);
                        if(trim($input[1]) == "*")
                            $matrix1->dot($matrix2);

                    
                }
            }
            else
                    eval('$matrix1 = '.$keyword.';');
			$output = (string)$matrix1;
			if($matrix1 == 0)
				$output == "0.000000000001";
            $result = $this->transmitText($object,$output);
                    
           
			return $result;
		}
		if($str_key == "天气"){
			$url = "http://apix.sinaapp.com/weather/?appkey=".$object->ToUserName."&city=".urlencode($keyword); 
			$output = file_get_contents($url);
			$content = json_decode($output, true);

			$result = $this->transmitNews($object, $content);
			return $result;
		}
        if($str_key == "邮件"){
			//引入发送邮件类
            require("smtp.php"); 
            //使用163邮箱服务器
            $smtpserver = "smtp.163.com";
            //163邮箱服务器端口 
            $smtpserverport = 25;
            //你的163服务器邮箱账号
            $smtpusermail = "mengdechaolive@163.com";
            //收件人邮箱
            $smtpemailto = "mengdechaolive@qq.com";
            //你的邮箱账号(去掉@163.com)
            $smtpuser = "mengdechaolive";//SMTP服务器的用户帐号 
            //你的邮箱密码
            $smtppass = "mdc86387968"; //SMTP服务器的用户密码 
            //邮件主题 
            $mailsubject = date("m-d")."意见反馈";
            //邮件内容 
            $mailbody = $keyword;
            //邮件格式（HTML/TXT）,TXT为文本邮件 
            $mailtype = "TXT";
            //这里面的一个true是表示使用身份验证,否则不使用身份验证. 
            
            $smtp = new smtp($smtpserver,$smtpserverport,true,$smtpuser,$smtppass);
            //是否显示发送的调试信息 
            $smtp->debug = TRUE;
            //发送邮件
            $smtp->sendmail($smtpemailto, $smtpusermail, $mailsubject, $mailbody, $mailtype); 
            $result = $this->transmitText($object, "成功发送邮件！");
			return $result;
		}

		if($str_key == "翻译"){
			$content = $this->baiduDic($keyword);
			$result = $this->transmitText($object, $content);
			return $result;
			
		}
		if(preg_match("/^[a-zA-Z\s]+$/",$keyword_raw)){
			$content = $this->baiduDic($keyword_raw);
			$result = $this->transmitText($object, $content);
			return $result;

		}
		$content = $this->tulingRobot($keyword_raw);
		if(!($content == ""))
			$result = $this->transmitText($object,$content);
		else{
			$output = $this->tulingRobotNews($keyword_raw);
			$temp = json_encode(array_slice($output,0,4));
			$final = json_decode($temp,true);
			$subtemp = mb_substr($temp,0,1022,"utf-8");
				
			$result = $this->transmitNews($object,$final);
		}
		
		return $result;
    }

    private function transmitText($object, $content)
    {
        if (!isset($content) || empty($content)){
            return "";
        }
        $textTpl = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[text]]></MsgType>
						<Content><![CDATA[%s]]></Content>
					</xml>";
        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }
	private function transmitPicture($object, $content)
    {
        if (!isset($content) || empty($content)){
            return "";
        }
        $textTpl = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[image]]></MsgType>
						<Image>
							<MediaId><![CDATA[%s]]></MediaId>
						</Image>
					</xml>";
        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }

    private function transmitNews($object, $newsArray)
    {
        if(!is_array($newsArray)){
            return "";
        }
        $itemTpl = "    <item>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <PicUrl><![CDATA[%s]]></PicUrl>
        <Url><![CDATA[%s]]></Url>
    </item>
";
        $item_str = "";
        foreach ($newsArray as $item){
			
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }
        $newsTpl = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[news]]></MsgType>
						<Content><![CDATA[]]></Content>
						<ArticleCount>%s</ArticleCount>
						<Articles>
						$item_str</Articles>
					</xml>";

        $result = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
        return $result;
    }

    private function logger($log_content)
    {
      
    }
	public function baiduDic($word,$from="auto",$to="auto"){
        
        //首先对要翻译的文字进行 urlencode 处理
        $word_code=urlencode($word);
        
        //注册的API Key
        $appid="43GmgjZb49PVYSGqGzuqOUpr";
        
        //生成翻译API的URL GET地址
        $baidu_url = "http://openapi.baidu.com/public/2.0/bmt/translate?client_id=".$appid."&q=".$word_code."&from=".$from."&to=".$to;
        
        $text=json_decode($this->language_text($baidu_url));

        $text = $text->trans_result;

        return $text[0]->dst;
    }
	public function tulingRobot($word){
        
        //首先对要翻译的文字进行 urlencode 处理
        //$word_code=urlencode($word);
        
        //注册的API Key
        $apiKey="7729f655e49da88e4e294d693cfe334d";
        
        //生成翻译API的URL GET地址
        $tuling_url = "http://www.tuling123.com/openapi/api?key=".$apiKey."&info=".$word;
        
        $text=json_decode($this->language_text($tuling_url)); 
		
		if($text->code == 100000){

			
			return $text->text;
		}
		if($text->code == 200000){

			
			return $text->text."".'<a href="'.$text->url.'">点击此处查看</a>';
		}
		
		return "";
    }
	public function tulingRobotNews($word){
        
        //首先对要翻译的文字进行 urlencode 处理
        //$word_code=urlencode($word);
        
        //注册的API Key
        $apiKey="7729f655e49da88e4e294d693cfe334d";
        
        //生成翻译API的URL GET地址
        $tuling_url = "http://www.tuling123.com/openapi/api?key=".$apiKey."&info=".$word;
        
        $text=json_decode($this->language_text($tuling_url));
		
		if($text->code == 302000){//新闻
		
			$content = str_replace("detailurl","Url",str_replace("icon","PicUrl",str_replace("source","Description",str_replace('article','Title',$this->language_text($tuling_url)))));
			$text=json_decode($content,true);

			
			return $text["list"];
		}
		if($text->code == 308000){
			$content = str_replace("detailurl","Url",str_replace("icon","PicUrl",str_replace("info","Description",str_replace('name','Title',$this->language_text($tuling_url)))));
			
			$text=json_decode($content,true);

			
			return $text["list"];
		}
		
		return "";
    }
        
    //百度翻译-获取目标URL所打印的内容
    public function language_text($url){

        if(!function_exists('file_get_contents')){

            $file_contents = file_get_contents($url);
           

        }else{
                
            //初始化一个cURL对象
            $ch = curl_init();

            $timeout = 5;

            //设置需要抓取的URL
            curl_setopt ($ch, CURLOPT_URL, $url);

            //设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);

            //在发起连接前等待的时间，如果设置为0，则无限等待
            curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

            //运行cURL，请求网页
            $file_contents = curl_exec($ch);

            //关闭URL请求
            curl_close($ch);
        }

        return $file_contents;
    }
}
?>