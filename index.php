<?php
    include_once '/home/coocon-deposit-tcpip-server/KISA_SEED_WRAP.php';

    // 호스트와 포트 설정
    $host = '211.45.163.4';
    $port = 3000;

    class Tikitaca
    {
        function __construct() 
        {
            $this->ciper = new Cipher("KIBNETCOOCON1004");
            #$this->url = 'http://api.tikitaka.kr:2500/api/push/coocon';
            $this->url = 'http://localhost:2500/api/push/coocon';
        }

        function setParams($result)
        {
            return [
                'plain_text' => base64_encode(iconv("EUC-KR", "UTF-8", $result['data']))
            ];
        }

        function sendHttpRequest($data)
        {
            $json_data = json_encode($data);
            $options = [
                'http' => [
                    'method'  => 'POST',
                    'header'  => 'Content-type: application/json',
                    'content' => $json_data,
                ],
            ];
            $context = stream_context_create($options);
            $response = file_get_contents($this->url, false, $context);
            return $response;
        }

        function process($clientSocket, $data)
        {
            try
            {
                if(strlen($data) > 4)
                {
                    $total_size = substr($data, 0, 4);
                    if($total_size == '0404')
                    {
                        $result = $this->ciper->decrypt(substr($data, 4));
                        if($result['code'] == 0)
                        {
                            $res = $this->sendHttpRequest($this->setParams($result));
                            if($res == "0000")
                            {
                                $a = substr($result['data'], 0, 30);
                                $b = substr($result['data'], 34, 4);
                                $c = substr($result['data'], 42);
                                $enc = $this->ciper->encrypt($a.'0210'.$b.'0000'.$c, 400);
                                $send_text = "0404".$enc;
                                socket_write($clientSocket, $send_text, strlen($send_text));
                                return 1;
                            }
                        }
                        else
                            $this->logging("Decrypt Fail : $data\n", $clientSocket);
                    }
                    else
                    {
                        if(strpos($data, "GET /socket.io") !== false)
                            return 2;
                        else
                        {
			    // $this->logging("UNKOWN data size : $data\n", $clientSocket);
			    return -2;
			}
                    }
                }
                else
		{ 
			// $this->logging("UNKOWN data : $data", $clientSocket); 
			return -1;
		}
            }
            catch (Exception $e) 
            {
                $this->logging('예외 발생 (Exception): ' . $e->getMessage(), $clientSocket);
            } 
            catch (Throwable $t) 
            {
                $this->logging('예외 발생: (Throwable)' . $t->getMessage(), $clientSocket);
            }
            return 0;
        }

        function logging($text, $clientSocket)
        {
            $log = "[".date('Y-m-d H:i:s')."]";
            if($clientSocket)
            {
                socket_getpeername($clientSocket, $addr, $port);
                $log .= " - $addr:$port: $text\n";
            }
            else
                $log .=  ": $text\n";
            
            try
            {
                echo $log;
                file_put_contents('/home/coocon-deposit-tcpip-server/logs/log_'.date("Ymd").'.log', $log, FILE_APPEND);
            }
            catch (Exception $e) 
            {
                echo ('예외 발생 (Exception): ' . $e->getMessage())."\n";
            } 
            catch (Throwable $t) 
            {
                echo ('예외 발생: (Throwable)' . $t->getMessage())."\n";
            }
        }
    }
    sleep(10);
    $tikitaca = new Tikitaca();
    // 소켓 생성
    if(($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) 
    {
        $tikitaca->logging("socket_create() failed: reason: " . socket_strerror(socket_last_error()), null);
        return;
    }
    else
        $tikitaca->logging("socket_create success", null);

    if(socket_bind($socket, $host, $port) === false) 
    {
        $tikitaca->logging("socket_bind() failed: reason: " . socket_strerror(socket_last_error()), null);
        return;
    }
    else
        $tikitaca->logging("socket_bind success", null);

    if(socket_listen($socket) === false)
    {
        $tikitaca->logging("socket_listen() failed: reason: " . socket_strerror(socket_last_error()), null);
        return;
    }
    else
        $tikitaca->logging("socket_listen success", null);

    $tikitaca->logging("서버가 시작되었습니다. 호스트: $host, 포트: $port", null);

    while (true) 
    {
        // 클라이언트로부터 연결 수락
        if(($client = socket_accept($socket)) === false)
            $tikitaca->logging("socket_accept() failed: reason: " . socket_strerror(socket_last_error()), null);
        else
        {
            // 클라이언트로부터 데이터 읽기
            $data = socket_read($client, 1024);
            $result = $tikitaca->process($client, $data);
            if($result == 1)
                $tikitaca->logging("OK", $client);
            else if($result == 0)
                $tikitaca->logging("FAIL", $client);
            socket_close($client);
        }
    }
    // 소켓 닫기
    socket_close($socket);
?>
