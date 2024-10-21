killall -9 php /home/coocon-deposit-tcpip-server/index.php
fuser -k -n tcp 3000
nohup php /home/coocon-deposit-tcpip-server/index.php > /home/coocon-deposit-tcpip-server/nohup.out &
ps -ef | grep index.php

