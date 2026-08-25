#!/bin/bash
# Instalar fail2ban si no está instalado
if ! command -v fail2ban-server &> /dev/null; then
    dnf install -y fail2ban
fi

# Configuración principal
cat > /etc/fail2ban/jail.local << 'EOF'
[DEFAULT]
bantime = 3600
findtime = 300
maxretry = 5
banaction = iptables-multiport
ignoreip = 127.0.0.1/8 10.0.0.0/16

[nginx-limit-req]
enabled = true
filter = nginx-limit-req
logpath = /var/log/nginx/error.log
maxretry = 10
findtime = 60
bantime = 3600

[nginx-botsearch]
enabled = true
filter = nginx-botsearch
logpath = /var/log/nginx/access.log
maxretry = 5
findtime = 60
bantime = 86400

[nginx-badbots]
enabled = true
filter = apache-badbots
logpath = /var/log/nginx/access.log
maxretry = 3
findtime = 60
bantime = 86400
EOF

# Filtro para detectar requests bloqueadas por rate limit (429)
cat > /etc/fail2ban/filter.d/nginx-limit-req.conf << 'EOF'
[Definition]
failregex = limiting requests, excess:.* by zone .*, client: <HOST>
ignoreregex =
EOF

# Filtro para bots buscando vulnerabilidades
cat > /etc/fail2ban/filter.d/nginx-botsearch.conf << 'EOF'
[Definition]
failregex = ^<HOST> -.*"(GET|POST|HEAD).*(wp-admin|wp-login|phpmyadmin|\.env|\.git|xmlrpc|administrator|config\.php|shell|eval).*".*$
ignoreregex =
EOF

# Iniciar fail2ban
systemctl enable fail2ban
systemctl restart fail2ban
