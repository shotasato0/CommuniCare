FROM laravel/sail:latest

# Cronのインストール
RUN apt-get update && apt-get install -y cron

# Cronジョブの設定
RUN echo "* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1" | crontab -

# Cronサービスを起動するスクリプトの作成
RUN echo '#!/bin/sh\ncron && php-fpm' > /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container
