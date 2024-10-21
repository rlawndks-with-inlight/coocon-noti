<?php
    include_once '/home/coocon-deposit-tcpip-server/KISA_SEED_ECB.php';

    class Cipher
    {        
        function __construct($user_key)
        {
            $this->key = $this->setUserKey($user_key);
        }        
        public function encrypt($plaintext, $size)
        {
            $data   = $this->strToAsciiArray($plaintext);

            if($size - count($data) > 0)
            {
                for($i=0; $i<$size - count($data); $i++)
                {
                    array_push($data, 0x00);
                }    
            }

            $asciis = $this->kisa128_ebc_encrypt($data);
            $chiper = $this->asciiStrArrayTostr($asciis);
            return $chiper;
        }

        public function decrypt($chiperText, $istest=False)
        {
            $result = ['code'=>0, 'data'=>''];
            $data   = $this->strToAsciiArray($chiperText);
            $asciis = $this->kisa128_ebc_decrypt($data);
            $data   = $this->asciiStrArrayTostr($asciis);

            if(strlen($data) == 0)
                $result['code'] = -151;
            else
                $result['data'] = $data;
            
            if($istest)
            {
                echo "plainText:".$data."\n";
            }
            return $result;
        }
        
        // Device ID to User key
        private function setUserKey($userKey)
        {
            $asciis = $this->strToAsciiArray($userKey);
            $left_length = 16 - count($asciis);
            for($i=0; $i<$left_length; $i++)
            {
                array_push($asciis, hexdec(0x00));
            }
            return $asciis;
        }

        //  hex array to dec array
        private function strHexArrayToDecArray($strHexArray)
        {
            $data = explode(",", $strHexArray);
            for($i = 0; $i<count($data); $i++)
            {
                $data[$i] = hexdec($data[$i]);
            }
            return $data;
        }

        //  ascii array to assci str
        private function asciiStrArrayTostr($asciiArray)
        {
            $data = explode(",", $asciiArray);
            for($i=0; $i<count($data); $i++)
            {
                $data[$i] = chr(hexdec($data[$i]));
            }
            return join("", $data);
        }

        //  plaintext to dec array
        private function strToAsciiArray($text)
        {
            $data = [];
            for($i=0; $i<strlen($text); $i++)
            {
                $dec = hexdec(bin2hex($text[$i]));
                array_push($data, $dec);
            }
            return $data;
        }

        function strhex($string) {
            $hexstr = unpack('H*', $string);
            return array_shift($hexstr);
          }

        private function kisa128_ebc_encrypt($planBytes) 
        {
            if(count($planBytes) == 0)
                return "";
            $ret = null;        

            $bszChiperText = KISA_SEED_ECB::SEED_ECB_Encrypt($this->key , $planBytes, 0, count($planBytes));        
            $r = count($bszChiperText);        
            for($i=0;$i< $r;$i++) 
            {
                $ret .=  sprintf("%02X", $bszChiperText[$i]).",";
            }
            return substr($ret,0,strlen($ret)-1);
        }

        private function kisa128_ebc_decrypt($planBytes)
        {
            if(count($planBytes) == 0)
                return "";
            $planBytresMessage = "";
            $bszPlainText = KISA_SEED_ECB::SEED_ECB_Decrypt($this->key, $planBytes, 0, count($planBytes));
            if($bszPlainText != null)
            {
                for($i=0;$i< sizeof($bszPlainText);$i++) 
                {
                    $planBytresMessage .=  sprintf("%02X", $bszPlainText[$i]).",";
                }
                return substr($planBytresMessage,0,strlen($planBytresMessage)-1);    
            }
            else
                return "";
        }
    }
?>
