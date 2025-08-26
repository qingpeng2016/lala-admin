# 使用基础镜像
FROM 907381039844.dkr.ecr.us-west-2.amazonaws.com/base/ng-php:8.0

# 设置工作目录
WORKDIR /var/www/html

# 安装 bcmath 扩展
RUN docker-php-ext-install bcmath

# 配置 Git 安全目录
RUN git config --global --add safe.directory /var/www/html

# 复制项目文件
COPY . .

# 安装项目依赖（忽略平台要求）
RUN composer install --no-dev --no-interaction --optimize-autoloader --ignore-platform-reqs

# 创建必要的目录并设置权限
RUN mkdir -p /var/www/html/runtime \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/runtime \
    && chmod -R 755 /var/www/html/public

# 暴露端口
EXPOSE 80
