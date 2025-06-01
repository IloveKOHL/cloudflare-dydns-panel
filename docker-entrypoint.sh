#!/bin/bash
set -e

# Start cron
service cron start

# Start Apache in foreground
apache2-foreground
