git clone https://github.com/Imagick/imagick.git --depth 1 /tmp/imagick && \
cd /tmp/imagick && \
git fetch origin master && \
git switch master && \
cd /tmp/imagick && \
phpize && \
./configure && \
make && \
make install && \
docker-php-ext-enable imagick && \
sed -i 's#<policy domain="coder" rights="none" pattern="PDF" />#<policy domain="coder" rights="read|write" pattern="PDF" />#g' /etc/ImageMagick-6/policy.xml