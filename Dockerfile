FROM ubuntu:20.04

# 環境変数設定
ARG DEBIAN_FRONTEND=noninteractive

# 基本的なシステムパッケージをインストール
RUN apt-get update && apt-get install -y \
    apache2 \
    php8.3 \
    libapache2-mod-php8.3 \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Apacheの設定をコピー
COPY ./6.example.com.conf /etc/apache2/sites-available/6.example.com.conf
RUN a2ensite 6.example.com.conf
RUN a2enmod php8.3

# Apacheのポートを開放
EXPOSE 80 8080

# Apacheを起動
CMD ["apachectl", "-D", "FOREGROUND"]

