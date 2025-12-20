#!/bin/bash
# Helper script to sync log files from Docker container to host

docker-compose cp php:/app/var/log/dev.log var/log/dev.log 2>/dev/null
echo "Log file synced to var/log/dev.log"

