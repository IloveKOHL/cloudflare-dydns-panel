#!/bin/bash

# Check Root
if [ "$EUID" -ne 0 ]; then
  echo "Bitte als root ausführen."
  exit 1
fi

RAM=512
CPU=1
BRIDGE="vmbr0"

read -p "CT-ID (z.B. 100): " CTID
read -p "Hostname für CT (z.B. ubuntu-web): " HOSTNAME


if [ -z "$CTID" ] || [ -z "$HOSTNAME" ]; then
  echo "CT-ID und Hostname sind erforderlich."
  exit 1
fi

echo "CT wird erstellt..."

pct create $CTID local:vztmpl/ubuntu-22.04-standard_22.04-1_amd64.tar.zst \
  -hostname $HOSTNAME \
  -memory $RAM \
  -cores $CPU \
  -net0 name=eth0,bridge=$BRIDGE,ip=dhcp \
  -rootfs local-lvm:8 \
  -features nesting=1 \
  -start 0

echo "CT $CTID erstellt. Starte CT..."

pct start $CTID

echo "Warte 10 Sekunden, bis CT bootet..."
sleep 10

echo "Setup im CT wird ausgeführt..."

pct exec $CTID -- bash -c '
  # Root-Passwort setzen
  echo "root:admin123" | chpasswd

  if [ "$(lsb_release -is 2>/dev/null)" != "Ubuntu" ]; then
    echo "Nicht Ubuntu, Setup kann abweichen."
  else
    echo "Ubuntu erkannt."
  fi

  # Pakete prüfen & installieren falls nicht vorhanden
  for pkg in apache2 php php-curl php-json php-cli git cron; do
    dpkg -s $pkg >/dev/null 2>&1 || apt-get update && apt-get install -y $pkg
  done

  systemctl enable apache2 cron
  systemctl start apache2 cron

  cd /var/www/html

  if [ ! -d cloudflare-dyndns-panel ]; then
    git clone https://github.com/IloveKOHL/cloudflare-dyndns-panel.git
  fi

  mv cloudflare-dyndns-panel/* . || true
  rm -r cloudflare-dyndns-panel || true

  chmod 755 *.php
  chown -R www-data:www-data /var/www/html
  chmod -R 755 /var/www/html

  # Standard Apache index.html löschen
  rm -f /var/www/html/index.html
'

echo "Setup abgeschlossen!"
echo "CT $CTID läuft mit Ubuntu Webserver & DynDNS Panel."
echo "Root-Passwort ist: admin123"
