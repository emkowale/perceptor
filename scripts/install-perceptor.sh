#!/usr/bin/env bash
set -euo pipefail
need_root(){ [ "$EUID" -eq 0 ] || exec sudo -E bash "$0" "$@"; }
need_root "$@"

APP_DIR="/var/www/perceptor"
SITE_CONF="/etc/nginx/sites-available/perceptor"
REPO_URL="${REPO_URL:-https://github.com/emkowale/perceptor.git}"
BRANCH="${BRANCH:-main}"
LOGO_URL="${LOGO_URL:-https://thebeartraxs.com/wp-content/uploads/2025/04/Bear-Traxs-Logo-favicon-70px-x-70px.png}"

apt-get update -y
DEBIAN_FRONTEND=noninteractive apt-get install -y git curl rsync unzip nginx php-fpm php-cli ffmpeg

systemctl enable --now nginx || true
systemctl enable --now php*-fpm.service || true

PHP_SOCK="$(basename /run/php/php*-fpm.sock 2>/dev/null || true)"
[ -z "$PHP_SOCK" ] && PHP_SOCK="$(ls /run/php | grep -E 'php.*fpm\.sock' | head -n1 || true)"
[ -z "$PHP_SOCK" ] && { echo "PHP-FPM socket not found"; exit 1; }

SRC="/opt/perceptor-src"
if [ -d "$SRC/.git" ]; then
  git -C "$SRC" fetch --all --prune
  git -C "$SRC" checkout "$BRANCH"
  git -C "$SRC" reset --hard "origin/$BRANCH"
else
  rm -rf "$SRC"
  git clone --branch "$BRANCH" "$REPO_URL" "$SRC"
fi

mkdir -p "$APP_DIR"
rsync -a --delete "$SRC"/ "$APP_DIR"/
mkdir -p "$APP_DIR/captures" "$APP_DIR/public" "$APP_DIR/assets" "$APP_DIR/config"

if [ ! -s "$APP_DIR/config/cameras.json" ]; then
  cat > "$APP_DIR/config/cameras.json" <<'JSON'
{
  "camera1": {"ip": "10.255.252.77"},
  "camera2": {"ip": ""},
  "camera3": {"ip": ""},
  "camera4": {"ip": ""},
  "camera5": {"ip": ""},
  "camera6": {"ip": ""}
}
JSON
fi

if [ ! -s "$APP_DIR/assets/logo.png" ]; then
  curl -fsSL "$LOGO_URL" -o "$APP_DIR/assets/logo.png" || true
fi

chown -R www-data:www-data "$APP_DIR"

cat > "$SITE_CONF" <<NGX
server {
  listen 80;
  server_name _;
  root $APP_DIR/public;
  index index.php;
  location / { try_files \$uri \$uri/ /index.php?\$args; }
  location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/run/php/$PHP_SOCK;
  }
  location ~* \.(mp4|m3u8|ts|jpg|png|css|js)$ {
    expires 7d;
    add_header Cache-Control "public";
  }
}
NGX
ln -sf "$SITE_CONF" /etc/nginx/sites-enabled/perceptor
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

IP="$(hostname -I | awk '{print $1}')"
echo "[✓] Perceptor installed. Open: http://$IP/"
