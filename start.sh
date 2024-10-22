killall -9 php /home/ec2-user/coocon-noti/index.php
fuser -k -n tcp 3000
nohup php /home/ec2-user/coocon-noti/index.php > /home/ec2-user/coocon-noti/nohup.out &
ps -ef | grep index.php

